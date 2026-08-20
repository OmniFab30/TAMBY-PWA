<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_visitor_auth();

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        "UPDATE scheduled_appointment SET response_status = 'accepted', responded_at = NOW() WHERE id = 1"
    );
    $stmt->execute();

    $appt = $pdo->query(
        'SELECT appointment_date, appointment_time, place_type, place_detail, note, response_status
         FROM scheduled_appointment WHERE id = 1'
    )->fetch();

    send_json(['success' => true, 'appointment' => $appt]);
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
