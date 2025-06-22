<?php
session_start();
header('Content-Type: application/json');

// DEBUG (eliminar en producción): forzar sesión
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

// Conexión a la base de datos
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit();
}

// Acción solicitada
$action = $_GET['action'] ?? '';

// === ACCIÓN: Obtener notificaciones y recordatorios ===
if ($action === 'all') {
    // NOTIFICACIONES
    $notificaciones = [];
    $sql = "SELECT mensaje, fecha FROM notificaciones WHERE usuario_id = ? OR usuario_id IS NULL ORDER BY fecha DESC LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $fecha = new DateTime($row['fecha']);
        $ahora = new DateTime();
        $diff = $ahora->getTimestamp() - $fecha->getTimestamp();
        if ($diff < 60) {
            $time = "Hace $diff seg";
        } elseif ($diff < 3600) {
            $time = "Hace " . floor($diff / 60) . " min";
        } elseif ($diff < 86400) {
            $time = "Hace " . floor($diff / 3600) . " h";
        } else {
            $time = $fecha->format('d/m/Y H:i');
        }

        $notificaciones[] = [
            "msg" => $row['mensaje'],
            "time" => $time
        ];
    }

    // RECORDATORIOS
    $recordatorios = [];
    $sql2 = "SELECT id, mensaje, fecha FROM recordatorios WHERE usuario_id = ? ORDER BY fecha ASC LIMIT 15";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $usuario_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($row = $res2->fetch_assoc()) {
        $fecha = new DateTime($row['fecha']);
        $hoy = new DateTime();
        $date = ($fecha->format('Y-m-d') === $hoy->format('Y-m-d')) ? "Hoy" : $fecha->format('d/m/Y');
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

// === ACCIÓN: Agregar recordatorio ===
if ($action === 'add_recordatorio') {
    $mensaje = trim($_POST['mensaje'] ?? '');
    $fecha = $_POST['fecha'] ?? '';

    if ($mensaje && $fecha) {
        $sql = "INSERT INTO recordatorios (usuario_id, mensaje, fecha) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $mensaje, $fecha);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
    exit();
}

// === ACCIÓN: Eliminar recordatorio ===
if ($action === 'delete_recordatorio') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $sql = "DELETE FROM recordatorios WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $usuario_id);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
    }
    exit();
}

// Acción no válida
echo json_encode(['error' => 'Acción no válida']);
?>
