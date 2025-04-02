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
    <div id="personal-post-index-notice" class="notice" style="display: none;"></div>
    
    <!-- Formulario de búsqueda y acciones -->
    <div class="personal-post-index-actions">
        <div class="personal-post-index-search">
            <form method="get">
                <input type="hidden" name="page" value="personal-post-index">
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_html_e('Buscar entradas...', 'PersonalPostIndex'); ?>">
                <input type="submit" class="button" value="<?php esc_html_e('Buscar', 'PersonalPostIndex'); ?>">
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=personal-post-index')); ?>" class="button"><?php esc_html_e('Limpiar', 'PersonalPostIndex'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="personal-post-index-buttons">
            <button id="personal-post-index-add-button" class="button button-primary">
                <?php esc_html_e('Agregar Nuevo', 'PersonalPostIndex'); ?>
            </button>
            <button id="personal-post-index-import-button" class="button button-primary">
                <?php esc_html_e('Importar CSV', 'PersonalPostIndex'); ?>
            </button>
        </div>
    </div>
    
    <!-- Tabla de entradas -->
    <table class="wp-list-table widefat fixed striped personal-post-index-table">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Nombre', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('URL', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Creado por', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Fecha de creación', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Modificado por', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Fecha de modificación', 'PersonalPostIndex'); ?></th>
                <th><?php esc_html_e('Acciones', 'PersonalPostIndex'); ?></th>
            </tr>
        </thead>
        <tbody id="personal-post-index-table-body">
            <?php if (empty($entries)) : ?>
                <tr>
                    <td colspan="8"><?php esc_html_e('No hay entradas disponibles.', 'PersonalPostIndex'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($entries as $entry) : 
                    // Obtener información de usuarios
                    $created_user = get_userdata($entry['created_by']);
                    $created_by_name = $created_user ? $created_user->display_name : __('Usuario desconocido', 'PersonalPostIndex');
                    
                    $modified_by_name = '';
                    $modified_at = '';
                    if (!empty($entry['modified_by'])) {
                        $modified_user = get_userdata($entry['modified_by']);
                        $modified_by_name = $modified_user ? $modified_user->display_name : __('Usuario desconocido', 'PersonalPostIndex');
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
                        <button class="button personal-post-index-edit" data-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php esc_html_e('Editar', 'PersonalPostIndex'); ?>
                        </button>
                        <button class="button personal-post-index-delete" data-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php esc_html_e('Borrar', 'PersonalPostIndex'); ?>
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
                echo esc_html(sprintf(_n('%s elemento', '%s elementos', $total_entries, 'PersonalPostIndex'), number_format_i18n($total_entries))); ?>
            </span>
            <span class="pagination-links">
                <?php
                // Construir URL base para paginación
                $base_url = add_query_arg('page', 'personal-post-index', admin_url('admin.php'));
                if (!empty($search_query)) {
                    $base_url = add_query_arg('s', urlencode($search_query), $base_url);
                }
                
                // Primera página
                if ($current_page > 1) :
                    $first_url = esc_url(add_query_arg('paged', 1, $base_url));
                    echo '<a class="first-page button" href="' . esc_url($first_url) . '"><span class="screen-reader-text">' . esc_html__('Primera página', 'PersonalPostIndex') . '</span><span aria-hidden="true">&laquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                endif;
                
                // Página anterior
                if ($current_page > 1) :
                    $prev_url = esc_url(add_query_arg('paged', $current_page - 1, $base_url));
                    echo '<a class="prev-page button" href="' . esc_url($prev_url) . '"><span class="screen-reader-text">' . esc_html__('Página anterior', 'PersonalPostIndex') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                endif;
                
                // Número de página actual
                echo '<span class="paging-input">';
                echo '<span class="tablenav-paging-text">' . esc_html($current_page) . ' ' . esc_html__('de', 'PersonalPostIndex') . ' <span class="total-pages">' . esc_html($total_pages) . '</span></span>';
                echo '</span>';
                
                // Página siguiente
                if ($current_page < $total_pages) :
                    $next_url = esc_url(add_query_arg('paged', $current_page + 1, $base_url));
                    echo '<a class="next-page button" href="' . esc_url($next_url) . '"><span class="screen-reader-text">' . esc_html__('Página siguiente', 'PersonalPostIndex') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                endif;
                
                // Última página
                if ($current_page < $total_pages) :
                    $last_url = esc_url(add_query_arg('paged', $total_pages, $base_url));
                    echo '<a class="last-page button" href="' . esc_url($last_url) . '"><span class="screen-reader-text">' . esc_html__('Última página', 'PersonalPostIndex') . '</span><span aria-hidden="true">&raquo;</span></a>';
                else :
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                endif;
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Template para nueva fila -->
    <script type="text/template" id="personal-post-index-row-template">
        <tr data-id="{{id}}">
            <td>{{id}}</td>
            <td>{{name}}</td>
            <td><a href="{{url}}" target="_blank">{{url}}</a></td>
            <td>{{created_by_name}}</td>
            <td>{{created_at}}</td>
            <td>{{modified_by_name}}</td>
            <td>{{modified_at}}</td>
            <td>
                <button class="button personal-post-index-edit" data-id="{{id}}">
                    <?php esc_html_e('Editar', 'PersonalPostIndex'); ?>
                </button>
                <button class="button personal-post-index-delete" data-id="{{id}}">
                    <?php esc_html_e('Borrar', 'PersonalPostIndex'); ?>
                </button>
            </td>
        </tr>
    </script>
    
    <!-- Modal para agregar/editar entrada -->
    <div id="personal-post-index-modal" class="personal-post-index-modal" style="display: none;">
        <div class="personal-post-index-modal-content">
            <span class="personal-post-index-modal-close">&times;</span>
            <h2 id="personal-post-index-modal-title"><?php esc_html_e('Agregar Entrada', 'PersonalPostIndex'); ?></h2>
            <form id="personal-post-index-form">
                <input type="hidden" id="personal-post-index-id" name="id" value="">
                <div class="personal-post-index-form-group">
                    <label for="personal-post-index-name"><?php esc_html_e('Nombre', 'PersonalPostIndex'); ?></label>
                    <input type="text" id="personal-post-index-name" name="name" required>
                </div>
                <div class="personal-post-index-form-group">
                    <label for="personal-post-index-url"><?php esc_html_e('URL', 'PersonalPostIndex'); ?></label>
                    <input type="url" id="personal-post-index-url" name="url" required>
                </div>
                <div class="personal-post-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Guardar', 'PersonalPostIndex'); ?>
                    </button>
                    <button type="button" class="button personal-post-index-modal-cancel">
                        <?php esc_html_e('Cancelar', 'PersonalPostIndex'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para importar CSV -->
    <div id="personal-post-index-import-modal" class="personal-post-index-modal" style="display: none;">
        <div class="personal-post-index-modal-content">
            <span class="personal-post-index-modal-close">&times;</span>
            <h2><?php esc_html_e('Importar CSV', 'PersonalPostIndex'); ?></h2>
            <form id="personal-post-index-import-form" enctype="multipart/form-data">
                <div class="personal-post-index-form-group">
                    <label for="personal-post-index-csv-file"><?php esc_html_e('Archivo CSV', 'PersonalPostIndex'); ?></label>
                    <input type="file" id="personal-post-index-csv-file" name="csv_file" accept=".csv" required>
                    <p class="description"><?php esc_html_e('El archivo debe tener dos columnas: Nombre y URL.', 'PersonalPostIndex'); ?></p>
                </div>
                <div class="personal-post-index-form-group">
                    <label for="personal-post-index-skip-header">
                        <input type="checkbox" id="personal-post-index-skip-header" name="skip_header" checked>
                        <?php esc_html_e('Omitir primera fila (encabezados)', 'PersonalPostIndex'); ?>
                    </label>
                </div>
                <div class="personal-post-index-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Importar', 'PersonalPostIndex'); ?>
                    </button>
                    <button type="button" class="button personal-post-index-modal-cancel">
                        <?php esc_html_e('Cancelar', 'PersonalPostIndex'); ?>
                    </button>
                </div>
            </form>
            <div id="personal-post-index-import-results" style="display: none;">
                <h3><?php esc_html_e('Resultados de la importación', 'PersonalPostIndex'); ?></h3>
                <div id="personal-post-index-import-summary"></div>
            </div>
        </div>
    </div>
</div>