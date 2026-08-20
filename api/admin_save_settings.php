<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

$in = json_input();
$settings = is_array($in['settings'] ?? null) ? $in['settings'] : [];

// Liste blanche des clés modifiables depuis ce formulaire — évite toute injection de clé arbitraire.
$allowedKeys = [
    'first_talk_datetime',
    'story_eyebrow',
    'story_title',
    'story_first_label',
    'story_first_note',
    'story_quote',
    'story_today_label',
    'story_today_value',
    'counter_label_days',
    'counter_label_hours',
    'counter_label_mins',
    'counter_label_secs',
];

$maxLengths = [
    'first_talk_datetime' => 30,
    'story_quote'          => 1000,
];
$defaultMaxLength = 255;

$errors = [];
$clean = [];

foreach ($allowedKeys as $key) {
    if (!array_key_exists($key, $settings)) continue;
    $value = trim((string)$settings[$key]);
    $max = $maxLengths[$key] ?? $defaultMaxLength;
    if (mb_strlen($value) > $max) {
        $errors[] = "Le champ « $key » est trop long ($max caractères max).";
        continue;
    }
    $clean[$key] = $value;
}

if (isset($clean['first_talk_datetime']) && $clean['first_talk_datetime'] !== '') {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $clean['first_talk_datetime'])
        ?: DateTime::createFromFormat('Y-m-d\TH:i', $clean['first_talk_datetime']);
    if (!$dt) {
        $errors[] = 'Date/heure de la première conversation invalide.';
    } else {
        $clean['first_talk_datetime'] = $dt->format('Y-m-d H:i:s');
    }
}

if (!empty($errors)) {
    send_json(['error' => implode(' ', $errors)], 422);
}

if (empty($clean)) {
    send_json(['error' => 'Aucun réglage à enregistrer.'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $pdo->beginTransaction();
    foreach ($clean as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    $pdo->commit();

    $all = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    send_json(['success' => true, 'settings' => $all]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['error' => 'Erreur serveur'], 500);
}
