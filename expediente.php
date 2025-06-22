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

if ($action === 'buscar') {
  $term = '%' . ($_GET['term'] ?? '') . '%';
  $sql = "SELECT m.id_mascota, m.nombre_mascota, m.especie, cl.nombre AS dueno
          FROM mascotas m
          LEFT JOIN clientes cl ON m.id_cliente = cl.id_cliente
          WHERE m.nombre_mascota LIKE ? OR cl.nombre LIKE ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $term, $term);
  $stmt->execute();
  $res = $stmt->get_result();
  $out = [];
  while($row = $res->fetch_assoc()) $out[] = $row;
  echo json_encode($out);
  exit();
}

if ($action === 'info') {
  $id = $_GET['id_mascota'] ?? 0;
  $sql = "SELECT m.*, cl.nombre as dueno
          FROM mascotas m
          LEFT JOIN clientes cl ON m.id_cliente = cl.id_cliente
          WHERE m.id_mascota = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  echo json_encode($row ?: []);
  exit();
}

if ($action === 'historial') {
  $id = $_GET['id_mascota'] ?? 0;
  $sql = "SELECT ec.fecha, ec.nota, u.usuario
          FROM expediente_clinico ec
          LEFT JOIN usuarios u ON ec.id_usuario = u.id
          WHERE ec.id_mascota = ?
          ORDER BY ec.fecha DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $out = [];
  while($row = $res->fetch_assoc()) $out[] = $row;
  echo json_encode($out);
  exit();
}

if ($action === 'agregar') {
  $id_mascota = $_POST['id_mascota'] ?? 0;
  $nota = $_POST['nota'] ?? '';
  $id_usuario = $_SESSION['usuario_id'] ?? 1; // Modifica para obtener el id real del usuario en sesión
  if($id_mascota && $nota) {
    $sql = "INSERT INTO expediente_clinico (id_mascota, id_usuario, fecha, nota)
            VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $id_mascota, $id_usuario, $nota);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
  } else {
    echo json_encode(['success' => false]);
  }
  exit();
}

echo json_encode(['error'=>'Acción no válida']);
?>