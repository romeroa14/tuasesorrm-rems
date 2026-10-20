"""Supervisor LangGraph: un subagente por módulo. Finanzas depende del permiso del turno."""

from __future__ import annotations

from functools import lru_cache

from langchain_openai import ChatOpenAI
from langgraph.prebuilt import create_react_agent
from langgraph_supervisor import create_supervisor
from langgraph_supervisor.handoff import create_forward_message_tool

from agente.config import get_settings
from agente.tools_modules import (
    get_property_detail,
    get_seller,
    list_assignments,
    list_leads,
    list_publications,
    list_searches,
    list_sellers,
    list_visits,
    list_wasi,
    operational_summary,
    proponer_crear_visita,
    proponer_registrar_telefono,
    search_properties,
)
from agente.finance_tools import (
    finance_summary_tool,
    list_finance_expenses_tool,
    list_finance_income_tool,
)

SPECIALISTS = frozenset({
    "catalogo",
    "captadores",
    "visitas",
    "busquedas",
    "crm",
    "marketing",
    "wasi",
    "finanzas",
})

SUPERVISOR_PROMPT = """Eres el supervisor del agente REMS de Asesores RM.
Repartes cada pregunta a un solo subagente. No mezcles módulos. No inventes precios, fotos ni captadores.

El sustantivo manda. Un adjetivo no cambia de módulo.
- Si la frase dice propiedad, propiedades, inmueble, inmuebles, catálogo, casa, apartamento u oficina, es catalogo. Aunque también diga últimas, agregadas, recientes, publicadas o agendadas. Agendadas no significa visitas.
- visitas solo si dice visita o visitas, y no dice propiedad ni inmueble.
- captadores: el equipo comercial, sin un inmueble concreto.
- busquedas: búsquedas inmobiliarias de clientes.
- crm: prospectos y clientes asignados. También proponer guardar el teléfono o agendar la visita del cliente de esta conversación (solo si el mensaje de sistema lo permite).
- marketing: publicaciones en redes.
- wasi: códigos Wasi.
- catalogo también cubre precio, fotos, habitaciones y el captador de una ficha. Si hay un id de inmueble, es catalogo.
- Un conteo general lo respondes tú con operational_summary.
- finanzas: ingresos, egresos y resumen. Solo si el mensaje de sistema dice que este usuario puede consultar finanzas. Si dice que no tiene acceso, respondes tú que no tiene acceso y no transfieres.
- Un saludo lo respondes tú, sin transferir.
Cuando un subagente ya respondió con datos, llama forward_message con su nombre. No resumas ni quites precios, listas ni captador.
"""


def _model() -> ChatOpenAI:
    s = get_settings()
    return ChatOpenAI(
        model=s.openrouter_model,
        api_key=s.openrouter_api_key,
        base_url=s.openrouter_base_url,
        temperature=0.2,
        timeout=90,
        default_headers={
            "HTTP-Referer": s.public_base_url,
            "X-Title": "Agente REMS",
        },
    )


def _agent(model: ChatOpenAI, name: str, tools: list, prompt: str):
    return create_react_agent(model=model, tools=tools, name=name, prompt=prompt)


@lru_cache(maxsize=1)
def get_graph():
    model = _model()
    catalogo = _agent(
        model,
        "catalogo",
        [search_properties, get_property_detail],
        "Eres el subagente de catálogo. Si dan un id, llama get_property_detail antes de escribir. "
        "Si piden las últimas, las recientes, las agregadas o las propiedades agendadas, llama search_properties solo con limit. "
        "Propiedades agendadas son inmuebles, no visitas. "
        "No pases since_date: el listado ya viene de la más nueva a la más vieja. "
        "since_date solo si nombran un día de inicio, como desde el 20 de septiembre. "
        "En un listado muestra solo lo que devolvió la búsqueda: "
        "publicado el, tipo, dirección, precios, captador y foto. No escribas No especificado. "
        "Si la herramienta devuelve filas, no digas que no hay. "
        "En una ficha por id, copia habitaciones, baños, puestos, metros, ambientes, comodidades, exteriores, adyacencias, "
        "precio de venta, precio de alquiler, fotos y el captador. No acortes esas listas.",
    )
    captadores = _agent(
        model,
        "captadores",
        [list_sellers, get_seller],
        "Eres el subagente de captadores. Solo informas nombre, teléfono, correo e inmuebles a su cargo. No inventes personas.",
    )
    visitas = _agent(
        model,
        "visitas",
        [list_visits],
        "Eres el subagente de visitas. Solo actúas si la pregunta dice visita o visitas. "
        "Si habla de propiedades, inmuebles o catálogo, no llames herramientas y no listes visitas. "
        "Resume solo las visitas que devuelva la herramienta.",
    )
    busquedas = _agent(
        model,
        "busquedas",
        [list_searches],
        "Eres el subagente de búsquedas inmobiliarias. No inventes zonas ni presupuestos.",
    )
    crm = _agent(
        model,
        "crm",
        [list_leads, list_assignments, proponer_registrar_telefono, proponer_crear_visita],
        "Eres el subagente de CRM. Prospectos y asignaciones. No reveles cédulas ni datos de finanzas. "
        "proponer_registrar_telefono y proponer_crear_visita solo PROPONEN: un asesor humano aprueba antes de que ocurra algo. "
        "Propón el teléfono solo si el cliente lo escribió; propón la visita solo si confirmó día y hora concretos. "
        "Tras proponer, dile que un asesor confirmará; nunca afirmes que quedó registrado o agendado.",
    )
    marketing = _agent(
        model,
        "marketing",
        [list_publications],
        "Eres el subagente de publicaciones. Solo enlaces y estatus que devuelva la herramienta.",
    )
    wasi = _agent(
        model,
        "wasi",
        [list_wasi],
        "Eres el subagente de Wasi. Informa el código y el inmueble. No relaciones el código con el nombre de una foto.",
    )
    finanzas = _agent(
        model,
        "finanzas",
        [finance_summary_tool, list_finance_income_tool, list_finance_expenses_tool],
        "Eres el subagente de finanzas. Solo lees. No registres, apruebes ni modifiques. "
        "Para totales usa finance_summary. Para movimientos usa list_finance_income o list_finance_expenses. "
        "Si la herramienta responde sin_acceso, dilo y no inventes cifras.",
    )
    workflow = create_supervisor(
        [catalogo, captadores, visitas, busquedas, crm, marketing, wasi, finanzas],
        model=model,
        tools=[operational_summary, create_forward_message_tool("supervisor")],
        prompt=SUPERVISOR_PROMPT,
        supervisor_name="supervisor",
        output_mode="full_history",
    )
    return workflow.compile()
