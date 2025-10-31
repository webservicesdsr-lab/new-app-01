// Kingdom Nexus - Navbar Script (v4 Formal)
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("knxNavToggle");
  const menu = document.getElementById("knxNavMenu");
  if (!btn || !menu) return;

  btn.addEventListener("click", () => {
    menu.classList.toggle("is-open");
    document.body.style.overflow = menu.classList.contains("is-open") ? "hidden" : "";
  });

  document.addEventListener("click", (e) => {
    if (!menu.classList.contains("is-open")) return;
    const within = e.target.closest("#knxNavMenu") || e.target.closest("#knxNavToggle");
    if (!within) {
      menu.classList.remove("is-open");
      document.body.style.overflow = "";
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 900 && menu.classList.contains("is-open")) {
      menu.classList.remove("is-open");
      document.body.style.overflow = "";
    }
  });
});
