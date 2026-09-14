"""Qdrant remains a disposable, rebuildable derived index."""
from collections.abc import Sequence
from typing import Any

from qdrant_client import QdrantClient, models

from .documents import SearchDocument
from .models import HardFilters, SearchIntent


class VectorIndex:
    def __init__(self, client: QdrantClient, collection: str, dimensions: int):
        self.client, self.collection, self.dimensions = client, collection, dimensions

    def ensure_collection(self) -> None:
        if not self.client.collection_exists(self.collection):
            self.client.create_collection(
                self.collection, vectors_config=models.VectorParams(size=self.dimensions, distance=models.Distance.COSINE)
            )
        else:
            vectors = self.client.get_collection(self.collection).config.params.vectors
            if not isinstance(vectors, models.VectorParams) or vectors.size != self.dimensions:
                raise ValueError("Qdrant collection vector dimensions do not match the configured embedding model")
        for field, schema in {
            "make": models.PayloadSchemaType.KEYWORD, "model": models.PayloadSchemaType.KEYWORD,
            "trim": models.PayloadSchemaType.KEYWORD, "body_style": models.PayloadSchemaType.KEYWORD,
            "body_style_group": models.PayloadSchemaType.KEYWORD,
            "fuel_type": models.PayloadSchemaType.KEYWORD, "drivetrain": models.PayloadSchemaType.KEYWORD,
            "transmission": models.PayloadSchemaType.KEYWORD, "province": models.PayloadSchemaType.KEYWORD,
            "city": models.PayloadSchemaType.KEYWORD, "postal_code": models.PayloadSchemaType.KEYWORD,
            "availability": models.PayloadSchemaType.KEYWORD, "features": models.PayloadSchemaType.KEYWORD,
            "year": models.PayloadSchemaType.INTEGER, "price": models.PayloadSchemaType.FLOAT,
            "mileage_km": models.PayloadSchemaType.FLOAT, "seating_capacity": models.PayloadSchemaType.INTEGER,
            "searchable_text": models.TextIndexParams(type="text", tokenizer=models.TokenizerType.WORD),
            "location": models.PayloadSchemaType.GEO,
        }.items():
            self.client.create_payload_index(self.collection, field_name=field, field_schema=schema, wait=True)

    def ping(self) -> None:
        self.client.get_collection(self.collection)

    def existing_hash(self, vehicle_id: int) -> str | None:
        points = self.client.retrieve(self.collection, ids=[vehicle_id], with_payload=True, with_vectors=False)
        return points[0].payload.get("document_hash") if points else None

    def upsert(self, document: SearchDocument, vector: Sequence[float]) -> None:
        self.client.upsert(self.collection, points=[models.PointStruct(
            id=document.vehicle_id, vector=list(vector), payload=document.payload,
        )], wait=True)

    def upsert_batch(self, documents: list[SearchDocument], vectors: Sequence[Sequence[float]]) -> None:
        if documents:
            self.client.upsert(self.collection, points=[models.PointStruct(
                id=doc.vehicle_id, vector=list(vector), payload=doc.payload,
            ) for doc, vector in zip(documents, vectors, strict=True)], wait=True)

    def delete(self, vehicle_id: int) -> None:
        self.client.delete(self.collection, points_selector=models.PointIdsList(points=[vehicle_id]), wait=True)

    def iter_ids(self):
        cursor = None
        while True:
            points, cursor = self.client.scroll(self.collection, offset=cursor, limit=500,
                                                with_payload=False, with_vectors=False)
            for point in points:
                yield int(point.id)
            if cursor is None:
                return

    def search(self, vector: Sequence[float], query: str, intent: SearchIntent, limit: int,
               city_coordinates: dict[str, list[float]]) -> list[tuple[dict[str, Any], float]]:
        filt = build_filter(intent, city_coordinates)
        dense = self.client.query_points(self.collection, query=list(vector), query_filter=filt,
                                         limit=limit, with_payload=True, with_vectors=False).points
        # Text retrieval supplements dense search, so exact terminology can enter reranking.
        keyword = []
        terms = [part for part in query.split() if len(part) > 3][:6]
        if terms:
            keyword_filter = models.Filter(must=list(filt.must or []) + [models.FieldCondition(
                key="searchable_text", match=models.MatchText(text=" ".join(terms))
            )], must_not=list(filt.must_not or []))
            keyword, _ = self.client.scroll(self.collection, scroll_filter=keyword_filter,
                                            limit=limit, with_payload=True, with_vectors=False)
        merged: dict[int, tuple[dict[str, Any], float]] = {}
        for point in keyword:
            merged[int(point.id)] = (point.payload or {}, 0.0)
        for point in dense:
            merged[int(point.id)] = (point.payload or {}, float(point.score))
        return list(merged.values())


def build_filter(intent: SearchIntent, city_coordinates: dict[str, list[float]]) -> models.Filter:
    h: HardFilters = intent.hard_filters
    must: list[Any] = [models.FieldCondition(key="availability", match=models.MatchAny(
        any=["in stock", "available", "published", "active"]
    ))]
    must_not: list[Any] = []
    for key in ("make", "model", "trim", "body_style", "fuel_type", "drivetrain", "transmission", "province"):
        value = getattr(h, key)
        if value:
            payload_key = "body_style_group" if key == "body_style" else key
            must.append(models.FieldCondition(key=payload_key, match=models.MatchValue(value=value)))
    if h.city and h.radius_km is None:
        must.append(models.FieldCondition(key="city", match=models.MatchValue(value=h.city)))
    if h.postal_code and h.radius_km is None:
        must.append(models.FieldCondition(key="postal_code", match=models.MatchValue(value=h.postal_code)))
    if h.radius_km is not None:
        coords = city_coordinates.get((h.city or "").lower())
        if coords is None:
            raise ValueError("Radius search needs a configured city coordinate; postal-only radius is not supported")
        must.append(models.FieldCondition(key="location", geo_radius=models.GeoRadius(
            center=models.GeoPoint(lat=coords[0], lon=coords[1]), radius=h.radius_km * 1000
        )))
    for feature in h.required_features:
        must.append(models.FieldCondition(key="features", match=models.MatchValue(value=feature)))
    for key, lower, upper in (("price", h.min_price, h.max_price),
                              ("year", h.min_year, h.max_year),
                              ("mileage_km", None, h.max_mileage_km),
                              ("seating_capacity", h.min_seating, None)):
        if lower is not None or upper is not None:
            must.append(models.FieldCondition(key=key, range=models.Range(gte=lower, lte=upper)))
    for key, values in (("make", intent.excluded_values.makes),
                        ("body_style_group", intent.excluded_values.body_styles),
                        ("fuel_type", intent.excluded_values.fuel_types),
                        ("features", intent.excluded_values.features)):
        for value in values:
            must_not.append(models.FieldCondition(key=key, match=models.MatchValue(value=value)))
    return models.Filter(must=must, must_not=must_not)
