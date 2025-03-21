<?php
/**
 * Template para la página de configuración del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener categorías guardadas
$selected_categories = get_option('wp_entry_index_categories', array());
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Mensajes de notificación -->
    <div id="wp-entry-index-settings-notice" class="notice" style="display: none;"></div>
    
    <div class="card">
        <h2><?php _e('Configuración de Categorías', 'wp-entry-index'); ?></h2>
        <p><?php _e('Seleccione las categorías para las cuales desea que se creen automáticamente entradas en el índice cuando se publiquen nuevos posts.', 'wp-entry-index'); ?></p>
        
        <form id="wp-entry-index-settings-form" method="post">
            <?php wp_nonce_field('wp_entry_index_settings_nonce', 'wp_entry_index_settings_nonce'); ?>
            
            <div class="wp-entry-index-form-group">
                <label for="wp-entry-index-category-search"><?php _e('Buscar y seleccionar categorías:', 'wp-entry-index'); ?></label>
                <div class="wp-entry-index-category-search-container">
                    <input type="text" id="wp-entry-index-category-search" placeholder="<?php _e('Escriba para buscar categorías...', 'wp-entry-index'); ?>">
                    <div id="wp-entry-index-category-results" class="wp-entry-index-category-results"></div>
                </div>
            </div>
            
            <div class="wp-entry-index-form-group">
                <label><?php _e('Categorías seleccionadas:', 'wp-entry-index'); ?></label>
                <div id="wp-entry-index-selected-categories" class="wp-entry-index-selected-categories">
                    <?php if (empty($selected_categories)) : ?>
                        <p class="wp-entry-index-no-categories"><?php _e('No hay categorías seleccionadas.', 'wp-entry-index'); ?></p>
                    <?php else : ?>
                        <?php foreach ($selected_categories as $cat_id => $cat_name) : ?>
                            <div class="wp-entry-index-category-tag" data-id="<?php echo esc_attr($cat_id); ?>">
                                <span><?php echo esc_html($cat_name); ?></span>
                                <a href="#" class="wp-entry-index-remove-category">&times;</a>
                                <input type="hidden" name="wp_entry_index_categories[<?php echo esc_attr($cat_id); ?>]" value="<?php echo esc_attr($cat_name); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="wp-entry-index-form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Guardar Configuración', 'wp-entry-index'); ?>
                </button>
            </div>
        </form>
    </div>
</div>