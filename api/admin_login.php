<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

$in = json_input();
$username = (string)($in['username'] ?? '');
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
    send_json(['error' => 'Identifiant et mot de passe requis'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        // petite pause pour limiter le brute-force
        usleep(400000);
        send_json(['error' => 'Identifiants incorrects'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_username'] = $username;

    send_json(['success' => true]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
