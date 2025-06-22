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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CREAR
if($action==='crear'){
  $cliente = trim($_POST['cliente']??'');
  $mascota = trim($_POST['mascota']??'');
  $motivo = trim($_POST['motivo']??'');
  $notas = trim($_POST['notas']??'');
  $fecha_prox = $_POST['fecha_prox']??'';
  $tags = trim($_POST['tags']??'');
  if($cliente && $mascota && $motivo && $fecha_prox){
    $estado = (strtotime($fecha_prox) < strtotime(date('Y-m-d'))) ? 'atrasado' : 'pendiente';
    $stmt = $conn->prepare("INSERT INTO seguimientos (cliente, mascota, motivo, notas, fecha_proximo, tags, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss",$cliente,$mascota,$motivo,$notas,$fecha_prox,$tags,$estado);
    $ok = $stmt->execute();
    if($ok){
      $id = $conn->insert_id;
      $accion = "Creación de seguimiento para $mascota ($cliente)";
      $stmt2 = $conn->prepare("INSERT INTO historial_seguimiento (id_seguimiento, accion, fecha_accion) VALUES (?, ?, NOW())");
      $stmt2->bind_param("is",$id,$accion); $stmt2->execute();
    }
    echo json_encode(['success'=>$ok]);
    exit();
  }
  echo json_encode(['success'=>false,'error'=>'Campos obligatorios']);
  exit();
}

// EDITAR
if($action==='editar'){
  $id = intval($_POST['id']??0);
  $cliente = trim($_POST['cliente']??'');
  $mascota = trim($_POST['mascota']??'');
  $motivo = trim($_POST['motivo']??'');
  $notas = trim($_POST['notas']??'');
  $fecha_prox = $_POST['fecha_prox']??'';
  $tags = trim($_POST['tags']??'');
  if($id && $cliente && $mascota && $motivo && $fecha_prox){
    $estado = (strtotime($fecha_prox) < strtotime(date('Y-m-d'))) ? 'atrasado' : 'pendiente';
    $stmt = $conn->prepare("UPDATE seguimientos SET cliente=?, mascota=?, motivo=?, notas=?, fecha_proximo=?, tags=?, estado=? WHERE id_seguimiento=?");
    $stmt->bind_param("sssssssi",$cliente,$mascota,$motivo,$notas,$fecha_prox,$tags,$estado,$id);
    $ok = $stmt->execute();
    if($ok){
      $accion = "Edición de seguimiento ($mascota/$cliente)";
      $stmt2 = $conn->prepare("INSERT INTO historial_seguimiento (id_seguimiento, accion, fecha_accion) VALUES (?, ?, NOW())");
      $stmt2->bind_param("is",$id,$accion); $stmt2->execute();
    }
    echo json_encode(['success'=>$ok]);
    exit();
  }
  echo json_encode(['success'=>false,'error'=>'Campos obligatorios']);
  exit();
}

// LISTAR
if($action==='listar'){
  $buscar = trim($_GET['buscar']??'');
  $estado = trim($_GET['estado']??'');
  $orden = $_GET['orden']??'fecha_proximo ASC';
  $pagina = intval($_GET['pagina']??1);
  $limite=20; $offset=($pagina-1)*$limite;
  $where=[]; $params=[]; $types='';
  if($buscar){
    $where[]="(cliente LIKE ? OR mascota LIKE ? OR motivo LIKE ? OR notas LIKE ? OR tags LIKE ?)";
    for($i=0;$i<5;$i++) {$params[]="%$buscar%"; $types.="s";}
  }
  if($estado){
    $where[]="estado=?";
    $params[]=$estado; $types.="s";
  }
  $sqlf = $where?"WHERE ".implode(" AND ",$where):"";
  // Total
  $sql_count = "SELECT COUNT(*) as total FROM seguimientos $sqlf";
  $stmt = $conn->prepare($sql_count);
  if($types) $stmt->bind_param($types, ...$params);
  $stmt->execute(); $res = $stmt->get_result(); $total = $res->fetch_assoc()['total']??0;
  $paginas = ceil($total/$limite);
  // Listar
  $sql = "SELECT * FROM seguimientos $sqlf ORDER BY $orden LIMIT $limite OFFSET $offset";
  $stmt = $conn->prepare($sql);
  if($types) $stmt->bind_param($types, ...$params);
  $stmt->execute(); $res = $stmt->get_result();
  $seguimientos = [];
  while($row=$res->fetch_assoc()) $seguimientos[] = $row;
  echo json_encode(['seguimientos'=>$seguimientos,'paginas'=>$paginas,'total'=>$total]);
  exit();
}

// MARCAR COMPLETADO
if($action==='completar'){
  $id = intval($_POST['id']??0);
  if($id){
    $stmt = $conn->prepare("UPDATE seguimientos SET estado='completado' WHERE id_seguimiento=?");
    $stmt->bind_param("i",$id); $ok = $stmt->execute();
    if($ok){
      $accion = "Seguimiento marcado como completado";
      $stmt2 = $conn->prepare("INSERT INTO historial_seguimiento (id_seguimiento, accion, fecha_accion) VALUES (?, ?, NOW())");
      $stmt2->bind_param("is",$id,$accion); $stmt2->execute();
    }
    echo json_encode(['success'=>$ok]);
    exit();
  }
  echo json_encode(['success'=>false]);
  exit();
}

// ELIMINAR
if($action==='eliminar'){
  $id = intval($_POST['id']??0);
  if($id){
    $stmt = $conn->prepare("DELETE FROM seguimientos WHERE id_seguimiento=?");
    $stmt->bind_param("i",$id); $ok = $stmt->execute();
    $accion = "Seguimiento eliminado";
    $stmt2 = $conn->prepare("INSERT INTO historial_seguimiento (id_seguimiento, accion, fecha_accion) VALUES (?, ?, NOW())");
    $stmt2->bind_param("is",$id,$accion); $stmt2->execute();
    echo json_encode(['success'=>$ok]);
    exit();
  }
  echo json_encode(['success'=>false]);
  exit();
}

// VER
if($action==='ver'){
  $id = intval($_GET['id']??0);
  $sql = "SELECT * FROM seguimientos WHERE id_seguimiento=?";
  $stmt = $conn->prepare($sql); $stmt->bind_param("i",$id); $stmt->execute();
  $res = $stmt->get_result();
  if($s = $res->fetch_assoc()) echo json_encode($s);
  else echo json_encode(null);
  exit();
}

// HISTORIAL DE UN SEGUIMIENTO
if($action==='historial'){
  $id = intval($_GET['id']??0);
  $sql_s = "SELECT * FROM seguimientos WHERE id_seguimiento=?";
  $stmt = $conn->prepare($sql_s); $stmt->bind_param("i",$id); $stmt->execute();
  $res = $stmt->get_result(); $s = $res->fetch_assoc();
  $hist = [];
  $sql_h = "SELECT * FROM historial_seguimiento WHERE id_seguimiento=? ORDER BY fecha_accion DESC";
  $stmt2 = $conn->prepare($sql_h); $stmt2->bind_param("i",$id); $stmt2->execute();
  $res2 = $stmt2->get_result();
  while($row=$res2->fetch_assoc()) $hist[] = $row;
  echo json_encode(['seguimiento'=>$s,'historial'=>$hist]);
  exit();
}

// TIMELINE GLOBAL (últimas 25 acciones)
if($action==='timeline'){
  $sql = "SELECT * FROM historial_seguimiento ORDER BY fecha_accion DESC LIMIT 25";
  $res = $conn->query($sql);
  $hist = [];
  while($row=$res->fetch_assoc()) $hist[] = $row;
  echo json_encode($hist);
  exit();
}

echo json_encode(['error'=>'Acción no válida']);
?>