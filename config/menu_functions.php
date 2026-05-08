<?php
// config/menu_functions.php - FUNCIONES AUXILIARES PARA MENÚS

/**
 * Obtiene el HTML de un icono de menú
 * @param string $nombre_imagen Nombre del archivo (sin extensión)
 * @param string $alt Texto alternativo
 * @param string $icono_fallback Icono FontAwesome alternativo
 * @return string HTML del icono
 */
function getMenuIcon($nombre_imagen, $alt = '', $icono_fallback = '') {
    $ruta = "imagen/$nombre_imagen.png";
    if (file_exists($ruta)) {
        return '<img src="' . $ruta . '" alt="' . $alt . '" class="menu-icon custom-icon">';
    } else {
        return '<i class="fas ' . $icono_fallback . ' menu-icon"></i>';
    }
}

/**
 * Determina si un enlace está activo
 * @param string $pagina_actual
 * @param string $pagina_enlace
 * @return string Clase 'active' si coincide
 */
function isActive($pagina_actual, $pagina_enlace) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page == $pagina_enlace) ? 'active' : '';
}

/**
 * Crea un elemento de menú
 * @param string $href Enlace
 * @param string $icono Nombre de la imagen del icono
 * @param string $texto Texto del enlace
 * @param string $icono_fallback Icono FontAwesome alternativo
 * @param bool $activo Si el elemento está activo
 * @return string HTML del elemento de menú
 */
function menuItem($href, $icono, $texto, $icono_fallback = '', $activo = false) {
    $clase_activo = $activo ? 'active' : '';
    return '
    <li class="' . $clase_activo . '">
        <a href="' . $href . '">
            ' . getMenuIcon($icono, $texto, $icono_fallback) . '
            <span class="menu-text">' . $texto . '</span>
        </a>
    </li>';
}
?>
