<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

$in = json_input();
$currentPassword = (string)($in['current_password'] ?? '');
$newPassword     = (string)($in['new_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') {
    send_json(['error' => 'Merci de remplir tous les champs.'], 400);
}

if (mb_strlen($newPassword) < 8) {
    send_json(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'], 422);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        usleep(400000); // petite pause anti brute-force
        send_json(['error' => 'Le mot de passe actuel est incorrect.'], 401);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
    $upd->execute([$newHash, $_SESSION['admin_id']]);

    // On régénère la session par sécurité après un changement sensible.
    session_regenerate_id(true);

    send_json(['success' => true]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
