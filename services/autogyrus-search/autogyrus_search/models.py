"""Validated API and internal search contracts."""
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, model_validator


class HardFilters(BaseModel):
    model_config = ConfigDict(extra="forbid")

    make: str | None = None
    model: str | None = None
    trim: str | None = None
    min_price: float | None = Field(default=None, ge=0)
    max_price: float | None = Field(default=None, ge=0)
    min_year: int | None = Field(default=None, ge=1886, le=2100)
    max_year: int | None = Field(default=None, ge=1886, le=2100)
    max_mileage_km: int | None = Field(default=None, ge=0)
    body_style: str | None = None
    fuel_type: str | None = None
    drivetrain: str | None = None
    transmission: str | None = None
    min_seating: int | None = Field(default=None, ge=1, le=20)
    required_features: list[str] = Field(default_factory=list)
    city: str | None = None
    province: str | None = None
    postal_code: str | None = None
    radius_km: float | None = Field(default=None, gt=0, le=1000)
    availability: str = "available"

    @model_validator(mode="after")
    def ranges(self) -> "HardFilters":
        if self.min_price is not None and self.max_price is not None and self.min_price > self.max_price:
            raise ValueError("min_price exceeds max_price")
        if self.min_year is not None and self.max_year is not None and self.min_year > self.max_year:
            raise ValueError("min_year exceeds max_year")
        if self.radius_km is not None and not (self.city or self.postal_code):
            raise ValueError("radius_km requires a city or postal code")
        return self


class SoftPreferences(BaseModel):
    model_config = ConfigDict(extra="forbid")

    preferred_features: list[str] = Field(default_factory=list)
    preferred_seating: int | None = Field(default=None, ge=1, le=20)
    reliability: bool = False
    winter_suitability: bool = False
    fuel_efficiency: bool = False
    family_suitability: bool = False
    cargo_space: bool = False
    maintenance_cost: bool = False
    future_value: bool = False
    towing: bool = False


class ExcludedValues(BaseModel):
    model_config = ConfigDict(extra="forbid")
    makes: list[str] = Field(default_factory=list)
    body_styles: list[str] = Field(default_factory=list)
    fuel_types: list[str] = Field(default_factory=list)
    features: list[str] = Field(default_factory=list)


class SearchIntent(BaseModel):
    model_config = ConfigDict(extra="forbid")
    hard_filters: HardFilters = Field(default_factory=HardFilters)
    soft_preferences: SoftPreferences = Field(default_factory=SoftPreferences)
    excluded_values: ExcludedValues = Field(default_factory=ExcludedValues)
    detected_entities: dict[str, str | int | float] = Field(default_factory=dict)
    intent_profiles: list[str] = Field(default_factory=list)
    confidence: float = Field(default=1.0, ge=0, le=1)
    unresolved_terms: list[str] = Field(default_factory=list)
    sort_order: Literal["relevance", "price_asc", "price_desc", "year_desc", "mileage_asc"] = "relevance"
    page: int = Field(default=1, ge=1, le=1000)
    page_size: int = Field(default=12, ge=1, le=50)


class QueryRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")
    query: str = Field(min_length=1, max_length=500)
    page: int = Field(default=1, ge=1, le=1000)
    page_size: int = Field(default=12, ge=1, le=50)
    sort_order: Literal["relevance", "price_asc", "price_desc", "year_desc", "mileage_asc"] = "relevance"


class IndexVehiclesRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")
    vehicle_ids: list[int] = Field(min_length=1, max_length=500)


class SearchHit(BaseModel):
    vehicle_id: int
    score: float
    reasons: list[str]


class Timing(BaseModel):
    parse_ms: float = 0
    embedding_ms: float = 0
    retrieval_ms: float = 0
    rerank_ms: float = 0
    total_ms: float = 0


class SearchResponse(BaseModel):
    intent: SearchIntent
    hits: list[SearchHit]
    total_candidates: int
    timing: Timing
    request_id: str
