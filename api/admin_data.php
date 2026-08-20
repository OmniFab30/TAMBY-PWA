<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

try {
    $pdo = db();

    $answers = $pdo->query(
        'SELECT id, question_text, answer_text, session_token, created_at
         FROM answers ORDER BY created_at DESC'
    )->fetchAll();

    $appointment = $pdo->query(
        'SELECT appointment_date, appointment_time, place_type, place_detail, note, response_status, responded_at, updated_at
         FROM scheduled_appointment WHERE id = 1'
    )->fetch();

    $authQuestions = $pdo->query(
        'SELECT id, question, answer, hint, sort_order FROM auth_questions ORDER BY sort_order ASC'
    )->fetchAll();

    $siteQuestions = $pdo->query(
        'SELECT id, question, category, sort_order FROM site_questions ORDER BY sort_order ASC'
    )->fetchAll();

    $categories = $pdo->query(
        'SELECT id, category_key, label, sort_order FROM question_categories ORDER BY sort_order ASC'
    )->fetchAll();

    $photos = $pdo->query('SELECT id, url, caption, alt, sort_order FROM photos ORDER BY sort_order ASC')->fetchAll();

    $letters = $pdo->query('SELECT id, title, content, letter_date, sort_order FROM letters ORDER BY sort_order ASC')->fetchAll();

    $settings = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);

    send_json([
        'answers'       => $answers,
        'appointment'   => $appointment,
        'authQuestions' => $authQuestions,
        'siteQuestions' => $siteQuestions,
        'categories'    => $categories,
        'photos'        => $photos,
        'letters'       => $letters,
        'settings'      => $settings,
        'username'      => $_SESSION['admin_username'] ?? '',
    ]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
