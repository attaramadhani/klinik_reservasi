<?php
$root = realpath(__DIR__ . '/..');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = trim($requestPath ?: '/', '/');

if ($requestPath === '') {
    $requestPath = 'login.php';
}

if (substr($requestPath, -1) === '/') {
    $requestPath .= 'index.php';
}

if (!preg_match('/\.php$/', $requestPath)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $requestPath);
$target = realpath($root . DIRECTORY_SEPARATOR . $relativePath);

if (!$target || strpos($target, $root) !== 0 || !is_file($target)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

chdir(dirname($target));
$_SERVER['SCRIPT_FILENAME'] = $target;
$_SERVER['SCRIPT_NAME'] = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

require $target;
