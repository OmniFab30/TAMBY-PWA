<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

try {
    $pdo = db();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM auth_questions')->fetchColumn();
    $passed = count($_SESSION['tamby_progress'] ?? []);

    if ($total > 0 && $passed >= $total) {
        $_SESSION['tamby_authenticated'] = true;
        send_json(['authenticated' => true, 'token' => session_token()]);
    }

    send_json(['authenticated' => false], 403);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
