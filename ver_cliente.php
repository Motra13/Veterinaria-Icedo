<?php
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido.");

$sql = "SELECT * FROM clientes WHERE id_cliente=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$cliente = $res->fetch_assoc();
if (!$cliente) die("Cliente no encontrado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ficha Cliente: <?= htmlspecialchars($cliente['nombre']) ?></title>
</head>
<body>
  <h1>Cliente: <?= htmlspecialchars($cliente['nombre']) ?></h1>
  <ul>
    <li><b>Teléfono:</b> <?= htmlspecialchars($cliente['telefono'] ?? '-') ?></li>
    <li><b>Correo:</b> <?= htmlspecialchars($cliente['correo'] ?? '-') ?></li>
    <li><b>Dirección:</b> <?= htmlspecialchars($cliente['direccion'] ?? '-') ?></li>
  </ul>
  <a href="javascript:window.close()">Cerrar</a>
</body>
</html>
