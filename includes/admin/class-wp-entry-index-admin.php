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
            __('Índice de Entradas', 'WpEntryIndex'),
            __('Índice de Entradas', 'WpEntryIndex'),
            'manage_options',
            'WpEntryIndex',
            array($this, 'render_index_page'),
            'dashicons-list-view',
            30
        );
        
        // Submenú Índice (mismo que el principal)
        add_submenu_page(
            'WpEntryIndex',
            __('Índice', 'WpEntryIndex'),
            __('Índice', 'WpEntryIndex'),
            'manage_options',
            'WpEntryIndex',
            array($this, 'render_index_page')
        );
        
        // Submenú Configuración
        add_submenu_page(
            'WpEntryIndex',
            __('Configuración', 'WpEntryIndex'),
            __('Configuración', 'WpEntryIndex'),
            'manage_options',
            'WpEntryIndex-settings',
            array($this, 'render_settings_page')
        );
    }
    
    // Registrar scripts y estilos
    public function enqueue_scripts($hook) {
        // Cargar en las páginas del plugin
        if ('toplevel_page_WpEntryIndex' !== $hook && 'indice-de-entradas_page_WpEntryIndex-settings' !== $hook) {
            return;
        }
        
        // Registrar estilos
        wp_enqueue_style(
            'WpEntryIndex-admin',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/css/admin.css')
        );
        
        // Solo cargar scripts en la página principal (índice)
        if ('toplevel_page_WpEntryIndex' === $hook) {
            // Registrar scripts
            wp_enqueue_script(
                'WpEntryIndex-admin',
                WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/js/admin.js'),
                true
            );
            
            // Pasar variables al script
            wp_localize_script(
                'WpEntryIndex-admin',
                'wp_entry_index_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_entry_index_nonce'),
                    'messages' => array(
                        'confirm_delete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'WpEntryIndex'),
                        'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'WpEntryIndex'),
                        'success_add' => __('Elemento agregado correctamente.', 'WpEntryIndex'),
                        'success_edit' => __('Elemento actualizado correctamente.', 'WpEntryIndex'),
                        'success_delete' => __('Elemento eliminado correctamente.', 'WpEntryIndex'),
                        'success_import' => __('Importación completada correctamente.', 'WpEntryIndex'),
                        'error_import' => __('Error en la importación. Por favor, verifica el formato del archivo.', 'WpEntryIndex'),
                        'add_title' => __('Agregar Entrada', 'WpEntryIndex'),
                        'edit_title' => __('Editar Entrada', 'WpEntryIndex')
                    )
                )
            );
        }
        
        // Cargar scripts en la página de configuración
        if ('indice-de-entradas_page_WpEntryIndex-settings' === $hook) {
            // Registrar scripts
            wp_enqueue_script(
                'WpEntryIndex-settings',
                WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/settings.js',
                array('jquery'),
                filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/js/settings.js'),
                true
            );
            
            // Pasar variables al script
            wp_localize_script(
                'WpEntryIndex-settings',
                'wp_entry_index_settings_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_entry_index_settings_nonce'),
                    'messages' => array(
                        'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'WpEntryIndex'),
                        'success_save' => __('Configuración guardada correctamente.', 'WpEntryIndex'),
                        'no_results' => __('No se encontraron categorías.', 'WpEntryIndex'),
                        'searching' => __('Buscando...', 'WpEntryIndex')
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
        // Verificar nonce si se está enviando un formulario
        if (isset($_GET['s']) || isset($_GET['paged'])) {
            // No es necesario verificar nonce para operaciones de solo lectura como paginación y búsqueda
            // pero es una buena práctica sanitizar los datos de entrada
        }
        
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
    
        $offset = ($page - 1) * $per_page;
    
        $table_name = $wpdb->prefix . 'entry_index';
        $base_query = "SELECT * FROM {$wpdb->prefix}entry_index";
        $base_count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}entry_index";
        $query_args = array();
        $count_args = array();
    
        if (!empty($search)) {
            $base_query .= " WHERE name LIKE %s OR url LIKE %s";
            $base_count_query .= " WHERE name LIKE %s OR url LIKE %s";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $count_args[] = $search_term;
            $count_args[] = $search_term;
        }
    
        $base_query .= " ORDER BY id DESC LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = $offset;
    
        // Preparar consultas
        $query = $wpdb->prepare($base_query, ...$query_args);
        $count_query = $wpdb->prepare($base_count_query, ...$count_args);
    
        $cache_key = 'wp_entry_index_entries_' . md5($query);
        $cache_count_key = 'wp_entry_index_count_' . md5($count_query);
    
        $entries = wp_cache_get($cache_key, 'wp_entry_index');
        $total = wp_cache_get($cache_count_key, 'wp_entry_index');
    
        if (false === $entries) {
            // Asegurarse de que la consulta ya está preparada correctamente
            $entries = $wpdb->get_results($query, ARRAY_A);
            wp_cache_set($cache_key, $entries, 'wp_entry_index', 3600);
        }
    
        if (false === $total) {
            // Asegurarse de que la consulta de conteo ya está preparada correctamente
            $total = $wpdb->get_var($count_query);
            wp_cache_set($cache_count_key, $total, 'wp_entry_index', 3600);
        }
    
        return array(
            'entries' => $entries,
            'total' => $total
        );
    }
    
    // Obtener entradas (método original para compatibilidad)
    private function get_entries($limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        // Crear clave de caché basada en el límite
        $cache_key = 'wp_entry_index_entries_limit_' . $limit;
        
        // Intentar obtener desde caché
        $entries = wp_cache_get($cache_key, 'wp_entry_index');
        
        // Si no está en caché, ejecutar consulta
        if (false === $entries) {
            $entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}entry_index ORDER BY id DESC LIMIT %d",
                    $limit
                ),
                ARRAY_A
            );
            
            // Guardar en caché
            wp_cache_set($cache_key, $entries, 'wp_entry_index', 3600); // Caché por 1 hora
        }
        
        return $entries;
    }
    
    // Agregar entrada (AJAX)
    public function ajax_add_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
        }
        
        // Obtener datos
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        // Validar datos
        if (empty($name) || empty($url)) {
            wp_send_json_error(array('message' => __('El nombre y la URL son obligatorios.', 'WpEntryIndex')));
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
            wp_send_json_error(array('message' => __('Error al guardar los datos.', 'WpEntryIndex')));
        }
        
        // Invalidar caché de listas de entradas
        // Esto forzará a regenerar las listas de entradas en la próxima solicitud
        $cache_keys = array(
            'wp_entry_index_entries_all',
            'wp_entry_index_entries_limit_50'
        );
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'wp_entry_index');
        }
        
        // Obtener el ID insertado
        $entry_id = $wpdb->insert_id;
        
        // Obtener la entrada completa
        $table_name_esc = esc_sql($table_name);
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name_esc} WHERE id = %d",
                $entry_id
            ),
            ARRAY_A
        );
        
        // Agregar información del usuario
        $user = get_userdata($entry['created_by']);
        $entry['created_by_name'] = $user ? $user->display_name : __('Usuario desconocido', 'WpEntryIndex');
        
        wp_send_json_success(array(
            'message' => __('Entrada agregada correctamente.', 'WpEntryIndex'),
            'entry' => $entry
        ));
    }
    
    // Editar entrada (AJAX)
    public function ajax_edit_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
        }
        
        // Obtener datos
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        // Validar datos
        if (empty($id) || empty($name) || empty($url)) {
            wp_send_json_error(array('message' => __('Todos los campos son obligatorios.', 'WpEntryIndex')));
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
            wp_send_json_error(array('message' => __('Error al actualizar los datos.', 'WpEntryIndex')));
        }
        
        // Obtener la entrada actualizada
        // Crear clave de caché basada en el ID
        $cache_key = 'wp_entry_index_entry_id_' . $id;
        
        // Invalidar caché existente
        wp_cache_delete($cache_key, 'wp_entry_index');
        
        // Usar $wpdb->prepare para proteger contra inyecciones SQL
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}entry_index WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        // Guardar en caché
        if ($entry) {
            wp_cache_set($cache_key, $entry, 'wp_entry_index', 3600); // Caché por 1 hora
        }
        
        // Agregar información de los usuarios
        $created_user = get_userdata($entry['created_by']);
        $entry['created_by_name'] = $created_user ? $created_user->display_name : __('Usuario desconocido', 'WpEntryIndex');
        
        $modified_user = get_userdata($entry['modified_by']);
        $entry['modified_by_name'] = $modified_user ? $modified_user->display_name : __('Usuario desconocido', 'WpEntryIndex');
        
        wp_send_json_success(array(
            'message' => __('Entrada actualizada correctamente.', 'WpEntryIndex'),
            'entry' => $entry
        ));
    }
    
    // Eliminar entrada (AJAX)
    public function ajax_delete_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
        }
        
        // Obtener ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validar ID
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID no válido.', 'WpEntryIndex')));
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
            wp_send_json_error(array('message' => __('Error al eliminar la entrada.', 'WpEntryIndex')));
        }
        
        // Invalidar caché relacionada con esta entrada
        wp_cache_delete('wp_entry_index_entry_id_' . $id, 'wp_entry_index');
        
        // Invalidar caché de listas de entradas
        // Esto forzará a regenerar las listas de entradas en la próxima solicitud
        $cache_keys = array(
            'wp_entry_index_entries_all',
            'wp_entry_index_entries_limit_50'
        );
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'wp_entry_index');
        }
        
        wp_send_json_success(array(
            'message' => __('Entrada eliminada correctamente.', 'WpEntryIndex'),
            'id' => $id
        ));
    }
    
    // Obtener entrada (AJAX)
    public function ajax_get_entry() {
        // Verificar nonce
        check_ajax_referer('wp_entry_index_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
        }
        
        // Obtener ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validar ID
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID no válido.', 'WpEntryIndex')));
        }
        
        // Obtener de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        // Crear clave de caché basada en el ID
        $cache_key = 'wp_entry_index_entry_id_' . $id;
        
        // Intentar obtener desde caché
        $entry = wp_cache_get($cache_key, 'wp_entry_index');
        
        // Si no está en caché, ejecutar consulta
        if (false === $entry) {
            // Usar $wpdb->prepare para proteger contra inyecciones SQL
            $entry = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}entry_index WHERE id = %d",
                    $id
                ),
                ARRAY_A
            );
            
            // Guardar en caché
            if ($entry) {
                wp_cache_set($cache_key, $entry, 'wp_entry_index', 3600); // Caché por 1 hora
            }
        }
        
        if (null === $entry) {
            wp_send_json_error(array('message' => __('Entrada no encontrada.', 'WpEntryIndex')));
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
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
        }
        
        // Verificar si se ha subido un archivo
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error(array('message' => __('No se ha seleccionado ningún archivo.', 'WpEntryIndex')));
        }
        
        $file = $_FILES['csv_file'];
        
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Error al subir el archivo.', 'WpEntryIndex')));
        }
        
        // Verificar tipo de archivo
        $file_type = wp_check_filetype(basename($file['name']), array('csv' => 'text/csv'));
        if (!$file_type['ext']) {
            wp_send_json_error(array('message' => __('El archivo debe ser un CSV válido.', 'WpEntryIndex')));
        }
        
        // Usar WP_Filesystem en lugar de fopen
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        // Leer el contenido del archivo
        $file_content = $wp_filesystem->get_contents($file['tmp_name']);
        if (false === $file_content) {
            wp_send_json_error(array('message' => __('No se pudo abrir el archivo.', 'WpEntryIndex')));
        }
        
        // Convertir el contenido a un array de líneas
        $lines = explode("\n", str_replace("\r\n", "\n", $file_content));
        $handle = $lines; // Usamos $handle para mantener compatibilidad con el código existente
        
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
        
        // Procesar cada línea del archivo
        foreach ($handle as $line) {
            $row_count++;
            
            // Omitir líneas vacías
            if (empty(trim($line))) {
                continue;
            }
            
            // Omitir la primera fila si está marcada la opción
            if ($skip_header && $row_count === 1) {
                continue;
            }
            
            $stats['total']++;
            
            // Parsear la línea CSV manualmente
            $data = str_getcsv($line, ',');
            
            // Verificar que haya al menos dos columnas
            if (count($data) < 2) {
                $stats['error']++;
                /* translators: %d: número de línea en el archivo CSV */
                $stats['errors'][] = sprintf(__('Línea %d: Formato incorrecto.', 'WpEntryIndex'), $row_count);
                continue;
            }
            
            // Obtener nombre y URL
            $name = sanitize_text_field($data[0]);
            $url = esc_url_raw($data[1]);
            
            // Validar datos
            if (empty($name) || empty($url)) {
                $stats['error']++;
                /* translators: %d: número de línea en el archivo CSV */
                $stats['errors'][] = sprintf(__('Línea %d: Nombre o URL vacíos.', 'WpEntryIndex'), $row_count);
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
        
        // No necesitamos cerrar el handle ya que no estamos usando fopen
        
        // Si hay entradas para insertar
        if (!empty($values)) {
            $sql = "INSERT INTO {$wpdb->prefix}entry_index (name, url, created_by, created_at) VALUES ";
            $sql .= implode(', ', $placeholders);
    
            // Preparar y ejecutar consulta usando $wpdb->prepare para proteger contra inyecciones SQL
            $prepared_sql = $wpdb->prepare($sql, ...$values);
            $result = $wpdb->query($prepared_sql);
    
            if (false === $result) {
                wp_send_json_error(array(
                    'message' => __('Error al guardar los datos en la base de datos.', 'WpEntryIndex'),
                    'stats' => $stats
                ));
            }
            
            // Invalidar todas las cachés relacionadas con listas de entradas
            // ya que hemos agregado múltiples entradas
            $cache_patterns = array(
                'wp_entry_index_entries_',
                'wp_entry_index_count_'
            );
            
            // Usar wp_cache_flush() sería demasiado agresivo, así que invalidamos
            // solo las cachés relacionadas con nuestro plugin
            foreach ($cache_patterns as $pattern) {
                wp_cache_delete($pattern . 'all', 'wp_entry_index');
                wp_cache_delete($pattern . 'limit_50', 'wp_entry_index');
            }
        }
        
        // Devolver estadísticas
        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %1$d: número total de entradas, %2$d: número de entradas importadas con éxito, %3$d: número de entradas con error */
                __('Importación completada. Total: %1$d, Éxito: %2$d, Error: %3$d', 'WpEntryIndex'),
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
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
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
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'WpEntryIndex')));
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
            'message' => __('Configuración guardada correctamente.', 'WpEntryIndex'),
            'settings' => $sanitized_settings,
            'categories' => $sanitized_categories
        ));
    }
}