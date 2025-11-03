<?php
@http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
echo json_encode([
  'ok'   => true,
  'sid'  => session_id(),
  'user' => $_SESSION['iuid'] ?? null,
  'time' => date('c')
]);
