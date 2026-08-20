<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Méthode invalide'], 405);
}

require_admin_auth();

$in = json_input();
$type   = (string)($in['type'] ?? '');
$action = (string)($in['action'] ?? '');
$id     = (int)($in['id'] ?? 0);
$data   = is_array($in['data'] ?? null) ? $in['data'] : [];

$allowedTypes   = ['auth_question', 'site_question', 'category', 'photo', 'letter', 'answer'];
$allowedActions = ['create', 'update', 'delete'];

if (!in_array($type, $allowedTypes, true) || !in_array($action, $allowedActions, true)) {
    send_json(['error' => 'Requête invalide.'], 400);
}

// Les réponses de Tamby ne sont jamais créées/modifiées depuis l'admin, seulement supprimées.
if ($type === 'answer' && $action !== 'delete') {
    send_json(['error' => 'Requête invalide.'], 400);
}

if ($action !== 'create' && $id <= 0) {
    send_json(['error' => 'Identifiant manquant.'], 400);
}

try {
    $pdo = db();

    if ($action === 'delete') {
        if ($type === 'category') {
            $keyStmt = $pdo->prepare('SELECT category_key FROM question_categories WHERE id = ?');
            $keyStmt->execute([$id]);
            $catKey = $keyStmt->fetchColumn();

            if ($catKey === false) {
                send_json(['error' => 'Catégorie introuvable.'], 404);
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM site_questions WHERE category = ?');
            $countStmt->execute([$catKey]);
            $inUse = (int)$countStmt->fetchColumn();

            if ($inUse > 0) {
                send_json(['error' => "Impossible de supprimer : $inUse question(s) utilisent encore cette catégorie. Réattribue-les d'abord à une autre catégorie."], 409);
            }
        }

        $table = [
            'auth_question' => 'auth_questions',
            'site_question' => 'site_questions',
            'category'      => 'question_categories',
            'photo'         => 'photos',
            'letter'        => 'letters',
            'answer'        => 'answers',
        ][$type];
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        send_json(['success' => true]);
    }

    switch ($type) {
        case 'auth_question': {
            $question  = trim((string)($data['question'] ?? ''));
            $answer    = trim((string)($data['answer'] ?? ''));
            $hint      = trim((string)($data['hint'] ?? ''));
            $sortOrder = (int)($data['sort_order'] ?? 0);

            $errors = [];
            if ($question === '') $errors[] = 'La question est requise.';
            if ($answer === '') $errors[] = 'La réponse est requise.';
            if (mb_strlen($question) > 255) $errors[] = 'La question est trop longue (255 caractères max).';
            if (mb_strlen($answer) > 255) $errors[] = 'La réponse est trop longue (255 caractères max).';
            if (mb_strlen($hint) > 255) $errors[] = 'L\'indice est trop long (255 caractères max).';
            if (!empty($errors)) send_json(['error' => implode(' ', $errors)], 422);

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO auth_questions (question, answer, hint, sort_order) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$question, $answer, $hint ?: null, $sortOrder]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE auth_questions SET question = ?, answer = ?, hint = ?, sort_order = ? WHERE id = ?'
                );
                $stmt->execute([$question, $answer, $hint ?: null, $sortOrder, $id]);
                $newId = $id;
            }

            $row = $pdo->prepare('SELECT id, question, answer, hint, sort_order FROM auth_questions WHERE id = ?');
            $row->execute([$newId]);
            send_json(['success' => true, 'item' => $row->fetch()]);
            break;
        }

        case 'category': {
            $categoryKey = trim((string)($data['category_key'] ?? ''));
            $label       = trim((string)($data['label'] ?? ''));
            $sortOrder   = (int)($data['sort_order'] ?? 0);

            // Normalise la clé : minuscules, chiffres, tirets bas uniquement.
            $categoryKey = mb_strtolower($categoryKey);
            $categoryKey = preg_replace('/[^a-z0-9_]+/', '_', $categoryKey);
            $categoryKey = trim((string)$categoryKey, '_');

            $errors = [];
            if ($categoryKey === '') $errors[] = 'La clé de la catégorie est requise (lettres, chiffres, underscore).';
            if ($label === '') $errors[] = 'Le libellé est requis.';
            if (mb_strlen($categoryKey) > 50) $errors[] = 'La clé est trop longue (50 caractères max).';
            if (mb_strlen($label) > 100) $errors[] = 'Le libellé est trop long (100 caractères max).';
            if (!empty($errors)) send_json(['error' => implode(' ', $errors)], 422);

            if ($action === 'create') {
                $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM question_categories WHERE category_key = ?');
                $dupStmt->execute([$categoryKey]);
                if ((int)$dupStmt->fetchColumn() > 0) {
                    send_json(['error' => 'Cette clé de catégorie existe déjà.'], 409);
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO question_categories (category_key, label, sort_order) VALUES (?, ?, ?)'
                );
                $stmt->execute([$categoryKey, $label, $sortOrder]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM question_categories WHERE category_key = ? AND id != ?');
                $dupStmt->execute([$categoryKey, $id]);
                if ((int)$dupStmt->fetchColumn() > 0) {
                    send_json(['error' => 'Cette clé de catégorie existe déjà.'], 409);
                }

                $oldStmt = $pdo->prepare('SELECT category_key FROM question_categories WHERE id = ?');
                $oldStmt->execute([$id]);
                $oldKey = $oldStmt->fetchColumn();
                if ($oldKey === false) send_json(['error' => 'Catégorie introuvable.'], 404);

                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare(
                        'UPDATE question_categories SET category_key = ?, label = ?, sort_order = ? WHERE id = ?'
                    );
                    $stmt->execute([$categoryKey, $label, $sortOrder, $id]);

                    if ($oldKey !== $categoryKey) {
                        $sync = $pdo->prepare('UPDATE site_questions SET category = ? WHERE category = ?');
                        $sync->execute([$categoryKey, $oldKey]);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                $newId = $id;
            }

            $row = $pdo->prepare('SELECT id, category_key, label, sort_order FROM question_categories WHERE id = ?');
            $row->execute([$newId]);
            send_json(['success' => true, 'item' => $row->fetch()]);
            break;
        }

        case 'site_question': {
            $question  = trim((string)($data['question'] ?? ''));
            $category  = trim((string)($data['category'] ?? ''));
            $sortOrder = (int)($data['sort_order'] ?? 0);

            $catStmt = $pdo->query('SELECT category_key FROM question_categories');
            $validCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

            $errors = [];
            if ($question === '') $errors[] = 'La question est requise.';
            if (mb_strlen($question) > 500) $errors[] = 'La question est trop longue (500 caractères max).';
            if (!in_array($category, $validCategories, true)) $errors[] = 'Catégorie invalide.';
            if (!empty($errors)) send_json(['error' => implode(' ', $errors)], 422);

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO site_questions (question, category, sort_order) VALUES (?, ?, ?)'
                );
                $stmt->execute([$question, $category, $sortOrder]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE site_questions SET question = ?, category = ?, sort_order = ? WHERE id = ?'
                );
                $stmt->execute([$question, $category, $sortOrder, $id]);
                $newId = $id;
            }

            $row = $pdo->prepare('SELECT id, question, category, sort_order FROM site_questions WHERE id = ?');
            $row->execute([$newId]);
            send_json(['success' => true, 'item' => $row->fetch()]);
            break;
        }

        case 'photo': {
            $url       = trim((string)($data['url'] ?? ''));
            $caption   = trim((string)($data['caption'] ?? ''));
            $alt       = trim((string)($data['alt'] ?? ''));
            $sortOrder = (int)($data['sort_order'] ?? 0);

            $errors = [];
            if ($url === '') $errors[] = 'L\'image est requise (téléverse un fichier ou indique un chemin/URL).';
            if (mb_strlen($url) > 500) $errors[] = 'Le chemin de l\'image est trop long (500 caractères max).';
            if (mb_strlen($caption) > 255) $errors[] = 'La légende est trop longue (255 caractères max).';
            if (mb_strlen($alt) > 255) $errors[] = 'Le texte alternatif est trop long (255 caractères max).';
            if (!empty($errors)) send_json(['error' => implode(' ', $errors)], 422);

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO photos (url, caption, alt, sort_order) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$url, $caption, $alt, $sortOrder]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE photos SET url = ?, caption = ?, alt = ?, sort_order = ? WHERE id = ?'
                );
                $stmt->execute([$url, $caption, $alt, $sortOrder, $id]);
                $newId = $id;
            }

            $row = $pdo->prepare('SELECT id, url, caption, alt, sort_order FROM photos WHERE id = ?');
            $row->execute([$newId]);
            send_json(['success' => true, 'item' => $row->fetch()]);
            break;
        }

        case 'letter': {
            $title      = trim((string)($data['title'] ?? ''));
            $content    = (string)($data['content'] ?? '');
            $letterDate = trim((string)($data['letter_date'] ?? ''));
            $sortOrder  = (int)($data['sort_order'] ?? 0);

            $errors = [];
            if ($title === '') $errors[] = 'Le titre est requis.';
            if (trim($content) === '') $errors[] = 'Le contenu est requis.';
            if ($letterDate === '') $errors[] = 'La date (ou mention) est requise.';
            if (mb_strlen($title) > 255) $errors[] = 'Le titre est trop long (255 caractères max).';
            if (mb_strlen($letterDate) > 100) $errors[] = 'La date est trop longue (100 caractères max).';
            if (!empty($errors)) send_json(['error' => implode(' ', $errors)], 422);

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO letters (title, content, letter_date, sort_order) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$title, $content, $letterDate, $sortOrder]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE letters SET title = ?, content = ?, letter_date = ?, sort_order = ? WHERE id = ?'
                );
                $stmt->execute([$title, $content, $letterDate, $sortOrder, $id]);
                $newId = $id;
            }

            $row = $pdo->prepare('SELECT id, title, content, letter_date, sort_order FROM letters WHERE id = ?');
            $row->execute([$newId]);
            send_json(['success' => true, 'item' => $row->fetch()]);
            break;
        }
    }
} catch (Throwable $e) {
    send_json(['error' => 'Erreur serveur'], 500);
}
