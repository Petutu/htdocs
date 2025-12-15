<?php
// PHP kód zůstává beze změny
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db_connect.php';

if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

$users = $conn->query("SELECT ID, UZIVATELSKE_JMENO FROM uzivatel ORDER BY UZIVATELSKE_JMENO");
$csrf = htmlspecialchars(ensure_csrf(), ENT_QUOTES, 'UTF-8');
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Napsat zprávu – Online Hry IS</title>
<link rel="stylesheet" href="assets/css/styles.css" />
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
<a href="index.php">Domů</a>

<?php if (empty($_SESSION['user_id'])): ?>
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

<main class="page">
<div class="hero">
<div class="card" style="max-width: 100%">
<h1>Napsat zprávu</h1>

<form method="post" action="/actions/message_send.php">
<input type="hidden" name="csrf" value="<?= $csrf ?>">

<div class="message-form-grid">

<div class="message-recipient-wrap">
<label class="input-label" for="recipient">Komu:</label>

<div class="input-field" style="gap: 0;">
<div class="select-wrap">
<select id="recipient" name="recipient" required>
<option value="">-- vyberte --</option>
<?php while($u = $users->fetch_assoc()): ?>
<option value="<?= (int)$u['ID'] ?>">
<?= htmlspecialchars($u['UZIVATELSKE_JMENO']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
</div>
</div>

<div class="message-subject-wrap">
<label class="input-label" for="subject">Předmět:</label>

<div class="input-field" style="gap: 0;">
<input type="text" id="subject" name="subject" placeholder="Předmět zprávy" required>
</div>
</div>
</div>

<div class="field message-body-field">
<label class="input-label" for="message">Zpráva:</label>

<textarea id="message" name="message" rows="12" class="comment-input" required></textarea>
</div>

<div class="message-submit-action" style="margin-top: 20px;">
<button type="submit" class="save-btn">Odeslat</button>
</div>
</form>
</div>
</div>
</main>
</body>
</html>