<?php
/**
 * aliados-descarga.php
 * Endpoint DEDICADO para descargas de fotos individuales del portal aliados.
 * Archivo separado para que el .htaccess NO lo intercepte.
 */
session_start();

// Verificar autenticación
if (empty($_SESSION['aliado_auth'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

$slug_tour = $_GET['slug_tour'] ?? '';
$idx       = isset($_GET['dl_foto']) ? (int)$_GET['dl_foto'] : -1;

if (empty($slug_tour) || $idx < 0) {
    http_response_code(400);
    die('Parámetros inválidos.');
}

// Cargar el tour desde la BD
try {
    $db   = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT slug, imagen, galeria FROM tours WHERE slug = ?");
    $stmt->execute([$slug_tour]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de base de datos.');
}

if (!$tour) {
    http_response_code(404);
    die('Tour no encontrado.');
}

// Reconstruir lista de fotos
$todas = [];
if (!empty($tour['imagen'])) $todas[] = $tour['imagen'];
if (!empty($tour['galeria'])) {
    $galeria = json_decode($tour['galeria'], true);
    if (is_array($galeria)) {
        foreach ($galeria as $g) $todas[] = $g;
    }
}

if (!isset($todas[$idx])) {
    http_response_code(404);
    die('Foto no encontrada.');
}

$file = $todas[$idx];

if (!file_exists($file)) {
    http_response_code(404);
    die('Archivo no existe en el servidor.');
}

// Determinar extensión y MIME
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (empty($ext)) $ext = 'jpg';

$mime_map = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

$dlName = $tour['slug'] . '-foto-' . ($idx + 1) . '.' . $ext;

// Limpiar cualquier output pendiente
while (ob_get_level()) ob_end_clean();

// Enviar headers y archivo
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $dlName . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($file);
exit;
