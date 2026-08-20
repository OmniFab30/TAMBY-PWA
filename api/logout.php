<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

// On efface uniquement la session "visiteur" (Tamby), pas la session admin.
unset($_SESSION['tamby_authenticated']);
unset($_SESSION['tamby_progress']);
unset($_SESSION['tamby_token']);

send_json(['success' => true]);
