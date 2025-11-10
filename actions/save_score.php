<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db_connect.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$game = (int)($data['game_id'] ?? 0);
$score = (int)($data['score'] ?? 0);

$stmt = $conn->prepare("INSERT INTO highscore (ID_HRA, ID_UZIVATEL, SKORE) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $game, $_SESSION['user_id'], $score);
$stmt->execute();

echo "OK";
