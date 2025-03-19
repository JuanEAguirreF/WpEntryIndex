<?php
/**
 * Template para la página de administración del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Mensajes de notificación -->
    <div id="wp-entry-index-notice" class="notice" style="display: none;"></div>
    
    <!-- Botón para agregar nueva entrada -->
    <div class="wp-entry-index-actions">
        <button id="wp-entry-index-add-button" class="button button-primary">
            <?php _e('Agregar Nuevo', 'wp-entry-index'); ?>
        </button>
    </div>
    
    <!-- Tabla de entradas -->
    <table class="wp-list-table widefat fixed striped wp-entry-index-table">
        <thead>
            <tr>
                <th><?php _e('ID', 'wp-entry-index'); ?></th>
                <th><?php _e('Nombre', 'wp-entry-index'); ?></th>
                <th><?php _e('URL', 'wp-entry-index'); ?></th>
                <th><?php _e('Creado por', 'wp-entry-index'); ?></th>
                <th><?php _e('Fecha de creación', 'wp-entry-index'); ?></th>
                <th><?php _e('Modificado por', 'wp-entry-index'); ?></th>
                <th><?php _e('Fecha de modificación', 'wp-entry-index'); ?></th>
                <th><?php _e('Acciones', 'wp-entry-index'); ?></th>
            </tr>
        </thead>
        <tbody id="wp-entry-index-table-body">
            <?php if (empty($entries)) : ?>
                <tr>
                    <td colspan="8"><?php _e('No hay entradas disponibles.', 'wp-entry-index'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($entries as $entry) : 
                    // Obtener información de usuarios
                    $created_user = get_userdata($entry['created_by']);
                    $created_by_name = $created_user ? $created_user->display_name : __('Usuario desconocido', 'wp-entry-index');
                    
                    $modified_by_name = '';
                    $modified_at = '';
                    if (!empty($entry['modified_by'])) {
                        $modified_user = get_userdata($entry['modified_by']);
                        $modified_by_name = $modified_user ? $modified_user->display_name : __('Usuario desconocido', 'wp-entry-index');
                        $modified_at = $entry['modified_at'];
                    }
                ?>
                <tr data-id="<?php echo esc_attr($entry['id']); ?>">
                    <td><?php echo esc_html($entry['id']); ?></td>
                    <td><?php echo esc_html($entry['name']); ?></td>
                    <td><a href="<?php echo esc_url($entry['url']); ?>" target="_blank"><?php echo esc_url($entry['url']); ?></a></td>
                    <td><?php echo esc_html($created_by_name); ?></td>
                    <td><?php echo esc_html($entry['created_at']); ?></td>
                    <td><?php echo esc_html($modified_by_name); ?></td>
                    <td><?php echo esc_html($modified_at); ?></td>
                    <td>
                        <button class="button wp-entry-index-edit" data-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php _e('Editar', 'wp-entry-index'); ?>
                        </button>
                        <button class="button wp-entry-index-delete" data-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php _e('Borrar', 'wp-entry-index'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Template para nueva fila -->
    <script type="text/template" id="wp-entry-index-row-template">
        <tr data-id="{{id}}">
            <td>{{id}}</td>
            <td>{{name}}</td>
            <td><a href="{{url}}" target="_blank">{{url}}</a></td>
            <td>{{created_by_name}}</td>
            <td>{{created_at}}</td>
            <td>{{modified_by_name}}</td>
            <td>{{modified_at}}</td>
            <td>
                <button class="button wp-entry-index-edit" data-id="{{id}}">
                    <?php _e('Editar', 'wp-entry-index'); ?>
                </button>
                <button class="button wp-entry-index-delete" data-id="{{id}}">
                    <?php _e('Borrar', 'wp-entry-index'); ?>
                </button>
            </td>
        </tr>
    </script>
    
    <!-- Modal para agregar/editar entrada -->
    <div id="wp-entry-index-modal" class="wp-entry-index-modal" style="display: none;">
        <div class="wp-entry-index-modal-content">
            <span class="wp-entry-index-modal-close">&times;</span>
            <h2 id="wp-entry-index-modal-title"><?php _e('Agregar Entrada', 'wp-entry-index'); ?></h2>
            <form id="wp-entry-index-form">
                <input type="hidden" id="wp-entry-index-id" name="id" value="">
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-name"><?php _e('Nombre', 'wp-entry-index'); ?></label>
                    <input type="text" id="wp-entry-index-name" name="name" required>
                </div>
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-url"><?php _e('URL', 'wp-entry-index'); ?></label>
                    <input type="url" id="wp-entry-index-url" name="url" required>
                </div>
                <div class="wp-entry-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Guardar', 'wp-entry-index'); ?>
                    </button>
                    <button type="button" class="button wp-entry-index-modal-cancel">
                        <?php _e('Cancelar', 'wp-entry-index'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>