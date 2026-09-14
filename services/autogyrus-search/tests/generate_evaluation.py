"""Curated, reproducible 100-query evaluation set; edit cases before regenerating."""
import json
from pathlib import Path


def build_cases():
    cases = []

    def add(query, expected):
        cases.append({"query": query, "expected": expected})

    for amount, lead in zip((20000, 25000, 30000, 35000, 40000, 45000, 50000, 55000, 60000, 65000),
                            ("SUV", "Toyota", "family van", "used truck", "hybrid SUV", "sedan", "RAV4", "AWD vehicle", "Honda", "commuter car")):
        add(f"{lead} under ${amount:,}", {"hard_filters": {"max_price": amount}})
    for km, lead in zip((20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000),
                        ("RAV4", "Civic", "SUV", "Toyota", "Honda", "truck", "RAV4", "AWD vehicle", "sedan", "family car")):
        add(f"{lead} below {km:,} km", {"hard_filters": {"max_mileage_km": km}})
    for year, lead in zip(range(2016, 2026), ("SUV", "Toyota", "Civic", "truck", "sedan", "hybrid", "RAV4", "Honda", "Outback", "family vehicle")):
        add(f"{year} or newer {lead}", {"hard_filters": {"min_year": year}})
    for make, model in (("Toyota", "RAV4"), ("Honda", "Civic"), ("Ford", "F-150"), ("Subaru", "Outback"), ("Mazda", "CX-5")):
        add(f"Find a {make} {model}", {"hard_filters": {"make": make, "model": model}})
        add(f"Show me a used {make} {model}", {"hard_filters": {"make": make, "model": model}})
    for count, lead in ((4, "car"), (5, "SUV"), (6, "van"), (7, "vehicle"), (8, "family van")):
        add(f"A {lead} for a family of {count}", {"hard_filters": {"min_seating": count}, "soft_preferences": {"family_suitability": True}})
        add(f"Good {lead} for a family of {count}", {"hard_filters": {"min_seating": count}, "soft_preferences": {"family_suitability": True}})
    for lead in ("Reliable vehicle", "SUV", "Family car", "Toyota", "Used truck", "Honda", "Affordable car", "AWD vehicle", "Commuter car", "RAV4"):
        add(f"{lead} for Alberta winter", {"soft_preferences": {"winter_suitability": True}})
    for lead in ("Fuel-efficient car", "Fuel efficient SUV", "Fuel-efficient Toyota", "Fuel efficient Honda", "Fuel-efficient commuter", "Fuel efficient sedan", "Fuel-efficient hybrid", "Fuel efficient RAV4", "Fuel-efficient family car", "Fuel efficient AWD vehicle"):
        add(f"{lead} for commuting", {"soft_preferences": {"fuel_efficiency": True}})
    for city, radius in (("Edmonton", 10), ("Edmonton", 20), ("Edmonton", 30), ("Edmonton", 40), ("Edmonton", 50),
                         ("Calgary", 15), ("Calgary", 25), ("Calgary", 35), ("Calgary", 45), ("Calgary", 60)):
        add(f"Vehicle near {city} within {radius} km", {"hard_filters": {"city": city, "radius_km": float(radius)}})
    for lead, feature in (("SUV", "heated seats"), ("Toyota", "blind spot monitoring"), ("RAV4", "sunroof"),
                          ("Honda", "remote start"), ("Family car", "backup camera"), ("Hybrid SUV", "heated seats"),
                          ("Commuter car", "blind spot monitoring"), ("Truck", "sunroof"),
                          ("Used vehicle", "remote start"), ("AWD car", "backup camera")):
        add(f"{lead} with {feature}", {"hard_filters": {"required_features": [feature]}})
    for lead, excluded, kind in (("SUV", "Diesel", "fuel_types"), ("truck", "Ford", "makes"),
                                 ("car", "Coupe", "body_styles"), ("hybrid", "Honda", "makes"),
                                 ("Toyota", "Diesel", "fuel_types"), ("family car", "Coupe", "body_styles"),
                                 ("sedan", "Ford", "makes"), ("used SUV", "Diesel", "fuel_types"),
                                 ("commuter", "Coupe", "body_styles"), ("AWD vehicle", "Honda", "makes")):
        add(f"{lead} without {excluded}", {"excluded_values": {kind: [excluded]}})
    assert len(cases) == 100
    return cases


if __name__ == "__main__":
    destination = Path(__file__).with_name("evaluation_queries.jsonl")
    destination.write_text("\n".join(json.dumps(item, ensure_ascii=False) for item in build_cases()) + "\n", encoding="utf-8")
