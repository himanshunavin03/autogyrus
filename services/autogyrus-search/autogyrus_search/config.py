"""Environment-only service configuration; credentials are never checked into source."""
from functools import lru_cache

from pydantic import Field, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    app_env: str = "production"
    mysql_dsn: str = ""
    qdrant_url: str = "http://localhost:6333"
    qdrant_api_key: str = ""
    qdrant_collection: str = "autogyrus_vehicles_v1"
    public_api_key: str = ""
    admin_api_key: str = ""
    embedding_model: str = "sentence-transformers/all-MiniLM-L6-v2"
    request_timeout_seconds: int = Field(default=12, ge=1, le=60)
    max_request_bytes: int = Field(default=8192, ge=1024, le=1_048_576)

    @model_validator(mode="after")
    def validate_secrets(self) -> "Settings":
        if self.app_env != "test":
            if not self.mysql_dsn:
                raise ValueError("MYSQL_DSN is required")
            if not self.public_api_key or not self.admin_api_key:
                raise ValueError("PUBLIC_API_KEY and ADMIN_API_KEY are required")
            if len(self.public_api_key) < 32 or len(self.admin_api_key) < 32:
                raise ValueError("Service API keys must be at least 32 characters")
            if self.public_api_key == self.admin_api_key:
                raise ValueError("Public and admin API keys must differ")
        return self


@lru_cache
def get_settings() -> Settings:
    return Settings()
