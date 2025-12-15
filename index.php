<?php
// PHP kód zůstává stejný, zajišťuje připojení k DB a ověření admina
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';

$isAdmin = false;

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $isAdmin = $result->fetch_assoc()['JEADMIN'] == 1;
    }
}
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Online Hry IS – Přehled</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css" /> <script type="module" src="assets/js/messages.js"></script>
    <style>
        .dashboard {
             padding: 24px; 
        }
        .games-area { grid-area: games; } 
        .header-area { grid-area: header; }

        .game-card {
            min-height: 320px; 
            display: flex;
            flex-direction: column;
            padding: 24px; 
        }

        .game-content {
            flex: 1; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0 0 0;
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        @media (max-width: 1200px) {
            .dashboard {
                grid-template-areas: 
                    "header header header"
                    "games games games"
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-areas: 
                    "header"
                    "games"
                grid-template-columns: 1fr;
            }
            
            .game-card {
                min-height: 280px;
            }
        }
    </style>
</head>
<body>
<?php
$isAdmin = false;

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $isAdmin = $stmt->get_result()->fetch_assoc()['JEADMIN'] == 1;
}
?>
<header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
    <nav>
        <a href="index.php" aria-current="page">Domů</a> <?php if (empty($_SESSION['user_id'])): ?>
            <a href="register.php">Registrace</a>
            <a href="login.php">Přihlášení</a>
        <?php else: ?>
            <a href="inbox.php">
                Doručené (<span id="unreadCount">0</span>)
            </a>
            <a href="sent.php">Odeslané</a>
            <a href="compose.php">Napsat</a>
            <a href="profile.php">Profil</a>

            <?php if ($isAdmin): ?>
                <a href="admin_users.php" class="admin-link">Admin</a>
            <?php endif; ?>

            <a href="actions/logout.php">Odhlásit</a>
        <?php endif; ?>
    </nav>
</header>


<main class="dashboard">

    <header class="header-area card">
        <h1>Přehled her</h1>
        <p class="lead" style="color: var(--muted);">Vyberte si z našich populárních titulů</p>
    </header>

    <section class="games-area"> 
        <h2>Dostupné hry</h2>
        
        <div class="games-grid">
            <?php
            $games = $conn->query("SELECT ID, NAZEV, POPIS FROM hra ORDER BY ID");
            if ($games && $games->num_rows):
                while ($g = $games->fetch_assoc()):
            ?>
                <div class="card game-card">
                    <h3><?= htmlspecialchars($g['NAZEV']) ?></h3>
                    
                    <div class="game-content">
                        <p style="color: var(--text-muted);"><?= htmlspecialchars(substr($g['POPIS'], 0, 100)) ?>...</p>
                        
                        <div class="game-tags">
                            <span>Tag1</span>
                            <span>Tag2</span>
                        </div>

                        <a href="play.php?game=<?= (int)$g['ID'] ?>" class="btn-primary" style="margin-top:auto;">Hrát</a>
                    </div>
                </div>
            <?php
                endwhile;
            else:
                echo "<p>Žádné hry nejsou dostupné.</p>";
            endif;
            ?>
        </div>
    </section>
</main>

<script type="module">
    import { getUnreadCount } from './assets/js/messages.js';
    document.addEventListener('DOMContentLoaded', () => {
        const unreadEl = document.getElementById('unreadCount');
        if(unreadEl) { 
             getUnreadCount().then(count => {
                unreadEl.textContent = count;
             });
        }
    });
</script>
</body>
</html>