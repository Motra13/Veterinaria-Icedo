// --- Utilidades ---
function escapeHtml(txt) {
  return (txt||'').replace(/[&<>"']/g, function(c) {
    return {'&':"&amp;",'<':"&lt;",'>':"&gt;",'"':"&quot;","'":"&#39;"}[c];
  });
}

// --- Llenar autocompletado de clientes ---
document.getElementById('cliente').addEventListener('input', function() {
  let q = this.value;
  fetch('citas.php?action=clientes&q=' + encodeURIComponent(q))
    .then(r=>r.json())
    .then(data=>{
      let datalist = document.getElementById('clientes-list');
      datalist.innerHTML = '';
      data.forEach(c => {
        let opt = document.createElement('option');
        opt.value = c.nombre;
        datalist.appendChild(opt);
      });
    });
});

// --- Llenar autocompletado de mascotas, filtrando por cliente ---
document.getElementById('mascota').addEventListener('input', function() {
  let q = this.value;
  let cliente = document.getElementById('cliente').value;
  fetch('citas.php?action=mascotas&q=' + encodeURIComponent(q) + '&cliente=' + encodeURIComponent(cliente))
    .then(r=>r.json())
    .then(data=>{
      let datalist = document.getElementById('mascotas-list');
      datalist.innerHTML = '';
      data.forEach(m => {
        let opt = document.createElement('option');
        opt.value = m.nombre;
        datalist.appendChild(opt);
      });
    });
});
// También actualiza mascotas si cambia el cliente
document.getElementById('cliente').addEventListener('change', function() {
  let cliente = this.value;
  fetch('citas.php?action=mascotas&cliente=' + encodeURIComponent(cliente))
    .then(r=>r.json())
    .then(data=>{
      let datalist = document.getElementById('mascotas-list');
      datalist.innerHTML = '';
      data.forEach(m => {
        let opt = document.createElement('option');
        opt.value = m.nombre;
        datalist.appendChild(opt);
      });
    });
});

// --- Llenar horarios disponibles según fecha ---
document.getElementById('fecha').addEventListener('change', function() {
  let fecha = this.value;
  let selectHora = document.getElementById('hora');
  selectHora.innerHTML = '<option value="">Cargando horas...</option>';
  fetch('citas.php?action=horas_ocupadas&fecha=' + encodeURIComponent(fecha))
    .then(r=>r.json())
    .then(ocupadas=>{
      selectHora.innerHTML = '';
      for(let h=9; h<=20; h++) {
        for(let m=0; m<=30; m+=30) {
          let hora = (h<10?'0':'')+h+':'+(m==0?'00':'30');
          let disabled = ocupadas.includes(hora) ? 'disabled' : '';
          selectHora.innerHTML += `<option value="${hora}" ${disabled}>${hora}${disabled?' (Ocupado)':''}</option>`;
        }
      }
    });
});

// --- Envío del formulario de cita ---
document.getElementById('form-cita').addEventListener('submit', function(e){
  e.preventDefault();
  let fd = new FormData(this);
  document.getElementById('msg').style.color = '#b30000';
  document.getElementById('msg').textContent = 'Reservando...';
  fetch('citas.php?action=add', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp.success){
        document.getElementById('msg').style.color = 'green';
        document.getElementById('msg').textContent = 'Cita reservada correctamente.';
        this.reset();
        document.getElementById('fecha').value = (new Date()).toISOString().slice(0,10);
        document.getElementById('fecha').dispatchEvent(new Event('change'));
        cargarCalendario();
      } else {
        document.getElementById('msg').style.color = '#b30000';
        document.getElementById('msg').textContent = resp.error||'Error';
      }
    });
});

// --- FullCalendar 6.1.17 ---
let calendar;
function cargarCalendario() {
  fetch('citas.php?action=all')
    .then(r=>r.json())
    .then(citas=>{
      let eventos = citas.map(c => {
        let color = "#b30000";
        if(c.estado==="Completada") color = "#43A047";
        else if(c.estado==="Cancelada") color = "#888";
        else if(c.estado==="Pendiente") color = "#FB8C00";
        return {
          id: c.id,
          title: escapeHtml(c.mascota) + " (" + escapeHtml(c.cliente) + ")",
          start: c.start,
          description: escapeHtml(c.motivo) + (c.tags ? "<br><span class='tag'>"+escapeHtml(c.tags)+"</span>" : ""),
          color: color,
          estado: c.estado
        }
      });
      if(calendar){
        calendar.removeAllEvents();
        eventos.forEach(ev=>calendar.addEvent(ev));
        calendar.render();
        return;
      }
      calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        locale: 'es',
        slotMinTime: "09:00:00",
        slotMaxTime: "21:00:00",
        businessHours: { daysOfWeek: [1,2,3,4,5,6], startTime: "09:00", endTime: "21:00" },
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: eventos,
        eventClick: function(info) {
          mostrarDetalleCita(info.event.id);
        },
        nowIndicator: true,
        slotDuration: "01:00:00",
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false }
      });
      calendar.render();
    });
}
cargarCalendario();

// --- Modal detalle de cita ---
function mostrarDetalleCita(id) {
  fetch('citas.php?action=detalle&id=' + encodeURIComponent(id))
    .then(r=>r.json())
    .then(cita=>{
      let modal = document.getElementById('modal-cita');
      let box = document.getElementById('detalle-cita');
      if(cita.error){
        box.innerHTML = `<span style="color:#b30000">${cita.error}</span>`;
      } else {
        box.innerHTML = `
          <span style="position:absolute; top:10px; right:20px; color:#b30000; font-size:1.5em; cursor:pointer;" onclick="cerrarModalCita()">&times;</span>
          <h3>${escapeHtml(cita.mascota)} (${escapeHtml(cita.cliente)})</h3>
          <div><b>Fecha:</b> ${escapeHtml(cita.fecha)} <b>Hora:</b> ${escapeHtml(cita.hora)}</div>
          <div><b>Motivo:</b> ${escapeHtml(cita.motivo)}</div>
          ${cita.tags ? `<div><b>Etiquetas:</b> <span class="tag">${escapeHtml(cita.tags)}</span></div>` : ""}
          <div><b>Estado:</b> ${escapeHtml(cita.estado)}</div>
          <div style="margin-top:1em;">
            <button onclick="cambiarEstadoCita(${cita.id}, 'Completada')">Marcar como Completada</button>
            <button onclick="cambiarEstadoCita(${cita.id}, 'Cancelada')">Cancelar</button>
          </div>
        `;
      }
      modal.style.display = "flex";
      modal.onclick = function(e){ if(e.target===modal) cerrarModalCita(); }
    });
}
window.cerrarModalCita = function() {
  document.getElementById('modal-cita').style.display = 'none';
}

// --- Cambiar estado de cita ---
function cambiarEstadoCita(id, estado) {
  fetch('citas.php?action=update_estado', {
    method: "POST",
    body: new URLSearchParams({id, estado})
  }).then(r=>r.json())
    .then(resp=>{
      if(resp.success){
        cerrarModalCita();
        cargarCalendario();
      } else {
        alert(resp.error||"No se pudo actualizar el estado.");
      }
    });
}

// --- Inicializa autocompletes y horas en carga inicial ---
window.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('cliente').dispatchEvent(new Event('input'));
  document.getElementById('mascota').dispatchEvent(new Event('input'));
  document.getElementById('fecha').value = (new Date()).toISOString().slice(0,10);
  document.getElementById('fecha').dispatchEvent(new Event('change'));
});