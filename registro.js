// Manejo de pestañas
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(sec => sec.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
  });
});

// Función para enviar formularios vía AJAX
function enviarFormulario(formId, endpoint, msgId) {
  document.getElementById(formId).addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById(msgId);
    msg.textContent = '';
    const datos = new FormData(this);
    fetch(endpoint, { method: 'POST', body: datos })
      .then(res => res.json())
      .then(resp => {
        if (resp.success) {
          msg.style.color = "green";
          msg.textContent = "¡Registro exitoso!";
          this.reset();
        } else {
          msg.style.color = "#b30000";
          msg.textContent = resp.error || "No se pudo registrar.";
        }
      })
      .catch(() => {
        msg.style.color = "#b30000";
        msg.textContent = "Error de conexión.";
      });
  });
}

// Asociar cada formulario con su PHP y mensaje
enviarFormulario('form-cliente', 'registro.php?entidad=cliente', 'msg-cliente');
enviarFormulario('form-mascota', 'registro.php?entidad=mascota', 'msg-mascota');
enviarFormulario('form-proveedor', 'registro.php?entidad=proveedor', 'msg-proveedor');
enviarFormulario('form-empleado', 'registro.php?entidad=empleado', 'msg-empleado');