<?php

return [
    // Módulos que pueden tener imágenes asociadas
    'modules' => [
        'users',
        'documents',
        'roles',
        // Añadir más módulos según sea necesario
    ],
    
    // Tipos de imágenes por módulo
    'types' => [
        'users' => [
            'profile' => 'Fotografía de perfil',
        ],
        'documents' => [
            'preview' => 'Vista Previa',
            'attachment' => 'Adjunto',
            'other' => 'Otro',
        ],
        'roles' => [
            'icon' => 'Ícono',
            'other' => 'Otro',
        ],
        // Definir tipos para otros módulos según sea necesario
    ],
    
    // Configuración del almacenamiento
    'storage' => [
        'disk' => 'public',
        'path' => 'images',
        'thumbnails' => [
            'width' => 200,
            'height' => null, // null para mantener la proporción
        ],
    ],
    
    // Límites y restricciones
    'limits' => [
        'max_file_size' => 5120, // 5MB en KB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_width' => 4000,
        'max_height' => 4000,
    ],
];
