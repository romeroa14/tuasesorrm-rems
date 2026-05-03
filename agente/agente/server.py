"""API HTTP del microservicio agente."""

from __future__ import annotations

from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from agente import __version__
from agente.config import agente_env_file_path, get_settings
from agente.runner import run_agent_turn

app = FastAPI(title="Agente REMS", version=__version__)


class ChatMessage(BaseModel):
    role: str
    content: str = ""


class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    history: list[ChatMessage] = []
    debug: bool = False


class ChatResponse(BaseModel):
    reply: str
    debug: list[dict[str, Any]] | None = None


@app.get("/health")
def health():
    return {"status": "ok", "service": "agente-rems", "version": __version__}


@app.post("/v1/chat", response_model=ChatResponse)
def chat(req: ChatRequest):
    s = get_settings()
    if not (s.deepseek_api_key or "").strip():
        env_path = agente_env_file_path()
        raise HTTPException(
            status_code=503,
            detail={
                "error": "missing_deepseek_api_key",
                "message": "DEEPSEEK_API_KEY está vacía o no se cargó.",
                "env_file": str(env_path),
                "exists": env_path.is_file(),
                "hint": "Crea o edita ese archivo con una línea DEEPSEEK_API_KEY=sk-... y reinicia uvicorn.",
            },
        )

    hist = [m.model_dump() for m in req.history]
    try:
        reply, dbg = run_agent_turn(req.message, hist, include_debug=req.debug)
    except RuntimeError as e:
        raise HTTPException(status_code=503, detail=str(e)) from e

    return ChatResponse(reply=reply, debug=dbg if req.debug else None)
