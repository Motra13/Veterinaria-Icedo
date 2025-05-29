function renderInventario(tabla) {
  const tbody = document.getElementById('inventario-body');
  if (!tabla.length) {
    tbody.innerHTML = `<tr><td colspan="9">Sin productos en inventario</td></tr>`;
    return;
  }
  tbody.innerHTML = tabla.map(prod => `
    <tr>
      <td>${prod.nombre_producto}</td>
      <td>${prod.categoria || '-'}</td>
      <td>${prod.descripcion || '-'}</td>
      <td>${prod.proveedor || '-'}</td>
      <td>${prod.fecha_caducidad || '-'}</td>
      <td>${prod.stock}</td>
      <td>$${parseFloat(prod.precio).toFixed(2)}</td>
      <td>${prod.fecha_registro ? prod.fecha_registro.substring(0,16).replace('T',' ') : '-'}</td>
      <td>
        <button onclick="editarProducto(${prod.id_producto})">Editar</button>
        <button onclick="eliminarProducto(${prod.id_producto})" style="color:#b30000;">Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function cargarInventario() {
  fetch('inventario.php?action=listar')
    .then(res => res.json())
    .then(data => renderInventario(data.productos || []));
}

document.getElementById('form-producto').addEventListener('submit', function(e) {
  e.preventDefault();
  const id = document.getElementById('prod-id').value;
  const nombre_producto = document.getElementById('prod-nombre').value.trim();
  const categoria = document.getElementById('prod-categoria').value.trim();
  const descripcion = document.getElementById('prod-descripcion').value.trim();
  const proveedor = document.getElementById('prod-proveedor').value.trim();
  const fecha_caducidad = document.getElementById('prod-caducidad').value;
  const stock = document.getElementById('prod-stock').value;
  const precio = document.getElementById('prod-precio').value;
  const datos = new URLSearchParams({ nombre_producto, categoria, descripcion, proveedor, fecha_caducidad, stock, precio });
  let url, method;
  if (id) {
    datos.append('id_producto', id);
    url = 'inventario.php?action=editar';
    method = 'POST';
  } else {
    url = 'inventario.php?action=agregar';
    method = 'POST';
  }
  fetch(url, { method, body: datos })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        this.reset();
        document.getElementById('btn-guardar').textContent = "Agregar producto";
        document.getElementById('btn-cancelar').style.display = "none";
        cargarInventario();
      } else {
        alert("No se pudo guardar: " + (resp.error || ""));
      }
    });
});

window.editarProducto = function(id) {
  fetch('inventario.php?action=listar')
    .then(res => res.json())
    .then(data => {
      const prod = (data.productos || []).find(p => p.id_producto == id);
      if (prod) {
        document.getElementById('prod-id').value = prod.id_producto;
        document.getElementById('prod-nombre').value = prod.nombre_producto;
        document.getElementById('prod-categoria').value = prod.categoria || '';
        document.getElementById('prod-descripcion').value = prod.descripcion || '';
        document.getElementById('prod-proveedor').value = prod.proveedor || '';
        document.getElementById('prod-caducidad').value = prod.fecha_caducidad || '';
        document.getElementById('prod-stock').value = prod.stock;
        document.getElementById('prod-precio').value = prod.precio;
        document.getElementById('btn-guardar').textContent = "Guardar cambios";
        document.getElementById('btn-cancelar').style.display = "";
      }
    });
}

window.eliminarProducto = function(id) {
  if (!confirm("¿Eliminar este producto del inventario?")) return;
  fetch('inventario.php?action=eliminar', {
    method: 'POST',
    body: new URLSearchParams({ id_producto: id })
  })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) cargarInventario();
      else alert("No se pudo eliminar.");
    });
}

// Cancelar edición
document.getElementById('btn-cancelar').addEventListener('click', function() {
  document.getElementById('form-producto').reset();
  document.getElementById('prod-id').value = "";
  document.getElementById('btn-guardar').textContent = "Agregar producto";
  this.style.display = "none";
});

cargarInventario();