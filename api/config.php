<?php
/**
 * Configuration base de données — Anniversary Experience for Tamby
 * Modifie les 4 constantes ci-dessous avec tes identifiants MySQL.
 */
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'tamby_experience';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function send_json($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_str(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    // retire les accents
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    return trim($s);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function require_visitor_auth(): void {
    if (empty($_SESSION['tamby_authenticated'])) {
        send_json(['error' => 'Non autorisé. Merci de répondre aux questions de sécurité.'], 401);
    }
}

function require_admin_auth(): void {
    if (empty($_SESSION['admin_id'])) {
        send_json(['error' => 'Non autorisé.'], 401);
    }
}

function session_token(): string {
    if (empty($_SESSION['tamby_token'])) {
        $_SESSION['tamby_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['tamby_token'];
}
