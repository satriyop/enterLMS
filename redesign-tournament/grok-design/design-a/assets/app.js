/* Design A — light interactions */
(function () {
  function toast(msg) {
    let el = document.querySelector(".toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add("show");
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove("show"), 2400);
  }

  document.addEventListener("click", (e) => {
    const enroll = e.target.closest("[data-enroll]");
    if (enroll) {
      e.preventDefault();
      toast("Berhasil mendaftar. Mengarahkan ke pembelajaran…");
      setTimeout(() => {
        window.location.href = enroll.getAttribute("data-enroll") || "my-learning.html";
      }, 700);
    }

    const opt = e.target.closest(".quiz-option");
    if (opt) {
      const group = opt.closest(".quiz-options");
      if (group) {
        group.querySelectorAll(".quiz-option").forEach((o) => o.classList.remove("selected"));
        opt.classList.add("selected");
        const input = opt.querySelector("input");
        if (input) input.checked = true;
      }
    }

    const submit = e.target.closest("[data-submit-quiz]");
    if (submit) {
      e.preventDefault();
      toast("Jawaban terkirim. Skor: 100% — Lulus");
    }

    const mark = e.target.closest("[data-complete-lesson]");
    if (mark) {
      e.preventDefault();
      toast("Pelajaran ditandai selesai");
      const bar = document.querySelector("[data-progress-bar]");
      if (bar) bar.style.width = "80%";
      const label = document.querySelector("[data-progress-label]");
      if (label) label.textContent = "80% selesai";
    }

    const notify = e.target.closest("[data-toast]");
    if (notify && !enroll && !submit && !mark) {
      e.preventDefault();
      toast(notify.getAttribute("data-toast"));
    }
  });
})();
