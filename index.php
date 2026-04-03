<?php
// 1. CONFIGURACIÓN
$fileConfig = 'config.json';
$config = file_exists($fileConfig) ? json_decode(file_get_contents($fileConfig), true) : ['margen_usd' => 200, 'margen_brl' => 200];
$margen_usd = $config['margen_usd']; $margen_brl = $config['margen_brl'];

// 2. MONEDA
$cacheFile = 'tasa.json';
if (!file_exists($cacheFile) || (time() - filemtime($cacheFile)) > 43200) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://open.er-api.com/v6/latest/COP");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 seconds timeout
    $response = curl_exec($ch);
    curl_close($ch);
    if($response) file_put_contents($cacheFile, $response, LOCK_EX);
}
$rates = json_decode(file_get_contents($cacheFile), true);
$tasa_tuya_usd = (1 / $rates['rates']['USD']) - $margen_usd;
$tasa_tuya_brl = (1 / $rates['rates']['BRL']) - $margen_brl;

function precio_inteligente($valor) { return (float)(ceil($valor * 2) / 2); }

// 3. DATOS
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query("SELECT * FROM tours ORDER BY nombre COLLATE NOCASE ASC");
    $tours = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if(!empty($t['galeria'])) $t['galeria'] = json_decode($t['galeria'], true);
        else $t['galeria'] = [];
        $tours[$t['slug']] = $t;
    }
} catch(PDOException $e) { $tours = []; }

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if($base_path == '/') $base_path = '';
$slug_solicitado = trim(str_replace($base_path, '', $request_uri), '/');

$singleTour = null;
if (!empty($slug_solicitado) && isset($tours[$slug_solicitado])) {
    if (empty($tours[$slug_solicitado]['oculto']) || $tours[$slug_solicitado]['oculto'] == false) {
        $singleTour = $tours[$slug_solicitado];
    }
}

// 4. VARIABLES DE VISTA
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$waLink = "";

if ($singleTour) {
    $desc = $singleTour['descripcion'] ?? $singleTour['description'] ?? '';
    $inc = $singleTour['incluye'] ?? $singleTour['include'] ?? '';
    $no_inc = $singleTour['no_incluye'] ?? $singleTour['not_include'] ?? '';
    $info_adicional = $singleTour['info_adicional'] ?? '';

    // PRECIOS
    $precioBase = $singleTour['precio_cop'];
    $precioPromo = $singleTour['precio_promo'] ?? 0;
    $usarPromo = ($precioPromo > 0 && $precioPromo < $precioBase);
    $precioFinalCalc = $usarPromo ? $precioPromo : $precioBase;

    // SEO
    $metaTitle = $singleTour['nombre'];
    $metaDesc = !empty($desc) ? substr(strip_tags($desc), 0, 150) . "..." : "Reserva este tour en Cartagena.";
    if(!empty($singleTour['imagen'])) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $metaImage = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/" . $singleTour['imagen'];
    }

    // WHATSAPP & EMAIL
    $mensaje  = "Hola Descubre Cartagena, me gustaría reservar: \n\n";
    $mensaje .= "📍 *" . $singleTour['nombre'] . "*\n";
    $mensaje .= "🔗 " . $currentUrl;
    $waLink = "https://wa.me/573205899997?text=" . urlencode($mensaje);
    
    $mailSubj = "Nueva Reserva: " . $singleTour['nombre'];
    $mailBody = "Hola,\n\nMe gustaría consultar/reservar el siguiente tour:\n\nTour: " . $singleTour['nombre'] . "\nEnlace: " . $currentUrl . "\n\nQuedo atento a su respuesta para gestionar la reserva.\n\nGracias.";
    $mailLink = "mailto:info@descubrecartagena.com?subject=" . rawurlencode($mailSubj) . "&body=" . rawurlencode($mailBody);
} else {
    $metaTitle = "Descubre Cartagena";
    $metaDesc = "Los mejores tours y experiencias en Cartagena de Indias.";
}

// CARGAR TEMA
$temaActivo = $config['tema'] ?? 'default';
$rutaTema = __DIR__ . '/temas/' . $temaActivo . '/index.php';
if (file_exists($rutaTema)) {
    require $rutaTema;
} else {
    require __DIR__ . '/temas/default/index.php';
}
?>
