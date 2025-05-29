// Menú desplegable (side menu)
function toggleSubmenu(id) {
  document.querySelectorAll('.submenu').forEach(sub => {
    if (sub.id === id) {
      sub.classList.toggle('show');
    } else {
      sub.classList.remove('show');
    }
  });
}

// Cargar usuario en sesión, notificaciones y recordatorios desde panel.php
fetch('panel.php?action=session')
  .then(res => res.json())
  .then(data => {
    document.getElementById('user-session').textContent = data.usuario || "Usuario";
    document.getElementById('user-role').textContent = data.rol || "Empleado";
  });

// Notificaciones y recordatorios
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

// Agenda del día con FullCalendar y PHP/MySQL
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridDay',
    locale: 'es',
    headerToolbar: { left: '', center: 'title', right: '' },
    height: 420,
    events: function(fetchInfo, successCallback, failureCallback) {
      const today = new Date().toISOString().slice(0,10);
      fetch('panel.php?action=agenda&day=' + today)
        .then(res => res.json())
        .then(data => {
          successCallback(data); // [{title, start, end, color}]
        });
    }
  });
  calendar.render();
});