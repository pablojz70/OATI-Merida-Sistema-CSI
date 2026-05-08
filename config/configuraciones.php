<?php
return [
    'sistema' => [
        'nombre' => 'Areas Operativas: Infraestructura - OATI - Soporte Técnico',
        'logo' => 'logo2.png',
        'color_principal' => '#2c3e50',
        'items_por_pagina' => 20,
    ],
    'email' => [
        'notificaciones_activas' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'from_email' => 'notificaciones@sistema-csi.com',
        'from_nombre' => 'Areas Operativas: Infraestructura - OATI',
    ],
    'seguridad' => [
        'max_intentos_login' => 5,
        'tiempo_bloqueo_minutos' => 30,
        'sesion_expiracion_horas' => 8,
    ],
    'archivos' => [
        'max_tamano_mb' => 10,
        'extensiones_permitidas' => ['jpg', 'png', 'pdf', 'doc', 'docx'],
        'ruta_uploads' => 'uploads/',
    ],
    'tickets' => [
        'prioridad_defecto' => 'media',
        'dias_cierre_automatico' => 30,
        'notificar_siempre_al_usuario' => true,
    ]
];
