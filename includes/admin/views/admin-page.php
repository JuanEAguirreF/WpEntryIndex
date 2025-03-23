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
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_html_e('Buscar entradas...', 'WpEntryIndex'); ?>">
                <input type="submit" class="button" value="<?php esc_html_e('Buscar', 'WpEntryIndex'); ?>">
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-entry-index')); ?>" class="button"><?php esc_html_e('Limpiar', 'WpEntryIndex'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="wp-entry-index-buttons">
            <button id="wp-entry-index-add-button" class="button button-primary">
                <?php esc_html_e('Agregar Nuevo', 'WpEntryIndex'); ?>
            </button>
            <button id="wp-entry-index-import-button" class="button button-primary">
                <?php esc_html_e('Importar CSV', 'WpEntryIndex'); ?>
            </button>
        </div>
    </div>
    
    <!-- Tabla de entradas -->
    <table class="wp-list-table widefat fixed striped wp-entry-index-table">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Nombre', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('URL', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Creado por', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Fecha de creación', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Modificado por', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Fecha de modificación', 'WpEntryIndex'); ?></th>
                <th><?php esc_html_e('Acciones', 'WpEntryIndex'); ?></th>
            </tr>
        </thead>
        <tbody id="wp-entry-index-table-body">
            <?php if (empty($entries)) : ?>
                <tr>
                    <td colspan="8"><?php esc_html_e('No hay entradas disponibles.', 'WpEntryIndex'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($entries as $entry) : 
                    // Obtener información de usuarios
                    $created_user = get_userdata($entry['created_by']);
                    $created_by_name = $created_user ? $created_user->display_name : __('Usuario desconocido', 'WpEntryIndex');
                    
                    $modified_by_name = '';
                    $modified_at = '';
                    if (!empty($entry['modified_by'])) {
                        $modified_user = get_userdata($entry['modified_by']);
                        $modified_by_name = $modified_user ? $modified_user->display_name : __('Usuario desconocido', 'WpEntryIndex');
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
                            <?php esc_html_e('Editar', 'WpEntryIndex'); ?>
                        </button>
                        <button class="button wp-entry-index-delete" data-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php esc_html_e('Borrar', 'WpEntryIndex'); ?>
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
                <?php
                // Translators: %s: Number of elements in the list
                echo esc_html(sprintf(_n('%s elemento', '%s elementos', $total_entries, 'WpEntryIndex'), number_format_i18n($total_entries))); ?>
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
                    echo '<a class="first-page button" href="' . esc_url($first_url) . '"><span class="screen-reader-text">' . esc_html__('Primera página', 'WpEntryIndex') . '</span><span aria-hidden="true">&laquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                endif;
                
                // Página anterior
                if ($current_page > 1) :
                    $prev_url = esc_url(add_query_arg('paged', $current_page - 1, $base_url));
                    echo '<a class="prev-page button" href="' . esc_url($prev_url) . '"><span class="screen-reader-text">' . esc_html__('Página anterior', 'WpEntryIndex') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                endif;
                
                // Número de página actual
                echo '<span class="paging-input">';
                echo '<span class="tablenav-paging-text">' . esc_html($current_page) . ' ' . esc_html__('de', 'WpEntryIndex') . ' <span class="total-pages">' . esc_html($total_pages) . '</span></span>';
                echo '</span>';
                
                // Página siguiente
                if ($current_page < $total_pages) :
                    $next_url = esc_url(add_query_arg('paged', $current_page + 1, $base_url));
                    echo '<a class="next-page button" href="' . esc_url($next_url) . '"><span class="screen-reader-text">' . esc_html__('Página siguiente', 'WpEntryIndex') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                endif;
                
                // Última página
                if ($current_page < $total_pages) :
                    $last_url = esc_url(add_query_arg('paged', $total_pages, $base_url));
                    echo '<a class="last-page button" href="' . esc_url($last_url) . '"><span class="screen-reader-text">' . esc_html__('Última página', 'WpEntryIndex') . '</span><span aria-hidden="true">&raquo;</span></a>';
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
                    <?php esc_html_e('Editar', 'WpEntryIndex'); ?>
                </button>
                <button class="button wp-entry-index-delete" data-id="{{id}}">
                    <?php esc_html_e('Borrar', 'WpEntryIndex'); ?>
                </button>
            </td>
        </tr>
    </script>
    
    <!-- Modal para agregar/editar entrada -->
    <div id="wp-entry-index-modal" class="wp-entry-index-modal" style="display: none;">
        <div class="wp-entry-index-modal-content">
            <span class="wp-entry-index-modal-close">&times;</span>
            <h2 id="wp-entry-index-modal-title"><?php esc_html_e('Agregar Entrada', 'WpEntryIndex'); ?></h2>
            <form id="wp-entry-index-form">
                <input type="hidden" id="wp-entry-index-id" name="id" value="">
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-name"><?php esc_html_e('Nombre', 'WpEntryIndex'); ?></label>
                    <input type="text" id="wp-entry-index-name" name="name" required>
                </div>
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-url"><?php esc_html_e('URL', 'WpEntryIndex'); ?></label>
                    <input type="url" id="wp-entry-index-url" name="url" required>
                </div>
                <div class="wp-entry-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Guardar', 'WpEntryIndex'); ?>
                    </button>
                    <button type="button" class="button wp-entry-index-modal-cancel">
                        <?php esc_html_e('Cancelar', 'WpEntryIndex'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para importar CSV -->
    <div id="wp-entry-index-import-modal" class="wp-entry-index-modal" style="display: none;">
        <div class="wp-entry-index-modal-content">
            <span class="wp-entry-index-modal-close">&times;</span>
            <h2><?php esc_html_e('Importar CSV', 'WpEntryIndex'); ?></h2>
            <form id="wp-entry-index-import-form" enctype="multipart/form-data">
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-csv-file"><?php esc_html_e('Archivo CSV', 'WpEntryIndex'); ?></label>
                    <input type="file" id="wp-entry-index-csv-file" name="csv_file" accept=".csv" required>
                    <p class="description"><?php esc_html_e('El archivo debe tener dos columnas: Nombre y URL.', 'WpEntryIndex'); ?></p>
                </div>
                <div class="wp-entry-index-form-group">
                    <label for="wp-entry-index-skip-header">
                        <input type="checkbox" id="wp-entry-index-skip-header" name="skip_header" checked>
                        <?php esc_html_e('Omitir primera fila (encabezados)', 'WpEntryIndex'); ?>
                    </label>
                </div>
                <div class="wp-entry-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Importar', 'WpEntryIndex'); ?>
                    </button>
                    <button type="button" class="button wp-entry-index-modal-cancel">
                        <?php esc_html_e('Cancelar', 'WpEntryIndex'); ?>
                    </button>
                </div>
            </form>
            <div id="wp-entry-index-import-results" style="display: none;">
                <h3><?php esc_html_e('Resultados de la importación', 'WpEntryIndex'); ?></h3>
                <div id="wp-entry-index-import-summary"></div>
            </div>
        </div>
    </div>
</div>