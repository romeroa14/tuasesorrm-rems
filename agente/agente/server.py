"""API HTTP del microservicio agente."""

from __future__ import annotations

from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from agente import __version__
from agente.catalog_agent import run_catalog_chat
from agente.config import agente_env_file_path, get_settings
from agente.contact_extract import phones_from_chat
from agente.finance_access import FinanceAccess
from agente.runner import run_agent_turn

app = FastAPI(title="Agente REMS", version=__version__)


class ChatMessage(BaseModel):
    role: str
    content: str = ""


class FinanceAccessIn(BaseModel):
    access: bool = False
    dashboard: bool = False
    reports: bool = False
    income: bool = False
    expense: bool = False


class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    history: list[ChatMessage] = []
    debug: bool = False
    finance: FinanceAccessIn = Field(default_factory=FinanceAccessIn)


class ChatResponse(BaseModel):
    reply: str
    debug: list[dict[str, Any]] | None = None
    extracted_phones: list[str] = Field(default_factory=list)


class CatalogChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    history: list[ChatMessage] = []
    soul: str = Field(..., min_length=1)
    debug: bool = False


class CatalogChatResponse(BaseModel):
    reply: str
    debug: list[dict[str, Any]] | None = None
    extracted_phones: list[str] = Field(default_factory=list)


def _missing_key():
    env_path = agente_env_file_path()
    return {
        "error": "missing_openrouter_api_key",
        "message": "OPENROUTER_API_KEY está vacía o no se cargó.",
        "env_file": str(env_path),
        "exists": env_path.is_file(),
        "hint": "Crea o edita ese archivo con OPENROUTER_API_KEY y reinicia uvicorn.",
    }


@app.get("/health")
def health():
    return {"status": "ok", "service": "agente-rems", "version": __version__}


@app.post("/v1/chat", response_model=ChatResponse)
def chat(req: ChatRequest):
    s = get_settings()
    if not (s.openrouter_api_key or "").strip():
        raise HTTPException(status_code=503, detail=_missing_key())

    hist = [m.model_dump() for m in req.history]
    phones = phones_from_chat(req.message, hist)
    try:
        reply, dbg = run_agent_turn(
            req.message,
            hist,
            include_debug=req.debug,
            finance=FinanceAccess.from_payload(req.finance.model_dump()),
        )
    except RuntimeError as e:
        raise HTTPException(status_code=503, detail=str(e)) from e

    return ChatResponse(
        reply=reply,
        debug=dbg if req.debug else None,
        extracted_phones=phones,
    )


@app.post("/v1/catalog/chat", response_model=CatalogChatResponse)
def catalog_chat(req: CatalogChatRequest):
    s = get_settings()
    if not (s.openrouter_api_key or "").strip():
        raise HTTPException(status_code=503, detail=_missing_key())

    hist = [m.model_dump() for m in req.history]
    try:
        result = run_catalog_chat(req.message, hist, req.soul, include_debug=req.debug)
    except RuntimeError as e:
        raise HTTPException(status_code=503, detail=str(e)) from e

    return CatalogChatResponse(
        reply=result["reply"],
        debug=result["debug"] if req.debug else None,
        extracted_phones=result["extracted_phones"],
    )
