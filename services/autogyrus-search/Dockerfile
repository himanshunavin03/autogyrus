FROM python:3.11-slim
ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 TOKENIZERS_PARALLELISM=false HF_HOME=/srv/search/.cache/huggingface
WORKDIR /srv/search
RUN groupadd --system search && useradd --system --gid search --home-dir /srv/search search
COPY pyproject.toml ./
COPY autogyrus_search ./autogyrus_search
RUN pip install --no-cache-dir . && python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('sentence-transformers/all-MiniLM-L6-v2')" && chown -R search:search /srv/search
USER search
ENV HF_HUB_OFFLINE=1
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8080/health', timeout=3)"
CMD ["uvicorn", "autogyrus_search.api:app", "--host", "0.0.0.0", "--port", "8080", "--workers", "1"]
