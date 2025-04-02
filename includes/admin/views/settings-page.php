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
    <div id="personal-post-index-settings-notice" class="notice" style="display: none;"></div>
    
    <div class="card">
        <h2><?php esc_html_e('Configuración de Categorías', 'PersonalPostIndex'); ?></h2>
        <p><?php esc_html_e('Seleccione las categorías para las cuales desea que se creen automáticamente entradas en el índice cuando se publiquen nuevos posts.', 'PersonalPostIndex'); ?></p>
        
        <form id="personal-post-index-settings-form" method="post">
            <?php wp_nonce_field('wp_entry_index_settings_nonce', 'wp_entry_index_settings_nonce'); ?>
            
            <div class="personal-post-index-form-group">
                <label for="personal-post-index-category-search"><?php esc_html_e('Buscar y seleccionar categorías:', 'PersonalPostIndex'); ?></label>
                <div class="personal-post-index-category-search-container">
                    <input type="text" id="personal-post-index-category-search" placeholder="<?php esc_attr_e('Escriba para buscar categorías...', 'PersonalPostIndex'); ?>">
                    <div id="personal-post-index-category-results" class="personal-post-index-category-results"></div>
                </div>
            </div>
            
            <div class="personal-post-index-form-group">
                <label><?php esc_html_e('Categorías seleccionadas:', 'PersonalPostIndex'); ?></label>
                <div id="personal-post-index-selected-categories" class="personal-post-index-selected-categories">
                    <?php if (empty($selected_categories)) : ?>
                        <p class="personal-post-index-no-categories"><?php esc_html_e('No hay categorías seleccionadas.', 'PersonalPostIndex'); ?></p>
                    <?php else : ?>
                        <?php foreach ($selected_categories as $cat_id => $cat_name) : ?>
                            <div class="personal-post-index-category-tag" data-id="<?php echo esc_attr($cat_id); ?>">
                                <span><?php echo esc_html($cat_name); ?></span>
                                <a href="#" class="personal-post-index-remove-category">&times;</a>
                                <input type="hidden" name="wp_entry_index_categories[<?php echo esc_attr($cat_id); ?>]" value="<?php echo esc_attr($cat_name); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="personal-post-index-form-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Guardar Configuración', 'PersonalPostIndex'); ?>
                </button>
            </div>
        </form>
    </div>
</div>