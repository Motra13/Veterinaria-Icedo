// Utils
function escapeHtml(str) {
  return (str||'').replace(/[&<>"']/g, c => ({'&':"&amp;",'<':"&lt;",'>':"&gt;",'"':"&quot;","'":"&#39;"}[c]));
}

// SUBIDA DE ARCHIVO
document.getElementById('form-upload').addEventListener('submit', function(e){
  e.preventDefault();
  let fd = new FormData(this);
  let xhr = new XMLHttpRequest();
  let bar = document.getElementById('progress-bar');
  bar.style.width = "0%";
  xhr.upload.onprogress = (ev) => {
    if(ev.lengthComputable) bar.style.width = ((ev.loaded/ev.total)*100)+"%";
  };
  xhr.onload = () => {
    bar.style.width = "100%";
    let resp = JSON.parse(xhr.responseText);
    if(resp.success) {
      alert("Archivo subido correctamente");
      this.reset(); cargarArchivos();
      document.getElementById('preview-upload').innerHTML = "";
    } else alert("Error: "+(resp.error||""));
  };
  xhr.open("POST","archivos.php?action=subir");
  xhr.send(fd);
});

// Preview antes de subir
document.getElementById('input-file').addEventListener('change',function(){
  let f = this.files[0];
  let prev = document.getElementById('preview-upload');
  if(!f) { prev.innerHTML=""; return;}
  let ext = f.name.split('.').pop().toLowerCase();
  if(['png','jpg','jpeg'].includes(ext)) {
    let reader = new FileReader();
    reader.onload = e => {
      prev.innerHTML = `<img src="${e.target.result}" class="preview-img">`;
    };
    reader.readAsDataURL(f);
  } else if(ext==='pdf') {
    prev.innerHTML = `<span>PDF seleccionado: ${escapeHtml(f.name)}</span>`;
  } else {
    prev.innerHTML = `<span>Archivo seleccionado: ${escapeHtml(f.name)}</span>`;
  }
});

// BUSQUEDA Y FILTROS
document.getElementById('buscar').addEventListener('keyup',function(e){
  if(e.key=="Enter") buscarArchivos();
});
function buscarArchivos(){ cargarArchivos(1); }

// PAGINACION Y CARGA DE ARCHIVOS
let paginaActual = 1;
function cargarArchivos(pag){
  if(pag) paginaActual = pag;
  let buscar = document.getElementById('buscar').value.trim();
  let tipo = document.getElementById('filtro-tipo').value;
  let orden = document.getElementById('filtro-orden').value;
  fetch(`archivos.php?action=listar&buscar=${encodeURIComponent(buscar)}&tipo=${encodeURIComponent(tipo)}&orden=${encodeURIComponent(orden)}&pagina=${paginaActual}`)
    .then(r=>r.json()).then(data=>{
      renderArchivos(data.archivos||[]);
      renderPaginacion(data.paginas||1, paginaActual);
      renderEstadisticas(data.estadisticas || []);
    });
}
function renderArchivos(archs){
  let tbody = document.getElementById('archivos-body');
  if(!archs.length) { tbody.innerHTML=`<tr><td colspan="9">No hay archivos</td></tr>`; return;}
  tbody.innerHTML = archs.map(f=>`
    <tr class="file-row">
      <td>
        ${f.extension.match(/jpg|jpeg|png/) ? `<img src="${f.url}" class="preview-img" onclick="previewArchivo(${f.id_archivo})">`:
        f.extension=="pdf"? `<button onclick="previewArchivo(${f.id_archivo})">Ver PDF</button>`:
        `<span>${f.extension.toUpperCase()}</span>`}
      </td>
      <td>${escapeHtml(f.nombre||'Sin nombre')}</td>
      <td>${escapeHtml(f.descripcion||'')}</td>
      <td>${escapeHtml(f.tipo||'')}</td>
      <td>${(f.tags||'').split(',').filter(Boolean).map(t=>`<span class="tag">${escapeHtml(t.trim())}</span>`).join(' ')}</td>
      <td>${f.fecha_subida}</td>
      <td>${f.fecha_asociada||"-"}</td>
      <td>${escapeHtml(f.relacion||'-')}</td>
      <td class="file-actions">
        <button onclick="descargarArchivo(${f.id_archivo})">Descargar</button>
        <button onclick="editarArchivo(${f.id_archivo})">Editar</button>
        <button onclick="eliminarArchivo(${f.id_archivo})" style="color:#c00;">Eliminar</button>
        <button onclick="compartirArchivo(${f.id_archivo})">Compartir</button>
      </td>
    </tr>
  `).join('');
}
// Paginación
function renderPaginacion(total, actual){
  let pag = document.getElementById('paginacion');
  if(total<=1) {pag.innerHTML=""; return;}
  let html = "";
  for(let i=1;i<=total;i++)
    html+=`<button ${i==actual?'style="font-weight:bold"':''} onclick="cargarArchivos(${i})">${i}</button>`;
  pag.innerHTML = html;
}
// Estadísticas por tipo
function renderEstadisticas(est){
  let div = document.getElementById('estadisticas-archivos');
  if(!est.length) {div.innerHTML=""; return;}
  div.innerHTML = "<b>Estadísticas por tipo:</b> "+est.map(e=>`${escapeHtml(e.tipo)}: ${e.total}`).join(' | ');
}

// PREVIEW MODAL
window.previewArchivo = function(id){
  fetch(`archivos.php?action=preview&id=${id}`)
    .then(r=>r.json()).then(d=>{
      let modal = document.getElementById('modal-preview');
      let cont = document.getElementById('modal-content');
      if(d.tipo=="img")
        cont.innerHTML = `<img src="${d.src}" style="max-width:600px;max-height:80vh;">`;
      else if(d.tipo=="pdf")
        cont.innerHTML = `<embed src="${d.src}" type="application/pdf" width="600" height="500">`;
      else
        cont.innerHTML = `<span>No disponible para previsualizar</span>`;
      modal.style.display = "";
      modal.onclick = function(e){ if(e.target===modal) modal.style.display = "none"; }
    });
}

// DESCARGA
window.descargarArchivo = function(id){
  window.open(`archivos.php?action=descargar&id=${id}`,'_blank');
}

// ELIMINAR
window.eliminarArchivo = function(id){
  if(!confirm("¿Eliminar este archivo?")) return;
  fetch("archivos.php?action=eliminar",{
    method:'POST',
    body: new URLSearchParams({id})
  }).then(r=>r.json()).then(d=>{
    if(d.success) cargarArchivos();
    else alert("No se pudo eliminar.");
  });
}

// EDITAR
window.editarArchivo = function(id){
  let fila = [...document.querySelectorAll('.file-row')].find(tr=>tr.innerHTML.includes(`editarArchivo(${id})`));
  if(!fila) return;
  let tds = fila.querySelectorAll('td');
  let nombre = prompt("Nuevo nombre:", tds[1].textContent.trim());
  if(nombre===null) return;
  let descripcion = prompt("Nueva descripción:", tds[2].textContent.trim());
  if(descripcion===null) return;
  let tags = prompt("Etiquetas (coma):", tds[4].textContent.replace(/[\n\r]/g,'').replace(/\s+/g,' ').trim());
  if(tags===null) return;
  let tipo = prompt("Tipo:", tds[3].textContent.trim());
  if(tipo===null) return;
  let fecha_asociada = prompt("Fecha asociada (YYYY-MM-DD):", tds[6].textContent.trim());
  if(fecha_asociada===null) return;
  let relacion = prompt("Relacionado con:", tds[7].textContent.trim());
  if(relacion===null) return;
  fetch('archivos.php?action=editar',{
    method:'POST',
    body: new URLSearchParams({id, nombre, descripcion, tags, tipo, fecha_asociada, relacion})
  }).then(r=>r.json()).then(d=>{
    if(d.success) cargarArchivos();
    else alert("No se pudo editar.");
  });
}

// COMPARTIR
window.compartirArchivo = function(id){
  fetch('archivos.php?action=compartir',{
    method:'POST', body: new URLSearchParams({id})
  }).then(r=>r.json()).then(d=>{
    if(d.url) {
      prompt("Enlace temporal para compartir (válido 1h):", location.origin+'/'+d.url);
    }
  });
}

cargarArchivos();