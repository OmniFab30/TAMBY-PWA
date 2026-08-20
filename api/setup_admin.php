<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

/**
 * Script à usage UNIQUE pour créer le compte admin.
 * 1) Ouvre cette page dans ton navigateur : https://tondomaine.com/api/setup_admin.php
 * 2) Choisis un identifiant et un mot de passe solides.
 * 3) Une fois le compte créé, SUPPRIME ce fichier du serveur.
 */

$pdo = db();
$existing = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

$message = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($existing > 0) {
        $message = 'Un compte admin existe déjà. Supprime ce fichier immédiatement.';
    } elseif (mb_strlen($username) < 3 || mb_strlen($password) < 8) {
        $message = 'Identifiant (min. 3 caractères) et mot de passe (min. 8 caractères) requis.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $hash]);
        $done = true;
        $message = 'Compte admin créé avec succès ! Supprime maintenant ce fichier (setup_admin.php) du serveur.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Configuration admin — Tamby</title>
<style>
  body { font-family: system-ui, sans-serif; background:#06030d; color:#f5e6ec; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .box { background:#0d0718; border:1px solid rgba(201,85,110,.25); border-radius:16px; padding:32px; max-width:380px; width:100%; }
  h1 { font-size:1.3rem; margin-top:0; }
  input { width:100%; box-sizing:border-box; padding:10px 12px; margin:8px 0 16px; border-radius:8px; border:1px solid rgba(201,85,110,.25); background:#1a0e22; color:#f5e6ec; }
  button { width:100%; padding:12px; border-radius:8px; border:none; background:#c9556e; color:#fff; font-weight:600; cursor:pointer; }
  p.msg { padding:12px; border-radius:8px; background:rgba(201,85,110,.12); font-size:.9rem; }
</style>
</head>
<body>
  <div class="box">
    <h1>Configuration du compte admin</h1>
    <?php if ($message): ?><p class="msg"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if (!$done && $existing === 0): ?>
      <form method="post">
        <label>Identifiant</label>
        <input type="text" name="username" required minlength="3">
        <label>Mot de passe</label>
        <input type="password" name="password" required minlength="8">
        <button type="submit">Créer le compte</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
