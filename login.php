<?php
session_start();

// Configuración de conexión
$host = "sql108.infinityfree.com";
$user = "if0_39292268";
$pass = "y05lQCFT6T";
$db   = "if0_39292268_veterinaria";

// Obtener datos del formulario
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

if ($usuario === '' || $contrasena === '') {
    header('Location: login.html?error=Por+favor+rellena+todos+los+campos');
    exit();
}

// Conectar a la base de datos (¡CORREGIDO!)
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Buscar el usuario
$sql = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $fila = $result->fetch_assoc();
    // Validar contraseña (en tu base es texto plano: NO recomendado para producción)
    if ($contrasena === $fila['contrasena']) {
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['nombre'] = $fila['nombre'];
        header("Location: panel.html");
        exit();
    }
}

// Si llega aquí, es error
header('Location: login.html?error=Usuario+o+contraseña+incorrectos');
exit();
?>