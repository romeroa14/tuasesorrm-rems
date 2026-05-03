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
    Cuando utilices Instagram conectado a nuestra aplicación, parte del tratamiento puede realizarse también conforme a las políticas de Meta Platforms, Inc.
    Te recomendamos revisar la información que Meta pone a disposición sobre privacidad y mensajería profesional.
</p>

<h2>4. Conservación</h2>
<p>
    Conservamos la información el tiempo necesario para las finalidades indicadas y para cumplir obligaciones legales.
    Los plazos concretos pueden depender del tipo de dato y del uso del sistema por parte del responsable.
</p>

<h2>5. Tus derechos</h2>
<p>
    Según la legislación aplicable (por ejemplo, normativa de protección de datos en tu país), puedes solicitar acceso, rectificación,
    supresión, limitación u oposición al tratamiento, así como presentar reclamaciones ante la autoridad de control competente.
</p>

<h2>6. Contacto</h2>
<?php if ($contactEmail !== '') : ?>
    <p>Para ejercer derechos o consultas sobre privacidad: <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a>.</p>
<?php else : ?>
    <p>
        El correo de contacto para privacidad debe configurarse en el servidor (variable de entorno <code>LEGAL_CONTACT_EMAIL</code>).
    </p>
<?php endif ?>

<p>
    Las URLs públicas de estos documentos están pensadas entre otras cosas para cumplir requisitos del panel de Meta Developer al publicar la aplicación.
</p>
<?= $this->endSection() ?>
