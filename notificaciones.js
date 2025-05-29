// Mostrar notificaciones y recordatorios al cargar
function renderList(arr, id, tipo) {
  const ul = document.getElementById(id);
  if (arr.length === 0) {
    ul.innerHTML = "<li>No hay " + (tipo === "notificaciones" ? "notificaciones" : "recordatorios") + " pendientes</li>";
    return;
  }
  ul.innerHTML = arr.map(o =>
    `<li>
      ${o.msg}
      ${o.time ? `<br><span style='color:#888;font-size:0.93em;'>${o.time}</span>` : ""}
      ${o.date ? `<br><span style='color:#888;font-size:0.93em;'>${o.date}</span>` : ""}
      ${tipo === "recordatorios" ? ` <button onclick="borrarRecordatorio(${o.id})" style="margin-left:8px;color:#b30000;background:transparent;border:none;cursor:pointer;" title="Eliminar recordatorio">✖</button>` : ""}
    </li>`
  ).join("");
}

// Cargar listas
function cargarListas() {
  fetch('notificaciones.php?action=all')
    .then(res => res.json())
    .then(data => {
      renderList(data.notificaciones, "notif-list", "notificaciones");
      renderList(data.recordatorios, "reminders-list", "recordatorios");
    });
}
cargarListas();

// Agregar recordatorio
document.getElementById('form-recordatorio').addEventListener('submit', function(e) {
  e.preventDefault();
  const mensaje = document.getElementById('mensaje-recordatorio').value.trim();
  const fecha = document.getElementById('fecha-recordatorio').value;

  if (!mensaje || !fecha) return;
  fetch('notificaciones.php?action=add_recordatorio', {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `mensaje=${encodeURIComponent(mensaje)}&fecha=${encodeURIComponent(fecha)}`
  })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        document.getElementById('form-recordatorio').reset();
        cargarListas();
      } else {
        alert("No se pudo agregar el recordatorio.");
      }
    });
});

// Eliminar recordatorio
function borrarRecordatorio(id) {
  if (!confirm("¿Eliminar este recordatorio?")) return;
  fetch('notificaciones.php?action=delete_recordatorio&id=' + id)
    .then(r => r.json())
    .then(resp => {
      if (resp.success) cargarListas();
      else alert("No se pudo eliminar.");
    });
}
window.borrarRecordatorio = borrarRecordatorio;