<?php
session_start();
header('Content-Type: application/json');

// Conexión MySQL
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$action = $_GET['action'] ?? '';

if ($action === 'session') {
  echo json_encode([
    "usuario" => $_SESSION['nombre'] ?? "Usuario",
    "rol" => $_SESSION['rol'] ?? "Empleado"
  ]);
  exit();
}

// NUEVO: Widgets rápidos
if ($action === 'widgets') {
  // Totales
  $totales = [];
  $totales['clientes'] = $conn->query("SELECT COUNT(*) FROM clientes")->fetch_row()[0];
  $totales['mascotas'] = $conn->query("SELECT COUNT(*) FROM mascotas")->fetch_row()[0];
  $totales['productos'] = $conn->query("SELECT COUNT(*) FROM productos")->fetch_row()[0];
  $totales['citas_hoy'] = $conn->query("SELECT COUNT(*) FROM citas WHERE fecha_cita = CURDATE()")->fetch_row()[0];
  $totales['seguimientos_pendientes'] = $conn->query("SELECT COUNT(*) FROM seguimientos WHERE estado='pendiente'")->fetch_row()[0];
  $totales['productos_stock_bajo'] = $conn->query("SELECT COUNT(*) FROM productos WHERE stock <= 10")->fetch_row()[0];
  $totales['productos_caducan_pronto'] = $conn->query("SELECT COUNT(*) FROM productos WHERE fecha_caducidad IS NOT NULL AND fecha_caducidad != '0000-00-00' AND fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_row()[0];
  echo json_encode($totales);
  exit();
}

if ($action === 'notificaciones') {
  $usuario_id = $_SESSION['usuario_id'] ?? null;

  // NOTIFICACIONES automáticas y nuevas alertas
  $notificaciones = [];

  // 1. Citas próximas hoy/mañana
  $sql = "SELECT c.hora_cita, cl.nombre as cliente, m.nombre_mascota as mascota
          FROM citas c
          LEFT JOIN clientes cl ON c.id_cliente=cl.id_cliente
          LEFT JOIN mascotas m ON c.id_mascota=m.id_mascota
          WHERE c.fecha_cita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
          ORDER BY c.fecha_cita, c.hora_cita";
  $res = $conn->query($sql);
  while ($row = $res->fetch_assoc()) {
    $notificaciones[] = [
      "msg" => "Cita próxima: {$row['cliente']} / {$row['mascota']} a las {$row['hora_cita']}",
      "time" => "Próximas 24h"
    ];
  }

  // 2. Productos con stock bajo
  $sql = "SELECT nombre_producto, stock FROM productos WHERE stock <= 10 ORDER BY stock ASC";
  $res = $conn->query($sql);
  while ($row = $res->fetch_assoc()) {
    $notificaciones[] = [
      "msg" => "Stock bajo: {$row['nombre_producto']} (quedan {$row['stock']})",
      "time" => "Inventario"
    ];
  }

  // 3. Productos por caducar
  $sql = "SELECT nombre_producto, fecha_caducidad FROM productos WHERE fecha_caducidad IS NOT NULL AND fecha_caducidad != '0000-00-00' AND fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY fecha_caducidad ASC";
  $res = $conn->query($sql);
  while ($row = $res->fetch_assoc()) {
    $dias = (strtotime($row['fecha_caducidad']) - strtotime(date('Y-m-d'))) / 86400;
    $notificaciones[] = [
      "msg" => "Caduca pronto: {$row['nombre_producto']} (en " . round($dias) . " días)",
      "time" => $row['fecha_caducidad']
    ];
  }

  // 4. Seguimientos pendientes y atrasados
  $sql = "SELECT cliente, mascota, motivo, fecha_proximo, estado FROM seguimientos WHERE estado IN ('pendiente','atrasado') ORDER BY fecha_proximo ASC";
  $res = $conn->query($sql);
  while ($row = $res->fetch_assoc()) {
    $notificaciones[] = [
      "msg" => "Seguimiento: {$row['cliente']} / {$row['mascota']} - {$row['motivo']} ({$row['estado']})",
      "time" => $row['fecha_proximo']
    ];
  }

  // RECORDATORIOS (solo los del usuario)
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
  $start = $_GET['start'] ?? date('Y-m-d');
  $end = $_GET['end'] ?? date('Y-m-d');
  $events = [];

  // Trae citas, caducidades y seguimientos entre $start y $end
  $sql = "SELECT c.hora_cita, c.fecha_cita, c.motivo, cl.nombre as cliente, m.nombre_mascota as mascota
          FROM citas c
          LEFT JOIN clientes cl ON c.id_cliente=cl.id_cliente
          LEFT JOIN mascotas m ON c.id_mascota=m.id_mascota
          WHERE c.fecha_cita >= ? AND c.fecha_cita < ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $start, $end);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $fecha = $row['fecha_cita'];
    $startTime = $fecha . 'T' . $row['hora_cita'];
    $endTime = date("Y-m-d\TH:i:s", strtotime("$startTime +30 minutes"));
    $events[] = [
      "title" => "Cita: " . $row['cliente'] . " - " . $row['mascota'],
      "start" => $startTime,
      "end" => $endTime,
      "color" => "#b30000",
      "description" => $row['motivo'],
      "type" => "cita"
    ];
  }
  // (Si quieres, agrega aquí productos por caducar y seguimientos...)
  echo json_encode($events);
  exit();
}

// Por defecto, error
echo json_encode(['error'=>'Acción no válida']);
?>