-- ============================================================
-- Migration v2 — à exécuter UNE FOIS si ta base existe déjà
-- (installation faite avant l'ajout des catégories dynamiques,
-- de l'écran "compteur" configurable et de l'upload de photos).
--
-- Si tu pars d'une INSTALLATION NEUVE, inutile d'exécuter ce fichier :
-- sql/schema.sql contient déjà tout.
-- ============================================================
USE tamby_experience;

-- 1) Table des catégories de questions (dynamique, au lieu de l'ENUM figé)
CREATE TABLE IF NOT EXISTS question_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_key VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT IGNORE INTO question_categories (category_key, label, sort_order) VALUES
('music', '♪ Musique', 1),
('feelings', '❤ Émotions', 2),
('relationship', '✦ Nous', 3);

-- 2) La colonne category de site_questions passe d'ENUM à VARCHAR libre
ALTER TABLE site_questions
  MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'feelings';

-- 3) Réglages de l'écran "compteur" (histoire / countdown), éditables depuis l'admin
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
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

-- 4) Rien à migrer pour les photos : la colonne `url` accepte déjà aussi bien
--    une URL externe qu'un chemin local relatif (ex : uploads/photos/xxx.jpg).
--    Pense simplement à créer le dossier d'upload avec les bons droits :
--    mkdir -p uploads/photos && chmod 775 uploads/photos
