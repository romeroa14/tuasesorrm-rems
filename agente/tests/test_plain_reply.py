from agente.catalog_agent import plain_customer_reply


SAMPLE = """Aquí tienes la información sobre un inmueble en Ciudad Casarapa:

- **Tipo:** Apartamento
- **Ubicación:** Ciudad Casarapa, Guarenas, Plaza, Miranda
- **Precio:** $58,000
- **Habitaciones:** 3
- **Baños:** 2
- **Metros de construcción:** 79 m²
- **Descripción:** Apartamento en venta.

[![Apartamento en Ciudad Casarapa](https://rems.asesoresrm.com.ve/properties/RM0017997/graphic/1791325710_8779d9c5a64859eab923.jpg)

Si necesitas más información o deseas coordinar una visita, un asesor humano puede ayudarte.
"""


def test_property_reply_drops_markdown_and_keeps_the_photo_url():
    plain = plain_customer_reply(SAMPLE)
    assert "**" not in plain
    assert "![" not in plain
    assert "- Tipo" not in plain
    assert "Tipo: Apartamento" in plain
    assert "Precio: $58,000" in plain
    assert "https://rems.asesoresrm.com.ve/properties/RM0017997/graphic/1791325710_8779d9c5a64859eab923.jpg" in plain
    assert "un asesor humano puede ayudarte." in plain


def test_linked_image_keeps_one_url():
    raw = "[![Foto](https://rems.asesoresrm.com.ve/a.jpg)](https://rems.asesoresrm.com.ve/a.jpg)"
    plain = plain_customer_reply(raw)
    assert plain == "https://rems.asesoresrm.com.ve/a.jpg"


def test_plain_sentence_stays():
    assert plain_customer_reply("Hola, te escribo de la tienda.") == "Hola, te escribo de la tienda."
