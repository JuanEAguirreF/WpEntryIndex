<?php
/**
 * Clase para manejar el shortcode del plugin
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class Personal_Post_Index_Shortcode {
    
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
            'personal-post-index-public',
            PERSONAL_POST_INDEX_PLUGIN_URL . 'assets/css/public.css',
            array(),
            filemtime(PERSONAL_POST_INDEX_PLUGIN_DIR . 'assets/css/public.css')
        );
        
        // Registrar JavaScript
        wp_register_script(
            'personal-post-index-public',
            PERSONAL_POST_INDEX_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            filemtime(PERSONAL_POST_INDEX_PLUGIN_DIR . 'assets/js/public.js'),
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
            return '<p>' . __('No hay entradas disponibles.', 'PersonalPostIndex') . '</p>';
        }
        
        // Encolar estilos y scripts
        wp_enqueue_style('personal-post-index-public');
        wp_enqueue_script('personal-post-index-public');
        
        // Iniciar buffer de salida
        ob_start();
        
        // Abrir contenedor
        echo '<div class="personal-post-index-container">';
        
        // Agregar buscador
        echo '<div class="personal-post-index-search-container">';
        echo '<input type="text" id="personal-post-index-search-input" placeholder="' . esc_attr__('Buscar...', 'PersonalPostIndex') . '">';
        echo '<ul id="personal-post-index-autocomplete-results" style="display: none;"></ul>';
        echo '</div>';
        
        // Abrir lista
        echo '<ul class="personal-post-index-list">';
        
        // Recorrer entradas
        foreach ($entries as $entry) {
            echo '<li class="personal-post-index-item">';
            echo '<a href="' . esc_url($entry['url']) . '" target="_blank">';
            echo esc_html($entry['name']);
            
            // Mostrar "Aporte por: NombreDeUsuario" solo si el autor no es administrador
            if (isset($entry['is_admin']) && !$entry['is_admin'] && isset($entry['author_name'])) {
                echo '<span class="personal-post-index-author">';
                echo __('Aporte por:', 'PersonalPostIndex') . ' ' . esc_html($entry['author_name']);
                echo '</span>';
            }
            
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
            
            // Añadir información del autor a cada entrada
            if (!empty($entries)) {
                foreach ($entries as &$entry) {
                    // Obtener información del usuario que creó la entrada
                    $user = get_userdata($entry['created_by']);
                    
                    // Verificar si el usuario existe
                    if ($user) {
                        $entry['author_name'] = $user->display_name;
                        
                        // Verificar si el usuario es administrador
                        $entry['is_admin'] = user_can($user->ID, 'manage_options');
                    } else {
                        $entry['author_name'] = __('Usuario desconocido', 'PersonalPostIndex');
                        $entry['is_admin'] = false;
                    }
                }
                unset($entry); // Romper la referencia
            }
        
            // Guardar en caché
            wp_cache_set($cache_key, $entries, 'wp_entry_index', 3600);
        }
        
        return $entries;
    }
}