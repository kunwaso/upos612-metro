FROM python:3.12-slim
WORKDIR /app
ENV PYTHONUNBUFFERED=1
ENV PYTHONPATH=/app
COPY pyproject.toml README.md ./
COPY cyber_core cyber_core
COPY cyber_db cyber_db
COPY cyber_engine cyber_engine
COPY cyber_api cyber_api
COPY cyber_worker cyber_worker
COPY cyber_reports cyber_reports
COPY cyber_mcp cyber_mcp
COPY configs configs
COPY alembic.ini ./
COPY cyber_db/alembic cyber_db/alembic
RUN pip install --no-cache-dir -e .
EXPOSE 8000
CMD ["uvicorn", "cyber_api.main:app", "--host", "0.0.0.0", "--port", "8000"]
