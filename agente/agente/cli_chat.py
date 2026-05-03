"""Chat interactivo en terminal para probar el agente."""

from __future__ import annotations

import os
import sys
from pathlib import Path

# Carga .env desde directorio agente/ (padre del paquete)
_ROOT = Path(__file__).resolve().parent.parent
_env = _ROOT / ".env"
if _env.exists():
    try:
        from dotenv import load_dotenv

        load_dotenv(_env)
    except ImportError:
        pass

if str(_ROOT) not in sys.path:
    sys.path.insert(0, str(_ROOT))

from agente.runner import run_agent_turn


def main() -> None:
    os.chdir(_ROOT)
    print("Agente REMS — chat de prueba (escribe 'salir' para terminar)\n")

    history: list[dict] = []

    while True:
        try:
            user = input("Tú: ").strip()
        except (EOFError, KeyboardInterrupt):
            print()
            break

        if not user:
            continue
        if user.lower() in ("salir", "exit", "quit"):
            break

        reply, dbg = run_agent_turn(user, history, include_debug=True)
        print(f"Agente: {reply}\n")
        if dbg:
            print(f"[debug] {len(dbg)} eventos de tools\n")

        history.append({"role": "user", "content": user})
        history.append({"role": "assistant", "content": reply})
        # Mantener ventana razonable (últimos 10 pares)
        if len(history) > 20:
            history = history[-20:]


if __name__ == "__main__":
    main()
