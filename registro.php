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

$entidad = isset($_GET['entidad']) ? $_GET['entidad'] : '';

if ($entidad === 'cliente') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    if ($nombre === '' || $apellido === '') {
        echo json_encode(['success'=>false, 'error'=>'Nombre y apellido son obligatorios.']); exit();
    }
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellido, telefono, correo, direccion, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $nombre, $apellido, $telefono, $correo, $direccion);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'error'=>'No se pudo registrar el cliente.']);
    exit();
}

if ($entidad === 'mascota') {
    $nombre_mascota = $_POST['nombre_mascota'] ?? '';
    $especie = $_POST['especie'] ?? '';
    $raza = $_POST['raza'] ?? '';
    $edad = $_POST['edad'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $sexo = $_POST['sexo'] ?? '';
    $cliente_nombre = $_POST['cliente_nombre'] ?? '';
    if ($nombre_mascota === '' || $especie === '') {
        echo json_encode(['success'=>false, 'error'=>'Nombre y especie son obligatorios.']); exit();
    }
    // Buscar cliente por nombre simple (puedes mejorar esto para casos de duplicados)
    $id_cliente = null;
    if ($cliente_nombre !== '') {
        $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE nombre = ? LIMIT 1");
        $stmt->bind_param("s", $cliente_nombre);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) $id_cliente = $row['id_cliente'];
    }
    $stmt = $conn->prepare("INSERT INTO mascotas (id_cliente, nombre_mascota, especie, raza, edad, peso, sexo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssids", $id_cliente, $nombre_mascota, $especie, $raza, $edad, $peso, $sexo);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'error'=>'No se pudo registrar la mascota.']);
    exit();
}

if ($entidad === 'proveedor') {
    $nombre_proveedor = $_POST['nombre_proveedor'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    if ($nombre_proveedor === '') {
        echo json_encode(['success'=>false, 'error'=>'El nombre del proveedor es obligatorio.']); exit();
    }
    $stmt = $conn->prepare("INSERT INTO proveedores (nombre_proveedor, telefono, correo, direccion) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre_proveedor, $telefono, $correo, $direccion);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'error'=>'No se pudo registrar el proveedor.']);
    exit();
}

if ($entidad === 'empleado') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    if ($nombre === '' || $apellido === '') {
        echo json_encode(['success'=>false, 'error'=>'Nombre y apellido son obligatorios.']); exit();
    }
    $stmt = $conn->prepare("INSERT INTO empleados (nombre, apellido, correo, telefono, cargo, fecha_contratacion) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $telefono, $cargo);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'error'=>'No se pudo registrar el empleado.']);
    exit();
}

echo json_encode(['success'=>false, 'error'=>'Entidad no válida.']);
?>