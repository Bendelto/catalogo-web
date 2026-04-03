<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php if(isset($metaImage)): ?><meta property="og:image" content="<?= $metaImage ?>"><?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; color: #333; padding-bottom: 40px; }
        .main-container { max-width: 1200px; margin: 0 auto; }
        
        /* --- CONTENEDOR VISTA TOUR --- */
        .calc-container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding-bottom: 80px; 
        }
        /* Aumento en escritorio para mayor amplitud */
        @media (min-width: 992px) {
            .calc-container {
                max-width: 1000px;
            }
        }
        /* ---------------------------- */

        /* --- HEADER --- */
        .site-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
            margin-bottom: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .main-logo {
            width: 180px;
            max-width: 65%;
            height: auto;
            display: inline-block;
        }

        @media (min-width: 992px) {
            .site-header { 
                padding: 20px 0; 
            }
            .main-logo { 
                width: 250px; 
                max-width: 100%;
                display: block;
            }
        }
        /* --- FIN HEADER --- */

        .card-price { border: 0; border-radius: 16px; box-shadow: 0 10px 30px -4px rgba(0,0,0,0.08); text-decoration: none; color: inherit; display: block; background: white; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); overflow: hidden; height: 100%; position: relative; }
        .card-price:hover { box-shadow: 0 20px 40px -4px rgba(0,0,0,0.12); transform: translateY(-8px); }
        .tour-img-list { width: 100%; height: 200px; object-fit: cover; border-bottom: 1px solid #f0f0f0; transition: transform 0.5s ease; }
        .card-price:hover .tour-img-list { transform: scale(1.05); }
        .badge-oferta { position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white; padding: 5px 12px; border-radius: 50px; font-weight: 800; font-size: 0.75rem; box-shadow: 0 4px 10px rgba(255,75,43,0.3); z-index: 2; }

        .gallery-reel-container { width: 100%; overflow-x: auto; display: flex; gap: 10px; padding-bottom: 10px; scroll-snap-type: x mandatory; margin-bottom: 15px; }
        .gallery-reel-item { height: 38vh; width: auto; max-width: none; border-radius: 12px; scroll-snap-align: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: zoom-in; background: #fff; }
        @media (min-width: 768px) { .gallery-reel-item { height: 350px; } }
        
        /* ESTILOS LIGHTBOX */
        #lightbox { display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); align-items: center; justify-content: center; user-select: none; }
        #lightbox img { max-width: 90%; max-height: 85vh; object-fit: contain; pointer-events: none; }
        
        /* Flechas de navegación */
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 3rem;
            cursor: pointer;
            padding: 20px;
            z-index: 10001;
            transition: color 0.3s;
        }
        .lightbox-nav:hover { color: #fff; }
        .lightbox-nav.prev { left: 5px; }
        .lightbox-nav.next { right: 5px; }
        
        @media (max-width: 576px) {
            .lightbox-nav { font-size: 2rem; padding: 10px; }
        }

        .info-box { background: white; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); border-left: 3px solid #f0f4ff; }
        
        /* Incluye / No Incluye Pills */
        .section-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; }
        .section-pill.incluye { background: #d1fae5; color: #065f46; }
        .section-pill.no-incluye { background: #fee2e2; color: #991b1b; }
        .list-check li { list-style: none; padding: 6px 10px; margin-bottom: 6px; font-size: 0.85rem; border-radius: 8px; transition: background 0.2s, transform 0.2s; }
        .list-check.incluye-list li:hover { background: #f0fdf4; transform: translateX(3px); }
        .list-check.no-incluye-list li:hover { background: #fff5f5; transform: translateX(3px); }

        /* Descripción expandible */
        .desc-text { white-space: pre-line; line-height: 1.8; font-size: 0.92rem; color: #4a5568; border-left: 3px solid #e8f0fe; padding-left: 14px; }
        .desc-collapsed { max-height: 120px; overflow: hidden; position: relative; }
        .desc-collapsed::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 50px; background: linear-gradient(transparent, white); }
        .btn-leer-mas { background: none; border: none; color: #0d6efd; font-size: 0.85rem; font-weight: 600; padding: 0; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-top: 8px; }

        .accordion-item { border: 0; border-radius: 12px !important; overflow: hidden; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .accordion-button:not(.collapsed) { background-color: #f1f8ff; color: #0d6efd; font-weight: 600; }

        h4, h6, .tour-title { font-weight: 700; color: #1a1a1a; letter-spacing: -0.5px; }

        /* Tarjeta de Precios Mejorada */
        .price-card-hero { background: white; border-radius: 16px; padding: 18px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border-left: 4px solid #0d6efd; position: relative; overflow: hidden; }
        .price-card-hero::before { content: ''; position: absolute; top: 0; right: 0; width: 120px; height: 120px; background: radial-gradient(circle, rgba(13,110,253,0.05) 0%, transparent 70%); }
        .price-cop-highlight { color: #0d47a1; font-weight: 800; font-size: 1.7rem; display: block; line-height: 1.1; letter-spacing: -1px; }
        .price-old { text-decoration: line-through; color: #999; font-size: 0.8rem; font-weight: 400; display: block; margin-bottom: 2px; }
        .price-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 4px; display: block; }
        .currency-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; margin-top: 6px; }
        .currency-pill.usd { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .currency-pill.brl { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .price-divider { width: 1px; background: linear-gradient(to bottom, transparent, #e2e8f0, transparent); align-self: stretch; margin: 0 10px; }
        .rates-footnote { font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 6px; padding-top: 10px; margin-top: 10px; border-top: 1px solid #f1f5f9; }

        .flag-icon { width: 16px !important; height: auto; vertical-align: middle; margin-right: 4px; box-shadow: none; flex-shrink: 0; border-radius: 2px; }

        /* Calculadora táctil */
        .calc-box { background-color: #fff; border-radius: 16px; padding: 22px; border: 1px solid #edf2f7; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        .qty-control { display: flex; align-items: center; justify-content: center; gap: 0; border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .qty-btn { background: #f8fafc; border: none; width: 44px; height: 44px; font-size: 1.3rem; font-weight: 700; color: #0d6efd; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .qty-btn:active { background: #dbeafe; }
        .qty-val { width: 50px; text-align: center; font-weight: 700; font-size: 1.1rem; color: #1a1a1a; background: white; border: none; border-left: 1.5px solid #e2e8f0; border-right: 1.5px solid #e2e8f0; height: 44px; font-family: 'Poppins', sans-serif; }
        .qty-val:focus { outline: none; }
        .total-display { background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); border: 1px solid #bfdbfe; border-radius: 14px; padding: 20px; margin-top: 20px; }
        .total-cop-num { font-size: 2rem; font-weight: 800; color: #1d4ed8; letter-spacing: -1px; transition: all 0.3s ease; }
        .total-currency-pill { background: white; border-radius: 10px; padding: 10px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); flex: 1; text-align: center; }

        .btn-back { background-color: #e9ecef; color: #333; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; transition: all 0.2s; flex-shrink: 0; }
        .btn-back:hover { background: #dee2e6; }
        .btn-back:active { transform: scale(0.9); }

        .btn-share-native { background-color: #f8f9fa; color: #0d6efd; width: 40px; height: 40px; border-radius: 50%; border: 1px solid #dee2e6; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; transition: transform 0.2s; flex-shrink: 0; }
        .btn-share-native:active { transform: scale(0.9); }

        .btn-whatsapp-desktop { background: linear-gradient(135deg, #25D366, #1ebc57); color: white; font-weight: 700; border: none; border-radius: 50px; padding: 16px; text-decoration: none; display: block; text-align: center; transition: all 0.3s; font-size: 1.05rem; box-shadow: 0 6px 20px rgba(37, 211, 102, 0.35); }
        .btn-whatsapp-desktop:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(37, 211, 102, 0.45); color: white; }

        .btn-whatsapp-mobile { position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); z-index: 1050; background: linear-gradient(135deg, #25D366, #1ebc57); color: white; padding: 14px 30px; border-radius: 50px; box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4); font-weight: 700; font-size: 1rem; text-decoration: none; display: flex; align-items: center; gap: 10px; white-space: nowrap; transition: transform 0.2s; }
        .btn-whatsapp-mobile:active { transform: translateX(-50%) scale(0.95); }

        .btn-subtle { background-color: transparent; border: 1px solid #ced4da; color: #6c757d; border-radius: 50px; padding: 10px 20px; font-size: 0.9rem; width: 100%; display: block; text-align: center; text-decoration: none; transition: all 0.3s; margin-top: 20px; }
        .btn-subtle:hover { background-color: #e9ecef; border-color: #adb5bd; color: #495057; }

        /* Badge oferta mejorado */
        .badge-oferta-hero { display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white; padding: 4px 12px; border-radius: 50px; font-weight: 800; font-size: 0.7rem; box-shadow: 0 4px 10px rgba(255,75,43,0.3); }

        /* Animación entrada secciones */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-up { animation: fadeInUp 0.4s ease forwards; }
        .fade-in-up:nth-child(2) { animation-delay: 0.05s; }
        .fade-in-up:nth-child(3) { animation-delay: 0.1s; }
        .fade-in-up:nth-child(4) { animation-delay: 0.15s; }

        .search-container { max-width: 500px; margin: 0 auto 15px auto; position: relative; width: 100%; }
        @media (min-width: 992px) {
            .search-container.in-header {
                margin: 0;
                width: 400px;
            }
        }

        .search-input { width: 100%; padding: 14px 20px 14px 50px; border-radius: 50px; border: 1px solid #eee; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.03); outline: none; transition: all 0.3s ease; font-size: 1rem; font-family: 'Poppins', sans-serif; }
        .search-input:focus { border-color: #0d6efd; box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15); transform: translateY(-1px); }
        .search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #bbb; font-size: 1.1rem; }
        
        /* ESTILOS NUEVA BARRA DE FILTROS */
        .controls-bar {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            border: 1px solid #eee;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        @media (min-width: 768px) {
            .controls-bar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .sort-select {
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 30px 8px 15px;
            font-size: 0.9rem;
            color: #495057;
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
            cursor: pointer;
            min-width: 200px;
        }
        .filter-switches {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .form-check-label {
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
            color: #555;
        }

        .conversion-info { font-size: 0.75rem; color: #777; display: flex; align-items: center; gap: 8px; }
        .conversion-info strong { color: #444; }
    </style>
    <div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"es","native_language_names":true,"detect_browser_language":true,"url_structure":"sub_domain","languages":["es","pt","en","it","fr","de"],"wrapper_selector":".gtranslate_wrapper","switcher_horizontal_position":"right","switcher_vertical_position":"top","float_switcher_open_direction":"bottom","alt_flags":{"en":"usa","pt":"brazil","es":"colombia"}}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
</head>
<body>

<div class="site-header">
    <div class="container main-container d-lg-flex justify-content-between align-items-center">
        <div class="text-start mb-3 mb-lg-0">
            <a href="./">
                <img src="logo.svg" alt="Descubre Cartagena" class="main-logo">
            </a>
        </div>
        
        <?php if (!$singleTour): ?>
            <div class="search-container in-header">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="¿Qué te gustaría hacer?">
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="lightbox">
    <button class="lightbox-nav prev" onclick="changeImg(-1)">&#10094;</button>
    <img id="lightbox-img" src="">
    <button class="lightbox-nav next" onclick="changeImg(1)">&#10095;</button>
</div>

<div class="container main-container">
<?php if ($singleTour): ?>
    <div class="calc-container">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3" style="flex: 1;">
                <a href="./" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
                <h4 class="mb-0 lh-sm" style="font-size: 1.15rem;"><?= htmlspecialchars($singleTour['nombre']) ?></h4>
            </div>
            <button class="btn-share-native ms-2" onclick="shareNative()" title="Compartir">
                <i class="fa-solid fa-share-nodes"></i>
            </button>
        </div>

        <?php 
            $imagenesParaMostrar = [];
            if(!empty($singleTour['imagen'])) $imagenesParaMostrar[] = $singleTour['imagen'];
            if(!empty($singleTour['galeria'])) foreach($singleTour['galeria'] as $gImg) $imagenesParaMostrar[] = $gImg;
        ?>
        <?php if(count($imagenesParaMostrar) > 0): ?>
            <div class="gallery-reel-container">
                <?php foreach($imagenesParaMostrar as $idx => $imgSrc): ?>
                    <img src="<?= $imgSrc ?>" class="gallery-reel-item" onclick="openLightbox(<?= $idx ?>)" alt="Foto">
                <?php endforeach; ?>
            </div>
            <div class="text-center text-muted small mb-4" style="font-size:0.75rem;"><i class="fa-solid fa-hand-pointer"></i> Desliza o toca para ampliar</div>
        <?php endif; ?>

        <!-- TARJETA DE PRECIOS MEJORADA -->
        <div class="price-card-hero fade-in-up">
            <?php if($usarPromo): ?>
                <div class="mb-2"><span class="badge-oferta-hero"><i class="fa-solid fa-bolt"></i> OFERTA ESPECIAL</span></div>
            <?php endif; ?>
            <div class="d-flex align-items-stretch">
                <!-- ADULTOS -->
                <div class="flex-fill">
                    <span class="price-label">Adulto <?= !empty($singleTour['rango_adulto']) ? '('.$singleTour['rango_adulto'].')' : '' ?></span>
                    <?php if($usarPromo): ?>
                        <span class="price-old">$<?= number_format($precioBase) ?> COP</span>
                    <?php endif; ?>
                    <span class="price-cop-highlight">$<?= number_format($precioFinalCalc) ?> <small style="font-size:0.85rem;font-weight:500;color:#64748b;">COP</small></span>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="currency-pill usd"><img src="https://flagcdn.com/w40/us.png" class="flag-icon">USD $<?= precio_inteligente($precioFinalCalc / $tasa_tuya_usd) ?></span>
                        <span class="currency-pill brl"><img src="https://flagcdn.com/w40/br.png" class="flag-icon">BRL R$<?= precio_inteligente($precioFinalCalc / $tasa_tuya_brl) ?></span>
                    </div>
                </div>
                <?php if(!empty($singleTour['precio_nino'])): ?>
                <!-- Divider -->
                <div class="price-divider"></div>
                <!-- NIÑOS -->
                <div class="flex-fill ps-2">
                    <span class="price-label">Niño <?= !empty($singleTour['rango_nino']) ? '('.$singleTour['rango_nino'].')' : '' ?></span>
                    <span class="price-cop-highlight">$<?= number_format($singleTour['precio_nino']) ?> <small style="font-size:0.85rem;font-weight:500;color:#64748b;">COP</small></span>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="currency-pill usd"><img src="https://flagcdn.com/w40/us.png" class="flag-icon">USD $<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_usd) ?></span>
                        <span class="currency-pill brl"><img src="https://flagcdn.com/w40/br.png" class="flag-icon">BRL R$<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_brl) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="rates-footnote">
                <i class="fa-regular fa-clock"></i> Tasas del día — USD: <strong>$<?= number_format($tasa_tuya_usd, 0) ?></strong> &nbsp;|&nbsp; BRL: <strong>$<?= number_format($tasa_tuya_brl, 0) ?></strong> COP
            </div>
        </div>

        <?php 
            if(!empty($desc)): 
                $wordCount = str_word_count(strip_tags($desc));
                $isLong = $wordCount > 60;
        ?>
        <div class="info-box fade-in-up">
            <div id="descWrapper" class="desc-text <?= $isLong ? 'desc-collapsed' : '' ?>"><?= htmlspecialchars($desc) ?></div>
            <?php if($isLong): ?>
                <button class="btn-leer-mas" id="btnLeerMas" onclick="toggleDesc()">
                    <i class="fa-solid fa-chevron-down" id="iconLeerMas"></i> Leer m&aacute;s
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="info-box fade-in-up">
            <div class="row g-3">
                <?php if(!empty(trim($inc))): ?>
                <div class="col-12 col-md-6">
                    <span class="section-pill incluye"><i class="fa-solid fa-circle-check"></i> Incluye</span>
                    <ul class="list-check incluye-list ps-0 m-0 text-secondary">
                        <?php foreach(explode("\n", $inc) as $item): if(trim($item)=='')continue; ?>
                            <li><i class="fa-solid fa-check text-success me-2"></i><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if(!empty(trim($no_inc))): ?>
                <div class="col-12 col-md-6">
                    <span class="section-pill no-incluye"><i class="fa-solid fa-circle-xmark"></i> No incluye</span>
                    <ul class="list-check no-incluye-list ps-0 m-0 text-secondary">
                        <?php foreach(explode("\n", $no_inc) as $item): if(trim($item)=='')continue; ?>
                            <li><i class="fa-solid fa-xmark text-danger me-2"></i><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!empty($info_adicional)): ?>
        <div class="info-box fade-in-up mb-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i> Información Adicional</h6>
            <div class="text-secondary" style="font-size: 0.85rem;">
                <?= $info_adicional ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- BOTONES DE RESERVA EN PÁGINA (Activan el Modal) -->
        <div class="d-none d-md-block mt-5 mb-3">
            <button class="btn-whatsapp-desktop shadow w-100 py-3" style="font-size: 1.15rem;" data-bs-toggle="modal" data-bs-target="#reservaModal" onclick="setModalMode('whatsapp')">
                <i class="fa-brands fa-whatsapp fa-xl me-2"></i> Reservar por WhatsApp
            </button>
        </div>

        <div class="text-center mb-4">
            <a href="#" data-bs-toggle="modal" data-bs-target="#reservaModal" class="text-decoration-none text-muted small" style="transition: opacity 0.2s;" onmouseover="this.style.opacity='0.6'" onmouseout="this.style.opacity='1'" onclick="setModalMode('email')">
                <i class="fa-regular fa-envelope me-1"></i> O reservar por correo electrónico
            </a>
        </div>

        <a href="./" class="btn-subtle mb-5">Ver todos los tours</a>

        <!-- Botón Flotante para Móviles (Activa el Modal) -->
        <button class="btn-whatsapp-mobile d-md-none border-0" data-bs-toggle="modal" data-bs-target="#reservaModal" style="width: calc(100% - 30px); justify-content: center; padding: 16px; bottom: 15px; left: 15px; transform: none; z-index: 1045;" onclick="setModalMode('whatsapp')">
            <i class="fa-brands fa-whatsapp fa-xl me-2"></i> Reservar por WhatsApp
        </button>

        <!-- ======================= -->
        <!-- MODAL DE RESERVAS -->
        <!-- ======================= -->
        <div class="modal fade" id="reservaModal" tabindex="-1" aria-labelledby="reservaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark w-100 text-center" id="reservaModalLabel">Tu Reserva</h5>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px;"></button>
                    </div>
                    
                    <div class="modal-body px-4">
                        <div class="mb-4 text-center">
                            <h6 class="text-primary fw-bold mb-1 lh-sm"><?= htmlspecialchars($singleTour['nombre']) ?></h6>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary small mb-2"><i class="fa-regular fa-calendar me-1"></i> FECHA DEL TOUR</label>
                            <input type="date" id="resDate" class="form-control form-control-lg rounded-3 border-light shadow-sm bg-light" required>
                        </div>

                        <label class="form-label fw-bold text-secondary small mb-3"><i class="fa-solid fa-users me-1"></i> ¿CUÁNTAS PERSONAS?</label>
                        <div class="row g-2 mb-4 justify-content-center">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3 text-center border-light shadow-sm">
                                    <span class="d-block small text-secondary fw-semibold mb-2 lh-sm">Adultos<br><small class="text-muted fw-normal"><?= !empty($singleTour['rango_adulto']) ? '('.$singleTour['rango_adulto'].')' : '(10+ años)' ?></small></span>
                                    <div class="qty-control shadow-sm mx-auto bg-white" style="max-width: 120px;">
                                        <button class="qty-btn" onclick="changeQty('qtyAdult', -1)" type="button">&#8722;</button>
                                        <input type="number" id="qtyAdult" class="qty-val border-0" value="1" min="1" readonly>
                                        <button class="qty-btn" onclick="changeQty('qtyAdult', 1)" type="button">&#43;</button>
                                    </div>
                                </div>
                            </div>
                            <?php if(!empty($singleTour['precio_nino'])): ?>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3 text-center border-light shadow-sm">
                                    <span class="d-block small text-secondary fw-semibold mb-2 lh-sm">Niños<br><small class="text-muted fw-normal"><?= !empty($singleTour['rango_nino']) ? '('.$singleTour['rango_nino'].')' : '(3-9 años)' ?></small></span>
                                    <div class="qty-control shadow-sm mx-auto bg-white" style="max-width: 120px;">
                                        <button class="qty-btn" onclick="changeQty('qtyKid', -1)" type="button">&#8722;</button>
                                        <input type="number" id="qtyKid" class="qty-val border-0" value="0" min="0" readonly>
                                        <button class="qty-btn" onclick="changeQty('qtyKid', 1)" type="button">&#43;</button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="total-display text-center shadow-sm">
                            <div class="price-label mb-1">TOTAL ESTIMADO</div>
                            <div class="total-cop-num mb-3" id="totalCOP">$0</div>
                            <div class="d-flex gap-2 justify-content-center">
                                <div class="bg-white rounded p-2 text-center flex-fill border">
                                    <div style="font-size:0.65rem;color:#166534;font-weight:700;"><img src="https://flagcdn.com/w40/us.png" class="flag-icon"> USD</div>
                                    <div class="fw-bold text-success" id="totalUSD">$0</div>
                                </div>
                                <div class="bg-white rounded p-2 text-center flex-fill border">
                                    <div style="font-size:0.65rem;color:#1e40af;font-weight:700;"><img src="https://flagcdn.com/w40/br.png" class="flag-icon"> BRL</div>
                                    <div class="fw-bold text-primary" id="totalBRL">R$ 0</div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-top-0 pt-0 pb-4 px-4 flex-column gap-2">
                        <button type="button" id="btnModalWA" class="btn-whatsapp-desktop w-100 shadow m-0" onclick="sendWhatsApp()">
                            <i class="fa-brands fa-whatsapp fa-xl me-2"></i> Solicitar por WhatsApp
                        </button>
                        <button type="button" id="btnModalEmail" class="btn btn-primary w-100 m-0 shadow" onclick="sendEmail()" style="border-radius: 50px; padding: 12px; font-weight: 600; display: none;">
                            <i class="fa-regular fa-envelope me-2"></i> Solicitar por Correo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- FIN MODAL -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setModalMode(mode) {
            const btnWA = document.getElementById('btnModalWA');
            const btnEmail = document.getElementById('btnModalEmail');
            if (mode === 'whatsapp') {
                btnWA.style.display = 'block';
                btnEmail.style.display = 'none';
            } else {
                btnWA.style.display = 'none';
                btnEmail.style.display = 'block';
            }
        }

        const priceAdult = <?= $precioFinalCalc ?>;
        const priceKid = <?= $singleTour['precio_nino'] ?: 0 ?>;
        const rateUsd = <?= $tasa_tuya_usd ?>; const rateBrl = <?= $tasa_tuya_brl ?>;
        
        const inputAdult = document.getElementById('qtyAdult');
        const inputKid = document.getElementById('qtyKid');
        const dCOP = document.getElementById('totalCOP');
        const dUSD = document.getElementById('totalUSD');
        const dBRL = document.getElementById('totalBRL');
        const resDate = document.getElementById('resDate');

        // Configurar min date para hoy
        const today = new Date().toISOString().split('T')[0];
        if(resDate) resDate.setAttribute('min', today);

        function fmt(n){ return '$' + new Intl.NumberFormat('es-CO').format(n); }
        function pInt(v){ return Math.ceil(v * 2) / 2; }
        
        function calc() {
            let t = (parseInt(inputAdult ? inputAdult.value : 0)||0)*priceAdult + (parseInt(inputKid ? inputKid.value : 0)||0)*priceKid;
            if(dCOP) dCOP.innerText = fmt(t);
            if(dUSD) dUSD.innerText = '$' + pInt(t/rateUsd);
            if(dBRL) dBRL.innerText = 'R$ ' + pInt(t/rateBrl);
            // Animación pulso en total
            if(dCOP) {
                dCOP.style.transform = 'scale(1.06)';
                setTimeout(() => dCOP.style.transform = 'scale(1)', 150);
            }
        }
        
        function changeQty(id, delta) {
            const el = document.getElementById(id);
            if(!el) return;
            const min = parseInt(el.min) || 0;
            el.value = Math.max(min, (parseInt(el.value) || 0) + delta);
            calc();
        }
        
        // Enviar a WhatsApp formatteado
        function sendWhatsApp() {
            if(!resDate.value) {
                resDate.classList.add('is-invalid');
                resDate.focus();
                // Opcional: mostrar un Toast o alert "Por favor, selecciona una fecha"
                return;
            }
            resDate.classList.remove('is-invalid');

            // Format date to local standard DD/MM/YYYY
            const selectedDate = new Date(resDate.value);
            // fix timezone offset issue for pure dates
            selectedDate.setMinutes(selectedDate.getMinutes() + selectedDate.getTimezoneOffset());
            const strDate = selectedDate.toLocaleDateString('es-CO');

            let vAdult = inputAdult ? inputAdult.value : 1;
            let vKid = inputKid ? inputKid.value : 0;
            let currentUrl = window.location.href;

            let msg = `Hola Descubre Cartagena! 👋 Me gustaría reservar el siguiente tour:\n\n`;
            msg += `📍 *<?= htmlspecialchars($singleTour['nombre']) ?>*\n`;
            msg += `🗓️ *Fecha:* ${strDate}\n`;
            msg += `🔗 ${currentUrl}\n\n`;
            
            msg += `👥 *Personas:*\n`;
            msg += `  • Adultos: ${vAdult}\n`;
            if (vKid > 0) msg += `  • Niños: ${vKid}\n`;
            
            msg += `\n💰 *Total estimado:*\n`;
            msg += `  • ${dCOP.innerText} COP\n`;
            msg += `  • USD ${dUSD.innerText}\n`;
            msg += `  • BRL ${dBRL.innerText}\n\n`;
            msg += `⏰ Quedo atento para coordinar detalles. ¡Gracias!`;

            const waUrl = "https://wa.me/573205899997?text=" + encodeURIComponent(msg);
            window.open(waUrl, '_blank');
        }

        function sendEmail() {
            if(!resDate.value) {
                resDate.classList.add('is-invalid');
                resDate.focus();
                return;
            }
            resDate.classList.remove('is-invalid');

            const selectedDate = new Date(resDate.value);
            selectedDate.setMinutes(selectedDate.getMinutes() + selectedDate.getTimezoneOffset());
            const strDate = selectedDate.toLocaleDateString('es-CO');

            let vAdult = inputAdult ? inputAdult.value : 1;
            let vKid = inputKid ? inputKid.value : 0;
            let currentUrl = window.location.href;

            let mailSubj = `Reserva: <?= htmlspecialchars($singleTour['nombre']) ?> - ${strDate}`;
            
            let msg = `Hola Descubre Cartagena!\n\nMe gustaría reservar el siguiente tour:\n\n`;
            msg += `Tour: <?= htmlspecialchars($singleTour['nombre']) ?>\n`;
            msg += `Fecha: ${strDate}\n`;
            msg += `Enlace: ${currentUrl}\n\n`;
            
            msg += `Personas:\n`;
            msg += `- Adultos: ${vAdult}\n`;
            if (vKid > 0) msg += `- Niños: ${vKid}\n`;
            
            msg += `\nTotal estimado:\n`;
            msg += `- ${dCOP.innerText} COP\n`;
            msg += `- USD ${dUSD.innerText}\n`;
            msg += `- BRL ${dBRL.innerText}\n\n`;
            msg += `Quedo atento a su respuesta para gestionar la reserva.\n\nGracias.`;

            const mailToUrl = "mailto:info@descubrecartagena.com?subject=" + encodeURIComponent(mailSubj) + "&body=" + encodeURIComponent(msg);
            window.location.href = mailToUrl;
        }

        calc();

        // Descripción expandible
        function toggleDesc() {
            const wrap = document.getElementById('descWrapper');
            const btn = document.getElementById('btnLeerMas');
            const icon = document.getElementById('iconLeerMas');
            const collapsed = wrap.classList.toggle('desc-collapsed');
            btn.innerHTML = collapsed
                ? '<i class="fa-solid fa-chevron-down" id="iconLeerMas"></i> Leer más'
                : '<i class="fa-solid fa-chevron-up" id="iconLeerMas"></i> Leer menos';
        }

        // --- GALERIA INTERACTIVA (SLIDER / SWIPE) ---
        
        const galleryImages = <?= json_encode($imagenesParaMostrar) ?>;
        let currentImgIndex = 0;

        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        
        function openLightbox(index) {
            currentImgIndex = index;
            updateLightboxImage();
            lightbox.style.display = 'flex';
        }

        function updateLightboxImage() {
            if(galleryImages.length > 0) {
                lightboxImg.src = galleryImages[currentImgIndex];
            }
        }

        function changeImg(step) {
            currentImgIndex += step;
            if(currentImgIndex >= galleryImages.length) currentImgIndex = 0;
            if(currentImgIndex < 0) currentImgIndex = galleryImages.length - 1;
            updateLightboxImage();
        }

        function closeLightbox() {
            lightbox.style.display = 'none';
        }
        
        lightbox.addEventListener('click', function(e){
            // Cierra si el click fue en el fondo negro
            if(e.target === lightbox) {
                closeLightbox();
            }
        });

        // --- LÓGICA DE SWIPE (TACTIL) ---
        let touchStartX = 0;
        let touchEndX = 0;

        lightbox.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, {passive: true});

        lightbox.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, {passive: true});

        function handleSwipe() {
            if (touchStartX - touchEndX > 50) {
                changeImg(1);
            }
            if (touchEndX - touchStartX > 50) {
                changeImg(-1);
            }
        }

        function shareNative() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= htmlspecialchars($singleTour['nombre']) ?>',
                    text: '¡Mira este plan increíble en Cartagena!',
                    url: window.location.href
                }).catch(console.error);
            } else {
                navigator.clipboard.writeText(window.location.href).then(function() {
                    alert("¡Enlace copiado! Compártelo con tus amigos.");
                });
            }
        }
    </script>

<?php else: ?>
    
    <div class="controls-bar">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-sort text-secondary"></i>
            <select class="form-select border-0 bg-transparent shadow-none p-0 ps-1 fw-bold text-secondary" id="sortSelect" onchange="applyFilters()">
                <option value="nombre_asc">Nombre (A-Z)</option>
                <option value="precio_asc">Precio (Menor a Mayor)</option>
                <option value="precio_desc">Precio (Mayor a Menor)</option>
            </select>
        </div>

        <div class="filter-switches mt-2 mt-md-0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filterOfertas" onchange="applyFilters()">
                <label class="form-check-label" for="filterOfertas">Solo Ofertas</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filterNinos" onchange="applyFilters()">
                <label class="form-check-label" for="filterNinos">Planes con Niños</label>
            </div>
        </div>
    </div>
    
    <div class="row g-4" id="toursGrid">
        <?php foreach ($tours as $slug => $tour): 
            if(!empty($tour['oculto']) && $tour['oculto'] == true) continue;
            
            $pBase = $tour['precio_cop'];
            $pPromo = $tour['precio_promo'] ?? 0;
            $esOferta = ($pPromo > 0 && $pPromo < $pBase);
            $pFinal = $esOferta ? $pPromo : $pBase;
            $tienePrecioNino = (!empty($tour['precio_nino']) && $tour['precio_nino'] > 0);
        ?>
        <div class="col-12 col-md-6 col-lg-4 tour-card-col" 
             data-nombre="<?= htmlspecialchars($tour['nombre']) ?>" 
             data-precio="<?= $pFinal ?>"
             data-oferta="<?= $esOferta ? '1' : '0' ?>"
             data-nino="<?= $tienePrecioNino ? '1' : '0' ?>">
            <a href="./<?= $slug ?>" class="card card-price">
                <?php if(!empty($tour['imagen'])): ?><img src="<?= $tour['imagen'] ?>" class="tour-img-list"><?php endif; ?>
                
                <?php if($esOferta): ?>
                    <span class="badge-oferta">OFERTA</span>
                <?php endif; ?>

                <div class="p-4">
                    <h6 class="fw-bold mb-3 text-dark lh-base tour-title" style="font-size: 1.1rem;"><?= htmlspecialchars($tour['nombre']) ?></h6>
                    <div class="mb-3" style="min-height: 50px; display: flex; flex-direction: column; justify-content: center;">
                        <?php if($esOferta): ?>
                            <span class="price-old" style="font-size: 0.75rem;">$<?= number_format($pBase) ?></span>
                        <?php endif; ?>
                        <span class="price-cop-highlight" style="font-size: 1.2rem;">
                            $<?= number_format($pFinal) ?> <small class="text-muted fw-normal" style="font-size: 0.75rem;">COP</small>
                        </span>
                        <?php if(!empty($tour['rango_adulto'])): ?><div style="font-size:0.65rem;color:#999;font-weight:normal">(Adultos <?= $tour['rango_adulto'] ?>)</div><?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-auto pt-3 border-top">
                        <div class="d-flex flex-column gap-1">
                            <div class="price-usd" style="font-size: 0.8rem;"><img src="https://flagcdn.com/w40/us.png" class="flag-icon"> USD $<?= precio_inteligente($pFinal / $tasa_tuya_usd) ?></div>
                            <div class="price-brl" style="font-size: 0.8rem;"><img src="https://flagcdn.com/w40/br.png" class="flag-icon"> BRL R$ <?= precio_inteligente($pFinal / $tasa_tuya_brl) ?></div>
                        </div>
                        <div class="text-primary fs-5"><i class="fa-solid fa-circle-arrow-right"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Referencias elementos
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        const filterOfertas = document.getElementById('filterOfertas');
        const filterNinos = document.getElementById('filterNinos');
        const grid = document.getElementById('toursGrid');

        // Escuchar el buscador
        if(searchInput) {
            searchInput.addEventListener('keyup', applyFilters);
        }

        // Función Maestra: Combina Buscador + Filtros + Orden
        function applyFilters() {
            const cards = Array.from(grid.getElementsByClassName('tour-card-col'));
            
            // 1. Obtener valores
            const searchText = searchInput ? searchInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "") : "";
            const onlyOffers = filterOfertas.checked;
            const onlyKids = filterNinos.checked;
            const sortValue = sortSelect.value;

            // 2. Filtrar (Mostrar/Ocultar)
            cards.forEach(card => {
                let show = true;
                
                // Texto Buscador
                const title = card.querySelector('.tour-title').textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                if (searchText !== "" && title.indexOf(searchText) === -1) show = false;

                // Checkbox Ofertas
                if (onlyOffers && card.dataset.oferta !== '1') show = false;

                // Checkbox Niños
                if (onlyKids && card.dataset.nino !== '1') show = false;

                card.style.display = show ? '' : 'none';
            });

            // 3. Ordenar (Solo los visibles se reordenan visualmente, pero ordenamos todo el array en el DOM)
            cards.sort((a, b) => {
                switch(sortValue) {
                    case 'precio_asc':
                        return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
                    case 'precio_desc':
                        return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
                    case 'nombre_asc':
                    default:
                        return a.dataset.nombre.localeCompare(b.dataset.nombre);
                }
            });

            // 4. Re-insertar en orden
            cards.forEach(card => grid.appendChild(card));
        }
    </script>
<?php endif; ?>

</div>
</body>
</html>