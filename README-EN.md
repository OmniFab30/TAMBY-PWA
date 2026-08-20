# 💌 Tamby — A Birthday Surprise, Built by Vibe Coding

A private, installable Progressive Web App (PWA) built as a personal birthday surprise —
and as a hands-on exercise in **vibe coding**: describing what you want in plain language
and iterating with an AI pair-programmer until it feels right, rather than writing every
line by hand.

This entire project — backend, frontend, database schema, admin dashboard, and PWA
plumbing — was built through conversation with **[Claude](https://claude.com)**
(Anthropic's AI assistant). No boilerplate was scaffolded by hand: every screen, endpoint,
and table came out of describing the desired experience and refining it turn by turn.

> If you're looking for a real-world example of what "vibe coding" can produce when paired
> with careful review and testing at each step, this repo is exactly that.

## ✨ What it does

The visitor unlocks a private space by answering a handful of personal security questions,
then moves through a sequence of screens:

- 🔐 **A gated entrance** — custom security questions instead of a plain password
- 📖 **"Our story"** — a live countdown since a chosen first-conversation date/time, fully
  editable from the admin dashboard
- ❓ **Personal questions** — a curated set of heartfelt questions, grouped into
  admin-editable categories
- 🖼 **A photo gallery** — images either uploaded directly from the admin panel or linked
  from an external URL
- ✉️ **Letters** — a small collection of personal notes
- 🎂 **A birthday moment**
- 💌 **A date proposal** — with accept/decline handling and a live status the admin can track

Everything shown to the visitor — questions, photos, letters, category labels, the
countdown text, even the security questions — is editable from a password-protected
**admin dashboard**, with no code changes required.

## 🛠 Tech stack

- **Backend:** PHP 8 + PDO, MySQL
- **Frontend:** vanilla HTML / CSS / JavaScript — no framework, no build step
- **PWA:** Web App Manifest + Service Worker for offline app-shell caching and
  "Add to Home Screen" installability on both Android and iOS
- **Admin dashboard:** a small SPA-style panel (`/admin`) talking to a JSON API, covering
  full CRUD for every piece of visitor-facing content, plus password management

## 🗂 Project structure

```
/
├── index.php              → the visitor-facing experience (all screens)
├── manifest.json          → PWA manifest
├── sw.js                  → service worker (offline app-shell + installability)
├── admin/                 → password-protected admin dashboard (SPA-style)
├── api/                   → JSON endpoints (auth, content, admin CRUD, uploads…)
├── css/ · js/ · icons/    → static assets
├── uploads/photos/        → images uploaded from the admin panel
└── sql/
    ├── schema.sql         → full schema + seed data (fresh install)
    └── migration_v2.sql   → incremental migration for pre-existing installs
```

## 🚀 Getting started

1. Create a MySQL database and import `sql/schema.sql`.
2. Copy your database credentials into `api/config.php`.
3. Visit `/api/setup_admin.php` once to create your admin account, then **delete that
   file** — it should never remain reachable.
4. Make sure `uploads/photos/` is writable by the web server.
5. Serve the project over **HTTPS** (required for the service worker and PWA
   installability) and open `index.php`.

📖 A full, detailed deployment walkthrough (including a free-hosting guide) is available
in [`README.fr.md`](./README.fr.md) — written in French, alongside the rest of the original
build conversation.

## 🤖 Built with vibe coding

Every feature in this repo — from the authentication flow down to CSRF-safe file uploads
and dynamic question categories — started as a plain-language request to Claude and was
refined through real back-and-forth: describing a bug, pasting a screenshot, asking for a
design tweak, testing on a real device, and iterating again. It's a small case study in how
far natural-language-driven development can go when paired with actual testing at every
step, not just accepting whatever the AI outputs first.

## 🔒 A note on privacy

This project contains personal content intended for one specific person. If you fork or
adapt it, remember to swap out the questions, photos, and letters for your own — and keep
your `/admin` credentials private.

---

*Made with care, and a lot of back-and-forth with an AI.* ✦
