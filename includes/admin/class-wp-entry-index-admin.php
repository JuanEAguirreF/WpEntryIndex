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
    }
    
    // Agregar menú en el panel de administración
    public function add_admin_menu() {
        add_menu_page(
            __('Índice de Entradas', 'wp-entry-index'),
            __('Índice de Entradas', 'wp-entry-index'),
            'manage_options',
            'wp-entry-index',
            array($this, 'render_admin_page'),
            'dashicons-list-view',
            30
        );
    }
    
    // Registrar scripts y estilos
    public function enqueue_scripts($hook) {
        // Solo cargar en la página del plugin
        if ('toplevel_page_wp-entry-index' !== $hook) {
            return;
        }
        
        // Registrar estilos
        wp_enqueue_style(
            'wp-entry-index-admin',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_ENTRY_INDEX_VERSION
        );
        
        // Registrar scripts
        wp_enqueue_script(
            'wp-entry-index-admin',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_ENTRY_INDEX_VERSION,
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
    
    // Renderizar página de administración
    public function render_admin_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Obtener entradas
        $entries = $this->get_entries(50);
        
        // Incluir template
        include WP_ENTRY_INDEX_PLUGIN_DIR . 'includes/admin/views/admin-page.php';
    }
    
    // Obtener entradas
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
        
        // Leer el archivo línea por línea
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $stats['total']++;
            
            // Verificar que haya al menos dos columnas
            if (count($data) < 2) {
                $stats['error']++;
                $stats['errors'][] = sprintf(__('Línea %d: Formato incorrecto.', 'wp-entry-index'), $stats['total']);
                continue;
            }
            
            // Obtener nombre y URL
            $name = sanitize_text_field($data[0]);
            $url = esc_url_raw($data[1]);
            
            // Validar datos
            if (empty($name) || empty($url)) {
                $stats['error']++;
                $stats['errors'][] = sprintf(__('Línea %d: Nombre o URL vacíos.', 'wp-entry-index'), $stats['total']);
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
}