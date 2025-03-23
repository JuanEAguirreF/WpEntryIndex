<?php
/**
 * Plugin Name: WP Entry Index
 * Plugin URI: 
 * Description: Plugin para crear un índice de publicaciones manualmente desde el panel de administración.
 * Version: 1.5.3
 * Author: Waylayer
 * Author URI: https://profiles.wordpress.org/waylayer/
 * Text Domain: WpEntryIndex
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WP_ENTRY_INDEX_VERSION', '1.5.3');
define('WP_ENTRY_INDEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_ENTRY_INDEX_PLUGIN_URL', plugin_dir_url(__FILE__));

// Clase principal del plugin
class WP_Entry_Index {
    
    // Instancia única (patrón singleton)
    private static $instance = null;
    
    // Constructor
    private function __construct() {
        // Activación y desactivación del plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicializar el plugin
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    // Obtener instancia única
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Activación del plugin
    public function activate() {
        // Crear tabla en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            modified_by bigint(20) DEFAULT NULL,
            modified_at datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Inicializar opción para categorías seleccionadas
        add_option('wp_entry_index_categories', array());
    }
    
    // Desactivación del plugin
    public function deactivate() {
        // No eliminamos la tabla para preservar los datos
    }
    
    // Inicializar el plugin
    public function init() {
        // Cargar traducciones
        load_plugin_textdomain('WpEntryIndex', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Incluir archivos necesarios
        $this->includes();
        
        // Inicializar componentes
        $this->init_hooks();
    }
    
    // Incluir archivos necesarios
    private function includes() {
        // Admin
        require_once WP_ENTRY_INDEX_PLUGIN_DIR . 'includes/admin/class-wp-entry-index-admin.php';
        
        // Shortcode
        require_once WP_ENTRY_INDEX_PLUGIN_DIR . 'includes/shortcodes/class-wp-entry-index-shortcode.php';
    }
    
    // Inicializar hooks
    private function init_hooks() {
        // Inicializar admin
        WP_Entry_Index_Admin::init();
        
        // Registrar shortcode
        WP_Entry_Index_Shortcode::init();
        
        // Hook para agregar entradas automáticamente cuando se publica un post
        // Usamos transition_post_status para detectar específicamente cuando un post cambia a 'publish'
        // Usamos prioridad 20 para asegurar que se ejecute después de que WordPress haya guardado las categorías
        add_action('transition_post_status', array($this, 'check_post_status'), 20, 3);
        // Mantenemos save_post para compatibilidad con versiones anteriores
        add_action('save_post', array($this, 'add_post_to_index'));
        
        // Registrar hook para el evento programado
        add_action('wp_entry_index_process_post', array($this, 'process_post_for_index'));
    }
    
    // Función para verificar el cambio de estado de un post
    public function check_post_status($new_status, $old_status, $post) {
        // Solo procesar cuando un post cambia a 'publish'
        if ($new_status === 'publish' && $old_status !== 'publish' && $post->post_type === 'post') {
            error_log('WP Entry Index: Post ' . $post->ID . ' cambió de estado ' . $old_status . ' a ' . $new_status);
            
            // Programar la ejecución con un retraso de 5 segundos para permitir que WordPress finalice el guardado
            wp_schedule_single_event(time() + 5, 'wp_entry_index_process_post', array($post->ID));
            error_log('WP Entry Index: Programada la detección de categorías con retraso para el post ' . $post->ID);
        }
    }
    
    // Función para agregar automáticamente una entrada al índice cuando se publica un post
    // Mantenida para compatibilidad con versiones anteriores
    public function add_post_to_index($post_id) {
        // Verificar si ya existe un evento programado para este post
        if (wp_next_scheduled('wp_entry_index_process_post', array($post_id))) {
            error_log('WP Entry Index: Ya existe un evento programado para el post ' . $post_id . ', no se procesa inmediatamente.');
            return;
        }
        
        // Si no hay evento programado, llamamos a la función de procesamiento
        $this->process_post_for_index($post_id);
    }
    
    // Función para procesar un post y agregarlo al índice si cumple los requisitos
    public function process_post_for_index($post_id) {
        // Verificar si es una revisión o un autoguardado
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            error_log('WP Entry Index: Post ' . $post_id . ' es una revisión o autoguardado, no se procesa.');
            return;
        }
        
        // Obtener información del post
        $post = get_post($post_id);
        
        // Verificar que sea un post público
        if ($post->post_status !== 'publish' || $post->post_type !== 'post') {
            error_log('WP Entry Index: Post ' . $post_id . ' no es un post publicado o no es del tipo post. Estado: ' . $post->post_status . ', Tipo: ' . $post->post_type);
            return;
        }
        
        error_log('WP Entry Index: Procesando post ' . $post_id . ' - ' . $post->post_title);
        
        // Limpiar la caché del post para asegurar que obtenemos los datos más recientes
        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'posts');
        error_log('WP Entry Index: Caché del post limpiada para asegurar datos actualizados');
        
        // Obtener las categorías seleccionadas en la configuración
        $selected_categories = get_option('wp_entry_index_categories', array());
        error_log('WP Entry Index: Categorías seleccionadas en configuración (tipo: ' . gettype($selected_categories) . '): ' . print_r($selected_categories, true));
        
        // Si hay categorías seleccionadas, verificar si el post pertenece a alguna de ellas
        if (!empty($selected_categories)) {
            // Usar get_the_category en lugar de wp_get_post_categories para obtener información más precisa
            $categories = get_the_category($post_id);
            $post_categories = array();
            
            // Extraer los IDs de las categorías
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $post_categories[] = $category->term_id;
                }
            }
            
            error_log('WP Entry Index: Categorías del post (get_the_category): ' . print_r($post_categories, true));
            
            // También obtener categorías con el método anterior para comparación en logs
            $old_post_categories = wp_get_post_categories($post_id, array('fields' => 'ids'));
            error_log('WP Entry Index: Categorías del post (wp_get_post_categories): ' . print_r($old_post_categories, true));
            
            // Verificar si hay intersección entre las categorías del post y las seleccionadas
            $category_match = false;
            error_log('WP Entry Index: Tipo de dato de post_categories: ' . gettype($post_categories));
            error_log('WP Entry Index: Tipo de dato de selected_categories: ' . gettype($selected_categories));
            
            // Obtener los IDs de categorías seleccionadas y asegurar que sean enteros
            $selected_cat_ids = array_map('intval', array_keys($selected_categories));
            error_log('WP Entry Index: IDs de categorías seleccionadas: ' . implode(', ', $selected_cat_ids));
            
            // Simplificar la comparación de categorías
            $category_match = count(array_intersect($post_categories, $selected_cat_ids)) > 0;
            
            if ($category_match) {
                error_log('WP Entry Index: Coincidencia encontrada entre categorías del post y categorías seleccionadas');
            } else {
                // Verificación adicional para compatibilidad con diferentes tipos de datos
                foreach ($post_categories as $cat_id) {
                    $cat_id_str = strval($cat_id);
                    $cat_id_int = intval($cat_id);
                    
                    error_log('WP Entry Index: Verificando categoría ' . $cat_id . ' (tipo: ' . gettype($cat_id) . ')');
                    
                    if (in_array($cat_id, $selected_cat_ids) || 
                        isset($selected_categories[$cat_id]) || 
                        isset($selected_categories[$cat_id_str])) {
                        $category_match = true;
                        error_log('WP Entry Index: Coincidencia encontrada en categoría ' . $cat_id);
                        break;
                    } else {
                        error_log('WP Entry Index: Categoría ' . $cat_id . ' no coincide con ninguna categoría seleccionada');
                    }
                }
            }
            
            // Si no hay coincidencia, no agregar al índice
            if (!$category_match) {
                error_log('WP Entry Index: No se encontraron coincidencias de categorías para el post ' . $post_id . ', no se agrega al índice.');
                return;
            }
        }
        
        // Obtener título y URL del post
        $name = $post->post_title;
        $url = get_permalink($post_id);
        
        // Verificar si ya existe una entrada con esta URL para evitar duplicados
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        // Crear clave de caché basada en la URL
        $cache_key = 'wp_entry_index_existing_url_' . md5($url);
        
        // Intentar obtener desde caché
        $existing = wp_cache_get($cache_key, 'wp_entry_index');
        
        // Si no está en caché, ejecutar consulta
        if (false === $existing) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}entry_index WHERE url = %s",
                    $url
                )
            );
            
            // Guardar en caché
            wp_cache_set($cache_key, $existing, 'wp_entry_index', 3600); // Caché por 1 hora
        }
        
        // Si ya existe, no hacer nada
        if ($existing) {
            error_log('WP Entry Index: Ya existe una entrada con la URL ' . $url . ', no se agrega al índice.');
            return;
        }
        
        // Insertar en la base de datos
        $result = $wpdb->insert(
            $wpdb->prefix . 'entry_index',
            array(
                'name' => $name,
                'url' => $url,
                'created_by' => $post->post_author,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            error_log('WP Entry Index: Entrada agregada correctamente al índice. ID: ' . $wpdb->insert_id);
        } else {
            error_log('WP Entry Index: Error al agregar entrada al índice: ' . $wpdb->last_error);
        }
    }
}

// Iniciar el plugin
function wp_entry_index() {
    return WP_Entry_Index::get_instance();
}

// Arrancar el plugin
wp_entry_index();