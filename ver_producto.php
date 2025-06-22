<?php
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido.");

$sql = "SELECT * FROM productos WHERE id_producto=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
if (!$producto) die("Producto no encontrado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ficha Producto: <?= htmlspecialchars($producto['nombre_producto']) ?></title>
</head>
<body>
  <h1>Producto: <?= htmlspecialchars($producto['nombre_producto']) ?></h1>
  <ul>
    <li><b>Categoría:</b> <?= htmlspecialchars($producto['categoria']) ?></li>
    <li><b>Descripción:</b> <?= htmlspecialchars($producto['descripcion']) ?></li>
    <li><b>Stock:</b> <?= htmlspecialchars($producto['stock']) ?></li>
    <li><b>Fecha caducidad:</b> <?= htmlspecialchars($producto['fecha_caducidad']) ?></li>
  </ul>
  <a href="javascript:window.close()">Cerrar</a>
</body>
</html>
