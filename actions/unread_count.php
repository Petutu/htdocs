<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// když není přihlášen -> 0 (ať to nespadne)
if (empty($_SESSION['user_id'])) {
    echo json_encode(['unread' => 0]);
    exit;
}

$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM zprava
    WHERE PRIJEMCE_ID = ? AND PRECTENO = 0
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode(['unread' => (int)$row['c']]);
