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

document.querySelectorAll('.password-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const input = button.closest('form').querySelector('input[name="password"]');
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    button.setAttribute('aria-pressed', String(visible));
  });
});

document.querySelectorAll('.account-panel form').forEach((form) => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;
    button.disabled = true;
    button.textContent = 'Un momento…';
  });
});

window.addEventListener('pageshow', () => {
  document.querySelectorAll('.account-panel button[type="submit"]').forEach((button) => {
    button.disabled = false;
    button.textContent = document.querySelector('input[name="name"]') ? 'CREAR CUENTA' : 'ENTRAR';
  });
});

document.querySelectorAll('.nav a, .nav-celular a').forEach((link) => {
  const target = new URL(link.href);
  const current = location.pathname.replace(/\/index\.php$/, '/');
  if (!target.hash && target.pathname.replace(/\/index\.php$/, '/') === current) {
    link.setAttribute('aria-current', 'page');
  }
});
