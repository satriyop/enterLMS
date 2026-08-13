/* ============================================================
   Design A — app shell renderer
   Loaded synchronously in <head> so the theme applies pre-paint.
   Chrome is rendered from one registry; pages stay markup-light.
   ============================================================ */

/* --- pre-paint theme (avoids white flash on dark) --- */
(function () {
  try {
    var t = localStorage.getItem('enterlms-mockup-theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
  } catch (e) { /* noop */ }
})();

/* --- page registry: single source of truth for nav + hub index --- */
window.PAGES = [
  { id: 'home',              file: 'home.html',              title: 'Beranda publik',           group: 'Publik',   desc: 'Landing page & proposisi nilai' },
  { id: 'login',             file: 'login.html',             title: 'Masuk',                    group: 'Publik',   desc: 'Login + 2FA' },
  { id: 'register',          file: 'register.html',          title: 'Daftar',                   group: 'Publik',   desc: 'Registrasi akun baru' },
  { id: 'verify',            file: 'verify.html',            title: 'Verifikasi sertifikat',    group: 'Publik',   desc: 'Cek keaslian sertifikat' },

  { id: 'dashboard',         file: 'dashboard.html',         title: 'Dashboard',                group: 'Learner',  desc: 'Ringkasan & lanjutkan belajar' },
  { id: 'browse',            file: 'browse.html',            title: 'Jelajahi kursus',          group: 'Learner',  desc: 'Katalog + filter' },
  { id: 'course-detail',     file: 'course-detail.html',     title: 'Detail kursus',            group: 'Learner',  desc: 'Silabus, ulasan, enroll' },
  { id: 'checkout',          file: 'checkout.html',          title: 'Pembayaran',               group: 'Learner',  desc: 'Checkout kursus berbayar' },
  { id: 'my-learning',       file: 'my-learning.html',       title: 'Pembelajaran saya',        group: 'Learner',  desc: 'Kursus aktif & selesai' },
  { id: 'lesson',            file: 'lesson.html',            title: 'Ruang belajar',            group: 'Learner',  desc: 'Pemutar pelajaran + outline' },
  { id: 'assessment',        file: 'assessment.html',        title: 'Kuis',                     group: 'Learner',  desc: 'Pengerjaan asesmen' },
  { id: 'assessment-result', file: 'assessment-result.html', title: 'Hasil kuis',               group: 'Learner',  desc: 'Skor & pembahasan' },
  { id: 'certificates',      file: 'certificates.html',      title: 'Sertifikat saya',          group: 'Learner',  desc: 'Koleksi & unduh' },
  { id: 'learning-paths',    file: 'learning-paths.html',    title: 'Jalur pembelajaran',       group: 'Learner',  desc: 'Katalog jalur' },
  { id: 'path-progress',     file: 'path-progress.html',     title: 'Progres jalur',            group: 'Learner',  desc: 'Timeline & prasyarat' },
  { id: 'notifications',     file: 'notifications.html',     title: 'Notifikasi',               group: 'Learner',  desc: 'Undangan & pengingat' },
  { id: 'settings',          file: 'settings.html',          title: 'Pengaturan',               group: 'Learner',  desc: 'Profil, sandi, 2FA, tampilan' },

  { id: 'cm-courses',        file: 'cm-courses.html',        title: 'Kelola kursus',            group: 'Pengelola', desc: 'Daftar kursus + status' },
  { id: 'cm-course-edit',    file: 'cm-course-edit.html',    title: 'Editor kursus',            group: 'Pengelola', desc: 'Outline, seksi, pelajaran' },
  { id: 'question-bank',     file: 'question-bank.html',     title: 'Bank soal',                group: 'Pengelola', desc: 'Repositori & impor soal' },
  { id: 'grading',           file: 'grading.html',           title: 'Penilaian',                group: 'Pengelola', desc: 'Antrean koreksi esai' },

  { id: 'admin',             file: 'admin.html',             title: 'Administrasi',             group: 'Admin',    desc: 'Pengguna, kategori, arsip' },
  { id: 'compliance',        file: 'compliance.html',        title: 'Laporan kepatuhan',        group: 'Admin',    desc: 'Audit trail OJK + ekspor' },
];

/* --- primary top-nav --- */
window.NAV_PRIMARY = [
  { id: 'dashboard',      label: 'Dashboard' },
  { id: 'browse',         label: 'Jelajahi' },
  { id: 'my-learning',    label: 'Pembelajaran saya' },
  { id: 'learning-paths', label: 'Jalur' },
  { id: 'certificates',   label: 'Sertifikat' },
];

/* --- "Kelola" menu: surfaces only for privileged roles --- */
window.NAV_MANAGE = [
  { id: 'cm-courses',    label: 'Kelola kursus' },
  { id: 'question-bank', label: 'Bank soal' },
  { id: 'grading',       label: 'Penilaian' },
  { id: 'admin',         label: 'Administrasi' },
  { id: 'compliance',    label: 'Laporan kepatuhan' },
];

/* ------------------------------------------------------------
   ROLE → NAV VISIBILITY
   EnterLMS has 7 roles. This map decides what each one sees.
   Default below is "hide what you cannot use" — see README for
   the trade-off against "show but disable".
   ------------------------------------------------------------ */
window.ROLE_NAV = {
  learner:            { manage: [] },
  content_manager:    { manage: ['cm-courses', 'question-bank'] },
  trainer:            { manage: ['cm-courses', 'question-bank', 'grading'] },
  teaching_assistant: { manage: ['grading'] },
  compliance_officer: { manage: ['compliance'] },
  auditor:            { manage: ['compliance'] },
  lms_admin:          { manage: ['cm-courses', 'question-bank', 'grading', 'admin', 'compliance'] },
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

/* --- icons --- */
window.ICON = {
  search: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  bell:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 1 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
  sun:    '<svg class="sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  moon:   '<svg class="moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8"/></svg>',
  menu:   '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
  chev:   '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
  caret:  '<svg class="caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>',
  check:  '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
  play:   '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>',
  arrow:  '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
  info:   '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
  lock:   '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
  grip:   '<svg class="grip" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>',
};

/* --- render chrome --- */
document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  var current = body.dataset.page || '';
  var role = body.dataset.role || 'learner';
  var host = document.querySelector('[data-shell]');
  if (!host) return;

  var allowed = (window.ROLE_NAV[role] || { manage: [] }).manage;
  var manage = window.NAV_MANAGE.filter(function (i) { return allowed.indexOf(i.id) !== -1; });

  var primary = window.NAV_PRIMARY.map(function (i) {
    return '<a href="' + i.id + '.html"' + (i.id === current ? ' class="active"' : '') + '>' + i.label + '</a>';
  }).join('');

  var manageMenu = manage.length
    ? '<div class="menu-wrap"><button class="nav-manage' + (manage.some(function (m) { return m.id === current; }) ? ' active' : '') + '" data-menu-toggle>Kelola ' + window.ICON.chev + '</button>' +
      '<div class="menu" data-menu>' + manage.map(function (i) {
        return '<a href="' + i.id + '.html"' + (i.id === current ? ' class="active"' : '') + '>' + i.label + '</a>';
      }).join('') + '</div></div>'
    : '';

  host.className = 'nav';
  host.innerHTML =
    '<div class="wrap nav-in">' +
      '<a class="brand" href="../index.html"><span class="brand-mark">E</span> EnterLMS</a>' +
      '<nav class="nav-links">' + primary + manageMenu + '</nav>' +
      '<div class="nav-right">' +
        '<button class="icon-btn theme-toggle" data-theme-toggle title="Ganti tema" aria-label="Ganti tema">' + window.ICON.sun + window.ICON.moon + '</button>' +
        '<a class="icon-btn" href="notifications.html" aria-label="Notifikasi">' + window.ICON.bell + '<span class="dot"></span></a>' +
        '<a class="row" href="settings.html" style="gap:.5rem">' +
          '<span class="avatar">BS</span>' +
          '<span class="tiny" style="line-height:1.2">Budi S.<br><span style="color:var(--subtle-foreground)">' + (window.ROLE_LABEL[role] || role) + '</span></span>' +
        '</a>' +
        '<button class="icon-btn burger" data-sheet-toggle aria-label="Menu" aria-expanded="false">' + window.ICON.menu + '</button>' +
      '</div>' +
    '</div>';

  var sheet = document.createElement('div');
  sheet.className = 'sheet';
  sheet.setAttribute('data-sheet', '');
  sheet.innerHTML = '<div class="wrap">' +
    window.NAV_PRIMARY.concat(manage).map(function (i) {
      return '<a href="' + i.id + '.html"' + (i.id === current ? ' style="font-weight:600;color:var(--primary)"' : '') + '>' + i.label + '</a>';
    }).join('') +
    '<a href="settings.html">Pengaturan</a><a href="../index.html">← Semua layar</a></div>';
  host.after(sheet);

  document.querySelectorAll('[data-menu-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      btn.nextElementSibling.classList.toggle('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('[data-menu].open').forEach(function (m) { m.classList.remove('open'); });
  });
});
