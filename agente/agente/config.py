"""Configuración cargada desde variables de entorno y desde `agente/.env` (ruta fija)."""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path

from dotenv import load_dotenv
from pydantic_settings import BaseSettings, SettingsConfigDict

# Siempre el directorio que contiene este paquete: …/agente/agente/config.py → …/agente/
_AGENTE_SERVICE_ROOT = Path(__file__).resolve().parent.parent
_ENV_FILE = _AGENTE_SERVICE_ROOT / ".env"

# No pisar variables ya exportadas en el shell (override=False)
load_dotenv(_ENV_FILE, override=False)


class Settings(BaseSettings):
    """Variables con prefijo opcional; sin prefijo. Env: DEEPSEEK_API_KEY, MYSQL_HOST, …"""

    model_config = SettingsConfigDict(
        env_file_encoding="utf-8",
        extra="ignore",
        # Doble lectura: dotenv ya volcó a os.environ; si existe archivo, Pydantic también lo lee
        env_file=_ENV_FILE if _ENV_FILE.is_file() else None,
    )

    deepseek_api_key: str = ""
    deepseek_base_url: str = "https://api.deepseek.com/v1"
    deepseek_model: str = "deepseek-chat"

    mysql_host: str = "127.0.0.1"
    mysql_port: int = 3306
    mysql_user: str = ""
    mysql_password: str = ""
    mysql_database: str = ""

    agente_bind: str = "0.0.0.0"
    agente_port: int = 8090


@lru_cache
def get_settings() -> Settings:
    return Settings()


def agente_env_file_path() -> Path:
    """Ruta absoluta del `.env` que intentamos cargar (para mensajes de error)."""
    return _ENV_FILE
