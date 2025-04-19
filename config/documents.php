<?php

return [
    // Definición de tipos de documentos
    'types' => [
        [
            'code' => 'contract',
            'label' => 'Contrato'
        ],
        [
            'code' => 'invoice',
            'label' => 'Factura'
        ],
        [
            'code' => 'user_request',
            'label' => 'Solicitud de Usuario'
        ],
        [
            'code' => 'identity_document',
            'label' => 'Documento de Identidad'
        ],
        [
            'code' => 'other',
            'label' => 'Otro'
        ]
    ],
    
    // Configuración por módulo
    'modules' => [
        'users' => [
            'model' => \App\Models\User::class,
            'allowed_types' => ['contract', 'user_request', 'identity_document', 'other'],
            'default_type' => 'identity_document',
            'max_size' => 5120, // 5MB
            'allowed_mimes' => 'pdf'
        ],
    ]
];
