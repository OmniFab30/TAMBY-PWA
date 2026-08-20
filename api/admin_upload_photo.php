<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

if (empty($_FILES['photo']) || !is_array($_FILES['photo'])) {
    send_json(['error' => 'Aucun fichier reçu.'], 400);
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
        UPLOAD_ERR_FORM_SIZE  => 'Le fichier dépasse la taille maximale autorisée.',
        UPLOAD_ERR_PARTIAL    => 'Le téléversement a été interrompu.',
        UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu.',
    ];
    send_json(['error' => $messages[$file['error']] ?? 'Erreur lors du téléversement.'], 400);
}

const MAX_SIZE = 8 * 1024 * 1024; // 8 Mo
if ($file['size'] > MAX_SIZE) {
    send_json(['error' => 'L\'image est trop lourde (8 Mo maximum).'], 400);
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']) ?: '';

if (!isset($allowedMimes[$mime])) {
    send_json(['error' => 'Format non supporté. Utilise une image JPG, PNG, WEBP ou GIF.'], 415);
}

// On vérifie aussi qu'il s'agit bien d'une image valide (pas juste un mime usurpé).
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    send_json(['error' => 'Le fichier ne semble pas être une image valide.'], 415);
}

$ext = $allowedMimes[$mime];
$filename = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

$uploadDir = __DIR__ . '/../uploads/photos';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

$destination = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    send_json(['error' => 'Impossible d\'enregistrer le fichier sur le serveur.'], 500);
}

@chmod($destination, 0644);

// Chemin relatif à stocker en base et à utiliser tel quel dans <img src="...">
send_json(['success' => true, 'url' => 'uploads/photos/' . $filename]);
