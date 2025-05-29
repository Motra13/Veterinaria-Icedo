<?php
header('Content-Type: application/json');
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'veterinaria';
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) { echo json_encode(['error'=>'DB error']); exit(); }

$action = $_GET['action'] ?? '';

function get_extension($filename) {
  return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// SUBIDA DE ARCHIVOS
if($action === 'subir') {
  $errors = [];
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $tags = trim($_POST['tags'] ?? '');
  $tipo = trim($_POST['tipo'] ?? '');
  $fecha_asociada = $_POST['fecha_asociada'] ?? null;
  $relacion = trim($_POST['relacion'] ?? '');
  $usuario_subio = 1; // ID del usuario (debería venir de la sesión)
  $allowed_ext = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx','txt','zip','rar'];
  if(!isset($_FILES['archivo']) || $_FILES['archivo']['error']!=0){
      echo json_encode(['success'=>false,'error'=>'Error en archivo']);
      exit();
  }
  $ext = get_extension($_FILES['archivo']['name']);
  if(!in_array($ext, $allowed_ext)) {
    echo json_encode(['success'=>false,'error'=>'Tipo de archivo no permitido']);
    exit();
  }
  $filename = date('YmdHis').'_'.uniqid().'.'.$ext;
  $dest = __DIR__.'/uploads/'.$filename;
  if(!is_dir(__DIR__.'/uploads/')) mkdir(__DIR__.'/uploads/');
  if(move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
    // Insertar en BD
    $stmt = $conn->prepare("INSERT INTO archivos (nombre, descripcion, archivo, tipo, extension, tags, fecha_subida, fecha_asociada, relacion, usuario_subio)
      VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
    $stmt->bind_param("sssssssii", $nombre, $descripcion, $filename, $tipo, $ext, $tags, $fecha_asociada, $relacion, $usuario_subio);
    $ok = $stmt->execute();
    echo json_encode(['success'=>$ok]);
    exit();
  } else {
    echo json_encode(['success'=>false,'error'=>'No se pudo subir']);
    exit();
  }
}

// LISTAR ARCHIVOS (con búsqueda, paginación, filtros)
if($action === 'listar') {
  $buscar = trim($_GET['buscar'] ?? '');
  $tipo = trim($_GET['tipo'] ?? '');
  $orden = $_GET['orden'] ?? 'fecha_subida DESC';
  $pagina = intval($_GET['pagina'] ?? 1);
  $limite = 20; $offset = ($pagina-1)*$limite;
  $where = [];
  $params = [];
  $types = "";

  if($buscar) {
    $where[] = "(nombre LIKE ? OR descripcion LIKE ? OR tags LIKE ?)";
    $params[] = "%$buscar%"; $params[] = "%$buscar%"; $params[] = "%$buscar%";
    $types .= "sss";
  }
  if($tipo) {
    $where[] = "tipo = ?";
    $params[] = $tipo;
    $types .= "s";
  }
  $sqlf = $where ? "WHERE ".implode(" AND ", $where) : "";
  // Total para paginación
  $sql_count = "SELECT COUNT(*) as total FROM archivos $sqlf";
  $stmt = $conn->prepare($sql_count);
  if($types) $stmt->bind_param($types, ...$params);
  $stmt->execute(); $res = $stmt->get_result(); $total = $res->fetch_assoc()['total']??0;
  $paginas = ceil($total/$limite);
  // Listar archivos
  $sql = "SELECT * FROM archivos $sqlf ORDER BY $orden LIMIT $limite OFFSET $offset";
  $stmt = $conn->prepare($sql);
  if($types) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $archivos = [];
  while($row = $res->fetch_assoc()){
    $row['url'] = 'uploads/'.$row['archivo'];
    $archivos[] = $row;
  }
  // Estadísticas
  $sql_est = "SELECT tipo, COUNT(*) as total FROM archivos GROUP BY tipo";
  $est = $conn->query($sql_est);
  $estadisticas = [];
  while($row=$est->fetch_assoc()) $estadisticas[] = $row;
  echo json_encode(['archivos'=>$archivos,'paginas'=>$paginas,'total'=>$total,'estadisticas'=>$estadisticas]);
  exit();
}

// ELIMINAR ARCHIVO
if($action==="eliminar") {
  $id = intval($_POST['id']??0);
  $sql = "SELECT archivo FROM archivos WHERE id_archivo=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i',$id);
  $stmt->execute(); $res = $stmt->get_result();
  if($ar = $res->fetch_assoc()) {
    $file = __DIR__.'/uploads/'.$ar['archivo'];
    if(is_file($file)) unlink($file);
    $stmt = $conn->prepare("DELETE FROM archivos WHERE id_archivo=?");
    $stmt->bind_param('i',$id);
    $ok = $stmt->execute();
    echo json_encode(['success'=>$ok]);
    exit();
  }
  echo json_encode(['success'=>false,'error'=>'No encontrado']);
  exit();
}

// DESCARGA DIRECTA
if($action==="descargar") {
  $id = intval($_GET['id']??0);
  $sql = "SELECT archivo, nombre, extension FROM archivos WHERE id_archivo=?";
  $stmt = $conn->prepare($sql); $stmt->bind_param('i',$id); $stmt->execute();
  $res = $stmt->get_result();
  if($ar = $res->fetch_assoc()) {
    $file = __DIR__.'/uploads/'.$ar['archivo'];
    if(is_file($file)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.$ar['nombre'].'.'.$ar['extension'].'"');
      header('Content-Length: ' . filesize($file));
      readfile($file); exit();
    }
  }
  echo "Archivo no encontrado."; exit();
}

// PREVIEW (devuelve base64 para imágenes o pdfs)
if($action==="preview") {
  $id = intval($_GET['id']??0);
  $sql = "SELECT archivo, extension FROM archivos WHERE id_archivo=?";
  $stmt = $conn->prepare($sql); $stmt->bind_param('i',$id); $stmt->execute();
  $res = $stmt->get_result();
  if($ar = $res->fetch_assoc()) {
    $file = __DIR__.'/uploads/'.$ar['archivo'];
    $ext = strtolower($ar['extension']);
    if(is_file($file)) {
      if(in_array($ext,["jpg","jpeg","png"])) {
        $data = base64_encode(file_get_contents($file));
        $mime = $ext=="png"?"image/png":"image/jpeg";
        echo json_encode(['tipo'=>'img','src'=>"data:$mime;base64,$data"]);
        exit();
      }
      if($ext=="pdf") {
        $data = base64_encode(file_get_contents($file));
        echo json_encode(['tipo'=>'pdf','src'=>"data:application/pdf;base64,$data"]);
        exit();
      }
      echo json_encode(['tipo'=>'otro','src'=>$ar['archivo']]);
      exit();
    }
  }
  echo json_encode(['error'=>'No preview']);
  exit();
}

// EDITAR DATOS DE ARCHIVO (nombre, descripcion, tags, tipo, fecha_asociada, relacion)
if($action==="editar") {
  $id = intval($_POST['id']??0);
  $nombre = trim($_POST['nombre']??'');
  $descripcion = trim($_POST['descripcion']??'');
  $tags = trim($_POST['tags']??'');
  $tipo = trim($_POST['tipo']??'');
  $fecha_asociada = $_POST['fecha_asociada']??null;
  $relacion = trim($_POST['relacion']??'');
  $stmt = $conn->prepare("UPDATE archivos SET nombre=?, descripcion=?, tags=?, tipo=?, fecha_asociada=?, relacion=? WHERE id_archivo=?");
  $stmt->bind_param("ssssssi",$nombre,$descripcion,$tags,$tipo,$fecha_asociada,$relacion,$id);
  $ok = $stmt->execute();
  echo json_encode(['success'=>$ok]);
  exit();
}

// COMPARTIR ARCHIVO (genera URL temporal básica)
if($action==="compartir") {
  $id = intval($_POST['id']??0);
  // Para demo simple: un token con validez de 1h
  $token = bin2hex(random_bytes(8));
  $expira = time()+3600;
  file_put_contents(__DIR__."/tokens/$token", "$id|$expira");
  echo json_encode(['url'=>"archivos.php?action=public&id=$id&token=$token"]);
  exit();
}

// ACCESO PUBLICO
if($action==="public") {
  $id = intval($_GET['id']??0);
  $token = $_GET['token']??'';
  $tokfile = __DIR__."/tokens/$token";
  if(is_file($tokfile)){
    $data = file_get_contents($tokfile);
    [$id_token,$expira] = explode('|',$data);
    if($id_token==$id && $expira>=time()){
      $sql = "SELECT archivo, nombre, extension FROM archivos WHERE id_archivo=?";
      $stmt = $conn->prepare($sql); $stmt->bind_param('i',$id); $stmt->execute();
      $res = $stmt->get_result();
      if($ar = $res->fetch_assoc()) {
        $file = __DIR__.'/uploads/'.$ar['archivo'];
        if(is_file($file)) {
          header('Content-Description: File Transfer');
          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename="'.$ar['nombre'].'.'.$ar['extension'].'"');
          header('Content-Length: ' . filesize($file));
          readfile($file); exit();
        }
      }
    }
  }
  echo "Token inválido o expirado."; exit();
}

echo json_encode(['error'=>'Acción no válida']);
?>