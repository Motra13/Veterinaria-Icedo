<?php
header('Content-Type: application/json');
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
$q = trim($_GET['q'] ?? '');
if(strlen($q)<2) { echo json_encode(['error'=>'Búsqueda demasiado corta']); exit(); }

$like = "%$q%";
$resultados = [];

// Buscar en clientes
$sql = "SELECT id_cliente, nombre, telefono, correo FROM clientes
        WHERE nombre LIKE ? OR telefono LIKE ? OR correo LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'cliente';
  $resultados[] = $row;
}

// Buscar en mascotas (con nombre de dueño)
$sql = "SELECT m.id_mascota, m.nombre, m.especie, m.raza, c.id_cliente, c.nombre AS nombre_duenio
        FROM mascotas m
        LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
        WHERE m.nombre LIKE ? OR m.especie LIKE ? OR m.raza LIKE ? OR c.nombre LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'mascota';
  $resultados[] = $row;
}

// Buscar en productos
$sql = "SELECT id_producto, nombre_producto, categoria, descripcion FROM productos
        WHERE nombre_producto LIKE ? OR categoria LIKE ? OR descripcion LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'producto';
  $resultados[] = $row;
}

// Buscar en usuarios
$sql = "SELECT id_usuario, usuario, nombre_completo, correo FROM usuarios
        WHERE usuario LIKE ? OR nombre_completo LIKE ? OR correo LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'usuario';
  $resultados[] = $row;
}

// Buscar en ventas (folio o nombre de cliente)
$sql = "SELECT v.id_venta, v.fecha, v.total, c.nombre AS nombre_cliente
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
        WHERE v.id_venta LIKE ? OR c.nombre LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'venta';
  $resultados[] = $row;
}

echo json_encode(['resultados'=>$resultados]);
?>