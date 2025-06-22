<?php
session_start();
header('Content-Type: application/json');

$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$action = $_GET['action'] ?? '';

if ($action === 'listar') {
  $sql = "SELECT id_producto, nombre_producto, categoria, descripcion, proveedor, fecha_caducidad, stock, precio, fecha_registro FROM productos ORDER BY nombre_producto ASC";
  $res = $conn->query($sql);
  $productos = [];
  while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
  }
  echo json_encode(['productos' => $productos]);
  exit();
}

if ($action === 'agregar') {
  $nombre = trim($_POST['nombre_producto'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $proveedor = trim($_POST['proveedor'] ?? '');
  $fecha_caducidad = $_POST['fecha_caducidad'] ?? null;
  $stock = intval($_POST['stock'] ?? 0);
  $precio = floatval($_POST['precio'] ?? 0);
  if ($nombre && $stock >= 0 && $precio >= 0) {
    $stmt = $conn->prepare("INSERT INTO productos (nombre_producto, categoria, descripcion, proveedor, fecha_caducidad, stock, precio) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssid", $nombre, $categoria, $descripcion, $proveedor, $fecha_caducidad, $stock, $precio);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
    exit();
  }
  echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
  exit();
}

if ($action === 'editar') {
  $id = intval($_POST['id_producto'] ?? 0);
  $nombre = trim($_POST['nombre_producto'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $proveedor = trim($_POST['proveedor'] ?? '');
  $fecha_caducidad = $_POST['fecha_caducidad'] ?? null;
  $stock = intval($_POST['stock'] ?? 0);
  $precio = floatval($_POST['precio'] ?? 0);
  if ($id && $nombre && $stock >= 0 && $precio >= 0) {
    $stmt = $conn->prepare("UPDATE productos SET nombre_producto=?, categoria=?, descripcion=?, proveedor=?, fecha_caducidad=?, stock=?, precio=? WHERE id_producto=?");
    $stmt->bind_param("ssssssdi", $nombre, $categoria, $descripcion, $proveedor, $fecha_caducidad, $stock, $precio, $id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
    exit();
  }
  echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
  exit();
}

if ($action === 'eliminar') {
  $id = intval($_POST['id_producto'] ?? 0);
  if ($id) {
    $stmt = $conn->prepare("DELETE FROM productos WHERE id_producto=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
    exit();
  }
  echo json_encode(['success' => false, 'error' => 'ID inválido']);
  exit();
}

echo json_encode(['error'=>'Acción no válida']);
?>