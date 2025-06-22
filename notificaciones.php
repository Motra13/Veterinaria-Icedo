<?php
session_start();
header('Content-Type: application/json');

// DEBUG: Forzar usuario en sesión para pruebas
if (!isset($_SESSION['usuario_id'])) $_SESSION['usuario_id'] = 1;
$usuario_id = $_SESSION['usuario_id'];

$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
$action = $_GET['action'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;


// Listar notificaciones y recordatorios
if ($action === 'all') {
  // NOTIFICACIONES (automáticas)
  $notificaciones = [];
  $sql = "SELECT mensaje, fecha FROM notificaciones WHERE usuario_id=? OR usuario_id IS NULL ORDER BY fecha DESC LIMIT 15";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $usuario_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $fecha = new DateTime($row['fecha']);
    $ahora = new DateTime();
    $diff = $ahora->getTimestamp() - $fecha->getTimestamp();
    if ($diff < 60) $time = "Hace $diff seg";
    elseif ($diff < 3600) $time = "Hace ".floor($diff/60)." min";
    elseif ($diff < 86400) $time = "Hace ".floor($diff/3600)." h";
    else $time = $fecha->format('d/m/Y H:i');
    $notificaciones[] = [
      "msg" => $row['mensaje'],
      "time" => $time
    ];
  }
  // RECORDATORIOS (solo los del usuario)
  $recordatorios = [];
  $sql2 = "SELECT id, mensaje, fecha FROM recordatorios WHERE usuario_id=? ORDER BY fecha ASC LIMIT 15";
  $stmt2 = $conn->prepare($sql2);
  $stmt2->bind_param("i", $usuario_id);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  while ($row = $res2->fetch_assoc()) {
    $fecha = new DateTime($row['fecha']);
    $hoy = new DateTime();
    $date = ($fecha->format('Y-m-d') == $hoy->format('Y-m-d')) ? "Hoy" : $fecha->format('d/m/Y');
    $recordatorios[] = [
      "id" => $row['id'],
      "msg" => $row['mensaje'],
      "date" => $date
    ];
  }
  echo json_encode([
    "notificaciones" => $notificaciones,
    "recordatorios" => $recordatorios
  ]);
  exit();
}

// Agregar recordatorio
if ($action === 'add_recordatorio' && $usuario_id) {
  $mensaje = trim($_POST['mensaje'] ?? '');
  $fecha = $_POST['fecha'] ?? '';
  
  // DEBUG
  file_put_contents('debug.log', "MENSAJE: $mensaje\nFECHA: $fecha\nUSUARIO: $usuario_id\n", FILE_APPEND);

  if ($mensaje && $fecha) {
    $sql = "INSERT INTO recordatorios (usuario_id, mensaje, fecha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $usuario_id, $mensaje, $fecha);

    $ok = $stmt->execute();
    file_put_contents('debug.log', "SQL OK: $ok\n", FILE_APPEND);

    echo json_encode(['success' => $ok]);
    exit();
  }
  echo json_encode(['success' => false]);
  exit();
}

// Eliminar recordatorio
if ($action === 'delete_recordatorio' && $usuario_id) {
  $id = intval($_GET['id'] ?? 0);
  $sql = "DELETE FROM recordatorios WHERE id=? AND usuario_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $usuario_id);
  $ok = $stmt->execute();
  echo json_encode(['success' => $ok]);
  exit();
}

echo json_encode(['error'=>'Acción no válida']);
?>