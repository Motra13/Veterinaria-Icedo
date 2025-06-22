// Widgets rápidos
fetch('panel.php?action=widgets')
  .then(res => res.json())
  .then(wdg => {
    document.getElementById('wdg-clientes').innerHTML = `<b>Clientes</b><br>${wdg.clientes}`;
    document.getElementById('wdg-mascotas').innerHTML = `<b>Mascotas</b><br>${wdg.mascotas}`;
    document.getElementById('wdg-productos').innerHTML = `<b>Productos</b><br>${wdg.productos}`;
    document.getElementById('wdg-citas-hoy').innerHTML = `<b>Citas hoy</b><br>${wdg.citas_hoy}`;
    document.getElementById('wdg-seguimientos').innerHTML = `<b>Seguimientos pendientes</b><br>${wdg.seguimientos_pendientes}`;
    document.getElementById('wdg-stock-bajo').innerHTML = `<b>Stock bajo</b><br>${wdg.productos_stock_bajo}`;
    document.getElementById('wdg-caducan').innerHTML = `<b>Caducan pronto</b><br>${wdg.productos_caducan_pronto}`;
  });

// Usuario sesión igual
fetch('panel.php?action=session')
  .then(res => res.json())
  .then(data => {
    document.getElementById('user-session').textContent = data.usuario || "Usuario";
    document.getElementById('user-role').textContent = data.rol || "Empleado";
  });

// Notificaciones y recordatorios enriquecidas
fetch('panel.php?action=notificaciones')
  .then(res => res.json())
  .then(data => {
    renderList(data.notificaciones, "notif-list");
    renderList(data.recordatorios, "reminders-list");
  });

function renderList(arr, id) {
  const ul = document.getElementById(id);
  ul.innerHTML = arr.length === 0 ? "<li>No hay pendientes</li>" : arr.map(o =>
    `<li>${o.msg}${o.time ? `<br><span style='color:#888;font-size:0.93em;'>${o.time}</span>` : ''}${o.date ? `<br><span style='color:#888;font-size:0.93em;'>${o.date}</span>` : ''}</li>`
  ).join("");
}

// Agenda del día con FullCalendar y PHP/MySQL, ahora muestra citas, caducidades y seguimientos
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    locale: 'es',
    headerToolbar: { 
      left: 'prev,next today', 
      center: 'title', 
      right: '' 
    },
    height: 420,
    events: function(fetchInfo, successCallback, failureCallback) {
      // fetchInfo.startStr y fetchInfo.endStr están en formato YYYY-MM-DD
      fetch(`panel.php?action=agenda&start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
        .then(res => res.json())
        .then(data => {
          successCallback(data); // [{title, start, end, color, description, type}]
        });
    },
    eventDidMount: function(info) {
      // Tooltip con descripción
      if (info.event.extendedProps.description) {
        info.el.title = info.event.extendedProps.description;
      }
    }
  });
  calendar.render();
});