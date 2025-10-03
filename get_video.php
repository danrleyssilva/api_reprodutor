<?php
header('Content-Type: application/json');

require_once 'class/conexao.php';

if (!isset($_GET['codigo'])) {
  echo json_encode(['error' => 'Código do dispositivo não informado']);
  exit;
}

$codigo = $_GET['codigo'];
$ipLocal = $_SERVER['SERVER_ADDR'];

// print_r($ipLocal);
// die();


try {
  $db = new conexao();
  $conn = $db->getConexao();

  // Busca o id do dispositivo pelo código
  $stmt = $conn->prepare("SELECT id FROM dispositivos WHERE codigo = :codigo LIMIT 1");
  $stmt->bindValue(':codigo', $codigo);
  $stmt->execute();

  $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$dispositivo) {
    echo json_encode(['error' => 'Dispositivo não encontrado']);
    exit;
  }

  $id_dispositivo = $dispositivo['id'];

  $stmt2 = $conn->prepare("
    SELECT v.url, v.grupo
    FROM dispositivo_video dv
    INNER JOIN videos v ON dv.id_video = v.id_video
    WHERE dv.id_dispositivo = :id_dispositivo
    LIMIT 1
  ");
  $stmt2->bindValue(':id_dispositivo', $id_dispositivo);
  $stmt2->execute();

  $video = $stmt2->fetch(PDO::FETCH_ASSOC);

  if (!$video) {
    echo json_encode(['error' => 'Nenhum vídeo associado']);
    exit;
  }

  // 10.0.2.2 emulador
  // 192.168.1.167 ip
  // $urlVideo = str_replace('localhost', '10.0.2.2', $video['url']);
  // $urlVideo = str_replace('localhost', '192.168.0.106', $video['url']);
  // $urlVideo = str_replace('localhost', '192.168.1.167', $video['url']);
  $urlVideo = str_replace('localhost', $ipLocal, $video['url']);


  echo json_encode([
    'url' => $urlVideo,
    'grupo' => $video['grupo'],
    'ip_ws' => $ipLocal
  ]);

} catch (PDOException $ex) {
  echo json_encode(['error' => $ex->getMessage()]);
}



//  private static final String API_URL = "http://10.0.2.2/api_reprodutor/get_video.php?codigo=disp_01";
// private static final String API_URL = "http://192.168.0.112/api_reprodutor/get_video.php?codigo=disp_02T";