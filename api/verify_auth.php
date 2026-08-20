<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

$in = json_input();
$questionId = (int)($in['question_id'] ?? 0);
$answer     = (string)($in['answer'] ?? '');

if ($questionId <= 0 || $answer === '') {
    send_json(['error' => 'Requête invalide'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT answer FROM auth_questions WHERE id = ?');
    $stmt->execute([$questionId]);
    $correct = $stmt->fetchColumn();

    if ($correct === false) {
        send_json(['error' => 'Question introuvable'], 404);
    }

    $isCorrect = normalize_str($answer) === normalize_str((string)$correct);

    if ($isCorrect) {
        // On note la progression ; l'authentification complète est confirmée par /api/complete_auth.php
        $_SESSION['tamby_progress'][$questionId] = true;
    }

    send_json(['correct' => $isCorrect]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
