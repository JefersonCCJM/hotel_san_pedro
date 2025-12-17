<?php
// Temporary diagnostic endpoint. Remove after use.

$providedToken = $_GET['token'] ?? '';
$expectedToken = env('DIAG_TOKEN');

if (empty($expectedToken) || !hash_equals((string) $expectedToken, (string) $providedToken)) {
    http_response_code(401);
    exit('Unauthorized');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Diagnóstico de Sesión - MovilTech</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Sesión y CSRF</h1>
    
    <div class="section">
        <h2>Configuración de Aplicación</h2>
        <pre><?php
echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
        ?></pre>
    </div>

    <div class="section">
        <h2>Configuración de Sesión</h2>
        <pre><?php
echo "SESSION_DRIVER: " . config('session.driver') . "\n";
echo "SESSION_SECURE_COOKIE: " . (config('session.secure') ? 'true' : (config('session.secure') === false ? 'false' : 'null (auto)')) . "\n";
echo "SESSION_SAME_SITE: " . config('session.same_site') . "\n";
echo "SESSION_DOMAIN: " . (config('session.domain') ?: 'null (default)') . "\n";
echo "SESSION_LIFETIME: " . config('session.lifetime') . " minutos\n";
echo "SESSION_COOKIE: " . config('session.cookie') . "\n";
echo "SESSION_PATH: " . config('session.path') . "\n";
        ?></pre>
    </div>

    <div class="section">
        <h2>Información de la Request</h2>
        <pre><?php
echo "Scheme: " . $request->getScheme() . "\n";
echo "Is Secure: " . ($request->isSecure() ? '<span class=\"success\">true ✓</span>' : '<span class=\"error\">false ✗</span>') . "\n";
echo "Full URL: " . $request->fullUrl() . "\n";
echo "Method: " . $request->method() . "\n";
echo "Has Session: " . ($request->hasSession() ? '<span class=\"success\">true ✓</span>' : '<span class=\"error\">false ✗</span>') . "\n";

if ($request->hasSession()) {
    echo "Session ID: " . $request->session()->getId() . "\n";
    echo "CSRF Token: " . csrf_token() . "\n";
    echo "Session Data: " . json_encode($request->session()->all(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo '<span class=\"error\">⚠️ No hay sesión activa</span>' . "\n";
}
        ?></pre>
    </div>

    <div class="section">
        <h2>Headers de Proxy (X-Forwarded-*)</h2>
        <pre><?php
$proxyHeaders = [];
$headerSource = function_exists('getallheaders') ? getallheaders() : [];
foreach ($headerSource as $name => $value) {
    if (stripos($name, 'forwarded') !== false || stripos($name, 'x-forwarded') !== false) {
        $proxyHeaders[$name] = $value;
    }
}
if (empty($proxyHeaders)) {
    echo '<span class="warning">No se detectaron headers de proxy</span>' . "\n";
} else {
    foreach ($proxyHeaders as $name => $value) {
        echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\n";
    }
}
        ?></pre>
    </div>

    <div class="section">
        <h2>Cookies Recibidas</h2>
        <pre><?php
if (empty($_COOKIE)) {
    echo '<span class="error">⚠️ No se recibieron cookies</span>' . "\n";
} else {
    foreach ($_COOKIE as $name => $value) {
        $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8') . "\n";
    }
}
        ?></pre>
    </div>

    <div class="section">
        <h2>Verificación de Base de Datos (Sesiones)</h2>
        <pre><?php
try {
    $driver = config('session.driver');
    if ($driver === 'database') {
        $table = config('session.table', 'sessions');
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        if ($exists) {
            $count = \Illuminate\Support\Facades\DB::table($table)->count();
            echo "Tabla '$table': <span class=\"success\">existe ✓</span>\n";
            echo "Sesiones activas: $count\n";
        } else {
            echo "Tabla '$table': <span class=\"error\">no existe ✗</span>\n";
        }
    } else {
        echo "Driver de sesión: $driver (no requiere tabla)\n";
    }
} catch (\Exception $e) {
    echo '<span class="error">Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</span>' . "\n";
}
        ?></pre>
    </div>

    <div class="section">
        <h2>Recomendaciones</h2>
        <pre><?php
$recommendations = [];

if (config('app.env') === 'production' && !str_starts_with(config('app.url'), 'https://')) {
    $recommendations[] = "❌ APP_URL debe usar HTTPS en producción";
}

if (config('session.secure') === null && config('app.env') === 'production') {
    $recommendations[] = "⚠️ SESSION_SECURE_COOKIE debería ser 'true' en producción";
}

if (!$request->isSecure() && config('app.env') === 'production') {
    $recommendations[] = "❌ La request no se detecta como segura (HTTPS)";
}

if (!$request->hasSession()) {
    $recommendations[] = "❌ No hay sesión activa - esto causará error 419";
}

if (empty($recommendations)) {
    echo '<span class="success">✓ Configuración parece correcta</span>' . "\n";
} else {
    foreach ($recommendations as $rec) {
        echo "$rec\n";
    }
}
        ?></pre>
    </div>

    <div class="section" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h2>⚠️ IMPORTANTE</h2>
        <p><strong>Elimina este archivo después de usar:</strong></p>
        <pre>public_html/diagnose-session.php</pre>
    </div>
</body>
</html>
<?php
$kernel->terminate($request, $response);
?>
