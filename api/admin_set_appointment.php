<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

$in = json_input();

$date        = (string)($in['appointment_date'] ?? '');
$time        = (string)($in['appointment_time'] ?? '');
$placeType   = (string)($in['place_type'] ?? '');
$placeDetail = trim((string)($in['place_detail'] ?? ''));
$note        = trim((string)($in['note'] ?? ''));
$resetResponse = !empty($in['reset_response']);

$errors = [];

$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $errors[] = 'Date invalide.';
}

if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
    $errors[] = 'Heure invalide.';
}

$allowedPlaces = ['cafe', 'promenade', 'diner', 'autre'];
if (!in_array($placeType, $allowedPlaces, true)) {
    $errors[] = 'Type de lieu invalide.';
}

if ($placeType === 'autre' && $placeDetail === '') {
    $errors[] = 'Merci de préciser le lieu souhaité.';
}

if (mb_strlen($note) > 500) {
    $errors[] = 'Le message est trop long (500 caractères max).';
}

if (!empty($errors)) {
    send_json(['error' => implode(' ', $errors)], 422);
}

try {
    $pdo = db();

    $sql = 'UPDATE scheduled_appointment
            SET appointment_date = ?, appointment_time = ?, place_type = ?, place_detail = ?, note = ?';
    $params = [$date, $time, $placeType, $placeDetail, $note];

    if ($resetResponse) {
        $sql .= ", response_status = 'pending', responded_at = NULL";
    }
    $sql .= ' WHERE id = 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Table singleton : si aucune ligne n'existe encore, on la crée
    if ($stmt->rowCount() === 0) {
        $exists = (int)$pdo->query('SELECT COUNT(*) FROM scheduled_appointment WHERE id = 1')->fetchColumn();
        if ($exists === 0) {
            $ins = $pdo->prepare(
                'INSERT INTO scheduled_appointment (id, appointment_date, appointment_time, place_type, place_detail, note)
                 VALUES (1, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$date, $time, $placeType, $placeDetail, $note]);
        }
    }

    $appt = $pdo->query(
        'SELECT appointment_date, appointment_time, place_type, place_detail, note, response_status, responded_at
         FROM scheduled_appointment WHERE id = 1'
    )->fetch();

    send_json(['success' => true, 'appointment' => $appt]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
