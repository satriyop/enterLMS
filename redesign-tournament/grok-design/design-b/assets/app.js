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
    el._t = setTimeout(() => el.classList.remove("show"), 2500);
  }

  document.addEventListener("click", (e) => {
    const enroll = e.target.closest("[data-enroll]");
    if (enroll) {
      e.preventDefault();
      toast("Berhasil daftar! Mari mulai pelajaran pertama…");
      setTimeout(() => (window.location.href = enroll.getAttribute("data-enroll") || "my-learning.html"), 800);
    }

    const opt = e.target.closest(".quiz-opt");
    if (opt) {
      const group = opt.closest(".quiz-list");
      group?.querySelectorAll(".quiz-opt").forEach((o) => o.classList.remove("selected"));
      opt.classList.add("selected");
      const input = opt.querySelector("input");
      if (input) input.checked = true;
    }

    if (e.target.closest("[data-submit-quiz]")) {
      e.preventDefault();
      toast("Lulus 100% — hebat! Lanjut ke sertifikat jika kursus selesai.");
    }

    if (e.target.closest("[data-complete-lesson]")) {
      e.preventDefault();
      toast("Pelajaran selesai ✓ Progress diperbarui");
      const bar = document.querySelector("[data-progress-bar]");
      if (bar) bar.style.width = "80%";
      const label = document.querySelector("[data-progress-label]");
      if (label) label.textContent = "80% selesai — 1 pelajaran lagi!";
    }

    const t = e.target.closest("[data-toast]");
    if (t && !enroll) {
      e.preventDefault();
      toast(t.getAttribute("data-toast"));
    }
  });
})();
