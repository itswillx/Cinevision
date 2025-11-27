<?php

// Handle static files - let PHP server serve them directly
if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $_SERVER["REQUEST_URI"])) {
    return false;
}

session_start();

/**
 * Verifica se o usuário logado está desabilitado
 * @return bool true se desabilitado
 */
function checkUserDisabled(): bool {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        return false; // Não está logado, não precisa verificar
    }
    
    $config = require '/home/slinkysa/cinevision/config/env.php';
    $supabaseUrl = $config['SUPABASE_URL'];
    $serviceKey = $config['SUPABASE_SERVICE_KEY'];
    
    // Buscar todos os profiles e comparar manualmente (mais robusto)
    $email = strtolower(trim($_SESSION['user_email']));
    $userId = $_SESSION['user_id'];
    $checkUrl = $supabaseUrl . '/rest/v1/profiles?select=id,email,disabled&limit=100';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $serviceKey,
        'Authorization: Bearer ' . $serviceKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $profiles = json_decode($response, true);
    
    if (!is_array($profiles)) {
        return false;
    }
    
    // Procurar por ID ou email
    foreach ($profiles as $profile) {
        $profileEmail = strtolower(trim($profile['email'] ?? ''));
        $profileId = $profile['id'] ?? '';
        
        if ($profileId === $userId || $profileEmail === $email) {
            $disabled = $profile['disabled'] ?? false;
            $isDisabled = ($disabled === true || $disabled === 't' || $disabled === 'true' || $disabled === 1 || $disabled === '1');
            if ($isDisabled) {
                error_log("[MIDDLEWARE] Usuário desabilitado detectado: $email (ID: $userId)");
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Força logout do usuário desabilitado
 */
function forceLogoutIfDisabled(): void {
    if (checkUserDisabled()) {
        error_log("[SECURITY] Usuário desabilitado tentou acessar: " . ($_SESSION['user_email'] ?? 'unknown'));
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Sua conta foi desabilitada. Entre em contato com o administrador.';
        header('Location: /login');
        exit;
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = '/home/slinkysa/cinevision/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load Config
require_once '/home/slinkysa/cinevision/config/db.php';

// Simple Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// MIDDLEWARE: Verificar se usuário logado está desabilitado (exceto rotas de auth)
$authRoutes = ['/login', '/register', '/auth/login', '/auth/register', '/auth/logout', '/recover', '/auth/recover', '/auth/reset'];
if (isset($_SESSION['user_id']) && !in_array($uri, $authRoutes)) {
    forceLogoutIfDisabled();
}

// Debug log para rotas API
if (strpos($uri, '/api/') === 0) {
    error_log("API Request: $method $uri");
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
}

// Remove trailing slash
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = substr($uri, 0, -1);
}

// Routes
$routes = [
    'GET' => [
        '/' => ['App\Controllers\CatalogController', 'index'],
        '/login' => ['App\Controllers\AuthController', 'loginForm'],
        '/register' => ['App\Controllers\AuthController', 'registerForm'],
        '/recover' => ['App\Controllers\AuthController', 'recoverForm'],
        '/auth/reset' => ['App\Controllers\AuthController', 'resetForm'],
        '/search' => ['App\Controllers\CatalogController', 'search'],
        '/details' => ['App\Controllers\CatalogController', 'details'],
        '/settings' => ['App\Controllers\SettingsController', 'index'],
        '/favorites' => ['App\Controllers\FavoritesController', 'index'],
        '/watch' => ['App\Controllers\PlayerController', 'watch'],
        '/api/rd/torrent-info' => ['App\Controllers\RDController', 'getTorrentInfo'],
        '/api/subtitles/proxy' => ['App\Controllers\SubtitleController', 'proxy'],
        '/api/search/suggest' => ['App\Controllers\CatalogController', 'suggest'],
        '/api/favorites/list' => ['App\Controllers\FavoritesController', 'list'],
        // Progress routes
        '/api/progress/get' => ['App\Controllers\WatchProgressController', 'get'],
        '/api/progress/sync' => ['App\Controllers\WatchProgressController', 'sync'],
        '/api/progress/continue-watching' => ['App\Controllers\WatchProgressController', 'continueWatching'],
        // Admin routes
        '/admin' => ['App\Controllers\AdminController', 'index'],
        '/api/admin/users' => ['App\Controllers\AdminController', 'listUsers'],
        '/api/admin/user' => ['App\Controllers\AdminController', 'getUser'],
    ],
    'POST' => [
        '/auth/login' => ['App\Controllers\AuthController', 'login'],
        '/auth/register' => ['App\Controllers\AuthController', 'register'],
        '/auth/logout' => ['App\Controllers\AuthController', 'logout'],
        '/auth/recover' => ['App\Controllers\AuthController', 'recover'],
        '/settings/save' => ['App\Controllers\SettingsController', 'save'],
        '/api/favorites/add' => ['App\Controllers\FavoritesController', 'add'],
        '/api/favorites/remove' => ['App\Controllers\FavoritesController', 'remove'],
        '/api/rd/add-magnet' => ['App\Controllers\RDController', 'addMagnet'],
        '/api/rd/select-file' => ['App\Controllers\RDController', 'selectFile'],
        '/api/rd/unrestrict' => ['App\Controllers\RDController', 'unrestrictLink'],
        '/api/rd/unrestrict-link' => ['App\Controllers\RDController', 'unrestrictLink'],
        '/api/rd/resolve' => ['App\Controllers\RDController', 'resolve'],
        '/api/subtitles/search' => ['App\Controllers\SubtitleController', 'search'],
        '/api/subtitles/proxy' => ['App\Controllers\SubtitleController', 'proxy'],
        // Progress routes
        '/api/progress/save' => ['App\Controllers\WatchProgressController', 'save'],
        '/api/progress/remove' => ['App\Controllers\WatchProgressController', 'remove'],
        // Admin routes
        '/api/admin/user/update' => ['App\Controllers\AdminController', 'update'],
        '/api/admin/user/delete' => ['App\Controllers\AdminController', 'delete'],
        '/api/admin/user/restore' => ['App\Controllers\AdminController', 'restore'],
    ]
];

if (isset($routes[$method][$uri])) {
    $handler = $routes[$method][$uri];
    $controllerName = $handler[0];
    $action = $handler[1];
    
    error_log("Route matched: $controllerName::$action");
    
    // Basic Dependency Injection could go here, but for now just instantiate
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            http_response_code(500);
            echo json_encode(['error' => "Action not found: $action"]);
        }
    } else {
        http_response_code(500);
        error_log("Controller not found: $controllerName");
        echo json_encode(['error' => "Controller not found: $controllerName"]);
    }
} else {
    // 404
    http_response_code(404);
    error_log("404 Not Found: $method $uri - Available routes: " . implode(', ', array_keys($routes[$method] ?? [])));
    
    // Para APIs, retornar JSON
    if (strpos($uri, '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => '404 Not Found', 'uri' => $uri, 'method' => $method]);
    } else {
        echo "404 Not Found";
        echo "\nThe requested resource $uri was not found on this server.";
    }
}
