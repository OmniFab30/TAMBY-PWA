<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Tamby — Une expérience unique</title>
<meta name="description" content="Une expérience unique créée pour l'anniversaire de Tamby.">
<meta name="theme-color" content="#06030d">

<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icons/icon-192.png">
<link rel="icon" href="icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Tamby">
<meta name="mobile-web-app-capable" content="yes">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="dark">

<div class="ambient-glow">
  <div style="top:-10rem; left:33%; width:500px; height:500px; background:radial-gradient(circle,#c9556e,transparent);"></div>
  <div style="bottom:-10rem; right:25%; width:600px; height:600px; background:radial-gradient(circle,#8b1a3a,transparent);"></div>
</div>
<div id="particles"></div>

<!-- ============ 1. AUTH ============ -->
<section id="screen-auth" class="screen active">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">🔒</div>
      <h1 class="font-display auth-title">TAMBY</h1>
      <p class="font-body auth-sub">Espace privé · Accès sécurisé</p>
    </div>

    <div id="auth-done" class="text-center animate-fade-in hidden">
      <span class="animate-heartbeat" style="font-size:4rem;">❤</span>
      <p class="font-display" style="font-size:1.5rem; margin-top:1rem;">Bienvenue, Tamby...</p>
    </div>

    <div id="auth-card-body" class="card glow-border" style="padding:1.75rem;">
      <div class="progress-dots" id="auth-dots"></div>
      <p class="font-body muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.5rem;">
        Question <span id="auth-idx">1</span> · <span id="auth-total">4</span>
      </p>
      <h2 class="font-display" id="auth-question" style="font-size:1.25rem; margin:0 0 1.25rem; line-height:1.3;">…</h2>

      <div class="field-row" style="margin-bottom:0.75rem;">
        <input type="text" id="auth-input" placeholder="Ta réponse…" autocomplete="off">
      </div>
      <p id="auth-error" class="err-text hidden" style="margin:0 0 0.75rem;"></p>
      <button id="auth-hint-toggle" class="hint-toggle hidden" style="margin-bottom:0.5rem;">Besoin d'un indice ?</button>
      <p id="auth-hint-text" class="hint-text hidden" style="margin-bottom:0.75rem;"></p>

      <button id="auth-submit" class="btn-primary btn-lux w-full">
        <span id="auth-submit-label">Continuer</span> <span class="btn-lux-arrow">›</span>
      </button>
    </div>
  </div>
</section>

<!-- ============ 2. WELCOME ============ -->
<section id="screen-welcome" class="screen">
  <div class="text-center animate-fade-in" style="max-width:28rem; margin:0 auto;">
    <span class="animate-heartbeat" style="font-size:4.5rem;">❤</span>
    <h1 class="font-display text-shimmer title-hero" style="margin-top:1.5rem;">Tamby</h1>
    <p class="font-body" style="font-size:1.1rem; color:var(--foreground);">J'ai créé quelque chose rien que pour toi.</p>
    <p class="font-body muted" style="font-size:0.9rem; font-style:italic; opacity:0.6; margin-top:0.25rem;">Une expérience unique, du fond du cœur.</p>
    <div style="margin-top:2rem;">
      <button class="btn-ghost btn-lux next-btn" data-next="story"><span>✦ Commence l'aventure</span><span class="chev">›</span></button>
    </div>
  </div>
</section>

<!-- ============ 3. STORY ============ -->
<section id="screen-story" class="screen">
  <div style="width:100%; max-width:42rem; margin:0 auto;" class="text-center">
    <p class="font-body eyebrow" id="story-eyebrow">Notre histoire</p>
    <h2 class="font-display title-xl mb-3" id="story-title">Depuis le premier mot</h2>

    <div class="card glow-border text-left" style="padding:1.75rem; margin-top:2rem; margin-bottom:2rem;">
      <div class="timeline-item">
        <div class="timeline-dot"></div>
        <div>
          <p class="font-body muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; margin:0 0 0.15rem;" id="story-first-label">Notre première conversation</p>
          <p class="font-display" style="font-size:1.5rem; margin:0;" id="story-date">—</p>
          <p class="font-body muted" style="font-size:0.9rem; margin-top:0.25rem;" id="story-first-note">Le moment où tout a commencé.</p>
        </div>
      </div>
      <p class="timeline-quote" id="story-quote">« Il y a des rencontres qui arrivent par hasard, mais qui laissent une empreinte indélébile. La tienne en fait partie. »</p>
      <div class="timeline-item">
        <div class="timeline-dot accent"></div>
        <div>
          <p class="font-body muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; margin:0 0 0.15rem;" id="story-today-label">Aujourd'hui</p>
          <p class="font-display" style="font-size:1.5rem; margin:0;" id="story-today-value">Ton anniversaire ✦</p>
        </div>
      </div>
    </div>

    <div class="counter-grid mb-4">
      <div class="card counter-cell"><div class="counter-num" id="c-days">00</div><div class="counter-label" id="c-days-label">Jours</div></div>
      <div class="card counter-cell"><div class="counter-num" id="c-hours">00</div><div class="counter-label" id="c-hours-label">Heures</div></div>
      <div class="card counter-cell"><div class="counter-num" id="c-mins">00</div><div class="counter-label" id="c-mins-label">Minutes</div></div>
      <div class="card counter-cell"><div class="counter-num" id="c-secs">00</div><div class="counter-label" id="c-secs-label">Secondes</div></div>
    </div>

    <button class="btn-ghost next-btn" data-next="questions"><span>J'ai des questions pour toi</span><span class="chev">›</span></button>
  </div>
</section>

<!-- ============ 4. QUESTIONS ============ -->
<section id="screen-questions" class="screen">
  <div id="questions-flow" style="width:100%; max-width:36rem; margin:0 auto;">
    <div class="text-center mb-3">
      <p class="font-body q-category" id="q-category"></p>
      <div class="progress-dots" id="q-dots"></div>
      <p class="font-body muted" style="font-size:0.75rem; opacity:0.5;">Question <span id="q-idx">1</span> · <span id="q-total">9</span></p>
    </div>

    <div class="card glow-border" style="padding:1.75rem;">
      <h2 class="font-display" id="q-text" style="font-size:1.5rem; line-height:1.3; margin:0 0 1.25rem;">…</h2>
      <textarea id="q-answer" rows="4" placeholder="Prends le temps qu'il te faut…"></textarea>
      <div class="q-nav mt-3">
        <button id="q-prev" class="q-prev" disabled><span class="chev" style="display:inline-block; transform:rotate(180deg);">›</span> Précédent</button>
        <button id="q-next" class="btn-primary" style="flex:1;">Question suivante <span>›</span></button>
      </div>
    </div>
  </div>

  <div id="questions-done" class="text-center animate-fade-in hidden">
    <span class="animate-heartbeat" style="font-size:4rem;">💫</span>
    <h2 class="font-display" style="font-size:2.25rem; margin:1.25rem 0 0.75rem;">Merci, Tamby</h2>
    <p class="font-body muted">Tes réponses me touchent profondément…</p>
  </div>
</section>

<!-- ============ 5. GALLERY ============ -->
<section id="screen-gallery" class="screen">
  <div style="width:100%; max-width:56rem; margin:0 auto;">
    <div class="text-center mb-4">
      <p class="font-body eyebrow">Galerie</p>
      <h2 class="font-display title-xl">Des instants</h2>
    </div>
    <div class="gallery-grid mb-4" id="gallery-grid"></div>
    <div class="text-center">
      <button class="btn-ghost next-btn" data-next="letters"><span>Lire mes lettres</span><span class="chev">›</span></button>
    </div>
  </div>

  <div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightbox-close">✕</button>
    <div style="max-width:42rem; width:100%;">
      <img id="lightbox-img" src="" alt="">
      <p class="lightbox-caption" id="lightbox-caption"></p>
    </div>
  </div>
</section>

<!-- ============ 6. LETTERS ============ -->
<section id="screen-letters" class="screen">
  <div style="width:100%; max-width:32rem; margin:0 auto;">
    <div class="text-center mb-4">
      <p class="font-body eyebrow">Lettres personnelles</p>
      <h2 class="font-display title-xl">Pour toi</h2>
    </div>
    <div id="letters-list" class="mb-4"></div>
    <div class="text-center">
      <button class="btn-ghost next-btn hidden" id="letters-continue" data-next="birthday"><span>Continuer</span><span class="chev">›</span></button>
      <p class="font-body muted text-center" id="letters-hint" style="font-size:0.9rem; opacity:0.4;">Lis toutes les lettres pour continuer…</p>
    </div>
  </div>

  <div class="letter-modal" id="letter-modal">
    <div class="card letter-modal-card" id="letter-modal-card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
        <div>
          <p class="font-body muted" id="letter-modal-date" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; margin:0 0 0.25rem;"></p>
          <h3 class="font-display" id="letter-modal-title" style="font-size:1.5rem; margin:0;"></h3>
        </div>
        <button id="letter-modal-close" style="background:none; border:none; color:var(--muted-foreground); cursor:pointer;">✕</button>
      </div>
      <div class="letter-content" id="letter-modal-content"></div>
    </div>
  </div>
</section>

<!-- ============ 7. BIRTHDAY ============ -->
<section id="screen-birthday" class="screen">
  <div class="confetti-layer" id="confetti-layer"></div>
  <div class="text-center animate-fade-in" style="max-width:32rem; margin:0 auto; position:relative; z-index:30;">
    <span class="animate-heartbeat" style="font-size:6rem;">🎂</span>
    <h2 class="font-display" style="font-size:clamp(2.1rem,11vw,4.2rem); line-height:1.05; margin-top:1.5rem;">Joyeux<br>Anniversaire</h2>
    <p class="font-display" style="font-size:1.75rem; color:var(--primary); margin-top:0.75rem;">Tamby ✦</p>
    <p class="font-body" style="font-size:1.1rem; line-height:1.6; margin:1.5rem auto 0.5rem; max-width:24rem; color:var(--foreground);">Ce jour est le tien. Chaque seconde qui passe est une célébration de tout ce que tu es.</p>
    <p class="font-body muted" style="font-size:0.9rem; font-style:italic; opacity:0.5;">Que cette année t'apporte la joie, l'amour et toutes les choses qui font briller tes yeux.</p>
    <div style="display:flex; gap:1.25rem; justify-content:center; margin-top:2rem; font-size:1.5rem;">
      <span class="animate-heartbeat">❤</span><span class="animate-heartbeat" style="animation-delay:0.25s;">✦</span><span class="animate-heartbeat" style="animation-delay:0.5s;">❤</span>
    </div>
    <div style="margin-top:2.5rem;">
      <button class="btn-primary animate-pulse-glow next-btn" data-next="proposal" style="padding:1rem 2.5rem; font-size:1.05rem;">
        <span>J'ai quelque chose à te demander</span> <span>›</span>
      </button>
    </div>
  </div>
</section>

<!-- ============ 8. PROPOSAL / RENDEZ-VOUS (déjà défini par l'admin) ============ -->
<section id="screen-proposal" class="screen">

  <!-- Étape A : question sincère + rendez-vous proposé -->
  <div id="proposal-ask" class="text-center" style="max-width:30rem; margin:0 auto;">
    <span style="font-size:3.5rem;">💫</span>
    <p class="font-body eyebrow mt-3">Une question sincère</p>
    <h2 class="font-display" style="font-size:clamp(2rem,7vw,3.2rem); line-height:1.15;">Est-ce que tu veux bien partager un moment avec moi ?</h2>
    <p class="font-body" style="font-size:1.05rem; line-height:1.6; margin:1.1rem auto 0; max-width:24rem; color:var(--foreground); opacity:0.85;">
      J'ai déjà pensé à quelque chose pour nous deux…
    </p>

    <div class="card glow-border text-left" style="padding:1.75rem; margin:1.5rem 0 2rem;">
      <div class="rdv-detail-row">
        <span class="rdv-icon">📅</span>
        <div>
          <p class="rdv-detail-label">Date</p>
          <p class="rdv-detail-value" id="rdv-view-date">—</p>
        </div>
      </div>
      <div class="rdv-detail-row">
        <span class="rdv-icon">🕐</span>
        <div>
          <p class="rdv-detail-label">Heure</p>
          <p class="rdv-detail-value" id="rdv-view-time">—</p>
        </div>
      </div>
      <div class="rdv-detail-row" id="rdv-view-place-row">
        <span class="rdv-icon" id="rdv-view-place-icon">☕</span>
        <div>
          <p class="rdv-detail-label">Lieu</p>
          <p class="rdv-detail-value" id="rdv-view-place">—</p>
        </div>
      </div>
      <p class="font-body" id="rdv-view-note" style="margin:1rem 0 0; font-style:italic; color:var(--foreground); opacity:0.75; font-size:0.95rem;"></p>
    </div>

    <button id="proposal-yes" class="btn-primary btn-lux animate-pulse-glow" style="border-radius:999px; padding:1.1rem 2.5rem; font-size:1.05rem;">❤ Oui, avec plaisir</button>

    <div style="margin-top:1.1rem;">
      <button id="proposal-maybe-later" class="hint-toggle">Ce n'est pas le bon moment ?</button>
      <p id="proposal-maybe-text" class="hint-text hidden" style="margin-top:0.6rem; max-width:22rem; margin-left:auto; margin-right:auto;">
        Pas de souci — écris-moi directement et on trouvera un autre moment ensemble. ✦
      </p>
    </div>
  </div>

  <!-- Étape B : confirmation -->
  <div id="proposal-accepted" class="text-center animate-screen-in hidden" style="max-width:30rem; margin:0 auto;">
    <span class="animate-heartbeat" style="font-size:4.5rem;">❤</span>
    <h2 class="font-display text-shimmer" style="font-size:2.75rem; margin-top:1rem;">Je suis si heureux</h2>

    <div class="card glow-border text-left" style="padding:1.5rem; margin:1.5rem 0;">
      <div class="rdv-detail-row">
        <span class="rdv-icon">✦</span>
        <div>
          <p class="rdv-detail-label">C'est noté</p>
          <p class="rdv-detail-value" id="rdv-confirmed-recap" style="color:var(--primary);">—</p>
        </div>
      </div>
    </div>

    <p class="font-body" style="font-size:1.05rem; line-height:1.6; color:var(--foreground); opacity:0.85;">
      J'ai vraiment hâte de te voir. Merci pour ton oui. ✦
    </p>
    <p class="font-body" style="font-style:italic; color:var(--primary); opacity:0.85; margin-top:1rem;">À très bientôt, Tamby. ❤</p>
  </div>
</section>

<!-- ============ ADMIN TRIGGER (invisible) ============ -->
<button id="admin-trigger" aria-label="Administration" style="position:fixed; bottom:1rem; right:1rem; width:2.25rem; height:2.25rem; background:none; border:none; opacity:0; z-index:40; cursor:pointer;">⚙</button>

<!-- ============ DÉCONNEXION VISITEUR ============ -->
<button id="logout-trigger" class="mini-btn hidden" style="position:fixed; top:1rem; right:1rem; z-index:40;">⎋ Déconnexion</button>

<script src="js/app.js"></script>
</body>
</html>
