// Utils
function escapeHtml(str) {
  return (str||'').replace(/[&<>"']/g, c => ({'&':"&amp;",'<':"&lt;",'>':"&gt;",'"':"&quot;","'":"&#39;"}[c]));
}
function estadoBadge(estado) {
  if(estado==='completado') return '<span class="badge badge-completo">Completado</span>';
  if(estado==='atrasado') return '<span class="badge badge-atrasado">Atrasado</span>';
  return '<span class="badge badge-pendiente">Pendiente</span>';
}
function diasRestantes(fecha){
  let hoy = new Date();
  let f = new Date(fecha);
  let ms = f - hoy;
  let dias = Math.ceil(ms/(1000*60*60*24));
  if(isNaN(dias)) return '';
  if(dias===0) return '¡Hoy!';
  if(dias>0) return `En ${dias} día${dias>1?'s':''}`;
  return `Hace ${Math.abs(dias)} día${Math.abs(dias)>1?'s':''}`;
}

// Registrar nuevo seguimiento
document.getElementById('form-seguimiento').addEventListener('submit',function(e){
  e.preventDefault();
  let id = document.getElementById('seguimiento-id').value;
  let cliente = document.getElementById('input-cliente').value.trim();
  let mascota = document.getElementById('input-mascota').value.trim();
  let motivo = document.getElementById('input-motivo').value.trim();
  let notas = document.getElementById('input-notas').value.trim();
  let fecha_prox = document.getElementById('input-fecha-prox').value;
  let tags = document.getElementById('input-tags').value.trim();
  let fd = new URLSearchParams({cliente, mascota, motivo, notas, fecha_prox, tags});
  let url = id ? 'seguimiento.php?action=editar' : 'seguimiento.php?action=crear';
  if(id) fd.append('id', id);
  fetch(url, {method:'POST', body:fd})
    .then(r=>r.json()).then(resp=>{
      if(resp.success) {
        this.reset();
        document.getElementById('btn-cancelar').style.display="none";
        document.getElementById('seguimiento-id').value='';
        cargarSeguimientos();
        cargarTimeline();
      } else alert("No se pudo guardar: "+(resp.error||""));
    });
});

// Cancelar edición
document.getElementById('btn-cancelar').addEventListener('click',function(){
  document.getElementById('form-seguimiento').reset();
  document.getElementById('seguimiento-id').value='';
  document.getElementById('btn-cancelar').style.display="none";
});

// Buscar
document.getElementById('buscar').addEventListener('keyup',function(e){
  if(e.key=="Enter") buscarSeguimientos();
});
function buscarSeguimientos(){ cargarSeguimientos(1); }

let paginaActual=1;
function cargarSeguimientos(pag){
  if(pag) paginaActual=pag;
  let buscar = document.getElementById('buscar').value.trim();
  let estado = document.getElementById('filtro-estado').value;
  let orden = document.getElementById('filtro-orden').value;
  fetch(`seguimiento.php?action=listar&buscar=${encodeURIComponent(buscar)}&estado=${encodeURIComponent(estado)}&orden=${encodeURIComponent(orden)}&pagina=${paginaActual}`)
    .then(r=>r.json()).then(data=>{
      renderSeguimientos(data.seguimientos||[]);
      renderPaginacion(data.paginas||1,paginaActual);
    });
}
function renderSeguimientos(segs){
  let tbody = document.getElementById('seguimientos-body');
  if(!segs.length) { tbody.innerHTML='<tr><td colspan="9">No hay seguimientos</td></tr>'; return;}
  tbody.innerHTML = segs.map(s=>`
    <tr>
      <td>${escapeHtml(s.cliente)}</td>
      <td>${escapeHtml(s.mascota)}</td>
      <td>${escapeHtml(s.motivo)}</td>
      <td>${escapeHtml(s.notas).slice(0,70)}${s.notas.length>70?'...':''}</td>
      <td>${s.fecha_proximo} <br><small>${diasRestantes(s.fecha_proximo)}</small></td>
      <td>${estadoBadge(s.estado)}</td>
      <td>${(s.tags||'').split(',').filter(Boolean).map(t=>`<span class="tag">${escapeHtml(t.trim())}</span>`).join(' ')}</td>
      <td>${s.fecha_registro.slice(0,16).replace('T',' ')}</td>
      <td>
        <button class="icon-btn" title="Ver historial" onclick="verSeguimiento(${s.id_seguimiento})">📋</button>
        <button class="icon-btn" title="Completar" onclick="completarSeguimiento(${s.id_seguimiento})">✅</button>
        <button class="icon-btn" title="Editar" onclick="editarSeguimiento(${s.id_seguimiento})">✏️</button>
        <button class="icon-btn" title="Eliminar" onclick="eliminarSeguimiento(${s.id_seguimiento})">🗑️</button>
      </td>
    </tr>
  `).join('');
}
function renderPaginacion(total, actual){
  let pag = document.getElementById('paginacion');
  if(total<=1) {pag.innerHTML=""; return;}
  let html = "";
  for(let i=1;i<=total;i++)
    html+=`<button ${i==actual?'style="font-weight:bold"':''} onclick="cargarSeguimientos(${i})">${i}</button>`;
  pag.innerHTML = html;
}

// Acciones
window.completarSeguimiento = function(id){
  if(!confirm("¿Marcar seguimiento como completado?")) return;
  fetch('seguimiento.php?action=completar', {
    method:'POST',
    body: new URLSearchParams({id})
  }).then(r=>r.json()).then(resp=>{
    if(resp.success) { cargarSeguimientos(); cargarTimeline(); }
    else alert("No se pudo completar.");
  });
};
window.eliminarSeguimiento = function(id){
  if(!confirm("¿Eliminar seguimiento?")) return;
  fetch('seguimiento.php?action=eliminar',{
    method:'POST',
    body: new URLSearchParams({id})
  }).then(r=>r.json()).then(resp=>{
    if(resp.success) { cargarSeguimientos(); cargarTimeline(); }
    else alert("No se pudo eliminar.");
  });
};
window.editarSeguimiento = function(id){
  fetch(`seguimiento.php?action=ver&id=${id}`)
    .then(r=>r.json()).then(s=>{
      if(!s) return;
      document.getElementById('seguimiento-id').value = s.id_seguimiento;
      document.getElementById('input-cliente').value = s.cliente;
      document.getElementById('input-mascota').value = s.mascota;
      document.getElementById('input-motivo').value = s.motivo;
      document.getElementById('input-notas').value = s.notas;
      document.getElementById('input-fecha-prox').value = s.fecha_proximo;
      document.getElementById('input-tags').value = s.tags;
      document.getElementById('btn-cancelar').style.display="";
      window.scrollTo(0,0);
    });
};
window.verSeguimiento = function(id){
  fetch(`seguimiento.php?action=historial&id=${id}`)
    .then(r=>r.json()).then(data=>{
      let m = document.getElementById('modal-followup');
      let c = document.getElementById('modal-content');
      let s = data.seguimiento;
      let hist = data.historial||[];
      c.innerHTML = `<span class="close-btn" onclick="document.getElementById('modal-followup').style.display='none'">&times;</span>
        <h3>Seguimiento de ${escapeHtml(s.mascota)} (${escapeHtml(s.cliente)})</h3>
        <b>Motivo:</b> ${escapeHtml(s.motivo)}<br>
        <b>Notas:</b> ${escapeHtml(s.notas)}<br>
        <b>Próxima cita:</b> ${s.fecha_proximo} (${diasRestantes(s.fecha_proximo)})<br>
        <b>Estado:</b> ${estadoBadge(s.estado)}<br>
        <b>Etiquetas:</b> ${(s.tags||'').split(',').filter(Boolean).map(t=>`<span class="tag">${escapeHtml(t.trim())}</span>`).join(' ')}<br>
        <b>Registro:</b> ${s.fecha_registro.slice(0,16).replace('T',' ')}<br>
        <hr>
        <h4>Historial de acciones</h4>
        <div class="timeline">
          ${hist.length?
            hist.map(ev=>`
              <div class="timeline-event">
                <span class="date">${ev.fecha_accion.slice(0,16).replace('T',' ')}</span>
                <div class="desc">${escapeHtml(ev.accion)}</div>
              </div>
            `).join('')
            : '<div>No hay historial.</div>'
          }
        </div>
      `;
      m.style.display="";
      m.onclick = function(e){ if(e.target===m) m.style.display="none"; }
    });
};

// Timeline global
function cargarTimeline(){
  fetch('seguimiento.php?action=timeline')
    .then(r=>r.json()).then(data=>{
      let t = document.getElementById('timeline-historial');
      if(!data.length) { t.innerHTML='<div>No hay historial global.</div>'; return;}
      t.innerHTML = data.map(ev=>`
        <div class="timeline-event">
          <span class="date">${ev.fecha_accion.slice(0,16).replace('T',' ')}</span>
          <div class="desc">${escapeHtml(ev.accion)}</div>
        </div>
      `).join('');
    });
}

cargarSeguimientos();
cargarTimeline();