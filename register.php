<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrace – Online Hry IS</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
  <header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
   <nav>
  <a href="index.php">Domů</a>
  <a href="register.php">Registrace</a>

  <?php if (empty($_SESSION['user_id'])): ?>
    <a href="login.php">Přihlášení</a>
  <?php else: ?>
    <a href="inbox.php">
      Doručené (<span id="unreadCount">0</span>)
    </a>
    <a href="sent.php">Odeslané</a>
    <a href="compose.php">Napsat</a>
    <a href="actions/logout.php">Odhlásit</a>
  <?php endif; ?>
</nav>

  </header>

  <main class="page">
    <div class="hero">
      <div class="hero-inner">
        <section class="card">
          <h1>Registrace</h1>
          <p class="lead">Vytvořte si hráčský účet.</p>

          <form id="registerForm" method="post" action="/actions/register_action.php"
                enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(ensure_csrf()) ?>">

            <!-- Jméno -->
            <label class="field">
              <span class="field-label">Jméno</span>
              <div class="input-wrap">
                <input type="text" name="first_name" id="first_name"
                       required minlength="2" maxlength="50" placeholder="Jan">
              </div>
            </label>

            <!-- Příjmení -->
            <label class="field">
              <span class="field-label">Příjmení</span>
              <div class="input-wrap">
                <input type="text" name="last_name" id="last_name"
                       required minlength="2" maxlength="50" placeholder="Novák">
              </div>
            </label>

            <!-- Email -->
            <label class="field">
              <span class="field-label">Email</span>
              <div class="input-wrap">
                <input type="email" name="email" id="email"
                       required maxlength="255" placeholder="jan.novak@example.com">
              </div>
            </label>

            <!-- Telefon -->
            <label class="field">
              <span class="field-label">Telefon</span>
              <div class="input-wrap">
                <input type="tel" name="phone" id="phone"
                       required pattern="^\+?[0-9 ]{9,20}$"
                       placeholder="+420 777 123 456">
              </div>
            </label>

            <!-- Pohlaví -->
            <fieldset class="field">
              <legend class="field-label">Pohlaví</legend>
              <div class="input-wrap">
                <label><input type="radio" name="gender" value="M" required> Muž</label>
                <label><input type="radio" name="gender" value="F"> Žena</label>
                <label><input type="radio" name="gender" value="O"> Jiné / neuvádět</label>
              </div>
            </fieldset>

            <!-- Profilová fotografie -->
            <label class="field">
              <span class="field-label">Profilová fotografie</span>
              <div class="input-wrap">
                <input type="file" name="photo" id="photo"
                       required accept="image/*">
              </div>
              <small>Musí být obrázek, bude převeden na JPEG 800×XXX, kvalita 90.</small>
            </label>

            <!-- Login -->
            <label class="field">
              <span class="field-label">Login</span>
              <div class="input-wrap">
                <input type="text" name="login" id="login"
                       required pattern="[A-Za-z0-9_]{4,30}"
                       placeholder="player01" autocomplete="username">
              </div>
            </label>

            <!-- Heslo -->
            <label class="field">
              <span class="field-label">Heslo</span>
              <div class="input-wrap">
                <input type="password" name="password" id="password"
                       required minlength="10" autocomplete="new-password"
                       placeholder="min. 10 znaků">
              </div>
            </label>

            <!-- Heslo znovu -->
            <label class="field">
              <span class="field-label">Heslo znovu</span>
              <div class="input-wrap">
                <input type="password" name="password2" id="password2"
                       required minlength="10" autocomplete="new-password"
                       placeholder="zopakujte heslo">
              </div>
            </label>

            <div class="actions">
              <button class="btn-primary" type="submit">Registrovat</button>
              <a class="link" href="login.php">Už mám účet → Přihlášení</a>
            </div>
          </form>
        </section>
      </div>
    </div>
  </main>

  <script>
  // Jednoduchá JS validace na frontendu
  document.getElementById('registerForm').addEventListener('submit', function (e) {
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const pass  = document.getElementById('password').value;
    const pass2 = document.getElementById('password2').value;
    const photo = document.getElementById('photo').files[0];

    const errors = [];

    // Email
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
      errors.push('Zadejte platný email.');
    }
    // Telefon
    if (!/^\+?[0-9 ]{9,20}$/.test(phone)) {
      errors.push('Telefon musí mít 9–20 číslic (případně s + a mezerami).');
    }
    // Heslo
    if (pass.length < 10) {
      errors.push('Heslo musí mít alespoň 10 znaků.');
    }
    if (pass !== pass2) {
      errors.push('Hesla se neshodují.');
    }
    // Foto
    if (!photo) {
      errors.push('Nahrajte profilovou fotografii.');
    } else {
      if (!photo.type.startsWith('image/')) {
        errors.push('Soubor musí být obrázek.');
      }
      if (photo.size > 5 * 1024 * 1024) {
        errors.push('Obrázek může mít maximálně 5 MB.');
      }
    }

    if (errors.length > 0) {
      e.preventDefault();
      alert(errors.join('\n'));
    }
  });
  </script>
</body>
</html>
