<?php
session_start();

// 1. CREDENCIALES
$fileCreds = 'credenciales.json';
if (!file_exists($fileCreds)) {
    $defaultCreds = ['usuario' => 'Benko', 'password' => password_hash('Dc@6691400', PASSWORD_DEFAULT)];
    file_put_contents($fileCreds, json_encode($defaultCreds));
}
$creds = json_decode(file_get_contents($fileCreds), true);

// Upgrade legacy password to hash
if (isset($creds['password']) && !str_starts_with($creds['password'], '$2y$')) {
    $creds['password'] = password_hash($creds['password'], PASSWORD_DEFAULT);
    file_put_contents($fileCreds, json_encode($creds));
}

// 2. LOGIN
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$errorMsg = '';
if (isset($_POST['login'])) {
    $userInput = $_POST['user'] ?? '';
    $passInput = $_POST['pass'] ?? '';
    if ($userInput === $creds['usuario'] && password_verify($passInput, $creds['password'])) {
        $_SESSION['admin'] = true;
        header("Location: $BASE/admin");
        exit;
    } else {
        $errorMsg = 'Datos incorrectos';
    }
}

if (!isset($_SESSION['admin'])) {
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="d-flex justify-content-center align-items-center vh-100 px-3 bg-light"><form method="post" class="card p-4 shadow" style="max-width:400px;width:100%"><h3 class="text-center mb-3">🔐 Admin</h3><?php if($errorMsg): ?><div class="alert alert-danger py-1"><?= $errorMsg ?></div><?php endif; ?><input type="text" name="user" class="form-control mb-3" placeholder="Usuario" required autofocus><input type="password" name="pass" class="form-control mb-3" placeholder="Contraseña" required><button name="login" class="btn btn-primary w-100">Entrar</button></form></body></html>
    <?php exit;
}

// 3. DATOS
$dbFile = __DIR__ . '/database.sqlite';
$fileConfig = 'config.json';
try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query("SELECT * FROM tours ORDER BY nombre COLLATE NOCASE ASC");
    $tours = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if(!empty($t['galeria'])) $t['galeria'] = json_decode($t['galeria'], true);
        else $t['galeria'] = [];
        $tours[$t['slug']] = $t;
    }
} catch(PDOException $e) { $tours = []; }

// BACKUP
if (isset($_GET['backup'])) {
    if (file_exists($dbFile)) {
        $fecha = date('Y-m-d_H-i');
        header('Content-Type: application/vnd.sqlite3');
        header('Content-Disposition: attachment; filename="backup_'.$fecha.'.sqlite"');
        readfile($dbFile);
        exit;
    }
}

$config = file_exists($fileConfig) ? json_decode(file_get_contents($fileConfig), true) : ['margen_usd' => 200, 'margen_brl' => 200, 'comision_agencia' => 50];

// API AJAX: GUARDAR CONFIG
if (isset($_POST['ajax_config'])) {
    $config['margen_usd'] = floatval($_POST['margen_usd']);
    $config['margen_brl'] = floatval($_POST['margen_brl']);
    $config['comision_agencia'] = floatval($_POST['comision_agencia'] ?? 50);
    if (isset($_POST['tema'])) {
        $config['tema'] = $_POST['tema'];
    }
    file_put_contents($fileConfig, json_encode($config), LOCK_EX);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// API AJAX: OCULTAR / MOSTRAR
if (isset($_POST['ajax_toggle'])) {
    $slugTarget = $_POST['slug'];
    $tipo = $_POST['tipo'] ?? 'publico';
    $columna = ($tipo === 'aliado') ? 'oculto_aliado' : 'oculto';

    $stmt = $db->prepare("SELECT $columna FROM tours WHERE slug = ?");
    $stmt->execute([$slugTarget]);
    $tourData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tourData) {
        $nuevoEstado = $tourData[$columna] ? 0 : 1;
        $stmt = $db->prepare("UPDATE tours SET $columna = ? WHERE slug = ?");
        $stmt->execute([$nuevoEstado, $slugTarget]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'oculto' => $nuevoEstado]);
    }
    exit;
}

// ==========================================
//      LÓGICA DE GUARDADO (ADD/EDIT)
// ==========================================
if (isset($_POST['add'])) {
    $nombre = $_POST['nombre'] ?? 'Sin nombre';
    $slugInput = !empty($_POST['slug']) ? $_POST['slug'] : $nombre;
    $cleanSlug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $slugInput)));
    $cleanSlug = trim($cleanSlug, '-');
    $originalSlug = $_POST['original_slug'] ?? '';

    // RECUPERAR DATOS ANTERIORES
    $datosAnteriores = [];
    if (!empty($originalSlug) && isset($tours[$originalSlug])) {
        $datosAnteriores = $tours[$originalSlug];
    } elseif (isset($tours[$cleanSlug])) {
        $datosAnteriores = $tours[$cleanSlug];
    }

    // GESTIÓN DE GALERÍA (Borrado Individual)
    $galeriaActual = $datosAnteriores['galeria'] ?? [];
    if (isset($_POST['delete_imgs']) && is_array($_POST['delete_imgs'])) {
        foreach ($_POST['delete_imgs'] as $delImg) {
            if (file_exists($delImg)) unlink($delImg);
        }
        $galeriaActual = array_diff($galeriaActual, $_POST['delete_imgs']);
        $galeriaActual = array_values($galeriaActual);
    }

    // PREPARAR DATOS NUEVOS
    $nuevosDatos = [
        'nombre' => $nombre,
        'precio_cop' => $_POST['precio'] ?? 0, 
        'precio_promo' => $_POST['precio_promo'] ?? 0, 
        'rango_adulto' => $_POST['rango_adulto'] ?? '',
        'precio_nino' => $_POST['precio_nino'] ?? 0,
        'rango_nino' => $_POST['rango_nino'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'incluye' => $_POST['incluye'] ?? '',
        'no_incluye' => $_POST['no_incluye'] ?? '',
        'info_adicional' => $_POST['info_adicional'] ?? '', // NUEVO CAMPO
        'imagen' => $datosAnteriores['imagen'] ?? '', 
        'galeria' => $galeriaActual,
        'oculto' => $datosAnteriores['oculto'] ?? false
    ];

    // Limpieza de campos antiguos si existían
    if(isset($datosAnteriores['horario'])) unset($datosAnteriores['horario']);
    if(isset($datosAnteriores['punto_encuentro'])) unset($datosAnteriores['punto_encuentro']);

    function optimizarImagen($origen, $destino, $maxAncho = 1200) {
        $info = @getimagesize($origen);
        if (!$info) return move_uploaded_file($origen, $destino);
        $mime = $info['mime'];
        try {
            switch ($mime) {
                case 'image/jpeg': $img = imagecreatefromjpeg($origen); break;
                case 'image/png': $img = imagecreatefrompng($origen); break;
                case 'image/webp': $img = imagecreatefromwebp($origen); break;
                default: return move_uploaded_file($origen, $destino);
            }
        } catch (Exception $e) { return move_uploaded_file($origen, $destino); }

        $anchoOriginal = imagesx($img);
        $altoOriginal = imagesy($img);
        if ($anchoOriginal > $maxAncho) {
            $altoNuevo = floor($altoOriginal * ($maxAncho / $anchoOriginal));
            $imgResized = imagecreatetruecolor($maxAncho, $altoNuevo);
            if ($mime == 'image/png' || $mime == 'image/webp') {
                imagecolortransparent($imgResized, imagecolorallocatealpha($imgResized, 0, 0, 0, 127));
                imagealphablending($imgResized, false);
                imagesavealpha($imgResized, true);
            }
            imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $maxAncho, $altoNuevo, $anchoOriginal, $altoOriginal);
            $img = $imgResized;
        }
        
        $res = false;
        switch ($mime) {
            case 'image/jpeg': $res = imagejpeg($img, $destino, 80); break;
            case 'image/png': $res = imagepng($img, $destino, 8); break;
            case 'image/webp': $res = imagewebp($img, $destino, 80); break;
        }
        imagedestroy($img);
        if (!$res) move_uploaded_file($origen, $destino);
        return true;
    }

    // PROCESAR PORTADA
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $uploadDir = 'img/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $filename = $cleanSlug . '-portada-' . time() . '.' . $ext;
        if (optimizarImagen($_FILES['imagen']['tmp_name'], $uploadDir . $filename)) {
            if (!empty($datosAnteriores['imagen']) && file_exists($datosAnteriores['imagen'])) unlink($datosAnteriores['imagen']);
            $nuevosDatos['imagen'] = $uploadDir . $filename;
        }
    }

    // PROCESAR GALERÍA (NUEVAS FOTOS)
    if (isset($_FILES['galeria'])) {
        $uploadDir = 'img/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $count = count($_FILES['galeria']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['galeria']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION);
                $filename = $cleanSlug . '-galeria-' . time() . '-' . $i . '.' . $ext;
                if (optimizarImagen($_FILES['galeria']['tmp_name'][$i], $uploadDir . $filename)) {
                    $nuevosDatos['galeria'][] = $uploadDir . $filename;
                }
            }
        }
    }

    if (!empty($originalSlug) && $originalSlug != $cleanSlug) {
        $stmt = $db->prepare("DELETE FROM tours WHERE slug = ?");
        $stmt->execute([$originalSlug]);
    }
    
    // UPSERT
    $stmt = $db->prepare("INSERT INTO tours (slug, nombre, precio_cop, precio_neto, precio_promo, rango_adulto, precio_nino, precio_neto_nino, rango_nino, descripcion, incluye, no_incluye, info_adicional, imagen, galeria, oculto, oculto_aliado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(slug) DO UPDATE SET nombre=excluded.nombre, precio_cop=excluded.precio_cop, precio_neto=excluded.precio_neto, precio_promo=excluded.precio_promo, rango_adulto=excluded.rango_adulto, precio_nino=excluded.precio_nino, precio_neto_nino=excluded.precio_neto_nino, rango_nino=excluded.rango_nino, descripcion=excluded.descripcion, incluye=excluded.incluye, no_incluye=excluded.no_incluye, info_adicional=excluded.info_adicional, imagen=excluded.imagen, galeria=excluded.galeria, oculto=excluded.oculto, oculto_aliado=excluded.oculto_aliado");
    
    $galJSON = json_encode($nuevosDatos['galeria'] ?? []);
    $oculto = ($datosAnteriores['oculto'] ?? 0) ? 1 : 0;
    $oculto_aliado = ($datosAnteriores['oculto_aliado'] ?? 0) ? 1 : 0;
    
    $stmt->execute([
        $cleanSlug,
        $nuevosDatos['nombre'] ?? '',
        floatval($_POST['precio'] ?? 0),
        floatval($_POST['precio_neto'] ?? 0),
        floatval($_POST['precio_promo'] ?? 0),
        $_POST['rango_adulto'] ?? '',
        floatval($_POST['precio_nino'] ?? 0),
        floatval($_POST['precio_neto_nino'] ?? 0),
        $_POST['rango_nino'] ?? '',
        $_POST['descripcion'] ?? '',
        $_POST['incluye'] ?? '',
        $_POST['no_incluye'] ?? '',
        $_POST['info_adicional'] ?? '',
        $nuevosDatos['imagen'] ?? '',
        $galJSON,
        $oculto,
        $oculto_aliado
    ]);
    
    header("Location: admin.php");
    exit;
}

// BORRAR
if (isset($_GET['delete'])) {
    $slugToDelete = $_GET['delete'];
    $stmt = $db->prepare("SELECT imagen, galeria FROM tours WHERE slug = ?");
    $stmt->execute([$slugToDelete]);
    if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($r['imagen']) && file_exists($r['imagen'])) unlink($r['imagen']);
        if (!empty($r['galeria'])) {
            $gal = json_decode($r['galeria'], true) ?: [];
            foreach ($gal as $g) { if (file_exists($g)) unlink($g); }
        }
    }
    $stmt = $db->prepare("DELETE FROM tours WHERE slug = ?");
    $stmt->execute([$slugToDelete]);
    header("Location: admin.php");
    exit;
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }

// CARGAR PARA EDITAR
$tourToEdit = null;
$editingSlug = '';
if (isset($_GET['edit']) && isset($tours[$_GET['edit']])) {
    $d = $tours[$_GET['edit']];
    $editingSlug = $_GET['edit'];
    
    $tourToEdit = [
        'nombre' => $d['nombre'] ?? '',
        'precio_cop' => $d['precio_cop'] ?? '',
        'precio_promo' => $d['precio_promo'] ?? '',
        'rango_adulto' => $d['rango_adulto'] ?? '',
        'precio_nino' => $d['precio_nino'] ?? '',
        'rango_nino' => $d['rango_nino'] ?? '',
        'descripcion' => $d['descripcion'] ?? ($d['description'] ?? ''),
        'incluye' => $d['incluye'] ?? ($d['include'] ?? ''),
        'no_incluye' => $d['no_incluye'] ?? ($d['not_include'] ?? ''),
        'info_adicional' => $d['info_adicional'] ?? '', // Cargar nuevo campo
        'imagen' => $d['imagen'] ?? '',
        'galeria' => $d['galeria'] ?? []
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { padding-bottom: 50px; background-color: #f4f6f9; font-family: 'Poppins', sans-serif; }
        .card { border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: none; }
        .card-header { border-top-left-radius: 16px !important; border-top-right-radius: 16px !important; }
        .table { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .img-preview-mini { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .gallery-thumb-container { display: inline-block; margin: 5px; text-align: center; background: white; padding: 5px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .gallery-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; display: block; margin-bottom: 3px; }
        .btn { border-radius: 8px; }
        .btn-group-action { display: flex; gap: 5px; justify-content: flex-end; }
        @media (max-width: 576px) { .btn-group-action { flex-direction: column; } .btn-group-action .btn { width: 100%; } }
        .row-hidden { background-color: #e9ecef; opacity: 0.75; }
        .row-hidden td { color: #6c757d; }
        .badge-gallery { font-size: 0.65rem; background-color: #e7f1ff; color: #0d6efd; border: 1px solid #cce5ff; }
        
        /* Ajuste Summernote */
        .note-editor .note-toolbar { background: #f8f9fa; }
    </style>
</head>
<body class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="fw-bold mb-0">Panel de Control</h2></div>
        <div class="d-flex gap-2">
            <a href="?backup=1" class="btn btn-success btn-sm fw-bold">Backup</a>
            <a href="index.php" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">Web</a>
            <a href="?logout=1" class="btn btn-outline-secondary btn-sm">Salir</a>
        </div>
    </div>

    <div class="row mb-4 align-items-end">
        <div class="col-md-7 mb-3 mb-md-0">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-header bg-warning text-dark fw-bold">Configuración Global</div>
                <div class="card-body py-2">
                    <form id="configForm" class="row g-2 align-items-end">
                        <div class="col-2"><label class="small fw-bold">-$ USD</label><input type="number" name="margen_usd" class="form-control form-control-sm" value="<?= $config['margen_usd'] ?? 200 ?>"></div>
                        <div class="col-2"><label class="small fw-bold">-$ BRL</label><input type="number" name="margen_brl" class="form-control form-control-sm" value="<?= $config['margen_brl'] ?? 200 ?>"></div>
                        <div class="col-3"><label class="small fw-bold text-success">Ganancia %</label><input type="number" step="0.1" name="comision_agencia" class="form-control form-control-sm fw-bold border-success text-success" value="<?= $config['comision_agencia'] ?? 50 ?>" title="Define tú ganancia base. Ej: Si pones 45, tu ganas el 45% del margen y tu aliado el 55%."></div>
                        <div class="col-3"><label class="small fw-bold">Tema</label>
                            <select name="tema" class="form-select form-select-sm">
                                <?php 
                                if (is_dir(__DIR__ . '/temas')) {
                                    $dirs = scandir(__DIR__ . '/temas');
                                    foreach ($dirs as $d) {
                                        if ($d !== '.' && $d !== '..' && is_dir(__DIR__ . '/temas/' . $d)) {
                                            $selected = (($config['tema'] ?? 'default') === $d) ? 'selected' : '';
                                            echo "<option value=\"$d\" $selected>$d</option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-2"><button type="button" onclick="saveConfig()" class="btn btn-dark btn-sm w-100 px-0" title="Guardar"><i class="fa-solid fa-save"></i></button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-end">
             <div class="d-flex justify-content-end gap-3 align-items-center">
                 <div class="input-group shadow-sm bg-white rounded-pill overflow-hidden" style="max-width: 300px;">
                    <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="adminSearch" class="form-control border-0 bg-transparent shadow-none" placeholder="Buscar tour...">
                 </div>
                 <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#tourModal" onclick="clearForm()">
                    <i class="fa-solid fa-plus me-1"></i> Añadir Tour
                 </button>
             </div>
        </div>
    </div>

    <div class="modal modal-lg fade" id="tourModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white border-0">
            <h5 class="modal-title fw-bold" id="modalTitle"><?= $tourToEdit ? '✏️ Editando: '.htmlspecialchars($tourToEdit['nombre']) : '➕ Nuevo Tour' ?></h5>
            <button type="button" class="btn-close btn-close-white" <?= $tourToEdit ? 'onclick="window.location.href=\'admin.php\'"' : 'data-bs-dismiss="modal"' ?>></button>
          </div>
          <div class="modal-body p-4">
            <form id="mainForm" method="post" class="row g-3" enctype="multipart/form-data">
                <input type="hidden" name="original_slug" value="<?= $editingSlug ?>">

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nombre</label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control" required value="<?= htmlspecialchars($tourToEdit['nombre'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Slug</label>
                    <input type="text" name="slug" id="inputSlug" class="form-control bg-light text-muted" value="<?= $editingSlug ?>">
                </div>

                <div class="col-md-6 border-end">
                    <label class="form-label small fw-bold">Portada</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                    <?php if(!empty($tourToEdit['imagen'])): ?>
                        <div class="mt-1"><img src="<?= $tourToEdit['imagen'] ?>" class="img-preview-mini"> <small class="text-success">Guardada</small></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-primary">Galería</label>
                    <input type="file" name="galeria[]" class="form-control" accept="image/*" multiple>
                    <div class="mt-2">
                        <?php if(!empty($tourToEdit['galeria'])): ?>
                            <small class="d-block text-muted mb-1">Selecciona para borrar:</small>
                            <?php foreach($tourToEdit['galeria'] as $g): ?>
                                <div class="gallery-thumb-container">
                                    <img src="<?= $g ?>" class="gallery-thumb">
                                    <input type="checkbox" name="delete_imgs[]" value="<?= $g ?>" title="Borrar esta foto"> 🗑️
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 mt-3"><h6 class="text-primary border-bottom pb-1 small fw-bold">Información Básica</h6></div>
                
                <div class="col-12">
                    <label class="small fw-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($tourToEdit['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-success">Incluye</label>
                    <textarea name="incluye" class="form-control bg-success bg-opacity-10" rows="4"><?= htmlspecialchars($tourToEdit['incluye'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-danger">No Incluye</label>
                    <textarea name="no_incluye" class="form-control bg-danger bg-opacity-10" rows="4"><?= htmlspecialchars($tourToEdit['no_incluye'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12 mt-3">
                    <label class="small fw-bold text-dark mb-1"><i class="fa-solid fa-circle-info text-primary"></i> Información Adicional</label>
                    <textarea id="summernote" name="info_adicional"><?= $tourToEdit['info_adicional'] ?? '' ?></textarea>
                    <small class="text-muted">Aquí puedes poner horarios, puntos de encuentro y notas usando negritas, cursivas, listas, etc.</small>
                </div>

                <div class="col-12 mt-3"><h6 class="text-primary border-bottom pb-1 small fw-bold">Precios y Edades</h6></div>
                
                <div class="col-6 col-md-3">
                    <label class="small fw-bold text-dark">P. Público</label>
                    <input type="number" name="precio" class="form-control" required value="<?= $tourToEdit['precio_cop'] ?? '' ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="small fw-bold text-primary">N. Adultos B2B</label>
                    <input type="number" name="precio_neto" class="form-control border-primary" value="<?= $tourToEdit['precio_neto'] ?? '' ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="small fw-bold text-dark">P. Niños</label>
                    <input type="number" name="precio_nino" class="form-control" value="<?= $tourToEdit['precio_nino'] ?? '' ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="small fw-bold text-primary">N. Niños B2B</label>
                    <input type="number" name="precio_neto_nino" class="form-control border-primary" value="<?= $tourToEdit['precio_neto_nino'] ?? '' ?>">
                </div>
                
                <div class="col-md-6 mt-3">
                    <label class="small fw-bold text-danger">Precio de Promoción (Sólo Público)</label>
                    <input type="number" name="precio_promo" class="form-control border-danger" placeholder="Opcional" value="<?= $tourToEdit['precio_promo'] ?? '' ?>">
                </div>

                <div class="col-6">
                    <label class="small fw-bold text-muted">Edad Adultos</label>
                    <input type="text" name="rango_adulto" class="form-control bg-light" value="<?= htmlspecialchars($tourToEdit['rango_adulto'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="small fw-bold text-muted">Edad Niños</label>
                    <input type="text" name="rango_nino" class="form-control bg-light" value="<?= htmlspecialchars($tourToEdit['rango_nino'] ?? '') ?>">
                </div>

                <div class="col-12 mt-4"><button type="submit" name="add" class="btn btn-primary w-100 fw-bold">Guardar Cambios</button></div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive bg-white rounded-4 p-3 shadow-sm mb-5">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th class="ps-3">Tour</th><th class="text-end pe-3">Acción</th></tr></thead>
            <tbody>
                <?php foreach ($tours as $slug => $tour): 
                    $estaOculto = isset($tour['oculto']) && $tour['oculto'] == true;
                    $estaOcultoAli = isset($tour['oculto_aliado']) && $tour['oculto_aliado'] == true;
                    $cntFotos = (!empty($tour['galeria']) && is_array($tour['galeria'])) ? count($tour['galeria']) : 0;
                ?>
                <tr class="tour-row <?= $slug == $editingSlug ? 'table-warning' : '' ?>" data-nombre="<?= htmlspecialchars($tour['nombre']) ?>">
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <?php if(!empty($tour['imagen'])): ?>
                                <img src="<?= $tour['imagen'] ?>" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted border" style="width: 45px; height: 45px;"><i class="fa-regular fa-image"></i></div>
                            <?php endif; ?>

                            <div>
                                <div class="d-flex align-items-center flex-wrap gap-1">
                                    <?php if($estaOculto): ?><span class="badge bg-secondary" style="font-size:0.6rem">Oculto PUB</span><?php endif; ?>
                                    <?php if($estaOcultoAli): ?><span class="badge bg-info text-dark" style="font-size:0.6rem">Oculto ALI</span><?php endif; ?>
                                    <span class="fw-bold d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($tour['nombre']) ?></span>
                                </div>
                                <div class="small d-flex align-items-center gap-2">
                                    <span class="text-muted text-decoration-line-through border-end pe-2">$<?= number_format($tour['precio_cop']) ?></span>
                                    <span class="text-primary fw-bold">$<?= number_format($tour['precio_neto'] ?? 0) ?> Net</span>
                                    <?php if($cntFotos > 0): ?>
                                        <span class="badge badge-gallery"><i class="fa-solid fa-camera"></i> +<?= $cntFotos ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group-action justify-content-end align-items-center">
                            <div class="d-flex flex-column align-items-center me-3 border-end pe-3 border-start ps-3">
                                <span style="font-size:0.55rem; font-weight:800; letter-spacing:0.5px" class="text-success mb-1">PÚB.</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input mt-0 cursor-pointer text-success border-success" type="checkbox" role="switch" <?= !$estaOculto ? 'checked' : '' ?> onchange="toggleHide('<?= $slug ?>', this, 'publico')" title="Visible al público">
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-center me-3">
                                <span style="font-size:0.55rem; font-weight:800; letter-spacing:0.5px" class="text-primary mb-1">ALI.</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input mt-0 cursor-pointer text-primary border-primary" type="checkbox" role="switch" <?= !$estaOcultoAli ? 'checked' : '' ?> onchange="toggleHide('<?= $slug ?>', this, 'aliado')" title="Visible a aliados">
                                </div>
                            </div>
                            <a href="?edit=<?= $slug ?>" class="btn btn-warning btn-sm text-dark h-50">Editar</a>
                            <a href="?delete=<?= $slug ?>" class="btn btn-danger btn-sm h-50 ms-1" onclick="return confirm('¿Borrar permanente?');">Borrar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMsg">Cambios guardados.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    
    <script>
        <?php if($tourToEdit): ?>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('tourModal'));
            myModal.show();
        });
        <?php endif; ?>

        function showToast(msg, isError = false) {
            const t = document.getElementById('liveToast');
            t.className = 'toast align-items-center text-white border-0 ' + (isError ? 'bg-danger' : 'bg-success');
            document.getElementById('toastMsg').innerText = msg;
            new bootstrap.Toast(t).show();
        }

        function saveConfig() {
            const formData = new FormData(document.getElementById('configForm'));
            formData.append('ajax_config', '1');
            fetch('admin.php', { method: 'POST', body: formData })
                .then(r => r.json()).then(d => { if(d.success) showToast('Tasas actualizadas'); });
        }

        function toggleHide(slug, el, tipo = 'publico') {
            const formData = new FormData();
            formData.append('ajax_toggle', '1');
            formData.append('slug', slug);
            formData.append('tipo', tipo);
            fetch('admin.php', { method: 'POST', body: formData })
                .then(r => r.json()).then(d => {
                    if(d.success) showToast(d.oculto ? 'Tour Oculto' : 'Tour Visible');
                    else el.checked = !el.checked;
                });
        }

        const adminSearch = document.getElementById('adminSearch');
        if (adminSearch) {
            adminSearch.addEventListener('keyup', function() {
                const term = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                document.querySelectorAll('.tour-row').forEach(row => {
                    const txt = row.getAttribute('data-nombre').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    row.style.display = txt.includes(term) ? '' : 'none';
                });
            });
        }

        function clearForm() {
            document.getElementById('mainForm').reset();
            document.getElementById('modalTitle').innerHTML = '➕ Nuevo Tour';
            document.getElementById('inputSlug').value = '';
            document.querySelector('[name="original_slug"]').value = '';
            $('#summernote').summernote('code', '');
        }

        document.querySelector('input[name="imagen"]').addEventListener('change', function(e){
            const file = e.target.files[0];
            if(file) {
                let preview = document.getElementById('portadaPreview');
                if(!preview) {
                    preview = document.createElement('img');
                    preview.id = 'portadaPreview';
                    preview.className = 'img-preview-mini mt-2 d-block';
                    this.parentNode.appendChild(preview);
                }
                preview.src = URL.createObjectURL(file);
            }
        });

        // Inicializar editor
        $('#summernote').summernote({
            placeholder: 'Escribe aquí la información adicional...',
            tabsize: 2,
            height: 150,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });

        const inputNombre = document.getElementById('inputNombre');
        const inputSlug = document.getElementById('inputSlug');

        if (inputNombre && inputSlug) {
            let slugManual = false;
            if (inputSlug.value.trim() !== '') { slugManual = true; }

            inputNombre.addEventListener('input', function () {
                if (!slugManual || inputSlug.value === '') {
                    let texto = this.value.trim();
                    let slug = texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
                    inputSlug.value = slug;
                }
            });
            inputSlug.addEventListener('input', function() { slugManual = true; });
        }
    </script>
</body>
</html>