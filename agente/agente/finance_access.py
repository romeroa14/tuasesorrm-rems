"""Permiso de finanzas del turno. Lo envía PHP desde la sesión; por defecto no hay acceso."""

from __future__ import annotations

from contextvars import ContextVar, Token
from dataclasses import dataclass
from typing import Any


@dataclass(frozen=True)
class FinanceAccess:
    access: bool = False
    dashboard: bool = False
    reports: bool = False
    income: bool = False
    expense: bool = False

    @classmethod
    def from_payload(cls, data: dict[str, Any] | None) -> FinanceAccess:
        data = data or {}

        def flag(name: str) -> bool:
            return bool(data.get(name))

        access = flag("access")
        return cls(
            access=access,
            dashboard=access and flag("dashboard"),
            reports=access and flag("reports"),
            income=access and flag("income"),
            expense=access and flag("expense"),
        )

    def can_summarize(self) -> bool:
        return self.dashboard or self.reports

    def instruction(self) -> str:
        if not self.access:
            return (
                "Este usuario no tiene acceso a finanzas. "
                "Si pregunta por cuentas, ingresos, egresos, cuotas o caja, "
                "responde que no tiene acceso al módulo. No transfieras a finanzas."
            )
        parts: list[str] = []
        if self.can_summarize():
            parts.append("el resumen de ingresos y egresos publicados")
        if self.income:
            parts.append("el listado de ingresos")
        if self.expense:
            parts.append("el listado de egresos")
        if not parts:
            return (
                "Este usuario tiene membresía de finanzas, pero sin consultas habilitadas. "
                "No muestres cifras."
            )
        return (
            "Este usuario puede consultar finanzas: "
            + ", ".join(parts)
            + ". No puede registrar, aprobar ni modificar movimientos. "
            "Esas preguntas van a finanzas."
        )


_current: ContextVar[FinanceAccess] = ContextVar("finance_access", default=FinanceAccess())


def set_finance_access(access: FinanceAccess) -> Token:
    return _current.set(access)


def reset_finance_access(token: Token) -> None:
    _current.reset(token)


def get_finance_access() -> FinanceAccess:
    return _current.get()
