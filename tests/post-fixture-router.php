<?php
// Test-only Basic barrier for the isolated built-in PHP server. Never deploy.
if (PHP_SAPI !== 'cli-server' || ($_SERVER['SERVER_NAME'] ?? '') !== '127.0.0.1') {
  http_response_code(403);
  exit;
}
if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Basic ' . base64_encode('fixture:fixture')) {
  header('WWW-Authenticate: Basic realm="Local POST recipe"');
  http_response_code(401);
  exit;
}
require dirname(__DIR__) . '/drupal/local-router.php';
