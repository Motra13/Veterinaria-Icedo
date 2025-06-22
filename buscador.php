<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
if(strlen($q) < 2) {
  echo json_encode(['resultados' => [['tipo' => 'error', 'mensaje' => 'Por favor, escribe al menos 2 caracteres.']]]);
  exit();
}

$like = "%$q%";
$resultados = [];

// === CLIENTES ===
$sql = "SELECT id_cliente, nombre, telefono, correo FROM clientes
        WHERE nombre LIKE ? OR telefono LIKE ? OR correo LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'clientes: '.$conn->error]); exit(); }
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'cliente';
  $resultados[] = $row;
}

// === MASCOTAS ===
$sql = "SELECT m.id_mascota, m.nombre_mascota, m.especie, m.raza, c.id_cliente, c.nombre AS nombre_duenio
        FROM mascotas m
        LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
        WHERE m.nombre_mascota LIKE ? OR m.especie LIKE ? OR m.raza LIKE ? OR c.nombre LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'mascotas: '.$conn->error]); exit(); }
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'mascota';
  $resultados[] = $row;
}

// === PRODUCTOS ===
$sql = "SELECT id_producto, nombre_producto, categoria, descripcion, stock, fecha_caducidad FROM productos
        WHERE nombre_producto LIKE ? OR categoria LIKE ? OR descripcion LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'productos: '.$conn->error]); exit(); }
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'producto';
  $resultados[] = $row;
}

// === USUARIOS ===
$sql = "SELECT id, usuario, nombre FROM usuarios
        WHERE usuario LIKE ? OR nombre LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'usuarios: '.$conn->error]); exit(); }
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'usuario';
  $resultados[] = $row;
}

// === VENTAS ===
$sql = "SELECT v.id_venta, v.fecha_venta, v.total, c.nombre AS nombre_cliente
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
        WHERE v.id_venta LIKE ? OR c.nombre LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'ventas: '.$conn->error]); exit(); }
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'venta';
  $resultados[] = $row;
}

// === CITAS ===
$sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, c.motivo, cl.nombre AS cliente, m.nombre_mascota AS mascota
        FROM citas c
        LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN mascotas m ON c.id_mascota = m.id_mascota
        WHERE c.motivo LIKE ? OR cl.nombre LIKE ? OR m.nombre_mascota LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'citas: '.$conn->error]); exit(); }
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'cita';
  $resultados[] = $row;
}

// === ARCHIVOS ===
$sql = "SELECT id_archivo, nombre, `tipo`, extension, fecha_subida FROM archivos
        WHERE nombre LIKE ? OR `tipo` LIKE ? OR extension LIKE ? OR tags LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['error' => 'archivos: ' . $conn->error]);
  exit();
}
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$rs = $stmt->get_result();
while($row = $rs->fetch_assoc()) {
  $row['tipo'] = 'archivo';
  $resultados[] = $row;
}


echo json_encode(['resultados' => $resultados]);
?>
