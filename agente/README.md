# Microservicio **agente** (Python)

Servicio independiente con **prompt del sistema**, cliente **DeepSeek** (API compatible OpenAI) y **tools** que consultan MySQL con la misma lógica que `App\Libraries\CrmAiPropertyToolRunner` en PHP (solo `status.name = 'Aprobado'`).

## Requisitos

- Python 3.11+ recomendado
- Credenciales MySQL iguales que usa CodeIgniter (`database` en el `.env` PHP → copiar host, usuario, contraseña, base)
- `DEEPSEEK_API_KEY`

## Instalación

```bash
cd agente
python3 -m venv .venv
source .venv/bin/activate   # Windows: .venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
# Editar .env con MYSQL_* y DEEPSEEK_API_KEY (sin comillas en el valor)
# Tras cambiar .env: reinicia uvicorn (--reload no observa solo cambios en .env).
```

## Pruebas automáticas

```bash
cd agente
pytest -q
```

## Chat interactivo (módulo agente)

Desde el directorio `agente/`:

```bash
python -m agente
# o
python -m agente.cli_chat
```

Escribe preguntas; el agente llamará a las tools contra tu BD. `salir` para terminar.

## Servidor HTTP

```bash
cd agente
uvicorn agente.server:app --host 0.0.0.0 --port 8090 --reload
```

En **producción** suele bastar escuchar solo en localhost y que PHP use `AI_AGENT_URL=http://127.0.0.1:8090/v1/chat`:

```bash
cd /ruta/al/repo/agente && source .venv/bin/activate
uvicorn agente.server:app --host 127.0.0.1 --port 8090
```

Ejemplo **systemd** (ajusta `User` y rutas): archivo `/etc/systemd/system/agente-rems.service`

```ini
[Unit]
Description=Agente REMS (FastAPI + DeepSeek)
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/tu-proyecto/agente
EnvironmentFile=/var/www/tu-proyecto/agente/.env
ExecStart=/var/www/tu-proyecto/agente/.venv/bin/uvicorn agente.server:app --host 127.0.0.1 --port 8090
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Luego: `sudo systemctl daemon-reload && sudo systemctl enable --now agente-rems`

### Ejemplo `curl`

```bash
curl -sS -X POST http://127.0.0.1:8090/v1/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"Propiedades hasta 200000 con 3 dormitorios","debug":true}'
```

`debug: true` incluye un resumen de llamadas a tools (sin imprimir secretos).

## Estructura

| Ruta | Rol |
|------|-----|
| `agente/prompts.py` | System prompt (único lugar en Python) |
| `agente/db_tools.py` | SQL `search_properties` / `get_property_detail` |
| `agente/runner.py` | Bucle LLM ↔ tools ↔ DeepSeek |
| `agente/server.py` | FastAPI `/health`, `/v1/chat` |
| `agente/cli_chat.py` | REPL de prueba |

Integración futura con PHP: webhook o worker POST a `/v1/chat` y envío de respuesta por Graph API Instagram.
