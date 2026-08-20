<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(['error' => 'Méthode invalide'], 405);
}

try {
    $pdo = db();

    // Questions d'authentification : on n'envoie JAMAIS la réponse au client
    $authQ = $pdo->query('SELECT id, question, hint FROM auth_questions ORDER BY sort_order ASC')->fetchAll();

    $siteQ = $pdo->query('SELECT id, question, category FROM site_questions ORDER BY sort_order ASC')->fetchAll();

    $categories = $pdo->query(
        'SELECT category_key, label FROM question_categories ORDER BY sort_order ASC'
    )->fetchAll();

    $letters = $pdo->query('SELECT id, title, content, letter_date FROM letters ORDER BY sort_order ASC')->fetchAll();

    $photos = $pdo->query('SELECT id, url, caption, alt FROM photos ORDER BY sort_order ASC')->fetchAll();

    $settingsRows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);

    $defaultSettings = [
        'first_talk_datetime' => '2024-09-15 20:30:00',
        'story_eyebrow'       => 'Notre histoire',
        'story_title'         => 'Depuis le premier mot',
        'story_first_label'   => 'Notre première conversation',
        'story_first_note'    => 'Le moment où tout a commencé.',
        'story_quote'         => '« Il y a des rencontres qui arrivent par hasard, mais qui laissent une empreinte indélébile. La tienne en fait partie. »',
        'story_today_label'   => 'Aujourd\'hui',
        'story_today_value'   => 'Ton anniversaire ✦',
        'counter_label_days'  => 'Jours',
        'counter_label_hours' => 'Heures',
        'counter_label_mins'  => 'Minutes',
        'counter_label_secs'  => 'Secondes',
    ];
    $settings = array_merge($defaultSettings, array_filter($settingsRows, fn($v) => $v !== ''));

    $firstTalk = $settings['first_talk_datetime'];

    $appt = $pdo->query(
        'SELECT appointment_date, appointment_time, place_type, place_detail, note, response_status
         FROM scheduled_appointment WHERE id = 1'
    )->fetch();

    send_json([
        'authQuestions' => $authQ,
        'authTotal'     => count($authQ),
        'siteQuestions' => $siteQ,
        'categories'    => $categories,
        'letters'       => $letters,
        'photos'        => $photos,
        'firstTalk'     => str_replace(' ', 'T', $firstTalk),
        'settings'      => $settings,
        'appointment'   => $appt ?: null,
        'authenticated' => !empty($_SESSION['tamby_authenticated']),
    ]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur', 'detail' => $e->getMessage()], 500);
}
