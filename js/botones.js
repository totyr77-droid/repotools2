const btnNavCel = document.getElementById("btn.nav-celular");
const btnNavCelIcon = document.getElementById("btn-nav-celularA");
const navCelu = document.getElementById("nav-celular");
const cookiesBanner = document.getElementById("cookies");
const aceptarBtn = document.getElementById("aceptarCookies");
const cerrarAlertaBtn = document.querySelector(".alerta-cerrar");

btnNavCel?.addEventListener("click", () => {
  btnNavCelIcon?.classList.toggle("giro");
  navCelu?.classList.toggle("mostrarNavC");
});

navCelu?.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    btnNavCelIcon?.classList.remove("giro");
    navCelu.classList.remove("mostrarNavC");
  });
});

if (cookiesBanner && aceptarBtn) {
  cookiesBanner.style.display = localStorage.getItem("cookies") ? "none" : "flex";

  aceptarBtn.addEventListener("click", () => {
    localStorage.setItem("cookies", "true");
    cookiesBanner.classList.add("oculto");

    setTimeout(() => {
      cookiesBanner.style.display = "none";
    }, 300);
  });
}

cerrarAlertaBtn?.addEventListener("click", () => {
  window.cerrarAlerta?.();
});
