"""Tests para extracción de teléfonos."""

from agente.contact_extract import extract_phone_candidates, phones_from_chat


def test_ve_mobile_with_prefix():
    phones = extract_phone_candidates("Escríbanme al +58 414-1234567")
    assert "584141234567" in phones


def test_ve_mobile_leading_zero():
    phones = extract_phone_candidates("Mi número es 0412 5554433")
    assert any(p.endswith("584125554433") or p == "584125554433" for p in phones)


def test_phones_from_chat_history():
    phones = phones_from_chat(
        "Ok gracias",
        [{"role": "user", "content": "Llámame al 0424-3334455"}],
    )
    assert any("584243334455" in p or p.endswith("4243334455") for p in phones)


def test_empty():
    assert extract_phone_candidates("", "   ") == []
