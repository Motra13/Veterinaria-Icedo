<?php
session_start();
header('Content-Type: application/json');

// Conexión MySQL
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'veterinaria';
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
  echo json_encode(['error'=>'Error de conexión a la base de datos']); exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'session') {
  echo json_encode([
    "usuario" => $_SESSION['nombre'] ?? "Usuario",
    "rol" => $_SESSION['rol'] ?? "Empleado"
  ]);
  exit();
}

if ($action === 'notificaciones') {
  $usuario_id = $_SESSION['usuario_id'] ?? null;

  // NOTIFICACIONES
  $notificaciones = [];
  $sql = "SELECT mensaje, fecha FROM notificaciones WHERE usuario_id=? OR usuario_id IS NULL ORDER BY fecha DESC LIMIT 10";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $usuario_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    // Calcula el tiempo transcurrido (puedes mejorar este formato)
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

  // RECORDATORIOS
  $recordatorios = [];
  $sql2 = "SELECT mensaje, fecha FROM recordatorios WHERE usuario_id=? OR usuario_id IS NULL ORDER BY fecha ASC LIMIT 10";
  $stmt2 = $conn->prepare($sql2);
  $stmt2->bind_param("i", $usuario_id);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  while ($row = $res2->fetch_assoc()) {
    $fecha = new DateTime($row['fecha']);
    $hoy = new DateTime();
    $date = ($fecha->format('Y-m-d') == $hoy->format('Y-m-d')) ? "Hoy" : $fecha->format('d/m/Y');
    $recordatorios[] = [
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

if ($action === 'agenda') {
  $fecha = $_GET['day'] ?? date('Y-m-d');
  $sql = "SELECT c.hora_cita, c.motivo, cl.nombre as cliente, m.nombre_mascota as mascota
          FROM citas c
          LEFT JOIN clientes cl ON c.id_cliente=cl.id_cliente
          LEFT JOIN mascotas m ON c.id_mascota=m.id_mascota
          WHERE c.fecha_cita = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $fecha);
  $stmt->execute();
  $res = $stmt->get_result();
  $events = [];
  while ($row = $res->fetch_assoc()) {
    $start = $fecha . 'T' . $row['hora_cita'];
    $end = date("Y-m-d\TH:i:s", strtotime("$start +30 minutes"));
    $events[] = [
      "title" => "Cita: " . $row['cliente'] . " - " . $row['mascota'],
      "start" => $start,
      "end" => $end,
      "color" => "#b30000"
    ];
  }
  echo json_encode($events);
  exit();
}

// Por defecto, error
echo json_encode(['error'=>'Acción no válida']);
?>