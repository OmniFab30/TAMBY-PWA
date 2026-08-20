/* ============================================================
   Tamby — Anniversary Experience — app.js
   PWA front-end, backed by PHP + MySQL API (dossier /api)
============================================================ */
(() => {
  "use strict";

  const FLOW = ["auth", "welcome", "story", "questions", "gallery", "letters", "birthday", "proposal"];

  const state = {
    content: null,          // rempli par /api/get_content.php
    authIdx: 0,
    qIdx: 0,
    qAnswers: [],
    hintShown: false,
    lettersRead: new Set(),
  };

  /* ---------- helpers ---------- */
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  function normalize(s) {
    return s.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  async function api(path, opts = {}) {
    const res = await fetch(`api/${path}`, {
      method: opts.method || "GET",
      headers: { "Content-Type": "application/json" },
      body: opts.body ? JSON.stringify(opts.body) : undefined,
      credentials: "same-origin",
    });
    let data = {};
    try { data = await res.json(); } catch (_) {}
    if (!res.ok) throw Object.assign(new Error(data.error || "Erreur serveur"), { data, status: res.status });
    return data;
  }

  function showScreen(name) {
    $$(".screen").forEach((s) => s.classList.remove("active"));
    const el = $(`#screen-${name}`);
    if (el) el.classList.add("active");
    window.scrollTo({ top: 0, behavior: "instant" in window ? "instant" : "auto" });
    const logoutBtn = $("#logout-trigger");
    if (logoutBtn) logoutBtn.classList.toggle("hidden", name === "auth");
  }

  /* ---------- Déconnexion visiteur ---------- */
  async function logoutVisitor() {
    const btn = $("#logout-trigger");
    if (btn) { btn.disabled = true; btn.textContent = "…"; }
    try {
      await api("logout.php", { method: "POST" });
    } catch (e) { /* on réinitialise l'écran même en cas d'erreur réseau */ }
    window.location.reload();
  }

  function goNext(current) {
    const i = FLOW.indexOf(current);
    if (i < FLOW.length - 1) showScreen(FLOW[i + 1]);
  }

  /* ---------- floating particles ---------- */
  function initParticles() {
    const symbols = ["❤", "✦", "·", "°", "✿", "♡", "★"];
    const container = $("#particles");
    for (let i = 0; i < 22; i++) {
      const span = document.createElement("span");
      span.textContent = symbols[i % symbols.length];
      span.className = "animate-float";
      span.style.left = `${(i * 4.5 + 2) % 100}%`;
      span.style.top = `${(i * 7 + 5) % 100}%`;
      span.style.animationDelay = `${(i * 0.4) % 9}s`;
      span.style.animationDuration = `${7 + (i % 6)}s`;
      span.style.opacity = 0.08 + (i % 4) * 0.05;
      span.style.fontSize = `${9 + (i % 4) * 5}px`;
      container.appendChild(span);
    }
  }

  /* ---------- 1. AUTH ---------- */
  function renderAuthDots() {
    const total = state.content.authTotal;
    const wrap = $("#auth-dots");
    wrap.innerHTML = "";
    for (let i = 0; i < total; i++) {
      const dot = document.createElement("span");
      dot.className = i <= state.authIdx ? "on" : "off";
      wrap.appendChild(dot);
    }
  }

  function renderAuthQuestion() {
    const q = state.content.authQuestions[state.authIdx];
    $("#auth-idx").textContent = state.authIdx + 1;
    $("#auth-total").textContent = state.content.authTotal;
    $("#auth-question").textContent = q.question;
    $("#auth-input").value = "";
    $("#auth-error").classList.add("hidden");
    state.hintShown = false;
    $("#auth-hint-text").classList.add("hidden");
    $("#auth-hint-toggle").classList.toggle("hidden", !q.hint);
    $("#auth-hint-toggle").textContent = "Besoin d'un indice ?";
    $("#auth-hint-text").textContent = q.hint || "";
    renderAuthDots();
    setTimeout(() => $("#auth-input").focus(), 150);
  }

  async function submitAuth() {
    const input = $("#auth-input");
    const val = input.value.trim();
    if (!val) return;
    const q = state.content.authQuestions[state.authIdx];
    const btn = $("#auth-submit");
    btn.disabled = true;
    $("#auth-submit-label").textContent = "Vérification…";

    try {
      const { correct } = await api("verify_auth.php", {
        method: "POST",
        body: { question_id: q.id, answer: val },
      });

      if (correct) {
        if (state.authIdx < state.content.authQuestions.length - 1) {
          state.authIdx++;
          renderAuthQuestion();
        } else {
          const done = await api("complete_auth.php", { method: "POST" });
          if (done.authenticated) {
            $("#auth-card-body").classList.add("hidden");
            $("#auth-done").classList.remove("hidden");
            setTimeout(() => goNext("auth"), 1400);
          }
        }
      } else {
        $("#auth-error").textContent = "Ce n'est pas la bonne réponse... Essaie encore.";
        $("#auth-error").classList.remove("hidden");
        $("#auth-card-body").classList.add("animate-shake");
        setTimeout(() => $("#auth-card-body").classList.remove("animate-shake"), 400);
        input.value = "";
        setTimeout(() => $("#auth-error").classList.add("hidden"), 3000);
      }
    } catch (e) {
      $("#auth-error").textContent = "Une erreur est survenue. Réessaie.";
      $("#auth-error").classList.remove("hidden");
    } finally {
      btn.disabled = false;
      $("#auth-submit-label").textContent = "Continuer";
    }
  }

  /* ---------- 3. STORY / COUNTER (contenu configurable depuis l'admin) ---------- */
  function renderStorySettings() {
    const s = state.content.settings || {};
    const set = (id, val) => { const el = $(id); if (el && val !== undefined) el.textContent = val; };
    set("#story-eyebrow", s.story_eyebrow);
    set("#story-title", s.story_title);
    set("#story-first-label", s.story_first_label);
    set("#story-first-note", s.story_first_note);
    set("#story-quote", s.story_quote);
    set("#story-today-label", s.story_today_label);
    set("#story-today-value", s.story_today_value);
    set("#c-days-label", s.counter_label_days);
    set("#c-hours-label", s.counter_label_hours);
    set("#c-mins-label", s.counter_label_mins);
    set("#c-secs-label", s.counter_label_secs);
  }

  function startCounter() {
    const first = new Date(state.content.firstTalk);
    function tick() {
      const ms = Date.now() - first.getTime();
      const days = Math.floor(ms / 86400000);
      const hours = Math.floor((ms % 86400000) / 3600000);
      const mins = Math.floor((ms % 3600000) / 60000);
      const secs = Math.floor((ms % 60000) / 1000);
      $("#c-days").textContent = String(days).padStart(2, "0");
      $("#c-hours").textContent = String(hours).padStart(2, "0");
      $("#c-mins").textContent = String(mins).padStart(2, "0");
      $("#c-secs").textContent = String(secs).padStart(2, "0");
    }
    tick();
    setInterval(tick, 1000);

    const d = first;
    const dateFmt = d.toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
    $("#story-date").textContent = dateFmt;
  }

  /* ---------- 4. QUESTIONS (catégories dynamiques) ---------- */
  const CAT_PALETTE = ["#a78bfa", "#c9556e", "#f5c67a", "#34d399", "#60a5fa", "#fb923c", "#f472b6", "#facc15"];
  let CAT_LABELS = {};
  let CAT_COLORS = {};

  function buildCategoryMaps() {
    CAT_LABELS = {};
    CAT_COLORS = {};
    (state.content.categories || []).forEach((c, i) => {
      CAT_LABELS[c.category_key] = c.label;
      CAT_COLORS[c.category_key] = CAT_PALETTE[i % CAT_PALETTE.length];
    });
  }

  function renderQDots() {
    const total = state.content.siteQuestions.length;
    const wrap = $("#q-dots");
    wrap.innerHTML = "";
    for (let i = 0; i < total; i++) {
      const dot = document.createElement("span");
      dot.className = i <= state.qIdx ? "on" : "off";
      wrap.appendChild(dot);
    }
  }

  function renderQuestion() {
    const q = state.content.siteQuestions[state.qIdx];
    $("#q-category").textContent = CAT_LABELS[q.category] || "";
    $("#q-category").style.color = CAT_COLORS[q.category] || "var(--foreground)";
    $("#q-idx").textContent = state.qIdx + 1;
    $("#q-total").textContent = state.content.siteQuestions.length;
    $("#q-text").textContent = q.question;
    $("#q-answer").value = state.qAnswers[state.qIdx] || "";
    $("#q-prev").disabled = state.qIdx === 0;
    $("#q-next").innerHTML = state.qIdx < state.content.siteQuestions.length - 1
      ? "Question suivante <span>›</span>"
      : "Terminer <span>›</span>";
    renderQDots();
  }

  async function advanceQuestion() {
    state.qAnswers[state.qIdx] = $("#q-answer").value;

    if (state.qIdx < state.content.siteQuestions.length - 1) {
      state.qIdx++;
      renderQuestion();
    } else {
      const payload = state.content.siteQuestions.map((q, i) => ({
        question_id: q.id,
        question: q.question,
        answer: state.qAnswers[i] || "",
      }));
      try {
        await api("save_answers.php", { method: "POST", body: { answers: payload } });
      } catch (e) { /* on continue même en cas d'erreur réseau, l'expérience ne doit pas bloquer */ }

      $("#questions-flow").classList.add("hidden");
      $("#questions-done").classList.remove("hidden");
      setTimeout(() => goNext("questions"), 1800);
    }
  }

  function prevQuestion() {
    if (state.qIdx === 0) return;
    state.qAnswers[state.qIdx] = $("#q-answer").value;
    state.qIdx--;
    renderQuestion();
  }

  /* ---------- 5. GALLERY ---------- */
  function renderGallery() {
    const grid = $("#gallery-grid");
    grid.innerHTML = "";
    state.content.photos.forEach((p) => {
      const btn = document.createElement("button");
      btn.className = "gallery-item";
      btn.innerHTML = `
        <img src="${p.url}" alt="${escapeHtml(p.alt)}" loading="lazy">
        <div class="gallery-caption"><span>${escapeHtml(p.caption)}</span></div>`;
      btn.addEventListener("click", () => openLightbox(p));
      grid.appendChild(btn);
    });
  }

  function openLightbox(p) {
    $("#lightbox-img").src = p.url;
    $("#lightbox-img").alt = p.alt;
    $("#lightbox-caption").textContent = p.caption;
    $("#lightbox").classList.add("active");
  }

  /* ---------- 6. LETTERS ---------- */
  function renderLetters() {
    const list = $("#letters-list");
    list.innerHTML = "";
    state.content.letters.forEach((l) => {
      const btn = document.createElement("button");
      btn.className = "letter-btn mb-1";
      btn.innerHTML = `
        <div class="letter-btn-inner">
          <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
              <span style="color:var(--primary);">✉</span>
              <span class="read-badge muted hidden" style="font-size:0.75rem;">Lue ✓</span>
            </div>
            <h3 class="font-display" style="font-size:1.25rem; margin:0;">${escapeHtml(l.title)}</h3>
            <p class="font-body muted" style="font-size:0.9rem; margin-top:0.15rem;">${escapeHtml(l.letter_date)}</p>
          </div>
          <span style="opacity:0.4;">›</span>
        </div>`;
      btn.addEventListener("click", () => openLetter(l, btn));
      list.appendChild(btn);
    });
    updateLettersProgress();
  }

  function openLetter(l, btnEl) {
    $("#letter-modal-date").textContent = l.letter_date;
    $("#letter-modal-title").textContent = l.title;
    $("#letter-modal-content").textContent = l.content;
    $("#letter-modal").classList.add("active");
    state.lettersRead.add(l.id);
    btnEl.querySelector(".read-badge").classList.remove("hidden");
    updateLettersProgress();
  }

  function updateLettersProgress() {
    const allRead = state.lettersRead.size >= state.content.letters.length;
    $("#letters-continue").classList.toggle("hidden", !allRead);
    $("#letters-hint").classList.toggle("hidden", allRead);
  }

  /* ---------- 7. BIRTHDAY ---------- */
  function renderConfetti() {
    const layer = $("#confetti-layer");
    layer.innerHTML = "";
    const colors = ["#c9556e", "#e8a4b0", "#8b1a3a", "#f5c6d0", "#d47090", "#a04060"];
    for (let i = 0; i < 45; i++) {
      const piece = document.createElement("div");
      piece.className = "confetti-piece animate-confetti";
      const size = 5 + (i % 5) * 2;
      piece.style.left = `${(i * 2.3) % 100}%`;
      piece.style.animationDelay = `${i * 0.07}s`;
      piece.style.animationDuration = `${2.5 + (i % 4) * 0.5}s`;
      piece.style.width = `${size}px`;
      piece.style.height = `${size}px`;
      piece.style.backgroundColor = colors[i % colors.length];
      piece.style.borderRadius = i % 3 !== 0 ? "50%" : "2px";
      piece.style.transform = `rotate(${i * 17}deg)`;
      layer.appendChild(piece);
    }
  }

  /* ---------- 8. PROPOSAL / RENDEZ-VOUS (déjà défini, modifiable via /admin) ---------- */
  const PLACE_ICONS = { cafe: "☕", promenade: "🌇", diner: "🍽", autre: "✦" };
  const PLACE_LABELS = { cafe: "Un café", promenade: "Une promenade", diner: "Un dîner", autre: "Une idée à moi" };

  function fmtApptDate(dateStr) {
    const d = new Date(`${dateStr}T00:00:00`);
    return d.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long" });
  }

  function renderAppointment() {
    const a = state.content.appointment;
    if (!a) return;

    $("#rdv-view-date").textContent = fmtApptDate(a.appointment_date);
    $("#rdv-view-time").textContent = a.appointment_time ? a.appointment_time.slice(0, 5) : "—";

    const placeLabel = PLACE_LABELS[a.place_type] || a.place_type;
    $("#rdv-view-place-icon").textContent = PLACE_ICONS[a.place_type] || "✦";
    $("#rdv-view-place").textContent = a.place_detail ? `${placeLabel} — ${a.place_detail}` : placeLabel;

    const noteEl = $("#rdv-view-note");
    if (a.note) {
      noteEl.textContent = `« ${a.note} »`;
      noteEl.classList.remove("hidden");
    } else {
      noteEl.textContent = "";
      noteEl.classList.add("hidden");
    }

    const recap = `${fmtApptDate(a.appointment_date)} à ${a.appointment_time ? a.appointment_time.slice(0, 5) : ""}`;
    $("#rdv-confirmed-recap").textContent = recap;

    // Si Tamby a déjà répondu (rechargement de page), montrer directement la confirmation
    if (a.response_status === "accepted") {
      $("#proposal-ask").classList.add("hidden");
      $("#proposal-accepted").classList.remove("hidden");
    }
  }

  function initProposal() {
    $("#proposal-yes").addEventListener("click", acceptAppointment);

    const maybeBtn = $("#proposal-maybe-later");
    if (maybeBtn) {
      maybeBtn.addEventListener("click", () => {
        const txt = $("#proposal-maybe-text");
        const shown = !txt.classList.contains("hidden");
        txt.classList.toggle("hidden", shown);
      });
    }
  }

  async function acceptAppointment() {
    const btn = $("#proposal-yes");
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.textContent = "Un instant…";

    try {
      const res = await api("respond_appointment.php", { method: "POST" });
      if (res.appointment) state.content.appointment = res.appointment;
      renderAppointment();
      $("#proposal-ask").classList.add("hidden");
      $("#proposal-accepted").classList.remove("hidden");
    } catch (e) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      alert(e.data?.error || "Une erreur est survenue. Réessaie.");
    }
  }

  /* ---------- utils ---------- */
  function escapeHtml(s) {
    const div = document.createElement("div");
    div.textContent = s ?? "";
    return div.innerHTML;
  }

  /* ---------- boot ---------- */
  async function boot() {
    initParticles();

    try {
      state.content = await api("get_content.php");
    } catch (e) {
      document.body.innerHTML = `<div style="padding:3rem;text-align:center;font-family:sans-serif;color:#f5e6ec;">
        Impossible de charger l'expérience. Vérifie la connexion à la base de données (api/config.php).</div>`;
      return;
    }

    state.qAnswers = new Array(state.content.siteQuestions.length).fill("");
    buildCategoryMaps();

    renderAuthQuestion();
    renderStorySettings();
    startCounter();
    renderQuestion();
    renderGallery();
    renderLetters();
    renderConfetti();
    renderAppointment();
    initProposal();

    // Si déjà authentifié (rechargement de page), sauter l'écran de connexion
    if (state.content.authenticated) {
      showScreen("welcome");
    }

    /* Navigation events */
    $$(".next-btn").forEach((btn) => {
      btn.addEventListener("click", () => showScreen(btn.dataset.next));
    });

    $("#auth-submit").addEventListener("click", submitAuth);
    $("#auth-input").addEventListener("keydown", (e) => { if (e.key === "Enter") submitAuth(); });
    $("#auth-hint-toggle").addEventListener("click", () => {
      state.hintShown = !state.hintShown;
      $("#auth-hint-text").classList.toggle("hidden", !state.hintShown);
      $("#auth-hint-toggle").textContent = state.hintShown ? "Masquer l'indice" : "Besoin d'un indice ?";
    });

    $("#q-next").addEventListener("click", advanceQuestion);
    $("#q-prev").addEventListener("click", prevQuestion);

    $("#lightbox-close").addEventListener("click", () => $("#lightbox").classList.remove("active"));
    $("#lightbox").addEventListener("click", (e) => { if (e.target.id === "lightbox") $("#lightbox").classList.remove("active"); });

    $("#letter-modal-close").addEventListener("click", () => $("#letter-modal").classList.remove("active"));
    $("#letter-modal").addEventListener("click", (e) => { if (e.target.id === "letter-modal") $("#letter-modal").classList.remove("active"); });

    /* Admin secret: taper "admin" au clavier, ou bouton invisible */
    let buf = "";
    window.addEventListener("keydown", (e) => {
      buf = (buf + e.key).slice(-5);
      if (buf === "admin") { window.location.href = "admin/"; }
    });
    $("#admin-trigger").addEventListener("click", () => { window.location.href = "admin/"; });
    $("#logout-trigger").addEventListener("click", logoutVisitor);
  }

  document.addEventListener("DOMContentLoaded", boot);

  /* ---------- PWA service worker ---------- */
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
      navigator.serviceWorker.register("sw.js").catch(() => {});
    });
  }
})();
