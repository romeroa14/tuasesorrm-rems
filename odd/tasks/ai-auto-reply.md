# ai-auto-reply
Objective: agent auto-replies Instagram DMs per conversation (opt-in), hands off to a human, and gains write tools with confirmation.
Branch: feat/ai-auto-reply | Delivery: ask-on-risk | Test runner: vendor/bin/phpunit (tests/unit), pytest in agente/

## Specs
- S1: "Mantener ai_auto_reply apagado por defecto y que el asesor lo active por conversación; el agente responde solo ahí"
- S2: "con handoff a humano por score caliente o pedido explícito"
- S3: "Todo en modo dry-run hasta que lo apruebe." Default mode is dry_run: drafts are logged, nothing is sent.
- S4: Write tools (crear_visita, actualizar_lead, asignar_agente, registrar_telefono) ask for confirmation on irreversible actions.

## Tasks
- T1 (S1-S3) auto-reply service + policy + dry-run log + webhook hook | inline | done: RED->GREEN AiAutoReplyPolicyTest (4 tests); service paths (off/human/hot/agent-down) exercised on local docker DB in a rolled-back transaction; not verified: draft path (needs agent + DeepSeek key) and live send
- T2 (S4) propose-only write tools (registrar_telefono, crear_visita) + advisor approval | inline | done: PHP rules RED->GREEN (4 tests), pytest 5 new (11 total), approve/reject/fail paths and a real Python insert verified on local docker DB (test rows removed); not verified: agent calling the tools through DeepSeek, advisor UI (only JSON endpoints /app/crm/api/ai-actions)

## Log
- L1 (2026-10-19) user: "Perfecto vamos a ejecutar estas mejoras de IA por impacto empecemos con la 1 y la 2"
- L2 (2026-10-19) user: "Mantener ai_auto_reply apagado por defecto y que el asesor lo active por conversación; el agente responde solo ahí, con handoff a humano por score caliente o pedido explícito. Todo en modo dry-run hasta que lo apruebe."
