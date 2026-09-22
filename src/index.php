<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$connected = false;
$database = [];

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3,
        ]
    );

    $database = $pdo->query(
        'SELECT DATABASE() AS name, VERSION() AS version, CURRENT_TIMESTAMP AS server_time'
    )->fetch();
    $connected = true;
} catch (PDOException $exception) {
    http_response_code(503);
    error_log('Error de conexión PDO: ' . $exception->getMessage());
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Práctica 1 · Entorno servidor con Docker</title>
    <style>
        :root { color-scheme: light; font-family: "Trebuchet MS", Arial, sans-serif; color: #18343c; background: #edf4f6; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 48px 20px; line-height: 1.6; }
        main { max-width: 780px; margin: auto; padding: 36px; background: #fff; border-top: 6px solid #176576; }
        header p, footer { color: #50646b; }
        h1 { font-size: clamp(1.8rem, 5vw, 2.5rem); line-height: 1.15; margin: 12px 0 20px; }
        h2 { font-size: 1.25rem; margin-top: 28px; }
        .status { padding: 16px 20px; background: #e5f3ee; color: #185b45; border-left: 4px solid currentColor; }
        .error { background: #fff0ed; color: #923822; }
        table { border-collapse: collapse; width: 100%; }
        th, td { text-align: left; padding: 12px 0; border-bottom: 1px solid #dbe5e8; overflow-wrap: anywhere; }
        th { width: 45%; font-weight: normal; color: #50646b; padding-right: 16px; }
        code { font-family: Consolas, monospace; font-size: 0.95em; }
        footer { margin-top: 32px; font-size: 0.9rem; }
        @media (max-width: 480px) { body { padding: 16px 12px; } main { padding: 24px 18px; } }
    </style>
</head>
<body>
<main>
    <header>
        <p>0613 · Desarrollo Web en Entorno Servidor · 2.º DAW A</p>
        <h1>Entorno servidor con Docker</h1>
        <p>Tres servicios conectados: nginx, PHP-FPM y MySQL.</p>
    </header>

    <?php if ($connected): ?>
        <p class="status" role="status"><strong>Conexión correcta con MySQL mediante PDO.</strong></p>
    <?php else: ?>
        <p class="status error" role="alert"><strong>No se ha podido conectar con MySQL.</strong><br>
            Comprueba que el servicio db esté disponible y que las credenciales sean correctas.
            Consulta los detalles con <code>docker compose logs php db</code>.</p>
    <?php endif; ?>

    <h2>Resultado de la ejecución</h2>
    <table aria-label="Información de PHP y de la consulta a MySQL">
        <tbody>
            <tr><th scope="row">Versión de PHP</th><td><?= escape(PHP_VERSION) ?></td></tr>
            <?php if ($connected): ?>
                <tr><th scope="row">Base de datos</th><td><?= escape($database['name']) ?></td></tr>
                <tr><th scope="row">Versión de MySQL</th><td><?= escape($database['version']) ?></td></tr>
                <tr><th scope="row">Fecha y hora de MySQL (UTC)</th><td><?= escape($database['server_time']) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Recorrido de la petición</h2>
    <p>Navegador → nginx (<code>web:80</code>) → PHP-FPM (<code>php:9000</code>) → MySQL (<code>db:3306</code>).</p>
    <p>nginx recibe la petición en <code>localhost:8080</code> y la envía a PHP mediante FastCGI.
        PHP consulta MySQL con PDO y genera el HTML que nginx devuelve al navegador.</p>

    <footer>Práctica 1 · UD1 · Arranque del entorno con Docker</footer>
</main>
</body>
</html>
