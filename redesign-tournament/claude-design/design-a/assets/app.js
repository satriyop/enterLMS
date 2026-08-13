/* ============================================================
   EnterLMS redesign mockup — interaction layer
   Vanilla JS, no build step. Declarative via data-attributes so
   markup stays readable and every page shares one behaviour set.
   ============================================================ */
(function () {
  'use strict';

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  /* ---------- theme (persisted, applied pre-paint via inline head script) ---------- */
  const THEME_KEY = 'enterlms-mockup-theme';

  function setTheme(mode) {
    document.documentElement.setAttribute('data-theme', mode);
    try { localStorage.setItem(THEME_KEY, mode); } catch (e) { /* private mode */ }
  }

  function initTheme() {
    $$('[data-theme-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(next);
        toast(next === 'dark' ? 'Mode gelap aktif' : 'Mode terang aktif');
      });
    });
  }

  /* ---------- mobile nav sheet ---------- */
  function initSheet() {
    const sheet = $('[data-sheet]');
    if (!sheet) return;
    $$('[data-sheet-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const open = sheet.classList.toggle('open');
        btn.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
      });
    });
  }

  /* ---------- sidebar collapse (Design B) ---------- */
  function initSidebar() {
    $$('[data-sidebar-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');
      });
    });
  }

  /* ---------- tabs ----------
     <div data-tabs>
       <button data-tab="x" aria-selected="true">…</button>
       <div data-panel="x">…</div>
  */
  function initTabs() {
    $$('[data-tabs]').forEach((group) => {
      const tabs   = $$('[data-tab]', group);
      const panels = $$('[data-panel]', group);
      tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
          tabs.forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
          panels.forEach((p) => { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        });
      });
    });
  }

  /* ---------- accordion ---------- */
  function initAccordion() {
    $$('[data-acc]').forEach((head) => {
      head.addEventListener('click', () => {
        const open = head.getAttribute('aria-expanded') === 'true';
        head.setAttribute('aria-expanded', String(!open));
      });
    });
  }

  /* ---------- toggle chips (filters) ---------- */
  function initChips() {
    $$('[data-chip-group]').forEach((group) => {
      const single = group.dataset.chipGroup === 'single';
      $$('[data-chip]', group).forEach((chip) => {
        chip.addEventListener('click', () => {
          const on = chip.getAttribute('aria-pressed') === 'true';
          if (single) $$('[data-chip]', group).forEach((c) => c.setAttribute('aria-pressed', 'false'));
          chip.setAttribute('aria-pressed', String(single ? true : !on));
          const count = $$('[data-chip][aria-pressed="true"]', group).length;
          const out = $('[data-chip-count]');
          if (out) out.textContent = count ? `${count} filter aktif` : 'Semua kursus';
        });
      });
    });
  }

  /* ---------- modal ---------- */
  function initModal() {
    $$('[data-modal-open]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const el = document.getElementById(btn.dataset.modalOpen);
        if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
      });
    });
    const close = (el) => { el.classList.remove('open'); document.body.style.overflow = ''; };
    $$('[data-modal-close]').forEach((btn) => {
      btn.addEventListener('click', () => { const m = btn.closest('.backdrop'); if (m) close(m); });
    });
    $$('.backdrop').forEach((bd) => {
      bd.addEventListener('click', (e) => { if (e.target === bd) close(bd); });
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') $$('.backdrop.open').forEach(close);
    });
  }

  /* ---------- toast ---------- */
  function toast(msg, tone) {
    let host = $('.toasts');
    if (!host) { host = document.createElement('div'); host.className = 'toasts'; document.body.appendChild(host); }
    const el = document.createElement('div');
    el.className = 'toast';
    el.textContent = (tone === 'ok' ? '✓ ' : '') + msg;
    host.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .25s'; }, 2400);
    setTimeout(() => el.remove(), 2700);
  }

  function initToastTriggers() {
    $$('[data-toast]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        if (btn.tagName === 'A' && btn.getAttribute('href') === '#') e.preventDefault();
        toast(btn.dataset.toast, 'ok');
      });
    });
  }

  /* ---------- enroll: optimistic state swap ---------- */
  function initEnroll() {
    $$('[data-enroll]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = $('[data-enroll-state]');
        if (target) target.hidden = false;
        btn.closest('[data-enroll-cta]')?.setAttribute('hidden', '');
        toast('Berhasil terdaftar. Selamat belajar!', 'ok');
      });
    });
  }

  /* ---------- lesson: mark complete ---------- */
  function initLessonComplete() {
    $$('[data-complete]').forEach((btn) => {
      btn.addEventListener('click', () => {
        btn.textContent = '✓ Selesai';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline', 'is-disabled');
        const row = $(`[data-lesson="${btn.dataset.complete}"]`);
        if (row) { row.classList.add('done'); $('.tick', row)?.classList.add('on'); }
        const bar = $('[data-course-bar]');
        if (bar) { const next = Math.min(100, parseInt(bar.dataset.courseBar || '0', 10) + 12); bar.style.width = next + '%'; bar.dataset.courseBar = next; }
        toast('Pelajaran ditandai selesai', 'ok');
      });
    });
  }

  /* ---------- quiz ---------- */
  function initQuiz() {
    const quiz = $('[data-quiz]');
    if (!quiz) return;

    const answered = new Set();

    $$('[data-opt-group]', quiz).forEach((group) => {
      $$('.opt', group).forEach((opt) => {
        opt.addEventListener('click', () => {
          $$('.opt', group).forEach((o) => o.setAttribute('aria-pressed', 'false'));
          opt.setAttribute('aria-pressed', 'true');
          const q = group.dataset.optGroup;
          answered.add(q);
          $(`[data-qnav="${q}"]`)?.classList.add('answered');
          const out = $('[data-answered-count]');
          if (out) out.textContent = String(answered.size);
        });
      });
    });

    $$('[data-qnav]', quiz).forEach((btn) => {
      btn.addEventListener('click', () => {
        $$('[data-qnav]', quiz).forEach((b) => b.classList.remove('current'));
        btn.classList.add('current');
        $(`[data-question="${btn.dataset.qnav}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    // countdown
    const timer = $('[data-timer]');
    if (timer) {
      let left = parseInt(timer.dataset.timer, 10) || 1800;
      const tick = () => {
        left = Math.max(0, left - 1);
        const m = String(Math.floor(left / 60)).padStart(2, '0');
        const s = String(left % 60).padStart(2, '0');
        const label = $('[data-timer-label]', timer) || timer;
        label.textContent = `${m}:${s}`;
        if (left < 300) timer.classList.add('low');
        if (left > 0) setTimeout(tick, 1000);
      };
      tick();
    }
  }

  /* ---------- password reveal ---------- */
  function initReveal() {
    $$('[data-reveal]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.reveal);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? 'Tampilkan' : 'Sembunyikan';
      });
    });
  }

  /* ---------- animate progress bars/rings on load ----------
     Values start at 0 in the markup so CSS can transition them in.
     rAF is throttled in background tabs and offscreen frames, so a
     timeout backstops it — otherwise the bars would stay empty. */
  function initProgressReveal() {
    const apply = () => {
      $$('[data-bar]').forEach((el) => { el.style.width = el.dataset.bar + '%'; });
      $$('[data-ring]').forEach((el) => { el.style.setProperty('--p', el.dataset.ring); });
    };
    requestAnimationFrame(apply);
    setTimeout(apply, 150);
  }

  /* ---------- stepper (Design B wizard) ---------- */
  function initStepper() {
    const stepper = $('[data-stepper]');
    if (!stepper) return;
    const steps = $$('[data-step]', stepper);
    const panels = $$('[data-step-panel]');
    let index = 0;

    const render = () => {
      steps.forEach((s, i) => {
        s.classList.toggle('is-current', i === index);
        s.classList.toggle('is-done', i < index);
      });
      panels.forEach((p, i) => { p.hidden = i !== index; });
      $('[data-step-prev]')?.toggleAttribute('disabled', index === 0);
      const next = $('[data-step-next]');
      if (next) next.textContent = index === panels.length - 1 ? 'Terbitkan kursus' : 'Lanjut';
    };

    $('[data-step-next]')?.addEventListener('click', () => {
      if (index < panels.length - 1) { index++; render(); }
      else toast('Kursus diterbitkan', 'ok');
    });
    $('[data-step-prev]')?.addEventListener('click', () => { if (index > 0) { index--; render(); } });
    steps.forEach((s, i) => s.addEventListener('click', () => { index = i; render(); }));
    render();
  }

  /* ---------- boot ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSheet();
    initSidebar();
    initTabs();
    initAccordion();
    initChips();
    initModal();
    initToastTriggers();
    initEnroll();
    initLessonComplete();
    initQuiz();
    initReveal();
    initProgressReveal();
    initStepper();
  });

  window.mockToast = toast;
})();
