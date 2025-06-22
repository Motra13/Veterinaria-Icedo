<?php
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido.");

$sql = "SELECT m.*, c.nombre AS nombre_cliente FROM mascotas m LEFT JOIN clientes c ON m.id_cliente = c.id_cliente WHERE m.id_mascota=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$mascota = $res->fetch_assoc();
if (!$mascota) die("Mascota no encontrada.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ficha Mascota: <?= htmlspecialchars($mascota['nombre_mascota']) ?></title>
</head>
<body>
  <h1>Mascota: <?= htmlspecialchars($mascota['nombre_mascota']) ?></h1>
  <ul>
    <li><b>Especie:</b> <?= htmlspecialchars($mascota['especie']) ?></li>
    <li><b>Raza:</b> <?= htmlspecialchars($mascota['raza']) ?></li>
    <li><b>Sexo:</b> <?= htmlspecialchars($mascota['sexo']) ?></li>
    <li><b>Color:</b> <?= htmlspecialchars($mascota['color']) ?></li>
    <li><b>Dueño:</b> <?= htmlspecialchars($mascota['nombre_cliente']) ?></li>
  </ul>
  <a href="javascript:window.close()">Cerrar</a>
</body>
</html>
