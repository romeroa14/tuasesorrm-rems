"""API HTTP del microservicio agente."""

from __future__ import annotations

import hmac
import os
from typing import Any

from fastapi import Depends, FastAPI, Header, HTTPException
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
    conversation_id: int | None = None
    lead_id: int | None = None


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


def require_agent_token(authorization: str = Header(default="")) -> None:
    """Shared-secret check; fails closed when AGENT_API_TOKEN is not configured."""
    expected = os.getenv("AGENT_API_TOKEN", "").strip()
    provided = authorization.removeprefix("Bearer ").strip()
    if not expected or not hmac.compare_digest(expected, provided):
        raise HTTPException(status_code=401, detail="invalid_or_missing_token")


@app.get("/health")
def health():
    return {"status": "ok", "service": "agente-rems", "version": __version__}


@app.post("/v1/chat", response_model=ChatResponse, dependencies=[Depends(require_agent_token)])
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
            context=(
                {"conversation_id": req.conversation_id, "lead_id": req.lead_id}
                if req.conversation_id and req.lead_id
                else None
            ),
        )
    except RuntimeError as e:
        raise HTTPException(status_code=503, detail=str(e)) from e

    return ChatResponse(
        reply=reply,
        debug=dbg if req.debug else None,
        extracted_phones=phones,
    )


@app.post("/v1/catalog/chat", response_model=CatalogChatResponse, dependencies=[Depends(require_agent_token)])
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
