(function($) {
    'use strict';

    $(document).ready(function() {
        // Elementos del DOM
        const $container = $('.wp-entry-index-container');
        const $searchContainer = $('.wp-entry-index-search-container');
        const $searchInput = $('#wp-entry-index-search-input');
        const $autocompleteResults = $('#wp-entry-index-autocomplete-results');
        const $indexItems = $('.wp-entry-index-item');
        
        // Variables para el autocompletado
        let allItems = [];
        let searchTimeout;
        const MAX_SUGGESTIONS = 5;
        
        // Inicializar
        initSearch();
        
        /**
         * Inicializa la funcionalidad de búsqueda
         */
        function initSearch() {
            // Recopilar todos los elementos del índice
            $indexItems.each(function() {
                const $item = $(this);
                const $link = $item.find('a');
                
                allItems.push({
                    element: $item,
                    name: $link.text().trim(),
                    url: $link.attr('href'),
                    visible: true
                });
            });
            
            // Eventos de búsqueda
            $searchInput.on('input', function() {
                const query = $(this).val().trim().toLowerCase();
                
                // Limpiar el timeout anterior
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Establecer un nuevo timeout para evitar muchas búsquedas seguidas
                searchTimeout = setTimeout(function() {
                    if (query.length > 0) {
                        showAutocompleteResults(query);
                    } else {
                        hideAutocompleteResults();
                        showAllItems();
                    }
                }, 300);
            });
            
            // Cerrar autocompletado al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.wp-entry-index-search-container').length) {
                    hideAutocompleteResults();
                }
            });
        }
        
        /**
         * Muestra los resultados de autocompletado
         */
        function showAutocompleteResults(query) {
            // Filtrar elementos que coincidan con la búsqueda
            const matchedItems = allItems.filter(function(item) {
                return item.name.toLowerCase().includes(query);
            });
            
            // Limpiar resultados anteriores
            $autocompleteResults.empty();
            
            // Si no hay resultados
            if (matchedItems.length === 0) {
                $autocompleteResults.append('<li class="no-results">No se encontraron resultados</li>');
                $autocompleteResults.show();
                return;
            }
            
            // Mostrar hasta MAX_SUGGESTIONS resultados
            const itemsToShow = matchedItems.slice(0, MAX_SUGGESTIONS);
            
            // Agregar cada resultado al autocompletado
            itemsToShow.forEach(function(item) {
                const $suggestion = $('<li class="autocomplete-item">' + item.name + '</li>');
                
                // Al hacer clic en una sugerencia
                $suggestion.on('click', function() {
                    $searchInput.val(item.name);
                    hideAutocompleteResults();
                    filterItems(item.name);
                });
                
                $autocompleteResults.append($suggestion);
            });
            
            // Agregar opción "Mostrar más" si hay más resultados
            if (matchedItems.length > MAX_SUGGESTIONS) {
                const $showMore = $('<li class="show-more">Mostrar más resultados (' + matchedItems.length + ')</li>');
                
                // Al hacer clic en "Mostrar más"
                $showMore.on('click', function() {
                    hideAutocompleteResults();
                    filterItems(query, true);
                });
                
                $autocompleteResults.append($showMore);
            }
            
            // Mostrar resultados
            $autocompleteResults.show();
        }
        
        /**
         * Oculta los resultados de autocompletado
         */
        function hideAutocompleteResults() {
            $autocompleteResults.hide();
        }
        
        /**
         * Filtra los elementos según la búsqueda
         */
        function filterItems(query, isPartialMatch = false) {
            const searchQuery = query.toLowerCase();
            
            // Filtrar elementos
            allItems.forEach(function(item) {
                const matches = isPartialMatch 
                    ? item.name.toLowerCase().includes(searchQuery)
                    : item.name.toLowerCase() === searchQuery;
                
                // Actualizar visibilidad
                item.visible = matches;
                item.element.toggle(matches);
            });
        }
        
        /**
         * Muestra todos los elementos
         */
        function showAllItems() {
            allItems.forEach(function(item) {
                item.visible = true;
                item.element.show();
            });
        }
    });
    
})(jQuery);