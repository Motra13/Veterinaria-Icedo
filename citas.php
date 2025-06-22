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
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Listar todas las citas (formato compatible con FullCalendar)
if ($action === "all") {
    $sql = "SELECT c.id_cita, cl.nombre as cliente, m.nombre_mascota as mascota, c.fecha_cita as fecha, c.hora_cita as hora, c.motivo, c.estado, c.tags
            FROM citas c
            LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN mascotas m ON c.id_mascota = m.id_mascota";
    $result = $conn->query($sql);
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = [
            "id" => $row["id_cita"],
            "cliente" => $row["cliente"] ?? "",
            "mascota" => $row["mascota"] ?? "",
            "fecha" => $row["fecha"],
            "hora" => substr($row["hora"],0,5),
            "motivo" => $row["motivo"],
            "estado" => $row["estado"] ?: "Agendada",
            "tags" => $row["tags"] ?? "",
            "start" => $row["fecha"] . "T" . substr($row["hora"],0,5)
        ];
    }
    echo json_encode($citas);
    exit();
}

// --- (El resto del archivo es igual que tu versión anterior, sin cambios) ---

if ($action === "add") {
    $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
    $mascota = isset($_POST['mascota']) ? trim($_POST['mascota']) : '';
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $hora = isset($_POST['hora']) ? $_POST['hora'] : '';
    $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : '';
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    if ($cliente === '' || $mascota === '' || $fecha === '' || $hora === '' || $motivo === '') {
        echo json_encode(['success'=>false, "error"=>"Todos los campos son obligatorios."]); exit();
    }
    $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE nombre = ?");
    $stmt->bind_param("s", $cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row=$res->fetch_assoc()){
        $id_cliente = $row['id_cliente'];
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, fecha_registro) VALUES (?, NOW())");
        $stmt->bind_param("s", $cliente);
        $stmt->execute();
        $id_cliente = $stmt->insert_id;
    }
    $stmt = $conn->prepare("SELECT id_mascota FROM mascotas WHERE nombre_mascota = ? AND id_cliente = ?");
    $stmt->bind_param("si", $mascota, $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row=$res->fetch_assoc()){
        $id_mascota = $row['id_mascota'];
    } else {
        $stmt = $conn->prepare("INSERT INTO mascotas (id_cliente, nombre_mascota) VALUES (?, ?)");
        $stmt->bind_param("is", $id_cliente, $mascota);
        $stmt->execute();
        $id_mascota = $stmt->insert_id;
    }
    $stmt = $conn->prepare("SELECT id_cita FROM citas WHERE fecha_cita = ? AND hora_cita = ?");
    $stmt->bind_param("ss", $fecha, $hora);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        echo json_encode(['success'=>false, "error"=>"Ya hay una cita en esa fecha y hora."]);
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO citas (id_mascota, id_cliente, fecha_cita, hora_cita, motivo, estado, tags) VALUES (?, ?, ?, ?, ?, 'Agendada', ?)");
    $stmt->bind_param("iissss", $id_mascota, $id_cliente, $fecha, $hora, $motivo, $tags);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, "error"=>"No se pudo guardar la cita."]);
    }
    exit();
}

// --- autocomplete clientes, mascotas, horas, update_estado, detalle (igual que antes) ---

if ($action === "clientes") {
    $q = isset($_GET['q']) ? "%".$_GET['q']."%" : "%";
    $stmt = $conn->prepare("SELECT id_cliente, nombre FROM clientes WHERE nombre LIKE ? ORDER BY nombre LIMIT 10");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $res = $stmt->get_result();
    $clientes = [];
    while($row = $res->fetch_assoc()) {
        $clientes[] = ["id"=>$row["id_cliente"], "nombre"=>$row["nombre"]];
    }
    echo json_encode($clientes);
    exit();
}
if ($action === "mascotas") {
    $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
    $q = isset($_GET['q']) ? "%".$_GET['q']."%" : "%";
    $id_cliente = 0;
    if($cliente) {
        $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE nombre = ? LIMIT 1");
        $stmt->bind_param("s", $cliente);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row=$res->fetch_assoc()) {
            $id_cliente = $row["id_cliente"];
        }
    }
    if($id_cliente > 0) {
        $stmt = $conn->prepare("SELECT id_mascota, nombre_mascota FROM mascotas WHERE id_cliente = ? AND nombre_mascota LIKE ? ORDER BY nombre_mascota LIMIT 10");
        $stmt->bind_param("is", $id_cliente, $q);
    } else {
        $stmt = $conn->prepare("SELECT id_mascota, nombre_mascota FROM mascotas WHERE nombre_mascota LIKE ? ORDER BY nombre_mascota LIMIT 10");
        $stmt->bind_param("s", $q);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $mascotas = [];
    while($row = $res->fetch_assoc()) {
        $mascotas[] = ["id"=>$row["id_mascota"], "nombre"=>$row["nombre_mascota"]];
    }
    echo json_encode($mascotas);
    exit();
}
if ($action === "horas_ocupadas") {
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
    $sql = "SELECT hora_cita FROM citas WHERE fecha_cita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $res = $stmt->get_result();
    $horas = [];
    while($row = $res->fetch_assoc()) {
        $horas[] = substr($row["hora_cita"],0,5);
    }
    echo json_encode($horas);
    exit();
}
if ($action === "update_estado") {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    if ($id > 0 && in_array($estado, ['Agendada', 'Pendiente', 'Completada', 'Cancelada'])) {
        $stmt = $conn->prepare("UPDATE citas SET estado=? WHERE id_cita=?");
        $stmt->bind_param("si", $estado, $id);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok]);
    } else {
        echo json_encode(['success'=>false, "error"=>"Datos inválidos"]);
    }
    exit();
}
if ($action === "detalle") {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sql = "SELECT c.*, cl.nombre as cliente, m.nombre_mascota as mascota FROM citas c LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente LEFT JOIN mascotas m ON c.id_mascota = m.id_mascota WHERE c.id_cita=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) {
        echo json_encode([
            "id" => $row["id_cita"],
            "cliente" => $row["cliente"] ?? "",
            "mascota" => $row["mascota"] ?? "",
            "fecha" => $row["fecha_cita"],
            "hora" => substr($row["hora_cita"],0,5),
            "motivo" => $row["motivo"],
            "estado" => $row["estado"],
            "tags" => $row["tags"] ?? "",
            "creada" => $row["creada"] ?? "",
        ]);
    } else {
        echo json_encode(["error"=>"No encontrada"]);
    }
    exit();
}
echo json_encode(['success'=>false, "error"=>"Acción no válida."]);
?>