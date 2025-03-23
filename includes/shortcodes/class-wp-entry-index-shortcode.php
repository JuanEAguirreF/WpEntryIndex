<?php
/**
 * Clase para manejar el shortcode del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class WP_Entry_Index_Shortcode {
    
    // Inicializar la clase
    public static function init() {
        $instance = new self();
        return $instance;
    }
    
    // Constructor
    public function __construct() {
        // Registrar shortcode
        add_shortcode('wp_entry_index', array($this, 'render_shortcode'));
        
        // Registrar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
    }
    
    // Registrar scripts y estilos
    public function register_scripts() {
        // Registrar y encolar CSS
        wp_register_style(
            'wp-entry-index-public',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/css/public.css',
            array(),
            filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/css/public.css')
        );
        
        // Registrar JavaScript
        wp_register_script(
            'wp-entry-index-public',
            WP_ENTRY_INDEX_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            filemtime(WP_ENTRY_INDEX_PLUGIN_DIR . 'assets/js/public.js'),
            true
        );
    }
    
    // Renderizar shortcode
    public function render_shortcode($atts) {
        // Procesar atributos
        $atts = shortcode_atts(
            array(
                'limit' => 0, // 0 = sin límite
            ),
            $atts,
            'wp_entry_index'
        );
        
        // Obtener entradas
        $entries = $this->get_entries($atts['limit']);
        
        // Si no hay entradas, devolver mensaje
        if (empty($entries)) {
            return '<p>' . __('No hay entradas disponibles.', 'WpEntryIndex') . '</p>';
        }
        
        // Encolar estilos y scripts
        wp_enqueue_style('wp-entry-index-public');
        wp_enqueue_script('wp-entry-index-public');
        
        // Iniciar buffer de salida
        ob_start();
        
        // Abrir contenedor
        echo '<div class="wp-entry-index-container">';
        
        // Agregar buscador
        echo '<div class="wp-entry-index-search-container">';
        echo '<input type="text" id="wp-entry-index-search-input" placeholder="' . esc_attr__('Buscar...', 'WpEntryIndex') . '">';
        echo '<ul id="wp-entry-index-autocomplete-results" style="display: none;"></ul>';
        echo '</div>';
        
        // Abrir lista
        echo '<ul class="wp-entry-index-list">';
        
        // Recorrer entradas
        foreach ($entries as $entry) {
            echo '<li class="wp-entry-index-item">';
            echo '<a href="' . esc_url($entry['url']) . '" target="_blank">';
            echo esc_html($entry['name']);
            echo '</a>';
            echo '</li>';
        }
        
        // Cerrar lista
        echo '</ul>';
        
        // Cerrar contenedor
        echo '</div>';
        
        // Devolver contenido
        return ob_get_clean();
    }
    
    // Obtener entradas ordenadas alfabéticamente
    private function get_entries($limit = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'entry_index';
        
        // Crear clave de caché basada en el límite
        $cache_key = 'wp_entry_index_entries_' . ($limit > 0 ? 'limit_' . $limit : 'all');
        
        // Intentar obtener datos de la caché
        $entries = wp_cache_get($cache_key, 'wp_entry_index');
        
        // Si no hay datos en caché, ejecutar consulta
        if (false === $entries) {
            if ($limit > 0) {
                // Consulta con límite usando prepare() correctamente
                $entries = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}entry_index ORDER BY name ASC LIMIT %d",
                        $limit
                    ),
                    ARRAY_A
                );
            } else {
                // Consulta sin límite - Se fuerza un marcador de posición ficticio
                $entries = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}entry_index WHERE %d = %d ORDER BY name ASC",
                        1, 1
                    ),
                    ARRAY_A
                );
            }
        
            // Guardar en caché
            wp_cache_set($cache_key, $entries, 'wp_entry_index', 3600);
        }
        
        
        return $entries;
    }
}