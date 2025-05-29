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
        <b>Cliente:</b> <a href="ver_cliente.php?id=${r.id_cliente}" target="_blank">${r.nombre}</a> <br>
        <b>Tel:</b> ${r.telefono} <br>
        <b>Email:</b> ${r.correo}
      </div><hr>`;
    } else if(r.tipo === 'mascota') {
      html += `<div class="res-mascota">
        <b>Mascota:</b> <a href="ver_mascota.php?id=${r.id_mascota}" target="_blank">${r.nombre}</a> <br>
        <b>Especie:</b> ${r.especie} <br>
        <b>Raza:</b> ${r.raza} <br>
        <b>Dueño:</b> <a href="ver_cliente.php?id=${r.id_cliente}" target="_blank">${r.nombre_duenio}</a>
      </div><hr>`;
    } else if(r.tipo === 'producto') {
      html += `<div class="res-producto">
        <b>Producto:</b> <a href="ver_producto.php?id=${r.id_producto}" target="_blank">${r.nombre_producto}</a> <br>
        <b>Categoría:</b> ${r.categoria || '-'} <br>
        <b>Descripción:</b> ${r.descripcion || '-'}
      </div><hr>`;
    } else if(r.tipo === 'usuario') {
      html += `<div class="res-usuario">
        <b>Usuario:</b> ${r.usuario} <br>
        <b>Nombre:</b> ${r.nombre_completo} <br>
        <b>Email:</b> ${r.correo}
      </div><hr>`;
    } else if(r.tipo === 'venta') {
      html += `<div class="res-venta">
        <b>Venta/Folio:</b> <a href="ver_venta.php?id=${r.id_venta}" target="_blank">${r.id_venta}</a> <br>
        <b>Cliente:</b> ${r.nombre_cliente || '-'} <br>
        <b>Fecha:</b> ${r.fecha} <br>
        <b>Total:</b> $${parseFloat(r.total).toFixed(2)}
      </div><hr>`;
    } else if(r.tipo === 'error') {
      html += `<div style="color:#b30000;">${r.mensaje}</div>`;
    }
  });
  div.innerHTML = html;
}