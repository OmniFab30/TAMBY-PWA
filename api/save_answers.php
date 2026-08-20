<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_visitor_auth();

$in = json_input();
$answers = $in['answers'] ?? [];

if (!is_array($answers) || count($answers) === 0) {
    send_json(['error' => 'Aucune réponse fournie'], 400);
}

try {
    $pdo = db();
    $token = session_token();

    // On évite les doublons si Tamby soumet deux fois : on supprime ses anciennes réponses de cette session
    $del = $pdo->prepare('DELETE FROM answers WHERE session_token = ?');
    $del->execute([$token]);

    $stmt = $pdo->prepare(
        'INSERT INTO answers (question_id, question_text, answer_text, session_token) VALUES (?, ?, ?, ?)'
    );

    foreach ($answers as $a) {
        $qid = (int)($a['question_id'] ?? 0);
        $qtext = (string)($a['question'] ?? '');
        $answerText = (string)($a['answer'] ?? '');
        if ($qid <= 0) continue;
        $stmt->execute([$qid, $qtext, $answerText, $token]);
    }

    send_json(['success' => true]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
