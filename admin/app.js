(() => {
  "use strict";
  const $ = (s) => document.querySelector(s);
  const $$ = (s) => Array.from(document.querySelectorAll(s));

  const PLACE_LABELS = { cafe: "☕ Un café", promenade: "🌇 Une promenade", diner: "🍽 Un dîner", autre: "✦ Autre idée" };

  function escapeHtml(s) {
    const div = document.createElement("div");
    div.textContent = s ?? "";
    return div.innerHTML;
  }

  function fmtDate(iso) {
    if (!iso) return "";
    const d = new Date(iso.replace(" ", "T"));
    return d.toLocaleString("fr-FR", { dateStyle: "medium", timeStyle: "short" });
  }

  async function api(path, opts = {}) {
    const res = await fetch(`../api/${path}`, {
      method: opts.method || "GET",
      headers: { "Content-Type": "application/json" },
      body: opts.body ? JSON.stringify(opts.body) : undefined,
      credentials: "same-origin",
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Erreur");
    return data;
  }

  async function uploadFile(path, file, fieldName) {
    const form = new FormData();
    form.append(fieldName, file);
    const res = await fetch(`../api/${path}`, {
      method: "POST",
      body: form,
      credentials: "same-origin",
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Erreur lors du téléversement.");
    return data;
  }

  function saveContent(type, action, id, data) {
    return api("admin_content.php", { method: "POST", body: { type, action, id, data } });
  }

  let cache = null;

  /* ---------- Rendez-vous (singleton, modifiable) ---------- */
  function renderAppointment() {
    const wrap = $("#tab-appointments");
    const a = cache.appointment || {};

    const statusBadge = a.response_status === "accepted"
      ? `<span class="status-pill status-confirmed">✓ Accepté${a.responded_at ? " le " + fmtDate(a.responded_at) : ""}</span>`
      : `<span class="status-pill status-pending">En attente de réponse</span>`;

    wrap.innerHTML = `
      <div class="card admin-list-item mb-3">
        <p class="font-body muted" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; margin:0 0 0.5rem;">Statut actuel</p>
        ${statusBadge}
      </div>

      <div class="card admin-list-item">
        <h3 class="font-display" style="font-size:1.2rem; margin:0 0 1.1rem;">Modifier le rendez-vous proposé</h3>

        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Date</label>
            <input type="date" id="adm-rdv-date" value="${a.appointment_date || ""}">
          </div>
          <div class="field-row">
            <label class="field-label">Heure</label>
            <input type="time" id="adm-rdv-time" value="${a.appointment_time ? a.appointment_time.slice(0,5) : "18:00"}">
          </div>
        </div>

        <div class="field-row">
          <label class="field-label">Lieu</label>
          <div class="place-options" id="adm-rdv-places">
            ${Object.entries(PLACE_LABELS).map(([key, label]) => `
              <button type="button" class="place-option${a.place_type === key ? " selected" : ""}" data-place="${key}">${label}</button>
            `).join("")}
          </div>
        </div>

        <div class="field-row${a.place_type === "autre" ? "" : " hidden"}" id="adm-rdv-place-detail-wrap">
          <label class="field-label">Précision du lieu</label>
          <input type="text" id="adm-rdv-place-detail" value="${escapeHtml(a.place_detail || "")}" placeholder="Ex : le petit parc près de chez toi…">
        </div>

        <div class="field-row">
          <label class="field-label">Petit mot pour elle (facultatif)</label>
          <textarea id="adm-rdv-note" rows="3" placeholder="Un mot doux à afficher avec la proposition…">${escapeHtml(a.note || "")}</textarea>
        </div>

        <label style="display:flex; align-items:center; gap:0.5rem; font-family:'Crimson Pro',serif; font-size:0.9rem; color:var(--muted-foreground); margin-bottom:1.1rem; cursor:pointer;">
          <input type="checkbox" id="adm-rdv-reset" style="width:auto;">
          Remettre le statut à « en attente » (si tu changes la date après qu'elle a déjà accepté)
        </label>

        <p id="adm-rdv-error" class="err-text hidden mb-2"></p>
        <p id="adm-rdv-success" class="hidden mb-2" style="color:#34d399; font-family:'Crimson Pro',serif; font-size:0.9rem;">Rendez-vous mis à jour ✓</p>

        <button id="adm-rdv-save" class="btn-primary w-full">Enregistrer les modifications</button>
      </div>
    `;

    let selectedPlace = a.place_type || "cafe";

    $$("#adm-rdv-places .place-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        $$("#adm-rdv-places .place-option").forEach((b) => b.classList.remove("selected"));
        btn.classList.add("selected");
        selectedPlace = btn.dataset.place;
        $("#adm-rdv-place-detail-wrap").classList.toggle("hidden", selectedPlace !== "autre");
      });
    });

    $("#adm-rdv-save").addEventListener("click", async () => {
      const date = $("#adm-rdv-date").value;
      const time = $("#adm-rdv-time").value;
      const placeDetail = $("#adm-rdv-place-detail").value.trim();
      const note = $("#adm-rdv-note").value.trim();
      const resetResponse = $("#adm-rdv-reset").checked;
      const errEl = $("#adm-rdv-error");
      const okEl = $("#adm-rdv-success");
      errEl.classList.add("hidden");
      okEl.classList.add("hidden");

      if (!date || !time) {
        errEl.textContent = "Merci de renseigner une date et une heure.";
        errEl.classList.remove("hidden");
        return;
      }
      if (selectedPlace === "autre" && !placeDetail) {
        errEl.textContent = "Merci de préciser le lieu.";
        errEl.classList.remove("hidden");
        return;
      }

      const btn = $("#adm-rdv-save");
      btn.disabled = true;
      btn.textContent = "Enregistrement…";

      try {
        const res = await api("admin_set_appointment.php", {
          method: "POST",
          body: {
            appointment_date: date,
            appointment_time: time,
            place_type: selectedPlace,
            place_detail: placeDetail,
            note,
            reset_response: resetResponse,
          },
        });
        cache.appointment = res.appointment;
        renderAppointment();
        $("#adm-rdv-success")?.classList.remove("hidden");
      } catch (e) {
        errEl.textContent = e.message || "Erreur lors de l'enregistrement.";
        errEl.classList.remove("hidden");
      } finally {
        btn.disabled = false;
        btn.textContent = "Enregistrer les modifications";
      }
    });
  }

  /* ---------- 💬 Réponses (suppression possible, avec confirmation) ---------- */
  function renderAnswers() {
    const wrap = $("#tab-answers");
    const list = cache.answers;

    if (list.length === 0) {
      wrap.innerHTML = `<div class="card admin-list-item"><p class="font-body muted">Aucune réponse enregistrée pour l'instant.</p></div>`;
      return;
    }

    wrap.innerHTML = list.map((a) => `
      <div class="card admin-list-item" data-id="${a.id}">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.75rem;">
          <div style="flex:1; min-width:0;">
            <p class="font-body muted" style="font-size:0.9rem; margin:0 0 0.35rem;">${escapeHtml(a.question_text)}</p>
            <p class="font-body" style="margin:0;">${a.answer_text ? escapeHtml(a.answer_text) : '<em class="muted">— sans réponse —</em>'}</p>
            <p class="font-body muted" style="font-size:0.7rem; margin-top:0.5rem;">${fmtDate(a.created_at)}</p>
          </div>
          <button class="mini-btn delete-btn" title="Supprimer cette réponse" style="flex-shrink:0;">🗑</button>
        </div>
      </div>
    `).join("");

    list.forEach((a) => attachDelete(wrap, "answer", a.id, list, renderAnswers, "Supprimer définitivement cette réponse ? Cette action est irréversible."));
  }

  /* ============================================================
     Bloc générique : gère une liste éditable (create / update / delete)
  ============================================================ */
  function attachSave(wrap, type, id, collectFn, list, opts = {}) {
    const scope = id ? `[data-id="${id}"]` : `[data-id="new"]`;
    const errEl = wrap.querySelector(`${scope} .item-error`);
    const btn = wrap.querySelector(`${scope} .save-btn`);
    if (!btn) return;
    btn.addEventListener("click", async () => {
      errEl?.classList.add("hidden");
      const data = collectFn(wrap, scope);
      if (data.__error) {
        if (errEl) { errEl.textContent = data.__error; errEl.classList.remove("hidden"); }
        return;
      }
      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = "Enregistrement…";
      try {
        const action = id ? "update" : "create";
        const res = await saveContent(type, action, id || 0, data);
        if (id) {
          const idx = list.findIndex((it) => it.id === id);
          if (idx !== -1) list[idx] = res.item;
        } else {
          list.push(res.item);
        }
        opts.onSuccess ? opts.onSuccess() : null;
      } catch (e) {
        if (errEl) { errEl.textContent = e.message || "Erreur lors de l'enregistrement."; errEl.classList.remove("hidden"); }
      } finally {
        btn.disabled = false;
        btn.textContent = originalText;
      }
    });
  }

  function attachDelete(wrap, type, id, list, onSuccess, confirmMsg) {
    const scope = `[data-id="${id}"]`;
    const btn = wrap.querySelector(`${scope} .delete-btn`);
    if (!btn) return;
    btn.addEventListener("click", async () => {
      if (!confirm(confirmMsg || "Supprimer définitivement cet élément ? Cette action est irréversible.")) return;
      btn.disabled = true;
      try {
        await saveContent(type, "delete", id, {});
        const idx = list.findIndex((it) => it.id === id);
        if (idx !== -1) list.splice(idx, 1);
        onSuccess();
      } catch (e) {
        alert(e.message || "Erreur lors de la suppression.");
        btn.disabled = false;
      }
    });
  }

  /* ---------- 🔑 Questions de connexion (auth_questions) ---------- */
  function renderAuthQuestions() {
    const wrap = $("#tab-auth-questions");
    const list = cache.authQuestions;

    wrap.innerHTML = `
      <p class="font-body muted mb-3" style="font-size:0.85rem;">
        Ces questions sont posées à l'écran de connexion, dans l'ordre indiqué. La réponse n'est jamais visible côté site — seule toi la vois ici.
      </p>
      ${list.map((q) => `
        <div class="card admin-list-item" data-id="${q.id}">
          <div class="grid-2">
            <div class="field-row">
              <label class="field-label">Question</label>
              <input type="text" class="f-question" value="${escapeHtml(q.question)}">
            </div>
            <div class="field-row">
              <label class="field-label">Ordre</label>
              <input type="number" class="f-sort" value="${q.sort_order}">
            </div>
          </div>
          <div class="field-row">
            <label class="field-label">Réponse attendue</label>
            <input type="text" class="f-answer" value="${escapeHtml(q.answer)}">
          </div>
          <div class="field-row">
            <label class="field-label">Indice (facultatif)</label>
            <input type="text" class="f-hint" value="${escapeHtml(q.hint || "")}">
          </div>
          <p class="item-error err-text hidden mb-2"></p>
          <div style="display:flex; gap:0.5rem;">
            <button class="btn-primary save-btn" style="flex:1;">Enregistrer</button>
            <button class="mini-btn delete-btn">🗑 Supprimer</button>
          </div>
        </div>
      `).join("")}

      <div class="card admin-list-item" data-id="new" style="border-style:dashed;">
        <h3 class="font-display" style="font-size:1.05rem; margin:0 0 0.9rem;">✦ Ajouter une question</h3>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Question</label>
            <input type="text" class="f-question" placeholder="Ex : Quel est le prénom de ta meilleure amie ?">
          </div>
          <div class="field-row">
            <label class="field-label">Ordre</label>
            <input type="number" class="f-sort" value="${list.length + 1}">
          </div>
        </div>
        <div class="field-row">
          <label class="field-label">Réponse attendue</label>
          <input type="text" class="f-answer" placeholder="Réponse correcte">
        </div>
        <div class="field-row">
          <label class="field-label">Indice (facultatif)</label>
          <input type="text" class="f-hint" placeholder="Un petit indice…">
        </div>
        <p class="item-error err-text hidden mb-2"></p>
        <button class="btn-primary save-btn w-full">Ajouter la question</button>
      </div>
    `;

    const collect = (w, scope) => {
      const el = (cls) => w.querySelector(`${scope} .${cls}`);
      const question = el("f-question").value.trim();
      const answer = el("f-answer").value.trim();
      const hint = el("f-hint").value.trim();
      const sort_order = parseInt(el("f-sort").value, 10) || 0;
      if (!question) return { __error: "La question est requise." };
      if (!answer) return { __error: "La réponse est requise." };
      return { question, answer, hint, sort_order };
    };

    list.forEach((q) => attachSave(wrap, "auth_question", q.id, collect, list, { onSuccess: renderAuthQuestions }));
    attachSave(wrap, "auth_question", 0, collect, list, { onSuccess: renderAuthQuestions });
    list.forEach((q) => attachDelete(wrap, "auth_question", q.id, list, renderAuthQuestions, "Supprimer définitivement cette question de connexion ?"));
  }

  /* ---------- ⭐ Questions du site + catégories dynamiques ---------- */
  function renderQuestions() {
    const wrap = $("#tab-questions");
    const list = cache.siteQuestions;
    const cats = cache.categories;

    const catOptions = (selected) => cats.map((c) => `
      <option value="${escapeHtml(c.category_key)}"${c.category_key === selected ? " selected" : ""}>${escapeHtml(c.label)}</option>
    `).join("");

    wrap.innerHTML = `
      <div class="card admin-list-item mb-3" id="cat-manager">
        <h3 class="font-display" style="font-size:1.05rem; margin:0 0 0.9rem;">🏷 Catégories</h3>
        <div id="cat-list" style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem;">
          ${cats.map((c) => `
            <span class="mini-btn" data-cat-id="${c.id}" style="display:inline-flex; align-items:center; gap:0.4rem; cursor:default;">
              ${escapeHtml(c.label)}
              <button class="cat-delete-btn" data-cat-id="${c.id}" title="Supprimer la catégorie" style="background:none; border:none; color:var(--muted-foreground); cursor:pointer; padding:0; font-size:0.9rem; line-height:1;">✕</button>
            </span>
          `).join("") || '<p class="font-body muted" style="font-size:0.85rem;">Aucune catégorie pour l\'instant.</p>'}
        </div>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Nouvelle catégorie — libellé</label>
            <input type="text" id="cat-new-label" placeholder="Ex : ♪ Musique">
          </div>
          <div class="field-row">
            <label class="field-label">Clé technique (facultatif)</label>
            <input type="text" id="cat-new-key" placeholder="générée depuis le libellé si vide">
          </div>
        </div>
        <p id="cat-error" class="err-text hidden mb-2"></p>
        <button id="cat-add-btn" class="mini-btn">+ Ajouter la catégorie</button>
      </div>

      ${list.map((q) => `
        <div class="card admin-list-item" data-id="${q.id}">
          <div class="field-row">
            <label class="field-label">Question</label>
            <input type="text" class="f-question" value="${escapeHtml(q.question)}">
          </div>
          <div class="grid-2">
            <div class="field-row">
              <label class="field-label">Catégorie</label>
              <select class="f-category">${catOptions(q.category)}</select>
            </div>
            <div class="field-row">
              <label class="field-label">Ordre</label>
              <input type="number" class="f-sort" value="${q.sort_order}">
            </div>
          </div>
          <p class="item-error err-text hidden mb-2"></p>
          <div style="display:flex; gap:0.5rem;">
            <button class="btn-primary save-btn" style="flex:1;">Enregistrer</button>
            <button class="mini-btn delete-btn">🗑 Supprimer</button>
          </div>
        </div>
      `).join("")}

      <div class="card admin-list-item" data-id="new" style="border-style:dashed;">
        <h3 class="font-display" style="font-size:1.05rem; margin:0 0 0.9rem;">✦ Ajouter une question</h3>
        <div class="field-row">
          <label class="field-label">Question</label>
          <input type="text" class="f-question" placeholder="Ex : Quelle chanson te fait toujours sourire ?">
        </div>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Catégorie</label>
            <select class="f-category">${catOptions(cats[0]?.category_key)}</select>
          </div>
          <div class="field-row">
            <label class="field-label">Ordre</label>
            <input type="number" class="f-sort" value="${list.length + 1}">
          </div>
        </div>
        <p class="item-error err-text hidden mb-2"></p>
        <button class="btn-primary save-btn w-full">Ajouter la question</button>
      </div>
    `;

    // --- Gestion des catégories ---
    $("#cat-add-btn").addEventListener("click", async () => {
      const label = $("#cat-new-label").value.trim();
      const keyInput = $("#cat-new-key").value.trim();
      const errEl = $("#cat-error");
      errEl.classList.add("hidden");
      if (!label) {
        errEl.textContent = "Le libellé est requis.";
        errEl.classList.remove("hidden");
        return;
      }
      const category_key = keyInput || label;
      const btn = $("#cat-add-btn");
      btn.disabled = true;
      try {
        const res = await saveContent("category", "create", 0, {
          category_key,
          label,
          sort_order: cats.length + 1,
        });
        cats.push(res.item);
        renderQuestions();
      } catch (e) {
        errEl.textContent = e.message || "Erreur lors de l'ajout.";
        errEl.classList.remove("hidden");
        btn.disabled = false;
      }
    });

    $$(".cat-delete-btn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const catId = parseInt(btn.dataset.catId, 10);
        if (!confirm("Supprimer définitivement cette catégorie ?")) return;
        btn.disabled = true;
        try {
          await saveContent("category", "delete", catId, {});
          const idx = cats.findIndex((c) => c.id === catId);
          if (idx !== -1) cats.splice(idx, 1);
          renderQuestions();
        } catch (e) {
          alert(e.message || "Erreur lors de la suppression.");
          btn.disabled = false;
        }
      });
    });

    // --- Questions ---
    const collect = (w, scope) => {
      const el = (cls) => w.querySelector(`${scope} .${cls}`);
      const question = el("f-question").value.trim();
      const category = el("f-category").value;
      const sort_order = parseInt(el("f-sort").value, 10) || 0;
      if (!question) return { __error: "La question est requise." };
      if (!category) return { __error: "Merci de créer au moins une catégorie avant d'ajouter une question." };
      return { question, category, sort_order };
    };

    list.forEach((q) => attachSave(wrap, "site_question", q.id, collect, list, { onSuccess: renderQuestions }));
    attachSave(wrap, "site_question", 0, collect, list, { onSuccess: renderQuestions });
    list.forEach((q) => attachDelete(wrap, "site_question", q.id, list, renderQuestions, "Supprimer définitivement cette question ?"));
  }

  /* ---------- 🖼 Photos (upload direct ou chemin/URL modifiable) ---------- */
  function renderPhotos() {
    const wrap = $("#tab-photos");
    const list = cache.photos;

    wrap.innerHTML = `
      <div class="gallery-grid mb-3">
        ${list.map((p) => `
          <div class="card" data-id="${p.id}" style="overflow:hidden;">
            <img src="../${escapeHtml(p.url)}" alt="${escapeHtml(p.alt)}" style="width:100%; aspect-ratio:4/3; object-fit:cover; display:block;" onerror="this.src='${escapeHtml(p.url)}'; this.onerror=function(){this.style.opacity=0.15;};">
            <div style="padding:0.9rem;">
              <div class="field-row">
                <label class="field-label">Chemin / URL de l'image</label>
                <input type="text" class="f-url" value="${escapeHtml(p.url)}">
              </div>
              <div class="field-row">
                <label class="field-label">Remplacer par un fichier</label>
                <input type="file" class="f-upload" accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="upload-status err-text hidden mb-1" style="margin-top:0.35rem;"></p>
              </div>
              <div class="field-row">
                <label class="field-label">Légende</label>
                <input type="text" class="f-caption" value="${escapeHtml(p.caption || "")}">
              </div>
              <div class="grid-2">
                <div class="field-row">
                  <label class="field-label">Texte alternatif</label>
                  <input type="text" class="f-alt" value="${escapeHtml(p.alt || "")}">
                </div>
                <div class="field-row">
                  <label class="field-label">Ordre</label>
                  <input type="number" class="f-sort" value="${p.sort_order ?? 0}">
                </div>
              </div>
              <p class="item-error err-text hidden mb-2"></p>
              <div style="display:flex; gap:0.5rem;">
                <button class="btn-primary save-btn" style="flex:1;">Enregistrer</button>
                <button class="mini-btn delete-btn">🗑</button>
              </div>
            </div>
          </div>
        `).join("")}
      </div>

      <div class="card admin-list-item" data-id="new" style="border-style:dashed;">
        <h3 class="font-display" style="font-size:1.05rem; margin:0 0 0.9rem;">✦ Ajouter une photo</h3>
        <div class="field-row">
          <label class="field-label">Téléverser une image depuis ton appareil</label>
          <input type="file" class="f-upload" accept="image/jpeg,image/png,image/webp,image/gif">
          <p class="upload-status err-text hidden mb-1" style="margin-top:0.35rem;"></p>
        </div>
        <div class="field-row">
          <label class="field-label">— ou chemin / URL de l'image</label>
          <input type="text" class="f-url" placeholder="uploads/photos/… ou https://…">
        </div>
        <div class="field-row">
          <label class="field-label">Légende</label>
          <input type="text" class="f-caption" placeholder="Ex : Douceur du soir">
        </div>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Texte alternatif</label>
            <input type="text" class="f-alt" placeholder="Description de l'image">
          </div>
          <div class="field-row">
            <label class="field-label">Ordre</label>
            <input type="number" class="f-sort" value="${list.length + 1}">
          </div>
        </div>
        <p class="item-error err-text hidden mb-2"></p>
        <button class="btn-primary save-btn w-full">Ajouter la photo</button>
      </div>
    `;

    // --- Téléversement direct : remplit automatiquement le champ chemin/URL ---
    $$(".f-upload").forEach((input) => {
      input.addEventListener("change", async () => {
        const file = input.files[0];
        if (!file) return;
        const container = input.closest("[data-id]");
        const statusEl = container.querySelector(".upload-status");
        const urlInput = container.querySelector(".f-url");
        statusEl.classList.remove("hidden");
        statusEl.style.color = "var(--muted-foreground)";
        statusEl.textContent = "Téléversement en cours…";
        input.disabled = true;
        try {
          const res = await uploadFile("admin_upload_photo.php", file, "photo");
          urlInput.value = res.url;
          statusEl.style.color = "#34d399";
          statusEl.textContent = "Image téléversée ✓ — clique sur Enregistrer pour valider.";
        } catch (e) {
          statusEl.style.color = "";
          statusEl.classList.add("err-text");
          statusEl.textContent = e.message || "Erreur lors du téléversement.";
        } finally {
          input.disabled = false;
        }
      });
    });

    const collect = (w, scope) => {
      const el = (cls) => w.querySelector(`${scope} .${cls}`);
      const url = el("f-url").value.trim();
      const caption = el("f-caption").value.trim();
      const alt = el("f-alt").value.trim();
      const sort_order = parseInt(el("f-sort").value, 10) || 0;
      if (!url) return { __error: "Téléverse une image ou indique un chemin/URL." };
      return { url, caption, alt, sort_order };
    };

    list.forEach((p) => attachSave(wrap, "photo", p.id, collect, list, { onSuccess: renderPhotos }));
    attachSave(wrap, "photo", 0, collect, list, { onSuccess: renderPhotos });
    list.forEach((p) => attachDelete(wrap, "photo", p.id, list, renderPhotos, "Supprimer définitivement cette photo ?"));
  }

  /* ---------- ✉ Lettres ---------- */
  function renderLetters() {
    const wrap = $("#tab-letters");
    const list = cache.letters;

    wrap.innerHTML = `
      ${list.map((l) => `
        <div class="card admin-list-item" data-id="${l.id}">
          <div class="grid-2">
            <div class="field-row">
              <label class="field-label">Titre</label>
              <input type="text" class="f-title" value="${escapeHtml(l.title)}">
            </div>
            <div class="field-row">
              <label class="field-label">Date / mention</label>
              <input type="text" class="f-date" value="${escapeHtml(l.letter_date)}">
            </div>
          </div>
          <div class="field-row">
            <label class="field-label">Contenu</label>
            <textarea class="f-content" rows="6">${escapeHtml(l.content)}</textarea>
          </div>
          <div class="field-row">
            <label class="field-label">Ordre</label>
            <input type="number" class="f-sort" value="${l.sort_order ?? 0}">
          </div>
          <p class="item-error err-text hidden mb-2"></p>
          <div style="display:flex; gap:0.5rem;">
            <button class="btn-primary save-btn" style="flex:1;">Enregistrer</button>
            <button class="mini-btn delete-btn">🗑 Supprimer</button>
          </div>
        </div>
      `).join("")}

      <div class="card admin-list-item" data-id="new" style="border-style:dashed;">
        <h3 class="font-display" style="font-size:1.05rem; margin:0 0 0.9rem;">✦ Ajouter une lettre</h3>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Titre</label>
            <input type="text" class="f-title" placeholder="Ex : Pour ton anniversaire">
          </div>
          <div class="field-row">
            <label class="field-label">Date / mention</label>
            <input type="text" class="f-date" placeholder="Ex : Aujourd'hui">
          </div>
        </div>
        <div class="field-row">
          <label class="field-label">Contenu</label>
          <textarea class="f-content" rows="6" placeholder="Chère Tamby,…"></textarea>
        </div>
        <div class="field-row">
          <label class="field-label">Ordre</label>
          <input type="number" class="f-sort" value="${list.length + 1}">
        </div>
        <p class="item-error err-text hidden mb-2"></p>
        <button class="btn-primary save-btn w-full">Ajouter la lettre</button>
      </div>
    `;

    const collect = (w, scope) => {
      const el = (cls) => w.querySelector(`${scope} .${cls}`);
      const title = el("f-title").value.trim();
      const letter_date = el("f-date").value.trim();
      const content = el("f-content").value;
      const sort_order = parseInt(el("f-sort").value, 10) || 0;
      if (!title) return { __error: "Le titre est requis." };
      if (!letter_date) return { __error: "La date / mention est requise." };
      if (!content.trim()) return { __error: "Le contenu est requis." };
      return { title, content, letter_date, sort_order };
    };

    list.forEach((l) => attachSave(wrap, "letter", l.id, collect, list, { onSuccess: renderLetters }));
    attachSave(wrap, "letter", 0, collect, list, { onSuccess: renderLetters });
    list.forEach((l) => attachDelete(wrap, "letter", l.id, list, renderLetters, "Supprimer définitivement cette lettre ?"));
  }

  /* ---------- ⏱ Écran compteur (histoire / countdown, configurable) ---------- */
  function toDatetimeLocal(sqlDatetime) {
    if (!sqlDatetime) return "";
    return sqlDatetime.replace(" ", "T").slice(0, 16);
  }

  function renderStory() {
    const wrap = $("#tab-story");
    const s = cache.settings || {};

    wrap.innerHTML = `
      <div class="card admin-list-item">
        <h3 class="font-display" style="font-size:1.2rem; margin:0 0 0.4rem;">Écran « Notre histoire »</h3>
        <p class="font-body muted mb-3" style="font-size:0.85rem;">
          Configure les textes et le compte à rebours affichés à Tamby avant les questions.
        </p>

        <div class="field-row">
          <label class="field-label">Date et heure de votre première conversation</label>
          <input type="datetime-local" id="st-first-dt" value="${toDatetimeLocal(s.first_talk_datetime)}">
        </div>

        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Petit texte au-dessus du titre</label>
            <input type="text" id="st-eyebrow" value="${escapeHtml(s.story_eyebrow || "")}">
          </div>
          <div class="field-row">
            <label class="field-label">Titre de l'écran</label>
            <input type="text" id="st-title" value="${escapeHtml(s.story_title || "")}">
          </div>
        </div>

        <div class="field-row">
          <label class="field-label">Libellé de la date (ex : « Notre première conversation »)</label>
          <input type="text" id="st-first-label" value="${escapeHtml(s.story_first_label || "")}">
        </div>
        <div class="field-row">
          <label class="field-label">Note sous la date</label>
          <input type="text" id="st-first-note" value="${escapeHtml(s.story_first_note || "")}">
        </div>
        <div class="field-row">
          <label class="field-label">Citation affichée dans l'encart</label>
          <textarea id="st-quote" rows="3">${escapeHtml(s.story_quote || "")}</textarea>
        </div>

        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Libellé « Aujourd'hui »</label>
            <input type="text" id="st-today-label" value="${escapeHtml(s.story_today_label || "")}">
          </div>
          <div class="field-row">
            <label class="field-label">Texte affiché à droite</label>
            <input type="text" id="st-today-value" value="${escapeHtml(s.story_today_value || "")}">
          </div>
        </div>

        <h3 class="font-display" style="font-size:1.05rem; margin:1.5rem 0 0.9rem;">Libellés du compte à rebours</h3>
        <div class="grid-2">
          <div class="field-row">
            <label class="field-label">Jours</label>
            <input type="text" id="st-lbl-days" value="${escapeHtml(s.counter_label_days || "")}">
          </div>
          <div class="field-row">
            <label class="field-label">Heures</label>
            <input type="text" id="st-lbl-hours" value="${escapeHtml(s.counter_label_hours || "")}">
          </div>
          <div class="field-row">
            <label class="field-label">Minutes</label>
            <input type="text" id="st-lbl-mins" value="${escapeHtml(s.counter_label_mins || "")}">
          </div>
          <div class="field-row">
            <label class="field-label">Secondes</label>
            <input type="text" id="st-lbl-secs" value="${escapeHtml(s.counter_label_secs || "")}">
          </div>
        </div>

        <p id="st-error" class="err-text hidden mb-2"></p>
        <p id="st-success" class="hidden mb-2" style="color:#34d399; font-family:'Crimson Pro',serif; font-size:0.9rem;">Écran mis à jour ✓</p>

        <button id="st-save" class="btn-primary w-full">Enregistrer</button>
      </div>
    `;

    $("#st-save").addEventListener("click", async () => {
      const errEl = $("#st-error");
      const okEl = $("#st-success");
      errEl.classList.add("hidden");
      okEl.classList.add("hidden");

      const firstDt = $("#st-first-dt").value;
      if (!firstDt) {
        errEl.textContent = "Merci de renseigner la date et l'heure de votre première conversation.";
        errEl.classList.remove("hidden");
        return;
      }

      const settings = {
        first_talk_datetime: firstDt,
        story_eyebrow: $("#st-eyebrow").value.trim(),
        story_title: $("#st-title").value.trim(),
        story_first_label: $("#st-first-label").value.trim(),
        story_first_note: $("#st-first-note").value.trim(),
        story_quote: $("#st-quote").value.trim(),
        story_today_label: $("#st-today-label").value.trim(),
        story_today_value: $("#st-today-value").value.trim(),
        counter_label_days: $("#st-lbl-days").value.trim(),
        counter_label_hours: $("#st-lbl-hours").value.trim(),
        counter_label_mins: $("#st-lbl-mins").value.trim(),
        counter_label_secs: $("#st-lbl-secs").value.trim(),
      };

      const btn = $("#st-save");
      btn.disabled = true;
      btn.textContent = "Enregistrement…";

      try {
        const res = await api("admin_save_settings.php", { method: "POST", body: { settings } });
        cache.settings = res.settings;
        okEl.classList.remove("hidden");
      } catch (e) {
        errEl.textContent = e.message || "Erreur lors de l'enregistrement.";
        errEl.classList.remove("hidden");
      } finally {
        btn.disabled = false;
        btn.textContent = "Enregistrer";
      }
    });
  }

  /* ---------- 🔒 Compte (changer le mot de passe) ---------- */
  function renderAccount() {
    const wrap = $("#tab-account");
    wrap.innerHTML = `
      <div class="card admin-list-item">
        <h3 class="font-display" style="font-size:1.2rem; margin:0 0 0.25rem;">Connecté en tant que</h3>
        <p class="font-body muted" style="margin:0 0 1.3rem;">${escapeHtml(cache.username || "")}</p>

        <h3 class="font-display" style="font-size:1.1rem; margin:0 0 1.1rem;">Changer le mot de passe</h3>

        <div class="field-row">
          <label class="field-label">Mot de passe actuel</label>
          <input type="password" id="acc-current" autocomplete="current-password">
        </div>
        <div class="field-row">
          <label class="field-label">Nouveau mot de passe (8 caractères min.)</label>
          <input type="password" id="acc-new" autocomplete="new-password">
        </div>
        <div class="field-row">
          <label class="field-label">Confirmer le nouveau mot de passe</label>
          <input type="password" id="acc-confirm" autocomplete="new-password">
        </div>

        <p id="acc-error" class="err-text hidden mb-2"></p>
        <p id="acc-success" class="hidden mb-2" style="color:#34d399; font-family:'Crimson Pro',serif; font-size:0.9rem;">Mot de passe mis à jour ✓</p>

        <button id="acc-save" class="btn-primary w-full">Enregistrer le nouveau mot de passe</button>
      </div>
    `;

    $("#acc-save").addEventListener("click", async () => {
      const current = $("#acc-current").value;
      const next = $("#acc-new").value;
      const confirmVal = $("#acc-confirm").value;
      const errEl = $("#acc-error");
      const okEl = $("#acc-success");
      errEl.classList.add("hidden");
      okEl.classList.add("hidden");

      if (!current || !next || !confirmVal) {
        errEl.textContent = "Merci de remplir tous les champs.";
        errEl.classList.remove("hidden");
        return;
      }
      if (next.length < 8) {
        errEl.textContent = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        errEl.classList.remove("hidden");
        return;
      }
      if (next !== confirmVal) {
        errEl.textContent = "La confirmation ne correspond pas au nouveau mot de passe.";
        errEl.classList.remove("hidden");
        return;
      }

      const btn = $("#acc-save");
      btn.disabled = true;
      btn.textContent = "Enregistrement…";

      try {
        await api("admin_change_password.php", {
          method: "POST",
          body: { current_password: current, new_password: next },
        });
        $("#acc-current").value = "";
        $("#acc-new").value = "";
        $("#acc-confirm").value = "";
        okEl.classList.remove("hidden");
      } catch (e) {
        errEl.textContent = e.message || "Erreur lors de la mise à jour.";
        errEl.classList.remove("hidden");
      } finally {
        btn.disabled = false;
        btn.textContent = "Enregistrer le nouveau mot de passe";
      }
    });
  }

  async function loadData() {
    cache = await api("admin_data.php");
    renderAppointment();
    renderAnswers();
    renderAuthQuestions();
    renderQuestions();
    renderPhotos();
    renderLetters();
    renderStory();
    renderAccount();
  }

  function initTabs() {
    $$(".admin-tab").forEach((tab) => {
      tab.addEventListener("click", () => {
        $$(".admin-tab").forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        $$(".tab-panel").forEach((p) => p.classList.add("hidden"));
        $(`#tab-${tab.dataset.tab}`).classList.remove("hidden");
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTabs();
    loadData().catch((e) => {
      document.body.innerHTML = `<p style="color:#f5e6ec; padding:2rem; font-family:sans-serif;">Erreur : ${e.message}</p>`;
    });

    const logout = $("#logout-btn");
    if (logout) {
      logout.addEventListener("click", async () => {
        await api("admin_logout.php", { method: "POST" });
        window.location.reload();
      });
    }
  });
})();
