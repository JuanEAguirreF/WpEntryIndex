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
            return '<p>' . __('No hay entradas disponibles.', 'wp-entry-index') . '</p>';
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Abrir contenedor
        echo '<div class="wp-entry-index-container">';
        
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
        
        // Preparar consulta
        $sql = "SELECT * FROM $table_name ORDER BY name ASC";
        
        // Agregar límite si es necesario
        if ($limit > 0) {
            $sql = $wpdb->prepare($sql . " LIMIT %d", $limit);
        }
        
        // Ejecutar consulta
        $entries = $wpdb->get_results($sql, ARRAY_A);
        
        return $entries;
    }
}