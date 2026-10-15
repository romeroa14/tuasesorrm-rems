<?= $this->extend('legal/layout') ?>

<?= $this->section('content') ?>
<h1>Política de privacidad</h1>
<p class="meta">Última actualización: <?= esc($lastUpdatedLabel) ?></p>

<p>
    El responsable del tratamiento de los datos personales es <strong><?= esc($legalEntity) ?></strong>
    (en adelante, «nosotros» o «el responsable»), en el marco del uso de la plataforma interna de gestión inmobiliaria y CRM
    (incluida la integración con mensajería de Instagram/Meta cuando está habilitada).
</p>

<h2>1. Datos que podemos tratar</h2>
<ul>
    <li>Identificadores y datos de contacto proporcionados por usuarios o leads (nombre, teléfono, correo electrónico, usuario de redes sociales cuando aplique).</li>
    <li>Contenido de mensajes recibidos o gestionados a través de canales conectados (por ejemplo, mensajes directos de Instagram), metadatos asociados y registros operativos necesarios para dar soporte.</li>
    <li>Datos técnicos habituales en servidores y registros (dirección IP, fecha y hora de la solicitud, tipo de navegador), en la medida necesaria para seguridad y funcionamiento del servicio.</li>
</ul>

<h2>2. Finalidades</h2>
<ul>
    <li>Gestionar la relación comercial, consultas y seguimiento de leads y clientes.</li>
    <li>Operar el CRM, incluida la recepción de mensajes en tiempo real mediante las APIs y webhooks autorizados por Meta.</li>
    <li>Cumplir obligaciones legales aplicables y resolver incidencias de seguridad.</li>
</ul>

<h2>3. Instagram / Meta</h2>
<p>
    Cuando una cuenta profesional de Instagram está conectada a nuestra aplicación, tratamos los datos necesarios para recibir y responder
    mensajes directos: identificadores de la cuenta o página, identificador del remitente, nombre de usuario público cuando la API lo entrega,
    y el contenido del mensaje. No solicitamos el correo electrónico de los remitentes de Instagram a través de permisos de Facebook Login
    (<code>email</code> / <code>public_profile</code>) para esta integración. El tratamiento también queda sujeto a las políticas de Meta Platforms, Inc.
</p>

<h2>4. Conservación</h2>
<p>
    Conservamos la información el tiempo necesario para las finalidades indicadas y para cumplir obligaciones legales.
    Los plazos concretos pueden depender del tipo de dato y del uso del sistema por parte del responsable.
</p>

<h2>5. Eliminación de datos</h2>
<p>
    Puedes solicitar la supresión de tus datos personales escribiendo a
    <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a>
    e identificando la cuenta, conversación o dato que deseas eliminar.
    Las instrucciones detalladas están en
    <a href="<?= site_url('legal/data-deletion') ?>">Eliminación de datos</a>.
</p>

<h2>6. Tus derechos</h2>
<p>
    Según la legislación aplicable (por ejemplo, normativa de protección de datos en tu país), puedes solicitar acceso, rectificación,
    supresión, limitación u oposición al tratamiento, así como presentar reclamaciones ante la autoridad de control competente.
</p>

<h2>7. Contacto</h2>
<p>Para ejercer derechos o consultas sobre privacidad: <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a>.</p>
<?= $this->endSection() ?>
