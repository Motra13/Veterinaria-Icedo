// login.js
// Opcional: Validación en el cliente antes de enviar (puedes omitir este archivo si no necesitas validación extra)

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('loginForm');
  form.addEventListener('submit', function (e) {
    // Validación simple (puedes expandirla según lo que necesites)
    const usuario = document.getElementById('usuario').value.trim();
    const contrasena = document.getElementById('contrasena').value;
    if (usuario === "" || contrasena === "") {
      e.preventDefault();
      document.getElementById('login-error').textContent = "Por favor, completa todos los campos.";
    }
    // De lo contrario, el formulario se envía a login.php para validarse en el servidor
  });
});