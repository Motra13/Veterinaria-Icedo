let selectedMascota = null;

document.getElementById('busqueda-form').addEventListener('submit', function(e) {
  e.preventDefault();
  let term = document.getElementById('busqueda').value.trim();
  fetch('expediente.php?action=buscar&term=' + encodeURIComponent(term))
    .then(r => r.json())
    .then(data => {
      let div = document.getElementById('resultados-busqueda');
      if(data.length === 0) {
        div.innerHTML = '<p>No se encontraron mascotas.</p>';
        document.getElementById('expediente-section').style.display = 'none';
        return;
      }
      div.innerHTML = '<ul>' + data.map(m => 
        `<li>
          <button onclick="seleccionarMascota(${m.id_mascota},'${m.nombre_mascota.replace(/'/g,"\\'")}')">
            ${m.nombre_mascota} (${m.especie}) - Dueño: ${m.dueno}
          </button>
        </li>`
      ).join('') + '</ul>';
    });
});

window.seleccionarMascota = function(id_mascota, nombre_mascota) {
  selectedMascota = id_mascota;
  document.getElementById('mascota-nombre').textContent = nombre_mascota;
  document.getElementById('expediente-section').style.display = '';
  // Info de mascota
  fetch('expediente.php?action=info&id_mascota=' + id_mascota)
    .then(r => r.json())
    .then(info => {
      document.getElementById('info-mascota').innerHTML = `
        <strong>Nombre:</strong> ${info.nombre_mascota}<br>
        <strong>Especie:</strong> ${info.especie}<br>
        <strong>Raza:</strong> ${info.raza}<br>
        <strong>Edad:</strong> ${info.edad || '-'} años<br>
        <strong>Peso:</strong> ${info.peso || '-'} kg<br>
        <strong>Sexo:</strong> ${info.sexo || '-'}<br>
        <strong>Dueño:</strong> ${info.dueno || '-'}
      `;
    });
  cargarHistorial();
};

function cargarHistorial() {
  fetch('expediente.php?action=historial&id_mascota=' + selectedMascota)
    .then(r => r.json())
    .then(historial => {
      if(historial.length === 0) {
        document.getElementById('historial-clinico').innerHTML = '<p>No hay notas clínicas registradas.</p>';
      } else {
        document.getElementById('historial-clinico').innerHTML = 
          '<ul>' + historial.map(n =>
            `<li>
              <div><b>${n.fecha}</b> - ${n.usuario}</div>
              <div>${n.nota}</div>
            </li>`
          ).join('') + '</ul>';
      }
    });
}

document.getElementById('nueva-nota-form').addEventListener('submit', function(e) {
  e.preventDefault();
  let nota = document.getElementById('nota').value.trim();
  if (!selectedMascota) return;
  fetch('expediente.php?action=agregar', {
    method: 'POST',
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id_mascota=${selectedMascota}&nota=${encodeURIComponent(nota)}`
  })
  .then(r => r.json())
  .then(resp => {
    if(resp.success) {
      document.getElementById('nota').value = '';
      cargarHistorial();
    } else {
      alert('Error al agregar nota.');
    }
  });
});