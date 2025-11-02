<?php
// actions/mark_read.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  exit('Not logged in.');
}

verify_csrf(); // DOPLNĚNO

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare('UPDATE zprava SET PRECTENO = 1 WHERE ID = ? AND PRIJEMCE_ID = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

header('Location: /inbox.php');
