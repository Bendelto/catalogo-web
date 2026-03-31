<?php
session_start();
$aliadosPass = 'AliadosDC';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /catalogo-web/aliados");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
    if ($_POST['pass'] === $aliadosPass) {
        $_SESSION['aliado_auth'] = true;
        header("Location: /catalogo-web/aliados");
        exit;
    } else {
        $loginError = "Contraseña incorrecta";
    }
}

$isAuth = !empty($_SESSION['aliado_auth']);

if (!$isAuth) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Aliados - Descubre Cartagena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width: 400px; width: 100%; text-align: center; }
        .btn-brand { background: #0d6efd; color: white; border-radius: 50px; padding: 12px; font-weight: 600; width: 100%; border:none; }
        .btn-brand:hover { background: #0b5ed7; text-decoration: none; color: white;}
    </style>
</head>
<body>
    <div class="login-card">
        <div class="mb-4">
            <span class="fs-1">🤝</span>
        </div>
        <h5 class="fw-bold mb-2">Portal de Vendedores</h5>
        <p class="text-muted small mb-4">Ingresa tu contraseña de acceso</p>
        <?php if(!empty($loginError)): ?><div class="alert alert-danger py-2 small"><?= $loginError ?></div><?php endif; ?>
        <form method="post">
            <input type="password" name="pass" class="form-control mb-3 text-center" placeholder="•••" required>
            <button type="submit" class="btn-brand">Ingresar</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// ======================
// ENTORNO AUTENTICADO
// ======================

$fileConfig = 'config.json';
$config = file_exists($fileConfig) ? json_decode(file_get_contents($fileConfig), true) : ['margen_usd' => 200, 'margen_brl' => 200];
$margen_usd = $config['margen_usd']; $margen_brl = $config['margen_brl'];

$cacheFile = 'tasa.json';
$rates = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : null;
$tasa_tuya_usd = $rates ? ((1 / $rates['rates']['USD']) - $margen_usd) : 4000;
$tasa_tuya_brl = $rates ? ((1 / $rates['rates']['BRL']) - $margen_brl) : 800;

function precio_inteligente($valor) { return (float)(ceil($valor * 2) / 2); }

try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query("SELECT * FROM tours ORDER BY nombre COLLATE NOCASE ASC");
    $tours = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if(!empty($t['oculto'])) continue;
        if(!empty($t['oculto_aliado'])) continue; // FILTRO B2B

        if(!empty($t['galeria'])) $t['galeria'] = json_decode($t['galeria'], true);
        else $t['galeria'] = [];

        $comisionAgencia = isset($config['comision_agencia']) ? floatval($config['comision_agencia']) : 50;
        $porcentajeAgencia = $comisionAgencia / 100;

        // ===== CALCULO DE PRECIO ALIADO =====
        $pPub = $t['precio_cop'];
        $pNeto = $t['precio_neto'] ?: $pPub; // si no hay neto, es igual al pub
        $gananciaTotal = $pPub - $pNeto;
        if ($gananciaTotal < 0) $gananciaTotal = 0;
        
        // Mi ganancia = gananciaTotal * porcentajeAgencia
        // Precio del aliado = Neto + Mi ganancia
        $t['precio_aliado'] = round($pNeto + ($gananciaTotal * $porcentajeAgencia));

        // Ninos
        $pnPub = $t['precio_nino'];
        if ($pnPub > 0) {
            $pnNeto = !empty($t['precio_neto_nino']) ? $t['precio_neto_nino'] : $pnPub;
            $gananciaTotalNino = $pnPub - $pnNeto;
            if ($gananciaTotalNino < 0) $gananciaTotalNino = 0;
            $t['precio_nino_aliado'] = round($pnNeto + ($gananciaTotalNino * $porcentajeAgencia));
        } else {
            $t['precio_nino_aliado'] = 0;
        }

        $tours[$t['slug']] = $t;
    }
} catch(PDOException $e) { $tours = []; }

// MANEJO DESCARGA FOTOS ZIP
if (isset($_GET['zip']) && isset($tours[$_GET['zip']])) {
    $slugZip = $_GET['zip'];
    $tourData = $tours[$slugZip];

    $zipName = 'img/Material_' . $slugZip . '.zip';
    if(file_exists($zipName)) unlink($zipName);
    
    $zip = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
        if (!empty($tourData['imagen']) && file_exists($tourData['imagen'])) {
            $zip->addFile($tourData['imagen'], basename($tourData['imagen']));
        }
        if (!empty($tourData['galeria'])) {
            $n = 1;
            foreach ($tourData['galeria'] as $g) {
                if (file_exists($g)) {
                    $ext = pathinfo($g, PATHINFO_EXTENSION);
                    $zip->addFile($g, $slugZip . '-foto-' . $n . '.' . $ext);
                    $n++;
                }
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.basename($zipName));
        header('Content-Length: ' . filesize($zipName));
        readfile($zipName);
        @unlink($zipName); // cleanup temp
        exit;
    } else {
        die("Error creando archivo ZIP");
    }
}

// MANEJO DESCARGA FOTO INDIVIDUAL
if (isset($_GET['dl_foto']) && isset($_GET['slug_tour']) && isset($tours[$_GET['slug_tour']])) {
    $idx = (int)$_GET['dl_foto'];
    $t = $tours[$_GET['slug_tour']];
    $todas = [];
    if(!empty($t['imagen'])) $todas[] = $t['imagen'];
    if(!empty($t['galeria'])) foreach($t['galeria'] as $g) $todas[] = $g;
    
    if (isset($todas[$idx]) && file_exists($todas[$idx])) {
        $file = $todas[$idx];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (empty($ext)) $ext = 'jpg';
        $dlName = $t['slug'] . '-foto-' . ($idx+1) . '.' . $ext;
        
        $mime = 'image/jpeg';
        if ($ext === 'png') $mime = 'image/png';
        if ($ext === 'webp') $mime = 'image/webp';

        header('Content-Type: ' . $mime);
        header('Content-disposition: attachment; filename=' . $dlName);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// ROUTING
$slug_solicitado = $_GET['slug'] ?? '';
$slug_solicitado = trim(str_replace('aliados/', '', $slug_solicitado), '/');
$singleTour = null;

if (!empty($slug_solicitado) && isset($tours[$slug_solicitado])) {
    $singleTour = $tours[$slug_solicitado];
}

$base_url = dirname($_SERVER['SCRIPT_NAME']);
if ($base_url === '/') $base_url = '';

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

if ($singleTour) {
    $desc = $singleTour['descripcion'] ?? '';
    $inc = $singleTour['incluye'] ?? '';
    $no_inc = $singleTour['no_incluye'] ?? '';
    $info_adicional = $singleTour['info_adicional'] ?? '';

    $precioBase = $singleTour['precio_aliado']; // AQUI SOBREESCRIBO AL PRECIO B2B
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Catálogo Aliados</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; color: #333; padding-bottom: 40px; }
        .main-container { max-width: 1200px; margin: 0 auto; }
        
        /* HEADER B2B Diferenciado */
        .site-header { background: #091b33; color: white; padding: 15px 0; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); position: sticky; top: 0; z-index: 1000; }
        .site-header a { color: white; text-decoration: none; }
        .main-logo { width: 140px; max-width: 65%; height: auto; filter: brightness(0) invert(1); }
        .badge-b2b { background: #0d6efd; color: white; border-radius: 5px; padding: 3px 8px; font-size: 0.7rem; font-weight: bold; margin-left:10px; vertical-align: middle; }

        @media (min-width: 992px) { .site-header { padding: 20px 0; } .main-logo { width: 180px; } }

        /* VISTA INDIVIDUAL Y B2B */
        .calc-container { max-width: 720px; margin: 0 auto; padding-bottom: 80px; }
        .card-price { border: 0; border-radius: 16px; box-shadow: 0 10px 30px -4px rgba(0,0,0,0.08); text-decoration: none; color: inherit; display: block; background: white; transition: all 0.3s ease; height: 100%; position: relative;}
        .card-price:hover { box-shadow: 0 20px 40px -4px rgba(0,0,0,0.12); transform: translateY(-8px); }
        
        .tour-img-list { width: 100%; height: 200px; object-fit: cover; border-bottom: 1px solid #f0f0f0; }

        .price-cop-highlight { color: #1a1a1a; font-weight: 800; font-size: 1.3rem; display: block; line-height: 1.1; color: #0d6efd; }
        
        .info-box { background: white; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); }
        .list-check li { list-style: none; padding-left: 0; margin-bottom: 8px; font-size: 0.95rem; }
        
        .calc-box { background-color: #fff; border-radius: 12px; padding: 20px; border: 1px solid #edf2f7; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .total-display { background-color: #e7f1ff; color: #0d6efd; border: 1px solid #cce5ff; border-radius: 12px; padding: 20px; margin-top: 20px; }
        
        .btn-back { background-color: rgba(255,255,255,0.2); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; }
        
        .search-input { width: 100%; padding: 12px 20px 12px 40px; border-radius: 50px; border: none; background: rgba(255,255,255,0.1); color:white; outline: none; transition: all 0.3s ease; font-size: 0.9rem; }
        .search-input::placeholder { color: rgba(255,255,255,0.6); }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.6); font-size: 1rem; }
        
        /* GALERIA DESCARGABLE */
        .gallery-download-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top:20px; }
        .thumb-box { position: relative; border-radius: 8px; overflow: hidden; height:100px; }
        .thumb-box img { width: 100%; height: 100%; object-fit: cover; }
        .thumb-overlay { position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
        .thumb-box:hover .thumb-overlay { opacity: 1; }
        .btn-dl-mini { background: white; color: #333; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        
    </style>
</head>
<body>

<div class="site-header">
    <div class="container main-container d-flex justify-content-between align-items-center">
        <div>
            <?php if ($singleTour): ?>
                <a href="/catalogo-web/aliados" class="btn-back d-inline-flex me-2"><i class="fa-solid fa-arrow-left"></i></a>
            <?php endif; ?>
            <a href="/catalogo-web/aliados">
                <img src="/catalogo-web/logo.svg" alt="Descubre Cartagena" class="main-logo">
            </a>
            <span class="badge-b2b d-none d-md-inline-block"><i class="fa-solid fa-briefcase"></i> ALIADOS B2B</span>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <?php if (!$singleTour): ?>
                <div class="position-relative d-none d-md-block" style="width: 300px;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Buscar tour...">
                </div>
            <?php endif; ?>
            <a href="?logout=true" class="text-white small text-decoration-none" title="Cerrar sesión"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>
</div>

<div class="container main-container">
<?php if ($singleTour): ?>
    <div class="calc-container">
        
        <h4 class="fw-bold mb-3"><?= htmlspecialchars($singleTour['nombre']) ?></h4>

        <div class="card card-price p-3 mb-4 border-primary">
            <div class="row g-0 text-center border-bottom pb-3 mb-3">
                <div class="col-6 border-end pe-2 d-flex flex-column justify-content-center">
                    <span class="text-uppercase text-muted fw-bold" style="font-size:0.65rem;">ADULTOS</span>
                    <div class="my-1">
                        <span class="price-cop-highlight">$<?= number_format($precioBase) ?> <span class="text-dark fs-6">NETO</span></span>
                    </div>
                </div>
                <div class="col-6 ps-2 d-flex flex-column justify-content-center">
                    <span class="text-uppercase text-muted fw-bold" style="font-size:0.65rem;">NIÑOS</span>
                    <?php if($singleTour['precio_nino_aliado'] > 0): ?>
                        <div class="my-1">
                            <span class="price-cop-highlight">$<?= number_format($singleTour['precio_nino_aliado']) ?> <span class="text-dark fs-6">NETO</span></span>
                        </div>
                    <?php else: ?>
                        <div class="text-muted mt-2 small">- No aplica -</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex justify-content-center gap-4 fw-bold text-muted mb-3" style="font-size:0.85rem;">
                <div><i class="fa-solid fa-earth-americas text-primary"></i> <span class="text-dark"><?= precio_inteligente($precioBase / $tasa_tuya_usd) ?> USD</span></div>
                <div><i class="fa-solid fa-earth-americas text-success"></i> <span class="text-dark"><?= precio_inteligente($precioBase / $tasa_tuya_brl) ?> BRL</span></div>
            </div>
            <div class="alert alert-info mt-1 mb-0 small py-2 text-center">
                <i class="fa-solid fa-lightbulb text-warning"></i> Precio de venta sugerido: <strong>$<?= number_format($singleTour['precio_cop']) ?></strong>
                <div style="font-size: 0.75rem; margin-top:3px;" class="text-secondary opacity-75">Eres libre de venderlo a un precio mayor para aumentar tus ganancias, pero recomendamos no exagerar para que mantengas un buen volumen de ventas.</div>
            </div>
        </div>

        <!-- RECURSOS PARA VENTAS -->
        <div class="info-box border-bottom border-4 border-primary pb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i> Material para Ventas</h6>
            <p class="text-secondary small">Descarga las fotos originales para postearlas en tus redes sociales o enviarlas por WhatsApp a tus clientes.</p>
            
            <a href="?slug=<?= $singleTour['slug'] ?>&zip=<?= $singleTour['slug'] ?>" class="btn btn-primary btn-sm fw-bold w-100 mb-3 shadow-sm rounded-pill">
                <i class="fa-solid fa-file-zipper me-1"></i> Descargar Todo en ZIP
            </a>

            <?php 
                $todasLasFotos = [];
                if(!empty($singleTour['imagen'])) $todasLasFotos[] = $singleTour['imagen'];
                if(!empty($singleTour['galeria'])) foreach($singleTour['galeria'] as $g) $todasLasFotos[] = $g;
            ?>
            <div class="gallery-download-grid">
                <?php foreach($todasLasFotos as $idx => $fto): ?>
                    <div class="thumb-box">
                        <img src="/catalogo-web/<?= $fto ?>">
                        <div class="thumb-overlay">
                            <!-- Endpoint dedicado, libre del .htaccess -->
                            <a href="/catalogo-web/aliados-descarga.php?slug_tour=<?= $singleTour['slug'] ?>&dl_foto=<?= $idx ?>" class="btn-dl-mini shadow" title="Descargar Imagen"><i class="fa-solid fa-download"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-box">
            <?php if(!empty($desc)): ?>
                <div class="text-secondary mb-4" style="white-space: pre-line; line-height: 1.6; font-size: 0.9rem;">
                    <?= htmlspecialchars($desc) ?>
                </div>
                <hr class="opacity-10 my-4">
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-md-6 border-bottom border-md-0 pb-3 pb-md-0">
                    <h6 class="text-dark mb-3 small"><i class="fa-solid fa-circle-check text-success"></i> Incluye</h6>
                    <ul class="list-check ps-0 m-0 text-secondary">
                        <?php foreach(explode("\n", $inc) as $item): if(trim($item)=='')continue; ?>
                            <li style="font-size: 0.85rem;"><i class="fa-solid fa-check text-success"></i> <?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-12 col-md-6">
                    <h6 class="text-dark mb-3 small"><i class="fa-solid fa-circle-xmark text-danger"></i> No incluye</h6>
                    <ul class="list-check ps-0 m-0 text-secondary">
                        <?php foreach(explode("\n", $no_inc) as $item): if(trim($item)=='')continue; ?>
                            <li style="font-size: 0.85rem;"><i class="fa-solid fa-xmark text-danger"></i> <?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>

<?php else: ?>
    
    <?php if (empty($tours)): ?>
        <div class="text-center mt-5 text-muted">
            <i class="fa-regular fa-folder-open fs-1 mb-3"></i>
            <p>No hay tours disponibles para aliados todavía.</p>
        </div>
    <?php else: ?>
        <div class="row g-4" id="toursGrid">
            <?php foreach ($tours as $slug => $tour): 
                $pFinal = $tour['precio_aliado'];
                $pPublic = $tour['precio_cop'];
            ?>
            <div class="col-12 col-md-6 col-lg-4 tour-card-col">
                <a href="/catalogo-web/aliados/<?= $slug ?>" class="card card-price">
                    <?php if(!empty($tour['imagen'])): ?><img src="/catalogo-web/<?= $tour['imagen'] ?>" class="tour-img-list"><?php endif; ?>
                    
                    <div class="p-4">
                        <h6 class="fw-bold mb-3 text-dark lh-base tour-title" style="font-size: 1.1rem;"><?= htmlspecialchars($tour['nombre']) ?></h6>
                        <div class="mb-3">
                            <span class="price-cop-highlight" style="font-size: 1.2rem;">
                                $<?= number_format($pFinal) ?> <small class="text-muted fw-normal" style="font-size: 0.75rem;">NETO</small>
                            </span>
                            <div class="d-flex align-items-center gap-2 mt-1 mb-2" style="font-size:0.75rem;">
                                <span class="badge bg-light text-dark shadow-sm border"><i class="fa-solid fa-earth-americas text-primary"></i> <?= precio_inteligente($pFinal / $tasa_tuya_usd) ?> USD</span>
                                <span class="badge bg-light text-dark shadow-sm border"><i class="fa-solid fa-earth-americas text-success"></i> <?= precio_inteligente($pFinal / $tasa_tuya_brl) ?> BRL</span>
                            </div>
                            <div class="small text-muted mt-1"><del>$<?= number_format($pPublic) ?></del> al púb.</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-end mt-auto pt-3 border-top">
                            <span class="text-primary small fw-bold"><i class="fa-solid fa-download"></i> Ver recursos</span>
                            <div class="text-primary fs-5"><i class="fa-solid fa-circle-arrow-right"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Buscador móvil y script referencial simple -->
        <script>
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const cards = document.querySelectorAll('.tour-card-col');
                    const text = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    cards.forEach(card => {
                        const title = card.querySelector('.tour-title').textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                        card.style.display = title.includes(text) ? '' : 'none';
                    });
                });
            }
        </script>
    <?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>
