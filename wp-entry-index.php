<?php
/**
 * Plugin Name: WP Entry Index
 * Plugin URI: 
 * Description: Plugin para crear un índice de publicaciones manualmente desde el panel de administración.
 * Version: 1.3.1
 * Author: Waylayer
 * Author URI: https://profiles.wordpress.org/waylayer/
 * Text Domain: wp-entry-index
 * Domain Path: /languages
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WP_ENTRY_INDEX_VERSION', '1.0.0');
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
    }
    
    // Desactivación del plugin
    public function deactivate() {
        // No eliminamos la tabla para preservar los datos
    }
    
    // Inicializar el plugin
    public function init() {
        // Cargar traducciones
        load_plugin_textdomain('wp-entry-index', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
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
        add_action('publish_post', array($this, 'add_post_to_index'));
    }
    
    // Función para agregar automáticamente una entrada al índice cuando se publica un post
    public function add_post_to_index($post_id) {
        // Verificar si es una revisión o un autoguardado
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Obtener información del post
        $post = get_post($post_id);
        
        // Verificar que sea un post público
        if ($post->post_status !== 'publish' || $post->post_type !== 'post') {
            return;
        }
        
        // Obtener título y URL del post
        $name = $post->post_title;
        $url = get_permalink($post_id);
        
        // Verificar si ya existe una entrada con esta URL para evitar duplicados
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE url = %s",
                $url
            )
        );
        
        // Si ya existe, no hacer nada
        if ($existing) {
            return;
        }
        
        // Insertar en la base de datos
        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'url' => $url,
                'created_by' => $post->post_author,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s')
        );
    }
}

// Iniciar el plugin
function wp_entry_index() {
    return WP_Entry_Index::get_instance();
}

// Arrancar el plugin
wp_entry_index();