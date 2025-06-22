<?php
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido.");

$sql = "SELECT v.*, c.nombre AS nombre_cliente FROM ventas v LEFT JOIN clientes c ON v.id_cliente = c.id_cliente WHERE v.id_venta=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$venta = $res->fetch_assoc();
if (!$venta) die("Venta no encontrada.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Venta #<?= htmlspecialchars($venta['id_venta']) ?></title>
</head>
<body>
  <h1>Venta #<?= htmlspecialchars($venta['id_venta']) ?></h1>
  <ul>
    <li><b>Cliente:</b> <?= htmlspecialchars($venta['nombre_cliente']) ?></li>
    <li><b>Fecha:</b> <?= htmlspecialchars($venta['fecha_venta']) ?></li>
    <li><b>Total:</b> $<?= htmlspecialchars(number_format($venta['total'], 2)) ?></li>
  </ul>
  <a href="javascript:window.close()">Cerrar</a>
</body>
</html>
