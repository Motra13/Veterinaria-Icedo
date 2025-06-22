document.getElementById('form-busqueda').addEventListener('submit', function(e){
  e.preventDefault();
  const q = document.getElementById('input-busqueda').value.trim();
  if(q.length < 2) {
    mostrarResultados([{tipo:'error', mensaje:'Por favor, escribe al menos 2 caracteres.'}]);
    return;
  }
  fetch('buscador.php?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => mostrarResultados(data.resultados || []));
});

function mostrarResultados(res) {
  const div = document.getElementById('resultados-busqueda');
  if(!res.length) {
    div.innerHTML = `<p>No se encontraron resultados.</p>`;
    return;
  }
  let html = '';
  res.forEach(r => {
    if(r.tipo === 'cliente') {
      html += `<div class="res-cliente">
        <b>Cliente:</b> <a href="ver_cliente.php?id=${r.id_cliente}" target="_blank">${r.nombre}</a><br>
        <b>Tel:</b> ${r.telefono}<br>
        <b>Email:</b> ${r.correo}
      </div><hr>`;
    } else if(r.tipo === 'mascota') {
      html += `<div class="res-mascota">
        <b>Mascota:</b> <a href="ver_mascota.php?id=${r.id_mascota}" target="_blank">${r.nombre_mascota}</a><br>
        <b>Especie:</b> ${r.especie}<br>
        <b>Raza:</b> ${r.raza}<br>
        <b>Dueño:</b> <a href="ver_cliente.php?id=${r.id_cliente}" target="_blank">${r.nombre_duenio}</a>
      </div><hr>`;
    } else if(r.tipo === 'producto') {
      html += `<div class="res-producto">
        <b>Producto:</b> <a href="ver_producto.php?id=${r.id_producto}" target="_blank">${r.nombre_producto}</a><br>
        <b>Categoría:</b> ${r.categoria || '-'}<br>
        <b>Descripción:</b> ${r.descripcion || '-'}<br>
        <b>Stock:</b> ${r.stock} - <b>Caducidad:</b> ${r.fecha_caducidad || 'N/A'}
      </div><hr>`;
    } else if(r.tipo === 'usuario') {
      html += `<div class="res-usuario">
        <b>Usuario:</b> ${r.usuario}<br>
        <b>Nombre:</b> ${r.nombre}
      </div><hr>`;
    } else if(r.tipo === 'venta') {
      html += `<div class="res-venta">
        <b>Venta/Folio:</b> <a href="ver_venta.php?id=${r.id_venta}" target="_blank">${r.id_venta}</a><br>
        <b>Cliente:</b> ${r.nombre_cliente || '-'}<br>
        <b>Fecha:</b> ${r.fecha_venta}<br>
        <b>Total:</b> $${parseFloat(r.total).toFixed(2)}
      </div><hr>`;
    } else if(r.tipo === 'cita') {
      html += `<div class="res-cita">
        <b>Cita:</b> <a href="ver_cita.php?id=${r.id_cita}" target="_blank">${r.motivo}</a><br>
        <b>Cliente:</b> ${r.cliente}<br>
        <b>Mascota:</b> ${r.mascota}<br>
        <b>Fecha:</b> ${r.fecha_cita} - <b>Hora:</b> ${r.hora_cita}
      </div><hr>`;
    } else if(r.tipo === 'archivo') {
      html += `<div class="res-archivo">
        <b>Archivo:</b> <a href="archivos.php?action=descargar&id=${r.id_archivo}" target="_blank">${r.nombre}.${r.extension}</a><br>
        <b>Tipo:</b> ${r.tipo}<br>
        <b>Subido el:</b> ${r.fecha_subida}<br>
        <button onclick="previewArchivo(${r.id_archivo})">Previsualizar</button>
        <button onclick="eliminarArchivo(${r.id_archivo})">Eliminar</button>
      </div><hr>`;
    } else if(r.tipo === 'error') {
      html += `<div style="color:#b30000;">${r.mensaje}</div>`;
    }
  });
  div.innerHTML = html;
}

function previewArchivo(id) {
  fetch(`archivos.php?action=preview&id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data.tipo === 'img' || data.tipo === 'pdf') {
        const vista = window.open('', '_blank');
        vista.document.write(`<html><body style="margin:0"><embed src="${data.src}" type="${data.tipo === 'img' ? 'image/png' : 'application/pdf'}" width="100%" height="100%"></body></html>`);
      } else {
        alert("No se puede previsualizar este tipo de archivo.");
      }
    });
}

function eliminarArchivo(id) {
  if(confirm("¿Estás seguro de que deseas eliminar este archivo?")) {
    fetch("archivos.php?action=eliminar", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        alert("Archivo eliminado.");
        document.getElementById('form-busqueda').dispatchEvent(new Event('submit'));
      } else {
        alert("No se pudo eliminar el archivo.");
      }
    });
  }
}
