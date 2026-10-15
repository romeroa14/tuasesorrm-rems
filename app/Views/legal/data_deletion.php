<?= $this->extend('legal/layout') ?>

<?= $this->section('content') ?>
<h1>Eliminación de datos</h1>
<p class="meta">Última actualización: <?= esc($lastUpdatedLabel) ?></p>

<p>
    Esta página describe cómo solicitar la eliminación de datos personales tratados por <strong><?= esc($legalEntity) ?></strong>
    en el CRM, incluidos los asociados a Instagram/Meta cuando esa integración está activa.
    Meta la puede usar como URL de instrucciones de eliminación de datos de usuario.
</p>

<h2>1. Qué datos pueden existir</h2>
<ul>
    <li>Conversaciones y mensajes directos recibidos o enviados a través de Instagram conectado al CRM.</li>
    <li>Identificadores técnicos (por ejemplo, ID de remitente o de cuenta profesional) y nombre de usuario público si la API lo entrega.</li>
    <li>Datos de contacto o notas que un asesor haya registrado en el CRM a partir de esa conversación.</li>
</ul>

<h2>2. Cómo solicitar la eliminación</h2>
<p>
    Escribe a <a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a> con el asunto
    «Eliminación de datos» e incluye, en la medida de lo posible:
</p>
<ul>
    <li>Tu nombre o el nombre de usuario de Instagram.</li>
    <li>La cuenta profesional a la que escribiste (por ejemplo, @tuasesorrm).</li>
    <li>Una fecha aproximada de la conversación.</li>
</ul>
<p>
    Confirmaremos la solicitud y eliminaremos o anonimizaremos los registros correspondientes en un plazo razonable,
    salvo que debamos conservarlos por una obligación legal.
</p>

<h2>3. Usuarios internos del CRM</h2>
<p>
    Si eres un usuario con cuenta en la plataforma, un administrador puede desactivar el acceso y solicitar la supresión
    de datos de la cuenta por el mismo correo.
</p>

<h2>4. Datos que controla Meta</h2>
<p>
    Parte de la información (tu perfil de Instagram, el historial en la app de Instagram, etc.) la trata Meta Platforms, Inc.
    Para ejercer derechos sobre esos datos debes usar las herramientas de Meta, además de esta solicitud cuando el dato esté copiado en nuestro CRM.
</p>
<?= $this->endSection() ?>
