-- ============================================================
-- Anniversary Experience for Tamby — Schéma MySQL
-- ============================================================
CREATE DATABASE IF NOT EXISTS tamby_experience
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tamby_experience;

-- Questions de sécurité (écran de connexion)
CREATE TABLE IF NOT EXISTS auth_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer VARCHAR(255) NOT NULL,
  hint VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Les 9 questions sincères
CREATE TABLE IF NOT EXISTS question_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_key VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(500) NOT NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'feelings',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Réponses envoyées par Tamby
CREATE TABLE IF NOT EXISTS answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  answer_text TEXT,
  session_token VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (question_id) REFERENCES site_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lettres personnelles
CREATE TABLE IF NOT EXISTS letters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  letter_date VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Galerie photos
CREATE TABLE IF NOT EXISTS photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(500) NOT NULL,
  caption VARCHAR(255) DEFAULT '',
  alt VARCHAR(255) DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Rendez-vous : une seule ligne (id=1), défini et modifiable par l'admin.
-- Tamby ne fait que consulter la proposition et l'accepter.
CREATE TABLE IF NOT EXISTS scheduled_appointment (
  id TINYINT PRIMARY KEY DEFAULT 1,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  place_type VARCHAR(50) NOT NULL DEFAULT 'cafe',
  place_detail VARCHAR(255) DEFAULT '',
  note VARCHAR(500) DEFAULT '',
  response_status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  responded_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_scheduled_appointment_single_row CHECK (id = 1)
) ENGINE=InnoDB;

-- Comptes admin
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Réglages divers (ex : date de premier échange pour le compteur)
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value VARCHAR(500) NOT NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO auth_questions (question, answer, hint, sort_order) VALUES
('Quel est le prénom de ta meilleure amie ?', 'tamby', 'Celle qui te connaît par cœur', 1),
('En quelle année as-tu commencé tes études supérieures ?', '2021', 'Le début d''une grande aventure', 2),
('Quelle est ta couleur préférée ?', 'rose', 'La couleur qui illumine ta vie', 3),
('Quel est le prénom de ta maman ?', 'tamby', 'La femme qui t''a tout appris', 4);

INSERT INTO question_categories (category_key, label, sort_order) VALUES
('music', '♪ Musique', 1),
('feelings', '❤ Émotions', 2),
('relationship', '✦ Nous', 3);

INSERT INTO site_questions (question, category, sort_order) VALUES
('Si tu pouvais revivre un moment de ta vie, lequel choisirais-tu ?', 'feelings', 1),
('Quelle chanson décrit exactement ce que tu ressens ce soir ?', 'music', 2),
('Qu''est-ce qui te rend la plus heureuse dans la vie, au quotidien ?', 'feelings', 3),
('Comment tu me décrirais à quelqu''un qui ne me connaît pas du tout ?', 'relationship', 4),
('Quelle musique tu mettrais pour créer l''ambiance parfaite ?', 'music', 5),
('Qu''est-ce que tu penses vraiment de moi, honnêtement ?', 'relationship', 6),
('Si on passait une journée parfaite ensemble, à quoi elle ressemblerait ?', 'relationship', 7),
('Quelle chanson te fait toujours sourire, peu importe ton humeur ?', 'music', 8),
('Est-ce que tu crois aux connexions qui arrivent sans qu''on s''y attende ?', 'feelings', 9);

INSERT INTO letters (title, content, letter_date, sort_order) VALUES
('Pour ton anniversaire',
'Chère Tamby,\n\nAujourd''hui est un jour spécial — le tien. Et j''ai voulu que cette journée soit marquée par quelque chose de sincère et de vrai.\n\nJe ne sais pas exactement comment décrire ce que je ressens quand je pense à toi. C''est quelque chose entre l''admiration et la curiosité, entre l''envie de te connaître davantage et la certitude que cette connexion est rare.\n\nTu mérites une journée où tu te sens vue, entendue, et célébrée pour tout ce que tu es.\n\nJoyeux anniversaire, Tamby. ❤',
'Aujourd''hui', 1),
('Ce que j''admire en toi',
'Tamby,\n\nIl y a des choses en toi que je remarque, même dans les silences.\n\nLa façon dont tu te tiens. La lumière dans tes yeux quand tu parles de ce qui te passionne. Ta capacité à être présente, vraiment présente, dans une conversation.\n\nCes détails-là, on ne les invente pas. Ils font partie de qui tu es, et ils me touchent profondément.\n\nMerci d''exister comme tu es.',
'De mon cœur', 2),
('Un rêve, une proposition',
'Tamby,\n\nJ''ai beaucoup pensé à ce moment. Comment le dire, comment trouver les mots justes.\n\nEt puis j''ai réalisé que la simplicité était la plus belle des élégances.\n\nJe voudrais te voir. Pas juste en ligne, pas juste dans des messages — en vrai. Un café, une promenade, un moment où le temps s''arrête et où on se découvre vraiment.\n\nEst-ce que tu veux bien me donner cette chance ? ✦',
'Avec espoir', 3);

INSERT INTO photos (url, caption, alt, sort_order) VALUES
('https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=600&h=400&fit=crop&auto=format', 'Douceur du soir', 'Lumières romantiques du soir', 1),
('https://images.unsplash.com/photo-1510771463146-e89e6e86560e?w=600&h=400&fit=crop&auto=format', 'Lumières de ville', 'Bokeh de lumières nocturnes', 2),
('https://images.unsplash.com/photo-1490750967868-88df5691cc3b?w=600&h=400&fit=crop&auto=format', 'Pétales de rose', 'Roses romantiques', 3),
('https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=600&h=400&fit=crop&auto=format', 'Silhouettes', 'Deux silhouettes au coucher du soleil', 4),
('https://images.unsplash.com/photo-1522673607200-164d1b6ce486?w=600&h=400&fit=crop&auto=format', 'Coucher de soleil', 'Coucher de soleil romantique', 5),
('https://images.unsplash.com/photo-1501554728187-ce583db33af7?w=600&h=400&fit=crop&auto=format', 'Instants précieux', 'Moment romantique au crépuscule', 6);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('first_talk_datetime', '2024-09-15 20:30:00'),
('story_eyebrow', 'Notre histoire'),
('story_title', 'Depuis le premier mot'),
('story_first_label', 'Notre première conversation'),
('story_first_note', 'À 20h30 — le moment où tout a commencé.'),
('story_quote', '« Il y a des rencontres qui arrivent par hasard, mais qui laissent une empreinte indélébile. La tienne en fait partie. »'),
('story_today_label', 'Aujourd''hui'),
('story_today_value', 'Ton anniversaire ✦'),
('counter_label_days', 'Jours'),
('counter_label_hours', 'Heures'),
('counter_label_mins', 'Minutes'),
('counter_label_secs', 'Secondes');

-- Rendez-vous proposé par défaut (à modifier depuis /admin ensuite)
INSERT INTO scheduled_appointment (id, appointment_date, appointment_time, place_type, place_detail, note) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '18:00:00', 'cafe', '', 'Un moment rien que pour nous deux.');

-- ⚠️ Aucun compte admin n'est créé ici volontairement.
-- Lance /api/setup_admin.php UNE SEULE FOIS après l'import (voir README.md)
-- pour créer ton identifiant/mot de passe avec un hash généré par PHP.
-- Supprime ensuite ce fichier setup_admin.php pour la sécurité du site.
