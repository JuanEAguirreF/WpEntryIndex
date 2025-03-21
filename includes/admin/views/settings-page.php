<?php
/**
 * Template para la página de configuración del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Esta sección está en construcción. Próximamente se añadirán opciones de configuración.', 'wp-entry-index'); ?></p>
    </div>
</div>