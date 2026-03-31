<?php
/**
 * reset-creds.php — Script de uso único para regenerar credenciales
 * ELIMINAR ESTE ARCHIVO inmediatamente después de usarlo.
 *
 * URL de uso en producción:
 *   https://descubrecartagena.com/catalogo/reset-creds.php?key=descubre2024reset
 */

// Clave secreta para autorizar el reset (evita que cualquiera lo ejecute)
$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'descubre2024reset') {
    die('Acceso no autorizado.');
}

$fileCreds = __DIR__ . '/credenciales.json';
$nuevaClave = 'Dc@6691400'; // Contraseña por defecto

$creds = [
    'usuario'  => 'Benko',
    'password' => password_hash($nuevaClave, PASSWORD_DEFAULT),
];

if (file_put_contents($fileCreds, json_encode($creds)) !== false) {
    echo '<div style="font-family:monospace;padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;">';
    echo '<h3>✅ Credenciales regeneradas correctamente</h3>';
    echo '<p><strong>Usuario:</strong> admin</p>';
    echo '<p><strong>Contraseña:</strong> ' . htmlspecialchars($nuevaClave) . '</p>';
    echo '<p><strong>Hash generado:</strong> ' . htmlspecialchars($creds['password']) . '</p>';
    echo '<hr><p style="color:red;font-weight:bold;">⚠️ ELIMINA ESTE ARCHIVO (reset-creds.php) DEL SERVIDOR INMEDIATAMENTE.</p>';
    echo '</div>';
} else {
    echo '<p style="color:red;">❌ Error: No se pudo escribir el archivo. Verifica los permisos de la carpeta.</p>';
}
