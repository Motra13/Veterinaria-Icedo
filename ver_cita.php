<?php
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido.");

$sql = "SELECT c.*, cl.nombre AS cliente, m.nombre_mascota AS mascota
        FROM citas c
        LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN mascotas m ON c.id_mascota = m.id_mascota
        WHERE c.id_cita=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$cita = $res->fetch_assoc();
if (!$cita) die("Cita no encontrada.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cita de <?= htmlspecialchars($cita['cliente']) ?></title>
</head>
<body>
  <h1>Cita</h1>
  <ul>
    <li><b>Cliente:</b> <?= htmlspecialchars($cita['cliente']) ?></li>
    <li><b>Mascota:</b> <?= htmlspecialchars($cita['mascota']) ?></li>
    <li><b>Fecha:</b> <?= htmlspecialchars($cita['fecha_cita']) ?></li>
    <li><b>Hora:</b> <?= htmlspecialchars($cita['hora_cita']) ?></li>
    <li><b>Motivo:</b> <?= htmlspecialchars($cita['motivo']) ?></li>
  </ul>
  <a href="javascript:window.close()">Cerrar</a>
</body>
</html>
