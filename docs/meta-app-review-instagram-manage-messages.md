# Meta App Review — `instagram_manage_messages` Advanced Access

**App**: crm_bot (ID: `1471966334307618`)  
**Permiso solicitado**: `instagram_manage_messages` — Advanced Access  
**Plataforma**: CRM Asesores RM (https://rems.admetricas.com)  
**Fecha**: Mayo 2026

---

## 1. ¿Qué hace la app y por qué necesita este permiso?

**Asesores RM** es un CRM inmobiliario que recibe mensajes de Instagram DM de clientes potenciales interesados en propiedades. El sistema:

1. Recibe webhooks de Meta en tiempo real cuando un usuario escribe a cualquiera de las 4 cuentas de Instagram del negocio
2. Clasifica automáticamente la intención de compra del lead (frío/tibio/caliente/listo)
3. Muestra los mensajes en un inbox unificado para que los asesores puedan responder
4. Enriquece el perfil del lead con datos públicos de Instagram: nombre real, username, foto de perfil, seguidores

**Para el paso 4 necesitamos Advanced Access**: con Standard Access solo podemos recibir mensajes. Para obtener el nombre y username del usuario que escribe, la API de Meta requiere `GET /{ig-user-id}?fields=name,username` que está protegido por Advanced Access.

**Sin este permiso**, los leads aparecen como "Instagram User 958904" en lugar de su nombre real, lo que dificulta la gestión comercial.

---

## 2. Datos que accedemos y cómo los usamos

| Dato | Endpoint | Uso en el CRM |
|------|----------|---------------|
| `name` | `GET /{ig-user-id}?fields=name` | Nombre del lead en la bandeja de entrada y pipeline kanban |
| `username` | `GET /{ig-user-id}?fields=username` | @username para identificar al cliente |
| `profile_pic_url` | `GET /{ig-user-id}?fields=profile_pic_url` | Foto de perfil en la ficha del lead (solo si es pública) |
| `is_private` | `GET /{ig-user-id}?fields=is_private` | Indicador de cuenta privada (no accedemos a datos privados) |
| `followers_count` | `GET /{ig-user-id}?fields=followers_count` | Métrica para scoring de calidad del lead |

**Solo accedemos a datos públicos del perfil**. No leemos mensajes antiguos, no publicamos contenido, no accedemos a seguidores ni following.

**No compartimos estos datos con terceros**. Se almacenan en la base de datos del CRM (MySQL) accesible solo por el equipo de Asesores RM.

---

## 3. Flujo técnico

```
Usuario escribe DM en Instagram
         ↓
Meta envía webhook POST a https://rems.admetricas.com/api/webhook/instagram
         ↓
WebhookController recibe el evento con sender.id (IG scoped ID)
         ↓
MetaInstagramGraph::resolveParticipantProfile($senderId)
  → GET /v21.0/{ig-scoped-id}?fields=name,username,is_private,profile_pic_url,followers_count
  → Guarda en tabla `leads`: name, instagram_username, profile_pic, followers, is_private
         ↓
CRM Inbox muestra el nombre real del usuario (ej: "María García" en vez de "Instagram User 958904")
```

---

## 4. Guión para el screencast

**Duración**: 2-3 minutos máximo

### Escena 1: El problema (30 seg)
1. Abrir https://rems.admetricas.com/crm/inbox
2. Mostrar un lead que aparece como "Instagram User 958904" sin nombre real
3. Señalar que sin Advanced Access no podemos resolver el nombre

### Escena 2: La app en Meta Developers (30 seg)
1. Ir a https://developers.facebook.com → My Apps → crm_bot
2. Mostrar App ID: `1471966334307618`
3. Navegar a App Review → Permissions and Features
4. Mostrar que `instagram_manage_messages` está en Standard Access
5. Hacer clic en "Request Advanced Access"

### Escena 3: Cómo funciona el permiso (60 seg)
1. Volver al CRM
2. Mostrar el código o explicar: cuando llega un webhook, llamamos a la API de Meta para resolver el perfil
3. Mostrar en el log (terminal) la llamada:
   ```
   GET /v21.0/{ig-scoped-id}?fields=name,username,is_private&access_token=TOKEN
   ```
4. Explicar: "Solo leemos name y username públicos. No accedemos a mensajes privados ni publicamos."

### Escena 4: Resultado esperado (30 seg)
1. Mostrar cómo se vería el CRM con el nombre real resuelto
2. Ejemplo: "María García" con su @username y foto de perfil
3. Explicar que esto permite a los asesores identificar rápidamente a cada cliente

---

## 5. Texto para el formulario de App Review

### Permission: `instagram_manage_messages`

**How is your app using this permission?**

> Our CRM platform (Asesores RM) helps real estate agents manage leads that come through Instagram DMs. When a potential client sends a direct message to any of our 4 business Instagram accounts, we receive the webhook event and create a lead in our CRM. To identify the client properly, we use the Instagram Graph API to look up their public profile information: name, username, profile picture URL, and follower count. This is done via `GET /{ig-user-id}?fields=name,username,is_private,profile_pic_url,followers_count`. Without Advanced Access, all leads appear as "Instagram User [random]" which makes it impossible for agents to identify and follow up with clients effectively.

**What specific endpoints are you calling?**

> `GET /v21.0/{ig-scoped-id}?fields=name,username,is_private,profile_pic_url,followers_count`  
> The `ig-scoped-id` comes from the webhook `sender.id` field when a user sends a DM to our business account. We also call `GET /v21.0/{ig-business-id}?fields=username,name` to identify which of our 4 business accounts received the message.

**How do you handle the data?**

> Data is stored in a MySQL database accessible only by the Asesores RM real estate team. We do NOT: access private profiles, read historical messages, publish content, access follower/following lists, or share data with third parties. The profile data (name, username, photo URL) is displayed in the CRM inbox so agents can identify leads. We only query the API once when a new lead first messages us, and optionally refresh if the resolution previously failed.

**Privacy Policy URL**: (provide your privacy policy URL here)

**App Review screencast**: (attach the video recording following the script above)

---

## 6. Requisitos previos al App Review

Antes de enviar, verificá en Meta Developers:

- [ ] La app tiene un **Privacy Policy URL** válido
- [ ] La app tiene un **App Icon** (1024x1024)
- [ ] El **Business Verification** está completo
- [ ] La app está en modo **Live** (no Development)
- [ ] Tenés un **screencast** mostrando el flujo completo (guión arriba)
- [ ] La descripción de la app en "App Settings → Basic" explica el caso de uso

---

## 7. Notas importantes

- Meta puede rechazar si la app no está en modo **Live** o si falta el Business Verification
- El screencast debe mostrar claramente que SOLO accedemos a datos públicos del perfil (name, username)
- No mencionar "scraping", "automatización de mensajes", ni "bots" — somos un CRM, no un bot de mensajería
- Enfatizar que el usuario INICIA la conversación escribiendo primero (no contactamos proactivamente)
- El tiempo de revisión típico es 3-7 días hábiles
