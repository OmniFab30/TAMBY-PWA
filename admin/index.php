<?php
declare(strict_types=1);
require __DIR__ . '/../api/config.php';
$isLoggedIn = !empty($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Tamby</title>
<link rel="stylesheet" href="../css/style.css">
<style>
  body { padding: 0; overflow-x: hidden; }
  .admin-inner { padding-top: 3rem; max-width: 56rem; }
  @media (max-width: 420px) {
    .admin-inner { padding-top: 2rem; }
  }
</style>
</head>
<body class="dark">

<?php if (!$isLoggedIn): ?>
  <!-- ===== LOGIN ===== -->
  <div class="admin-login-box card glow-border" style="padding:2rem;">
    <p class="font-body eyebrow text-center">Espace sécurisé</p>
    <h1 class="font-display text-center" style="font-size:1.75rem; margin:0 0 1.5rem;">Administration</h1>
    <div class="field-row">
      <label class="field-label">Identifiant</label>
      <input type="text" id="login-username" autocomplete="username">
    </div>
    <div class="field-row">
      <label class="field-label">Mot de passe</label>
      <input type="password" id="login-password" autocomplete="current-password">
    </div>
    <p id="login-error" class="err-text hidden mb-2"></p>
    <button id="login-submit" class="btn-primary w-full">Se connecter</button>
    <p class="text-center mt-3"><a href="../index.php" class="font-body muted" style="font-size:0.85rem;">← Retour au site</a></p>
  </div>
  <script>
    document.getElementById('login-submit').addEventListener('click', async () => {
      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value;
      const err = document.getElementById('login-error');
      err.classList.add('hidden');
      try {
        const res = await fetch('../api/admin_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password }),
          credentials: 'same-origin',
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur');
        window.location.reload();
      } catch (e) {
        err.textContent = e.message || 'Identifiants incorrects';
        err.classList.remove('hidden');
      }
    });
    document.getElementById('login-password').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') document.getElementById('login-submit').click();
    });
  </script>

<?php else: ?>
  <!-- ===== DASHBOARD ===== -->
  <div class="admin-inner">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; flex-wrap:wrap; gap:0.75rem;">
      <div>
        <p class="font-body eyebrow" style="margin-bottom:0.25rem;">Espace sécurisé</p>
        <h1 class="font-display" style="font-size:2rem; margin:0;">Administration</h1>
      </div>
      <div style="display:flex; gap:0.5rem; align-items:center;">
        <a href="../index.php" class="mini-btn" style="text-decoration:none; display:inline-block;">← Site</a>
        <button id="logout-btn" class="mini-btn">Déconnexion</button>
      </div>
    </div>

    <div class="admin-tabs">
      <button class="admin-tab active" data-tab="appointments">📅 Rendez-vous</button>
      <button class="admin-tab" data-tab="answers">💬 Réponses</button>
      <button class="admin-tab" data-tab="auth-questions">🔑 Connexion</button>
      <button class="admin-tab" data-tab="questions">⭐ Questions</button>
      <button class="admin-tab" data-tab="photos">🖼 Photos</button>
      <button class="admin-tab" data-tab="letters">✉ Lettres</button>
      <button class="admin-tab" data-tab="story">⏱ Écran compteur</button>
      <button class="admin-tab" data-tab="account">🔒 Compte</button>
    </div>

    <div id="tab-appointments" class="tab-panel"></div>
    <div id="tab-answers" class="tab-panel hidden"></div>
    <div id="tab-auth-questions" class="tab-panel hidden"></div>
    <div id="tab-questions" class="tab-panel hidden"></div>
    <div id="tab-photos" class="tab-panel hidden"></div>
    <div id="tab-letters" class="tab-panel hidden"></div>
    <div id="tab-story" class="tab-panel hidden"></div>
    <div id="tab-account" class="tab-panel hidden"></div>
  </div>

  <script src="app.js"></script>
<?php endif; ?>

</body>
</html>
