"""Deterministic automotive parsing; no generative models or generated SQL."""
import json
import re
from dataclasses import dataclass
from importlib.resources import files
from typing import Any

import spacy
from rapidfuzz import fuzz, process
from spacy.matcher import PhraseMatcher

from .models import ExcludedValues, HardFilters, QueryRequest, SearchIntent, SoftPreferences


def load_ontology() -> dict[str, Any]:
    return json.loads(files("autogyrus_search").joinpath("ontology.json").read_text())


@dataclass(frozen=True)
class Lexicon:
    makes: tuple[str, ...] = ()
    models: tuple[str, ...] = ()
    trims: tuple[str, ...] = ()
    body_styles: tuple[str, ...] = ()
    fuel_types: tuple[str, ...] = ()
    drive_types: tuple[str, ...] = ()
    transmission_types: tuple[str, ...] = ()
    features: tuple[str, ...] = ()
    cities: tuple[str, ...] = ()
    provinces: tuple[str, ...] = ()


NUMBER_WORDS = {"two": 2, "three": 3, "four": 4, "five": 5, "six": 6, "seven": 7, "eight": 8, "nine": 9}
PROVINCES = {"alberta": "AB", "british columbia": "BC", "ontario": "ON", "saskatchewan": "SK", "manitoba": "MB", "quebec": "QC"}
FILLER = {"a", "an", "the", "i", "me", "my", "give", "find", "show", "want", "need", "which", "is", "good", "for", "of", "with", "and", "or", "to", "near", "within", "under", "below", "over", "more", "than", "newer", "vehicle", "car", "cars", "one", "please", "km", "dollar", "dollars", "people", "person", "passenger", "seater", "seats", "family", "large", "cheap", "first", "in", "from", "not", "no", "without", "around", "at", "least", "most", "up", "maximum", "minimum", "mileage", "price", "year", "model", "high", "low", "very", "that", "has", "have", "it", "be", "on", "do", "you", "can", "commuting", "reliable", "winter", "fuel", "efficient", "towing", "cargo", "maintenance", "resale", "new", "used"}


class IntentParser:
    def __init__(self, lexicon: Lexicon | None = None, ontology: dict[str, Any] | None = None):
        self.lexicon = lexicon or Lexicon()
        self.ontology = ontology or load_ontology()
        self.nlp = spacy.blank("en")
        self.nlp.add_pipe("lemmatizer", config={"mode": "lookup"})
        self.nlp.initialize()
        self.matcher = PhraseMatcher(self.nlp.vocab, attr="LOWER")
        for label, entries in self._phrases().items():
            if entries:
                self.matcher.add(label, [self.nlp.make_doc(entry) for entry in entries])

    def _phrases(self) -> dict[str, list[str]]:
        o = self.ontology
        return {
            "make": list(self.lexicon.makes), "model": list(self.lexicon.models),
            "trim": list(self.lexicon.trims), "body": list(set(o["body_synonyms"]) | set(self.lexicon.body_styles)),
            "fuel": list(o["fuel_synonyms"]), "drive": list(o["drive_synonyms"]),
            "feature": list(o["feature_synonyms"]) + list(self.lexicon.features),
            "city": list(set(self.lexicon.cities) | set(self.ontology["city_coordinates"])), "province": list(PROVINCES),
            **{f"profile:{key}": values for key, values in o["intent_phrases"].items()},
        }

    def parse(self, request: QueryRequest) -> SearchIntent:
        query = request.query.strip()
        doc = self.nlp(query)
        text = query.lower()
        hard: dict[str, Any] = {}
        soft: dict[str, Any] = {}
        exclusions: dict[str, list[str]] = {"makes": [], "body_styles": [], "fuel_types": [], "features": []}
        detected: dict[str, str | int | float] = {}
        profiles: list[str] = []
        covered: set[int] = set()
        confidence = 1.0

        def mark(start: int, end: int) -> None:
            covered.update(range(start, end))

        for match_id, start, end in sorted(self.matcher(doc), key=lambda m: -(m[2] - m[1])):
            if all(i in covered for i in range(start, end)):
                continue
            label = self.nlp.vocab.strings[match_id]
            phrase = doc[start:end].text.lower()
            prefix = text[max(0, doc[start].idx - 12):doc[start].idx]
            negative = bool(re.search(r"(?:\bnot|\bno|without|exclude)\s*$", prefix))
            if label.startswith("profile:"):
                profile = label.split(":", 1)[1]
                if profile not in profiles:
                    profiles.append(profile)
                mark(start, end)
                continue
            key, value = self._match_value(label, phrase)
            if not key:
                continue
            if negative and label in ("make", "body", "fuel", "feature"):
                exclusions[{"make": "makes", "body": "body_styles", "fuel": "fuel_types", "feature": "features"}[label]].append(value)
            elif label == "feature":
                hard.setdefault("required_features", []).append(value)
            else:
                hard.setdefault(key, value)
            detected[key] = value
            mark(start, end)

        lemmas = {token.lemma_.casefold() for token in doc}
        for profile, phrases in self.ontology["intent_phrases"].items():
            if profile not in profiles and any(" " not in phrase and phrase in lemmas for phrase in phrases):
                profiles.append(profile)

        # Controlled typo correction applies only to long, otherwise unknown tokens.
        known = list(self.lexicon.makes) + list(self.lexicon.models)
        for token in doc:
            if token.i in covered or len(token.text) < 4 or not token.is_alpha:
                continue
            if token.lower_ in FILLER or token.is_stop:
                continue
            best = process.extractOne(token.text, known, scorer=fuzz.ratio, score_cutoff=90) if known else None
            if best and best[1] >= 90:
                target = "make" if best[0] in self.lexicon.makes else "model"
                hard.setdefault(target, best[0])
                detected[target] = best[0]
                covered.add(token.i)
                confidence = min(confidence, best[1] / 100)

        def number(raw: str) -> int:
            return int(raw.replace(",", ""))

        for pattern, key in [
            (r"(?:under|below|less than|max(?:imum)?|up to)\s*\$\s*([\d,]+)", "max_price"),
            (r"(?:over|above|at least|min(?:imum)?)\s*\$\s*([\d,]+)", "min_price"),
            (r"(?:price|budget)\s*(?:under|below|of|up to)?\s*\$?\s*([\d,]+)", "max_price"),
        ]:
            match = re.search(pattern, text)
            if match:
                hard[key] = number(match.group(1))
                detected[key] = hard[key]
                self._cover_char_span(doc, covered, match.span())
        mileage = re.search(r"(?:under|below|less than|max(?:imum)?|up to)\s*([\d,]+)\s*(?:km|kilomet(?:er|re)s?)", text)
        if mileage:
            hard["max_mileage_km"] = number(mileage.group(1))
            detected["max_mileage_km"] = hard["max_mileage_km"]
            self._cover_char_span(doc, covered, mileage.span())
        year = re.search(r"\b(19\d{2}|20\d{2})\s*(?:or\s+)?(newer|later|older|earlier)?\b", text)
        if year:
            key = "max_year" if year.group(2) in ("older", "earlier") else "min_year"
            hard[key] = int(year.group(1))
            detected[key] = hard[key]
            self._cover_char_span(doc, covered, year.span())
        radius = re.search(r"within\s*(\d+(?:\.\d+)?)\s*(?:km|kilomet(?:er|re)s?)", text)
        if radius:
            hard["radius_km"] = float(radius.group(1))
            self._cover_char_span(doc, covered, radius.span())
        postal = re.search(r"\b([a-z]\d[a-z])\s*(\d[a-z]\d)\b", text)
        if postal:
            hard["postal_code"] = (postal.group(1) + postal.group(2)).upper()
            self._cover_char_span(doc, covered, postal.span())

        # Explicit seat counts are mandatory; family counts also activate the profile.
        seats = re.search(r"\b(\d{1,2}|" + "|".join(NUMBER_WORDS) + r")[- ]?(?:seater|seat|passenger)s?\b", text)
        family_count = re.search(r"\bfamily\s+of\s+(\d{1,2}|" + "|".join(NUMBER_WORDS) + r")\b", text)
        count_match = family_count or seats
        if count_match:
            raw = count_match.group(1)
            count = NUMBER_WORDS.get(raw, int(raw) if raw.isdigit() else 0)
            if 1 <= count <= 20:
                hard["min_seating"] = count
                detected["passenger_count"] = count
                self._cover_char_span(doc, covered, count_match.span())
                if family_count:
                    profiles.append("family") if "family" not in profiles else None
                    soft["preferred_seating"] = min(20, count + self.ontology["family_profile"]["preferred_seating_offset"])
        if "large family" in text and not count_match:
            hard["min_seating"] = 7
            soft["preferred_seating"] = 7
        for profile, preference in {
            "family": "family_suitability", "winter": "winter_suitability", "reliability": "reliability",
            "fuel_efficiency": "fuel_efficiency", "maintenance_cost": "maintenance_cost",
            "cargo_space": "cargo_space", "future_value": "future_value", "towing": "towing",
        }.items():
            if profile in profiles:
                soft[preference] = True
        if "family" in profiles and "preferred_seating" not in soft:
            soft["preferred_seating"] = 5
        # 'Cheap to maintain' is a preference, not a maximum sale price.
        if "maintenance_cost" in profiles and "max_price" in hard and "$" not in text:
            hard.pop("max_price")
        unresolved = [t.text for t in doc if t.i not in covered and t.is_alpha and len(t.text) > 3
                      and not t.is_stop and t.lemma_.lower() not in FILLER and t.lower_ not in FILLER
                      and not any(t.lower_ in phrase for phrases in self.ontology["intent_phrases"].values() for phrase in phrases)]
        if unresolved:
            confidence = min(confidence, max(0.3, 1 - 0.1 * len(unresolved)))
        return SearchIntent(
            hard_filters=HardFilters(**hard), soft_preferences=SoftPreferences(**soft),
            excluded_values=ExcludedValues(**exclusions), detected_entities=detected,
            intent_profiles=list(dict.fromkeys(profiles)), confidence=confidence,
            unresolved_terms=list(dict.fromkeys(unresolved)), page=request.page,
            page_size=request.page_size, sort_order=request.sort_order,
        )

    def _match_value(self, label: str, phrase: str) -> tuple[str | None, str]:
        o = self.ontology
        if label in ("make", "model", "trim", "city"):
            values = {v.lower(): v for v in getattr(self.lexicon, {"make": "makes", "model": "models", "trim": "trims", "city": "cities"}[label])}
            return label, values.get(phrase, phrase.title())
        if label == "province":
            return "province", PROVINCES[phrase]
        if label == "body":
            return "body_style", o["body_synonyms"].get(phrase, o["body_style_groups"].get(phrase, phrase.title()))
        if label == "fuel":
            return "fuel_type", o["fuel_synonyms"][phrase]
        if label == "drive":
            return "drivetrain", o["drive_synonyms"][phrase]
        if label == "feature":
            return "feature", o["feature_synonyms"].get(phrase, phrase)
        return None, phrase

    @staticmethod
    def _cover_char_span(doc: Any, covered: set[int], span: tuple[int, int]) -> None:
        covered.update(t.i for t in doc if t.idx < span[1] and t.idx + len(t) > span[0])
