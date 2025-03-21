jQuery(document).ready(function($) {
    // Variables
    var searchTimeout;
    var categorySearch = $('#wp-entry-index-category-search');
    var categoryResults = $('#wp-entry-index-category-results');
    var selectedCategories = $('#wp-entry-index-selected-categories');
    var noCategories = $('.wp-entry-index-no-categories');
    var settingsForm = $('#wp-entry-index-settings-form');
    var settingsNotice = $('#wp-entry-index-settings-notice');
    
    // Función para buscar categorías
    function searchCategories(query) {
        // Limpiar timeout anterior
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Si la consulta está vacía, ocultar resultados
        if (query.length < 2) {
            categoryResults.empty().hide();
            return;
        }
        
        // Mostrar mensaje de búsqueda
        categoryResults.html('<p class="searching">' + wp_entry_index_settings_vars.messages.searching + '</p>').show();
        
        // Establecer timeout para evitar muchas peticiones
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: wp_entry_index_settings_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_entry_index_search_categories',
                    nonce: wp_entry_index_settings_vars.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        displayCategoryResults(response.data);
                    } else {
                        categoryResults.html('<p class="no-results">' + wp_entry_index_settings_vars.messages.no_results + '</p>');
                    }
                },
                error: function() {
                    categoryResults.html('<p class="error">' + wp_entry_index_settings_vars.messages.error + '</p>');
                }
            });
        }, 300);
    }
    
    // Función para mostrar resultados de categorías
    function displayCategoryResults(categories) {
        // Si no hay resultados
        if (categories.length === 0) {
            categoryResults.html('<p class="no-results">' + wp_entry_index_settings_vars.messages.no_results + '</p>');
            return;
        }
        
        // Crear lista de resultados
        var resultsList = $('<ul class="wp-entry-index-category-list"></ul>');
        
        // Añadir cada categoría a la lista
        $.each(categories, function(index, category) {
            // Comprobar si la categoría ya está seleccionada
            var isSelected = selectedCategories.find('[data-id="' + category.id + '"]').length > 0;
            
            // Si no está seleccionada, añadirla a los resultados
            if (!isSelected) {
                var item = $('<li class="wp-entry-index-category-item" data-id="' + category.id + '" data-name="' + category.name + '">' + category.name + '</li>');
                resultsList.append(item);
            }
        });
        
        // Si todos los resultados ya están seleccionados
        if (resultsList.children().length === 0) {
            categoryResults.html('<p class="no-results">' + wp_entry_index_settings_vars.messages.no_results + '</p>');
            return;
        }
        
        // Mostrar resultados
        categoryResults.empty().append(resultsList).show();
    }
    
    // Función para añadir una categoría seleccionada
    function addSelectedCategory(id, name) {
        // Ocultar mensaje de no categorías
        noCategories.hide();
        
        // Crear etiqueta de categoría
        var categoryTag = $('<div class="wp-entry-index-category-tag" data-id="' + id + '">' +
                           '<span>' + name + '</span>' +
                           '<a href="#" class="wp-entry-index-remove-category">&times;</a>' +
                           '<input type="hidden" name="wp_entry_index_categories[' + id + ']" value="' + name + '">' +
                           '</div>');
        
        // Añadir a la lista de seleccionados
        selectedCategories.append(categoryTag);
        
        // Limpiar búsqueda
        categorySearch.val('').focus();
        categoryResults.empty().hide();
    }
    
    // Función para eliminar una categoría seleccionada
    function removeSelectedCategory(categoryTag) {
        // Eliminar etiqueta
        categoryTag.remove();
        
        // Si no hay categorías seleccionadas, mostrar mensaje
        if (selectedCategories.children('.wp-entry-index-category-tag').length === 0) {
            noCategories.show();
        }
    }
    
    // Función para guardar la configuración
    function saveSettings(form) {
        // Serializar datos del formulario
        var formData = form.serialize();
        
        // Añadir acción y nonce
        formData += '&action=wp_entry_index_save_settings&nonce=' + wp_entry_index_settings_vars.nonce;
        
        // Enviar petición AJAX
        $.ajax({
            url: wp_entry_index_settings_vars.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                // Deshabilitar botón de envío
                form.find('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    settingsNotice.removeClass('notice-error').addClass('notice-success')
                        .html('<p>' + wp_entry_index_settings_vars.messages.success_save + '</p>')
                        .show();
                } else {
                    // Mostrar mensaje de error
                    settingsNotice.removeClass('notice-success').addClass('notice-error')
                        .html('<p>' + wp_entry_index_settings_vars.messages.error + '</p>')
                        .show();
                }
            },
            error: function() {
                // Mostrar mensaje de error
                settingsNotice.removeClass('notice-success').addClass('notice-error')
                    .html('<p>' + wp_entry_index_settings_vars.messages.error + '</p>')
                    .show();
            },
            complete: function() {
                // Habilitar botón de envío
                form.find('button[type="submit"]').prop('disabled', false);
                
                // Ocultar mensaje después de 3 segundos
                setTimeout(function() {
                    settingsNotice.fadeOut();
                }, 3000);
            }
        });
    }
    
    // Evento: Buscar categorías al escribir
    categorySearch.on('input', function() {
        var query = $(this).val().trim();
        searchCategories(query);
    });
    
    // Evento: Seleccionar categoría de los resultados
    categoryResults.on('click', '.wp-entry-index-category-item', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        addSelectedCategory(id, name);
    });
    
    // Evento: Eliminar categoría seleccionada
    selectedCategories.on('click', '.wp-entry-index-remove-category', function(e) {
        e.preventDefault();
        var categoryTag = $(this).closest('.wp-entry-index-category-tag');
        removeSelectedCategory(categoryTag);
    });
    
    // Evento: Enviar formulario
    settingsForm.on('submit', function(e) {
        e.preventDefault();
        saveSettings($(this));
    });
    
    // Ocultar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wp-entry-index-category-search-container').length) {
            categoryResults.hide();
        }
    });
});