<?php
/**
 * Clase para manejar la interfaz de administración del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class WP_Entry_Index_Admin {
    
    // Inicializar la clase
    public static function init() {
        $instance = new self();
        return $instance;
    }
    
    // Constructor
    public function __construct() {
        // Agregar menú en el panel de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar scripts y estilos
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Manejar acciones AJAX
        add_action('wp_ajax_wp_entry_index_add', array($this, 'ajax_add_entry'));
        add_action('wp_ajax_wp_entry_index_edit', array($this, 'ajax_edit_entry'));
        add_action('wp_ajax_wp_entry_index_delete', array($this, 'ajax_delete_entry'));
        add_action('wp_ajax_wp_entry_index_get', array($this, 'ajax_get_entry'));
        add_action('wp_ajax_wp_entry_index_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_wp_entry_index_search_categories', array($this, 'ajax_search_categories'));
        add_action('wp_ajax_wp_entry_index_save_settings', array($this, 'ajax_save_settings'));
    }
    
    // Agregar menú en el panel de administración
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Índice de Entradas', 'wp-entry-index'),
            __('Índice de Entradas', 'wp-entry-index'),
            'manage_options',
            'wp-entry-index',
            array($this, 'render_index_page'),
            'dashicons-list-view',
            30
        );
        
        // Submenú Índice (mismo que el principal)
        add_submenu_page(
            'wp-entry-index',
            __('Índice', 'wp-entry-index'),
            __('Índice', 'wp-entry-index'),
            'manage_options',
            'wp-entry-index',
            array($this, 'render_index_page')
        );
        
        // Submenú Configuración
        add_submenu_page(
            'wp-entry-index',
            __('Configuración', 'wp-entry-index'),
            __('Configuración', 'wp-entry-index'),
            'manage_options',
            'wp-entry-index-settings',
            array($this, 'render_settings_page')
        );
    }
    
    // Registrar scripts y estilos
    public function enqueue_scripts($hook) {
        // Cargar en las páginas del plugin
        if ('toplevel_page_wp-entry-index' !== $hook && 'indice-de-entradas_page_wp-entry-index-settings' !== $hook) {
            return;
        }
        
        // Registrar estilos
        wp_enqueue_style(
            'wp-entry-index-admin',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/css/admin.css')
        );
        
        // Solo cargar scripts en la página principal (índice)
        if ('toplevel_page_wp-entry-index' === $hook) {
            // Registrar scripts
            wp_enqueue_script(
                'wp-entry-index-admin',
                WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/js/admin.js'),
                true
            );
            
            // Pasar variables al script
            wp_localize_script(
                'wp-entry-index-admin',
                'wp_entry_index_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_entry_index_nonce'),
                    'messages' => array(
                        'confirm_delete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'wp-entry-index'),
                        'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-entry-index'),
                        'success_add' => __('Elemento agregado correctamente.', 'wp-entry-index'),
                        'success_edit' => __('Elemento actualizado correctamente.', 'wp-entry-index'),
                        'success_delete' => __('Elemento eliminado correctamente.', 'wp-entry-index'),
                        'success_import' => __('Importación completada correctamente.', 'wp-entry-index'),
                        'error_import' => __('Error en la importación. Por favor, verifica el formato del archivo.', 'wp-entry-index'),
                        'add_title' => __('Agregar Entrada', 'wp-entry-index'),
                        'edit_title' => __('Editar Entrada', 'wp-entry-index')
                    )
                )
            );
        }
        
        // Cargar scripts en la página de configuración
        if ('indice-de-entradas_page_wp-entry-index-settings' === $hook) {
            // Registrar scripts
            wp_enqueue_script(
                'wp-entry-index-settings',
                WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/settings.js',
                array('jquery'),
                filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/js/settings.js'),
                true
            );
            
            // Pasar variables al script
            wp_localize_script(
                'wp-entry-index-settings',
                'wp_entry_index_settings_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_entry_index_settings_nonce'),
                    'messages' => array(
                        'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-entry-index'),
                        'success_save' => __('Configuración guardada correctamente.', 'wp-entry-index'),
                        'no_results' => __('No se encontraron categorías.', 'wp-entry-index'),
                        'searching' => __('Buscando...', 'wp-entry-index')
                    )
                )
            );
        }
    }
    
    // Renderizar página de índice (antes render_admin_page)
    public function render_index_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Obtener parámetros de paginación y búsqueda
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $per_page = 20; // Número de elementos por página
        
        // Obtener entradas con paginación y búsqueda
        $entries_data = $this->get_entries_paginated($per_page, $current_page, $search_query);
        $entries = $entries_data['entries'];
        $total_entries = $entries_data['total'];
        $total_pages = ceil($total_entries / $per_page);
        
        // Incluir template
        include WP_ENTRY_INDEX_PLUGIN_DIR . 'includes/admin/views/admin-page.php';
    }
    
    // Renderizar página de configuración
    public function render_settings_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Incluir template
        include WP_ENTRY_INDEX_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
    
    // Obtener entradas con paginación y búsqueda
    private function get_entries_paginated($per_page = 20, $page = 1, $search = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        // Calcular offset
        $offset = ($page - 1) * $per_page;
        
        // Preparar consulta base
        $query = "SELECT * FROM $table_name";
        $count_query = "SELECT COUNT(*) FROM $table_name";
        $query_args = array();
        
        // Añadir condición de búsqueda si existe
        if (!empty($search)) {
            $query .= " WHERE name LIKE %s OR url LIKE %s";
            $count_query .= " WHERE name LIKE %s OR url LIKE %s";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Añadir ordenamiento y límite
        $query .= " ORDER BY id DESC LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = $offset;
        
        // Ejecutar consulta para obtener entradas
        $entries = $wpdb->get_results(
            $wpdb->prepare($query, $query_args),
            ARRAY_A
        );
        
        // Ejecutar consulta para obtener total
        $count_args = !empty($search) ? array($search_term, $search_term) : array();
        $total = $wpdb->get_var($wpdb->prepare($count_query, $count_args));
        
        return array(
            'entries' => $entries,
            'total' => $total
        );
    }
    
    // Obtener entradas (método original para compatibilidad)
    private function get_entries($limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $entries;
    }
    
    // Agregar entrada (AJAX)
    public function ajax_add_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener datos
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        // Validar datos
        if (empty($name) || empty($url)) {
            wp_send_json_error(array('message' => __('El nombre y la URL son obligatorios.', 'wp-entry-index')));
        }
        
        // Insertar en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'url' => $url,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s')
        );
        
        if (false === $result) {
            wp_send_json_error(array('message' => __('Error al guardar los datos.', 'wp-entry-index')));
        }
        
        // Obtener el ID insertado
        $entry_id = $wpdb->insert_id;
        
        // Obtener la entrada completa
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $entry_id
            ),
            ARRAY_A
        );
        
        // Agregar información del usuario
        $user = get_userdata($entry['created_by']);
        $entry['created_by_name'] = $user ? $user->display_name : __('Usuario desconocido', 'wp-entry-index');
        
        wp_send_json_success(array(
            'message' => __('Entrada agregada correctamente.', 'wp-entry-index'),
            'entry' => $entry
        ));
    }
    
    // Editar entrada (AJAX)
    public function ajax_edit_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener datos
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        // Validar datos
        if (empty($id) || empty($name) || empty($url)) {
            wp_send_json_error(array('message' => __('Todos los campos son obligatorios.', 'wp-entry-index')));
        }
        
        // Actualizar en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'url' => $url,
                'modified_by' => get_current_user_id(),
                'modified_at' => current_time('mysql')
            ),
            array('id' => $id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        if (false === $result) {
            wp_send_json_error(array('message' => __('Error al actualizar los datos.', 'wp-entry-index')));
        }
        
        // Obtener la entrada actualizada
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        // Agregar información de los usuarios
        $created_user = get_userdata($entry['created_by']);
        $entry['created_by_name'] = $created_user ? $created_user->display_name : __('Usuario desconocido', 'wp-entry-index');
        
        $modified_user = get_userdata($entry['modified_by']);
        $entry['modified_by_name'] = $modified_user ? $modified_user->display_name : __('Usuario desconocido', 'wp-entry-index');
        
        wp_send_json_success(array(
            'message' => __('Entrada actualizada correctamente.', 'wp-entry-index'),
            'entry' => $entry
        ));
    }
    
    // Eliminar entrada (AJAX)
    public function ajax_delete_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validar ID
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID no válido.', 'wp-entry-index')));
        }
        
        // Eliminar de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        if (false === $result) {
            wp_send_json_error(array('message' => __('Error al eliminar la entrada.', 'wp-entry-index')));
        }
        
        wp_send_json_success(array(
            'message' => __('Entrada eliminada correctamente.', 'wp-entry-index'),
            'id' => $id
        ));
    }
    
    // Obtener entrada (AJAX)
    public function ajax_get_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validar ID
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID no válido.', 'wp-entry-index')));
        }
        
        // Obtener de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if (null === $entry) {
            wp_send_json_error(array('message' => __('Entrada no encontrada.', 'wp-entry-index')));
        }
        
        wp_send_json_success(array(
            'entry' => $entry
        ));
    }
    
    // Importar CSV (AJAX)
    public function ajax_import_csv() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Verificar si se ha subido un archivo
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error(array('message' => __('No se ha seleccionado ningún archivo.', 'wp-entry-index')));
        }
        
        $file = $_FILES['csv_file'];
        
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Error al subir el archivo.', 'wp-entry-index')));
        }
        
        // Verificar tipo de archivo
        $file_type = wp_check_filetype(basename($file['name']), array('csv' => 'text/csv'));
        if (!$file_type['ext']) {
            wp_send_json_error(array('message' => __('El archivo debe ser un CSV válido.', 'wp-entry-index')));
        }
        
        // Abrir el archivo
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('No se pudo abrir el archivo.', 'wp-entry-index')));
        }
        
        // Preparar estadísticas
        $stats = array(
            'total' => 0,
            'success' => 0,
            'error' => 0,
            'errors' => array()
        );
        
        // Obtener usuario actual y fecha
        $user_id = get_current_user_id();
        $current_time = current_time('mysql');
        
        // Preparar inserción masiva
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        $values = array();
        $placeholders = array();
        $types = array();
        
        // Verificar si se debe omitir la primera fila (encabezados)
        $skip_header = isset($_POST['skip_header']) && $_POST['skip_header'] === '1';
        $row_count = 0;
        
        // Leer el archivo línea por línea
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row_count++;
            
            // Omitir la primera fila si está marcada la opción
            if ($skip_header && $row_count === 1) {
                continue;
            }
            
            $stats['total']++;
            
            // Verificar que haya al menos dos columnas
            if (count($data) < 2) {
                $stats['error']++;
                $stats['errors'][] = sprintf(__('Línea %d: Formato incorrecto.', 'wp-entry-index'), $row_count);
                continue;
            }
            
            // Obtener nombre y URL
            $name = sanitize_text_field($data[0]);
            $url = esc_url_raw($data[1]);
            
            // Validar datos
            if (empty($name) || empty($url)) {
                $stats['error']++;
                $stats['errors'][] = sprintf(__('Línea %d: Nombre o URL vacíos.', 'wp-entry-index'), $row_count);
                continue;
            }
            
            // Añadir a los valores para inserción masiva
            $values[] = $name;
            $values[] = $url;
            $values[] = $user_id;
            $values[] = $current_time;
            $placeholders[] = '(%s, %s, %d, %s)';
            $types = array_merge($types, array('%s', '%s', '%d', '%s'));
            
            $stats['success']++;
        }
        
        fclose($handle);
        
        // Si hay entradas para insertar
        if (!empty($values)) {
            // Construir consulta
            $sql = "INSERT INTO $table_name (name, url, created_by, created_at) VALUES ";
            $sql .= implode(', ', $placeholders);
            
            // Ejecutar consulta
            $result = $wpdb->query($wpdb->prepare($sql, $values));
            
            if (false === $result) {
                wp_send_json_error(array(
                    'message' => __('Error al guardar los datos en la base de datos.', 'wp-entry-index'),
                    'stats' => $stats
                ));
            }
        }
        
        // Devolver estadísticas
        wp_send_json_success(array(
            'message' => sprintf(
                __('Importación completada. Total: %d, Éxito: %d, Error: %d', 'wp-entry-index'),
                $stats['total'],
                $stats['success'],
                $stats['error']
            ),
            'stats' => $stats
        ));
    }
    
    // Buscar categorías (AJAX)
    public function ajax_search_categories() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_settings_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener término de búsqueda
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Buscar categorías
        $args = array(
            'taxonomy' => 'category',
            'hide_empty' => false,
            'name__like' => $search,  // Usar name__like en lugar de search para filtrar por nombre
            'number' => 10
        );
        
        $categories = get_terms($args);
        $results = array();
        
        // Verificar si hay categorías y no es un error
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                if (isset($category->term_id) && isset($category->name)) {
                    // Solo incluir categorías que contengan el término de búsqueda
                    if (empty($search) || stripos($category->name, $search) !== false) {
                        $results[] = array(
                            'id' => $category->term_id,
                            'text' => $category->name
                        );
                    }
                }
            }
        }
        
        // Asegurar que siempre devolvemos un array, incluso si está vacío
        wp_send_json_success(array('results' => $results));
    }
    
    // Guardar configuración (AJAX)
    public function ajax_save_settings() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_settings_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-entry-index')));
        }
        
        // Obtener categorías seleccionadas
        $categories = isset($_POST['wp_entry_index_categories']) ? $_POST['wp_entry_index_categories'] : array();
        
        // Sanitizar categorías
        $sanitized_categories = array();
        if (!empty($categories)) {
            foreach ($categories as $cat_id => $cat_name) {
                $sanitized_categories[absint($cat_id)] = sanitize_text_field($cat_name);
            }
        }
        
        // Guardar categorías
        update_option('wp_entry_index_categories', $sanitized_categories);
        
        // Obtener datos de configuración general
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Sanitizar datos
        $sanitized_settings = array();
        
        // Procesar configuración general
        if (isset($settings['general'])) {
            $sanitized_settings['general'] = array();
            
            // Título del índice
            if (isset($settings['general']['index_title'])) {
                $sanitized_settings['general']['index_title'] = sanitize_text_field($settings['general']['index_title']);
            }
            
            // Descripción del índice
            if (isset($settings['general']['index_description'])) {
                $sanitized_settings['general']['index_description'] = wp_kses_post($settings['general']['index_description']);
            }
            
            // Elementos por página
            if (isset($settings['general']['items_per_page'])) {
                $sanitized_settings['general']['items_per_page'] = absint($settings['general']['items_per_page']);
            }
        }
        
        // Procesar configuración de visualización
        if (isset($settings['display'])) {
            $sanitized_settings['display'] = array();
            
            // Mostrar buscador
            if (isset($settings['display']['show_search'])) {
                $sanitized_settings['display']['show_search'] = (bool) $settings['display']['show_search'];
            }
            
            // Mostrar paginación
            if (isset($settings['display']['show_pagination'])) {
                $sanitized_settings['display']['show_pagination'] = (bool) $settings['display']['show_pagination'];
            }
            
            // Estilo de visualización
            if (isset($settings['display']['display_style'])) {
                $sanitized_settings['display']['display_style'] = sanitize_text_field($settings['display']['display_style']);
            }
        }
        
        // Guardar configuración
        update_option('wp_entry_index_settings', $sanitized_settings);
        
        wp_send_json_success(array(
            'message' => __('Configuración guardada correctamente.', 'wp-entry-index'),
            'settings' => $sanitized_settings,
            'categories' => $sanitized_categories
        ));
    }
}