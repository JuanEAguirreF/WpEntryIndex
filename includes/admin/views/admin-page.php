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
    
    <!-- Formulario de búsqueda y acciones -->
    <div class="wp-entry-index-actions">
        <div class="wp-entry-index-search">
            <form method="get">
                <input type="hidden" name="page" value="wp-entry-index">
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php _e('Buscar entradas...', 'wp-entry-index'); ?>">
                <input type="submit" class="button" value="<?php _e('Buscar', 'wp-entry-index'); ?>">
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-entry-index')); ?>" class="button"><?php _e('Limpiar', 'wp-entry-index'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="wp-entry-index-buttons">
            <button id="wp-entry-index-add-button" class="button button-primary">
                <?php _e('Agregar Nuevo', 'wp-entry-index'); ?>
            </button>
            <button id="wp-entry-index-import-button" class="button button-primary">
                <?php _e('Importar CSV', 'wp-entry-index'); ?>
            </button>
        </div>
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
    
    <!-- Paginación -->
    <?php if ($total_pages > 1) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s elemento', '%s elementos', $total_entries, 'wp-entry-index'), number_format_i18n($total_entries)); ?>
            </span>
            <span class="pagination-links">
                <?php
                // Construir URL base para paginación
                $base_url = add_query_arg('page', 'wp-entry-index', admin_url('admin.php'));
                if (!empty($search_query)) {
                    $base_url = add_query_arg('s', urlencode($search_query), $base_url);
                }
                
                // Primera página
                if ($current_page > 1) :
                    $first_url = esc_url(add_query_arg('paged', 1, $base_url));
                    echo '<a class="first-page button" href="' . $first_url . '"><span class="screen-reader-text">' . __('Primera página', 'wp-entry-index') . '</span><span aria-hidden="true">&laquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                endif;
                
                // Página anterior
                if ($current_page > 1) :
                    $prev_url = esc_url(add_query_arg('paged', $current_page - 1, $base_url));
                    echo '<a class="prev-page button" href="' . $prev_url . '"><span class="screen-reader-text">' . __('Página anterior', 'wp-entry-index') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                endif;
                
                // Número de página actual
                echo '<span class="paging-input">';
                echo '<span class="tablenav-paging-text">' . $current_page . ' ' . __('de', 'wp-entry-index') . ' <span class="total-pages">' . $total_pages . '</span></span>';
                echo '</span>';
                
                // Página siguiente
                if ($current_page < $total_pages) :
                    $next_url = esc_url(add_query_arg('paged', $current_page + 1, $base_url));
                    echo '<a class="next-page button" href="' . $next_url . '"><span class="screen-reader-text">' . __('Página siguiente', 'wp-entry-index') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                endif;
                
                // Última página
                if ($current_page < $total_pages) :
                    $last_url = esc_url(add_query_arg('paged', $total_pages, $base_url));
                    echo '<a class="last-page button" href="' . $last_url . '"><span class="screen-reader-text">' . __('Última página', 'wp-entry-index') . '</span><span aria-hidden="true">&raquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                endif;
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    
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
    
    <!-- Modal para importar CSV -->
    <div id="wp-entry-index-import-modal" class="wp-entry-index-modal" style="display: none;">
        <div class="wp-entry-index-modal-content">
            <span class="wp-entry-index-modal-close">&times;</span>
            <h2><?php _e('Importar CSV', 'wp-entry-index'); ?></h2>
            <form id="wp-entry-index-import-form" enctype="multipart/form-data">
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-csv-file"><?php _e('Archivo CSV', 'wp-entry-index'); ?></label>
                    <input type="file" id="wp-entry-index-csv-file" name="csv_file" accept=".csv" required>
                    <p class="description"><?php _e('El archivo debe tener dos columnas: Nombre y URL.', 'wp-entry-index'); ?></p>
                </div>
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-skip-header">
                        <input type="checkbox" id="wp-entry-index-skip-header" name="skip_header" checked>
                        <?php _e('Omitir primera fila (encabezados)', 'wp-entry-index'); ?>
                    </label>
                </div>
                <div class="wp-entry-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Importar', 'wp-entry-index'); ?>
                    </button>
                    <button type="button" class="button wp-entry-index-modal-cancel">
                        <?php _e('Cancelar', 'wp-entry-index'); ?>
                    </button>
                </div>
            </form>
            <div id="wp-entry-index-import-results" style="display: none;">
                <h3><?php _e('Resultados de la importación', 'wp-entry-index'); ?></h3>
                <div id="wp-entry-index-import-summary"></div>
            </div>
        </div>
    </div>
</div>