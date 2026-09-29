<?php

declare(strict_types=1);

// PHP's development server does not process .htaccess. Only public assets may
// bypass Drupal here; every HTML route must pass through native maintenance.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, "\0") || str_contains($path, '\\') || preg_match('~(?:^|/)\.~', $path)) {
  http_response_code(404);
  exit;
}
$publicRoot = __DIR__ . '/web';
$real = realpath($publicRoot . $path);
if ($real !== FALSE && is_file($real)) {
  $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
  $inside = str_starts_with(str_replace('\\', '/', $real), str_replace('\\', '/', $publicRoot) . '/');
  if ($inside && in_array($ext, ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf'], TRUE)) {
    return FALSE;
  }
  if ($path !== '/index.php') {
    http_response_code(404);
    exit;
  }
}
chdir($publicRoot);
$_SERVER['SCRIPT_FILENAME'] = $publicRoot . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require $publicRoot . '/index.php';
