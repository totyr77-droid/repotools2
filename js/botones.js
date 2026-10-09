const btnNavCel = document.getElementById("btn.nav-celular");
const btnNavCelIcon = document.getElementById("btn-nav-celularA");
const navCelu = document.getElementById("nav-celular");
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


cerrarAlertaBtn?.addEventListener("click", () => {
  window.cerrarAlerta?.();
});
