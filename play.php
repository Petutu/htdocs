<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$game_id = intval($_GET['game'] ?? 0);

// Načtení hry z databáze
$stmt = $conn->prepare("SELECT ID, NAZEV, POPIS, ZANR, OBTIZNOST FROM hra WHERE ID = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();

$game = $result->fetch_assoc();
if (!$game) {
    die("Hra nebyla nalezena.");
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($game['NAZEV']) ?> – Hra</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
    <nav>
        <a href="index.php">Domů</a>
        <a href="inbox.php">Doručené</a>
        <a href="sent.php">Odeslané</a>
        <a href="compose.php">Napsat zprávu</a>
        <a href="actions/logout.php">Odhlásit</a>
    </nav>
</header>

<main class="page">
    <div class="hero">
        <div class="card">
            <h1><?= htmlspecialchars($game['NAZEV']) ?></h1>
            <p><strong>Žánr:</strong> <?= htmlspecialchars($game['ZANR']) ?></p>
            <p><strong>Obtížnost:</strong> <?= htmlspecialchars($game['OBTIZNOST']) ?></p>
            <p><?= nl2br(htmlspecialchars($game['POPIS'])) ?></p>

            <hr>

            
            <div id="game-area" style="position:relative;height:300px;border:2px solid #ccc;border-radius:8px;margin-top:20px;overflow:hidden;">
  <button id="target" style="position:absolute;display:none;" class="btn-primary">Klikni!</button>
</div>

<p>Skóre: <span id="score">0</span></p>
<button id="startBtn" class="btn-primary">Spustit hru</button>

<script>
let score = 0;
let active = false;

const gameArea = document.getElementById("game-area");
const target = document.getElementById("target");
const scoreEl = document.getElementById("score");
const startBtn = document.getElementById("startBtn");

function randomPosition() {
    const areaWidth = gameArea.clientWidth;
    const areaHeight = gameArea.clientHeight;

    const x = Math.random() * (areaWidth - 80);
    const y = Math.random() * (areaHeight - 40);

    target.style.left = x + "px";
    target.style.top = y + "px";
}

function spawnTarget() {
    if (!active) return;

    target.style.display = "block";
    randomPosition();

    setTimeout(spawnTarget, 900 - score * 10); // čím víc skóre, tím rychleji
}

target.addEventListener("click", () => {
    score++;
    scoreEl.textContent = score;
    randomPosition();
});

startBtn.addEventListener("click", () => {
    score = 0;
    scoreEl.textContent = score;
    active = true;
    spawnTarget();

    startBtn.disabled = true;

    // konec hry po 20 sekundách
    setTimeout(() => {
        active = false;
        target.style.display = "none";
        startBtn.disabled = false;

        alert("Konec hry! Skóre: " + score);

        // API hook – zde je možné odeslat skóre do PHP
        fetch("/actions/save_score.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                game_id: <?= (int)$game_id ?>,
                score: score
            })
        });
    }, 20000);
});
</script>

            

           

            <h2>Žebříček</h2>
            <?php
            $scoreStmt = $conn->prepare("
                SELECT u.UZIVATELSKE_JMENO, hscore.SKORE 
                FROM highscore hscore
                JOIN uzivatel u ON u.ID = hscore.ID_UZIVATEL
                WHERE hscore.ID_HRA = ?
                ORDER BY hscore.SKORE DESC
                LIMIT 10
            ");
            $scoreStmt->bind_param("i", $game_id);
            $scoreStmt->execute();
            $scores = $scoreStmt->get_result();
            ?>

            <ol>
            <?php while ($row = $scores->fetch_assoc()): ?>
                <li><strong><?= htmlspecialchars($row['UZIVATELSKE_JMENO']) ?></strong> – <?= $row['SKORE'] ?></li>
            <?php endwhile; ?>
            </ol>
        </div>
    </div>
</main>
</body>
</html>
