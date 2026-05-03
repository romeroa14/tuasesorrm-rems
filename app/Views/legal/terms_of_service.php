<?= $this->extend('legal/layout') ?>

<?= $this->section('content') ?>
<h1>Condiciones del servicio</h1>
<p class="meta">Última actualización: <?= esc($lastUpdatedLabel) ?></p>

<p>
    Las presentes condiciones regulan el uso de los sistemas y herramientas puestas a disposición por <strong><?= esc($legalEntity) ?></strong>
    (en adelante, «nosotros»), incluida la plataforma CRM y las integraciones opcionales con servicios de terceros como Meta/Instagram.
</p>

<h2>1. Objeto</h2>
<p>
    La plataforma está destinada al uso interno y profesional del equipo autorizado (gestión de leads, propiedades, comunicaciones con clientes potenciales, etc.).
    No constituye un servicio dirigido al público general como «consumidor» salvo que se acuerde por escrito.
</p>

<h2>2. Cuentas y acceso</h2>
<ul>
    <li>El acceso puede estar limitado a usuarios dados de alta por el administrador.</li>
    <li>Eres responsable de mantener la confidencialidad de tus credenciales y de las integraciones (tokens, webhooks) configuradas en el entorno del servidor.</li>
</ul>

<h2>3. Integración con Meta / Instagram</h2>
<p>
    Si se conecta Instagram u otros productos Meta, el uso de dichas funciones queda sujeto también a las políticas y condiciones de Meta,
    así como a los permisos aprobados para la aplicación. Nos reservamos el derecho de modificar o desactivar integraciones si Meta lo exige o si dejan de cumplir la normativa aplicable.
</p>

<h2>4. Contenido y mensajes</h2>
<p>
    Los usuarios del sistema deben utilizar los canales de mensajería de forma lícita y respetar la normativa aplicable en materia de publicidad,
    protección de datos y anti-spam. No estamos obligados a revisar todos los mensajes, pero podemos adoptar medidas si detectamos usos indebidos.
</p>

<h2>5. Limitación de responsabilidad</h2>
<p>
    El servicio se ofrece «en el estado en que se encuentra», dentro de lo permitido por la ley aplicable.
    No garantizamos disponibilidad ininterrumpida ni ausencia total de errores. La responsabilidad se limitará en la medida máxima permitida por la legislación vigente.
</p>

<h2>6. Modificaciones</h2>
<p>
    Podemos actualizar estas condiciones. La fecha de «Última actualización» reflejará el cambio más reciente publicado en esta página.
    El uso continuado del sistema después de la publicación puede implicar la aceptación de los cambios, según corresponda contractualmente.
</p>

<h2>7. Contacto</h2>
<?php if ($contactEmail !== '') : ?>
    <p>Consultas sobre estas condiciones: <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a>.</p>
<?php else : ?>
    <p>Configura <code>LEGAL_CONTACT_EMAIL</code> en el entorno del servidor para mostrar un correo de contacto público.</p>
<?php endif ?>
<?= $this->endSection() ?>
