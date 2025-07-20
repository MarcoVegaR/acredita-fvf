<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciales - Configuración general
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración para el sistema de credenciales,
    | incluyendo rutas de almacenamiento, configuración de QR, verificación, etc.
    |
    */

    // Rutas de almacenamiento relativas al disco 'public'
    'paths' => [
        'qr' => 'credentials/qr',         // Imágenes QR generadas
        'images' => 'credentials/images',  // Imágenes de credenciales
        'pdf' => 'credentials/pdf',        // PDFs de credenciales
    ],

    // Configuración del código QR
    'qr' => [
        'size' => 300,                     // Tamaño en píxeles
        'margin' => 1,                     // Margen
        'error_correction' => 'H',         // H: Alta corrección de errores
        'encoding' => 'UTF-8',             // Codificación
        'format' => 'png',                 // Formato de imagen
    ],

    // Configuración de la imagen de credencial
    'image' => [
        'width' => 1024,                   // Ancho en píxeles
        'height' => 1448,                  // Alto en píxeles (proporción ID estándar)
        'quality' => 90,                   // Calidad de imagen (0-100)
        'format' => 'png',                 // Formato de imagen
    ],

    // Configuración del PDF
    'pdf' => [
        'page_size' => 'A4',               // Tamaño de página
        'orientation' => 'portrait',       // Orientación
    ],

    // Configuración de verificación pública
    'verification' => [
        'url_base' => env('APP_URL', 'http://localhost') . '/verify', // URL base para verificación
        'rate_limit' => 60,                // Límite de verificaciones por minuto
    ],

    // Configuración de CDN para servir archivos
    'cdn' => [
        'enabled' => env('CREDENTIALS_CDN_ENABLED', false),
        'url' => env('CREDENTIALS_CDN_URL', env('APP_URL', 'http://localhost') . '/storage'),
    ],
];
