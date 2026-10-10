// Mantiene el desplazamiento con un dedo y bloquea el zoom de la página.
['gesturestart', 'gesturechange', 'gestureend'].forEach(type => {
  document.addEventListener(type, event => event.preventDefault(), { passive: false });
});
document.addEventListener('touchmove', event => {
  if (event.touches.length > 1) event.preventDefault();
}, { passive: false });

let confirmationOpen = false;
window.RepoConfirm = ({ title, message, confirmLabel = 'Eliminar' }) => {
  if (confirmationOpen) return Promise.resolve(false);
  confirmationOpen = true;
  return new Promise(resolve => {
    const dialog = document.createElement('dialog'); dialog.className = 'library-dialog library-confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'deleteConfirmTitle'); dialog.setAttribute('aria-describedby', 'deleteConfirmMessage');
    const heading = document.createElement('h2'); heading.id = 'deleteConfirmTitle'; heading.textContent = title;
    const description = document.createElement('p'); description.id = 'deleteConfirmMessage'; description.textContent = message;
    const actions = document.createElement('div'); actions.className = 'library-dialog-actions';
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'library-secondary'; cancel.textContent = 'Cancelar';
    const accept = document.createElement('button'); accept.type = 'button'; accept.className = 'library-danger library-confirm-delete'; accept.textContent = confirmLabel;
    cancel.addEventListener('click', () => dialog.close());
    accept.addEventListener('click', () => dialog.close('confirmed'));
    dialog.addEventListener('close', () => {
      const confirmed = dialog.returnValue === 'confirmed'; dialog.remove(); confirmationOpen = false; resolve(confirmed);
    }, { once: true });
    actions.append(cancel, accept); dialog.append(heading, description, actions); document.body.append(dialog);
    dialog.showModal(); cancel.focus();
  });
};

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
