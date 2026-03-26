<?php

// Configuración del Sistema CSI
// Archivo generado automáticamente

$sistema_config = [];

// Configuración general
$sistema_config['sistema_nombre'] = 'Sistema CSI';
$sistema_config['sistema_descripcion'] = 'Sistema de Control de Soporte Informático';
$sistema_config['sistema_version'] = '1.0.0';
$sistema_config['sistema_url'] = 'http://localhost/sistema_csi';
$sistema_config['sistema_email'] = 'soporte@csi.gob.mx';
$sistema_config['sistema_telefono'] = '+52 999 123 4567';

// Configuración de tickets
$sistema_config['tickets_prioridad_default'] = 'media';
$sistema_config['tickets_horas_sla'] = 24;
$sistema_config['tickets_max_adjuntos'] = 5;
$sistema_config['tickets_max_tamano_adjunto'] = 10;
$sistema_config['tickets_auto_asignar'] = true;

// Configuración de notificaciones
$sistema_config['notificaciones_email'] = true;
$sistema_config['notificaciones_sms'] = false;
$sistema_config['email_smtp_host'] = 'smtp.gmail.com';
$sistema_config['email_smtp_port'] = 587;
$sistema_config['email_smtp_user'] = '';
$sistema_config['email_smtp_pass'] = '';
$sistema_config['email_from'] = 'no-reply@csi.gob.mx';
$sistema_config['email_from_name'] = 'Sistema CSI';

// Configuración de seguridad
$sistema_config['seguridad_intentos_login'] = 3;
$sistema_config['seguridad_tiempo_bloqueo'] = 15;
$sistema_config['seguridad_requerir_captcha'] = false;
$sistema_config['seguridad_password_min_length'] = 8;
$sistema_config['seguridad_password_complejidad'] = true;
$sistema_config['seguridad_session_timeout'] = 60;

// Configuración de backups
$sistema_config['backup_auto'] = true;
$sistema_config['backup_frecuencia'] = 'daily';
$sistema_config['backup_hora'] = '02:00';
$sistema_config['backup_retener_dias'] = 30;
$sistema_config['backup_comprimir'] = true;
$sistema_config['backup_notificar'] = true;

// Configuración de mantenimiento
$sistema_config['mantenimiento_mode'] = false;
$sistema_config['mantenimiento_mensaje'] = 'Sistema en mantenimiento. Por favor, intente más tarde.';
$sistema_config['limpiar_logs_dias'] = 90;

// Configuración de apariencia
$sistema_config['tema_color_primario'] = '#3498db';
$sistema_config['tema_color_secundario'] = '#2ecc71';
$sistema_config['tema_logo'] = 'assets/logo.png';
$sistema_config['tema_favicon'] = 'assets/favicon.ico';
$sistema_config['tema_modo_oscuro'] = false;

// Configuración de reportes
$sistema_config['reportes_auto_generar'] = true;
$sistema_config['reportes_frecuencia'] = 'monthly';
$sistema_config['reportes_email_destino'] = 'admin@csi.gob.mx';

// Configuración de integración
$sistema_config['api_habilitada'] = false;
$sistema_config['api_key'] = '';
$sistema_config['api_rate_limit'] = 100;

// Fin de configuración
?>
