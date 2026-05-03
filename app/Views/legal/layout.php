<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title><?= esc($title) ?> · <?= esc($legalEntity) ?></title>
    <style>
        :root {
            --bg: #f6f7f9;
            --card: #fff;
            --text: #1a1d21;
            --muted: #5c6570;
            --accent: #0d6efd;
            --border: #e2e5ea;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, Cantarell, "Helvetica Neue", Arial, sans-serif;
            font-size: 1rem;
            line-height: 1.65;
            color: var(--text);
            background: var(--bg);
        }
        header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
        }
        header .inner {
            max-width: 52rem;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        header strong { font-size: 1.05rem; }
        nav a {
            color: var(--accent);
            text-decoration: none;
            margin-right: 1rem;
            font-size: 0.95rem;
        }
        nav a:hover { text-decoration: underline; }
        main {
            max-width: 52rem;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        h1 {
            margin: 0 0 0.35rem;
            font-size: 1.65rem;
            line-height: 1.25;
        }
        .meta {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        h2 {
            margin: 1.75rem 0 0.5rem;
            font-size: 1.15rem;
        }
        p, li { margin: 0 0 0.65rem; color: var(--text); }
        ul { padding-left: 1.25rem; margin: 0 0 1rem; }
        footer {
            max-width: 52rem;
            margin: 0 auto;
            padding: 0 1.25rem 2rem;
            color: var(--muted);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<header>
    <div class="inner">
        <strong><?= esc($legalEntity) ?></strong>
        <nav>
            <a href="<?= site_url('legal/privacy-policy') ?>">Privacidad</a>
            <a href="<?= site_url('legal/terms-of-service') ?>">Condiciones</a>
        </nav>
    </div>
</header>
<main>
    <div class="card">
        <?= $this->renderSection('content') ?>
    </div>
</main>
<footer>
    Documentos legales de uso general. Para solicitudes relativas a datos personales, utiliza el correo indicado en la política de privacidad (si está configurado).
</footer>
</body>
</html>
