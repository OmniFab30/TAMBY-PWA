<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

unset($_SESSION['admin_id'], $_SESSION['admin_username']);
session_regenerate_id(true);

send_json(['success' => true]);
