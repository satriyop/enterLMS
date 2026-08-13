/* ============================================================
   Design B — app shell renderer
   Persistent labelled sidebar + breadcrumb top bar.
   Loaded synchronously in <head> so the theme applies pre-paint.
   ============================================================ */

(function () {
  try {
    var t = localStorage.getItem('enterlms-mockup-theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
  } catch (e) { /* noop */ }
})();

/* --- icons (inline so the mockup has zero external requests) --- */
var I = {
  grid:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
  search:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  book:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19a2 2 0 0 1 2-2h13"/></svg>',
  map:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4 3 6.5v13L9 17l6 2.5 6-2.5v-13L15 7z"/><path d="M9 4v13M15 7v12.5"/></svg>',
  award: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-2.5 5 2.5-1.5-7"/></svg>',
  bell:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 1 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
  edit:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
  help:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.2 9a2.9 2.9 0 0 1 5.6 1c0 2-2.8 2.5-2.8 4"/><path d="M12 17.5h.01"/></svg>',
  check: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11.5 11.5 14 16 9"/><circle cx="12" cy="12" r="9"/></svg>',
  users: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.4"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.4 3.4 0 0 1 0 5.6M18 14.4a6.5 6.5 0 0 1 3.5 5.6"/></svg>',
  file:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>',
  cog:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1v.3a2 2 0 1 1-4 0v-.2a1.6 1.6 0 0 0-2.8-1.1l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3.5 14H3a2 2 0 1 1 0-4h.2A1.6 1.6 0 0 0 4.3 7.2l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 3.5V3a2 2 0 1 1 4 0v.2a1.6 1.6 0 0 0 2.7 1.1l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0 1.1 2.7h.3a2 2 0 1 1 0 4h-.2a1.6 1.6 0 0 0-1.3 1z"/></svg>',
  sun:   '<svg class="sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  moon:  '<svg class="moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8"/></svg>',
  menu:  '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
  panel: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>',
};
window.ICON = I;

/* --- page registry: nav, breadcrumbs, and the hub index all read from here --- */
window.PAGES = [
  { id: 'home',              file: 'home.html',              title: 'Beranda publik',        group: 'Publik',    desc: 'Landing page & proposisi nilai' },
  { id: 'login',             file: 'login.html',             title: 'Masuk',                 group: 'Publik',    desc: 'Login + 2FA' },
  { id: 'register',          file: 'register.html',          title: 'Daftar',                group: 'Publik',    desc: 'Registrasi akun baru' },
  { id: 'verify',            file: 'verify.html',            title: 'Verifikasi sertifikat', group: 'Publik',    desc: 'Cek keaslian sertifikat' },

  { id: 'dashboard',         file: 'dashboard.html',         title: 'Dashboard',             group: 'Learner',   desc: 'Ringkasan & langkah berikutnya' },
  { id: 'browse',            file: 'browse.html',            title: 'Jelajahi kursus',       group: 'Learner',   desc: 'Katalog + filter' },
  { id: 'course-detail',     file: 'course-detail.html',     title: 'Detail kursus',         group: 'Learner',   desc: 'Silabus, ulasan, enroll', parent: 'browse' },
  { id: 'checkout',          file: 'checkout.html',          title: 'Pembayaran',            group: 'Learner',   desc: 'Checkout 3 langkah',       parent: 'course-detail' },
  { id: 'my-learning',       file: 'my-learning.html',       title: 'Pembelajaran saya',     group: 'Learner',   desc: 'Kursus aktif & selesai' },
  { id: 'lesson',            file: 'lesson.html',            title: 'Ruang belajar',         group: 'Learner',   desc: 'Pemutar + outline',        parent: 'my-learning' },
  { id: 'assessment',        file: 'assessment.html',        title: 'Kuis',                  group: 'Learner',   desc: 'Pengerjaan asesmen',       parent: 'my-learning' },
  { id: 'assessment-result', file: 'assessment-result.html', title: 'Hasil kuis',            group: 'Learner',   desc: 'Skor & pembahasan',        parent: 'my-learning' },
  { id: 'certificates',      file: 'certificates.html',      title: 'Sertifikat saya',       group: 'Learner',   desc: 'Koleksi & unduh' },
  { id: 'learning-paths',    file: 'learning-paths.html',    title: 'Jalur pembelajaran',    group: 'Learner',   desc: 'Katalog jalur' },
  { id: 'path-progress',     file: 'path-progress.html',     title: 'Progres jalur',         group: 'Learner',   desc: 'Timeline & prasyarat',     parent: 'learning-paths' },
  { id: 'notifications',     file: 'notifications.html',     title: 'Notifikasi',            group: 'Learner',   desc: 'Undangan & pengingat' },
  { id: 'settings',          file: 'settings.html',          title: 'Pengaturan',            group: 'Learner',   desc: 'Profil, sandi, 2FA, tampilan' },

  { id: 'cm-courses',        file: 'cm-courses.html',        title: 'Kelola kursus',         group: 'Pengelola', desc: 'Daftar kursus + status' },
  { id: 'cm-course-edit',    file: 'cm-course-edit.html',    title: 'Editor kursus',         group: 'Pengelola', desc: 'Wizard 5 langkah',         parent: 'cm-courses' },
  { id: 'question-bank',     file: 'question-bank.html',     title: 'Bank soal',             group: 'Pengelola', desc: 'Repositori & impor soal' },
  { id: 'grading',           file: 'grading.html',           title: 'Penilaian',             group: 'Pengelola', desc: 'Antrean koreksi esai' },

  { id: 'admin',             file: 'admin.html',             title: 'Administrasi',          group: 'Admin',     desc: 'Pengguna, kategori, arsip' },
  { id: 'compliance',        file: 'compliance.html',        title: 'Laporan kepatuhan',     group: 'Admin',     desc: 'Audit trail OJK + ekspor' },
];

/* --- sidebar model: grouped and explicitly labelled --- */
window.NAV_GROUPS = [
  {
    label: 'Belajar',
    items: [
      { id: 'dashboard',      label: 'Dashboard',          icon: 'grid' },
      { id: 'browse',         label: 'Jelajahi kursus',    icon: 'search' },
      { id: 'my-learning',    label: 'Pembelajaran saya',  icon: 'book' },
      { id: 'learning-paths', label: 'Jalur pembelajaran', icon: 'map' },
      { id: 'certificates',   label: 'Sertifikat saya',    icon: 'award' },
      { id: 'notifications',  label: 'Notifikasi',         icon: 'bell', count: 4 },
    ],
  },
  {
    label: 'Mengajar',
    items: [
      { id: 'cm-courses',    label: 'Kelola kursus', icon: 'edit' },
      { id: 'question-bank', label: 'Bank soal',     icon: 'help' },
      { id: 'grading',       label: 'Penilaian',     icon: 'check', count: 14 },
    ],
  },
  {
    label: 'Administrasi',
    items: [
      { id: 'admin',      label: 'Pengguna & sistem',  icon: 'users' },
      { id: 'compliance', label: 'Laporan kepatuhan',  icon: 'file' },
    ],
  },
];

/* ------------------------------------------------------------
   ROLE → NAV VISIBILITY
   EnterLMS has 7 roles. This map decides what each one sees.
   Design B deliberately HIDES groups a role cannot use, rather
   than showing them disabled — a first-timer reading a sidebar
   should see only doors that open. See README for the trade-off.
   ------------------------------------------------------------ */
window.ROLE_NAV = {
  learner:            ['Belajar'],
  content_manager:    ['Belajar', 'Mengajar'],
  trainer:            ['Belajar', 'Mengajar'],
  teaching_assistant: ['Belajar', 'Mengajar'],
  compliance_officer: ['Belajar', 'Administrasi'],
  auditor:            ['Administrasi'],
  lms_admin:          ['Belajar', 'Mengajar', 'Administrasi'],
};

/* per-role item suppression inside an allowed group */
window.ROLE_HIDE_ITEMS = {
  content_manager:    ['grading'],
  teaching_assistant: ['cm-courses', 'question-bank'],
  compliance_officer: ['admin'],
};

window.ROLE_LABEL = {
  learner: 'Learner',
  content_manager: 'Content Manager',
  trainer: 'Trainer',
  teaching_assistant: 'Teaching Assistant',
  compliance_officer: 'Compliance Officer',
  auditor: 'Auditor',
  lms_admin: 'LMS Admin',
};

/* --- render --- */
document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  var current = body.dataset.page || '';
  var role = body.dataset.role || 'learner';
  var sideHost = document.querySelector('[data-side]');
  var topHost = document.querySelector('[data-top]');
  if (!sideHost) return;

  var byId = {};
  window.PAGES.forEach(function (p) { byId[p.id] = p; });

  var allowedGroups = window.ROLE_NAV[role] || ['Belajar'];
  var hidden = window.ROLE_HIDE_ITEMS[role] || [];

  /* ---- sidebar ---- */
  var html = '<a class="side-brand" href="../index.html"><span class="mark">E</span><span class="label">EnterLMS</span></a>';

  window.NAV_GROUPS.forEach(function (g) {
    if (allowedGroups.indexOf(g.label) === -1) return;
    var items = g.items.filter(function (i) { return hidden.indexOf(i.id) === -1; });
    if (!items.length) return;

    html += '<div class="side-group-label">' + g.label + '</div>';
    items.forEach(function (i) {
      html += '<a class="side-link' + (i.id === current ? ' active' : '') + '" href="' + i.id + '.html">' +
        I[i.icon] +
        '<span class="label">' + i.label + '</span>' +
        (i.count ? '<span class="count label">' + i.count + '</span>' : '') +
        '</a>';
    });
  });

  html += '<div class="side-foot">' +
    '<a class="side-link' + (current === 'settings' ? ' active' : '') + '" href="settings.html">' + I.cog + '<span class="label">Pengaturan</span></a>' +
    '<a class="side-user" href="settings.html">' +
      '<span class="avatar">BS</span>' +
      '<span class="label stack" style="gap:0">' +
        '<strong style="font-size:.83rem">Budi Santoso</strong>' +
        '<span class="tiny">' + (window.ROLE_LABEL[role] || role) + '</span>' +
      '</span>' +
    '</a>' +
  '</div>';

  sideHost.className = 'side';
  sideHost.innerHTML = html;

  /* ---- top bar with breadcrumbs derived from the registry ---- */
  if (topHost) {
    var page = byId[current];
    var trail = [];
    var walk = page;
    var guard = 0;
    while (walk && guard++ < 6) {
      trail.unshift(walk);
      walk = walk.parent ? byId[walk.parent] : null;
    }

    var crumbs = '<a href="dashboard.html">Beranda</a>';
    trail.forEach(function (p, idx) {
      crumbs += '<span>›</span>';
      crumbs += idx === trail.length - 1
        ? '<span class="now">' + p.title + '</span>'
        : '<a href="' + p.file + '">' + p.title + '</a>';
    });

    topHost.className = 'top';
    topHost.innerHTML =
      '<button class="icon-btn burger" data-nav-toggle aria-label="Buka menu">' + I.menu + '</button>' +
      '<button class="icon-btn" data-sidebar-toggle aria-label="Ciutkan menu samping" title="Ciutkan menu">' + I.panel + '</button>' +
      '<nav class="crumbs" aria-label="Remah roti">' + crumbs + '</nav>' +
      '<div class="top-right">' +
        '<button class="icon-btn theme-toggle" data-theme-toggle title="Ganti tema" aria-label="Ganti tema">' + I.sun + I.moon + '</button>' +
        '<a class="icon-btn" href="notifications.html" aria-label="Notifikasi">' + I.bell + '<span class="dot"></span></a>' +
      '</div>';
  }

  /* mobile drawer */
  document.querySelectorAll('[data-nav-toggle]').forEach(function (b) {
    b.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
  });
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('nav-open') &&
        !e.target.closest('.side') && !e.target.closest('[data-nav-toggle]')) {
      document.body.classList.remove('nav-open');
    }
  });
});
