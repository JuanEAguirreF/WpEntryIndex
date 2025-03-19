(function($) {
    'use strict';
    
    // Cuando el DOM esté listo
    $(document).ready(function() {
        // Referencias a elementos del DOM
        const $modal = $('#wp-entry-index-modal');
        const $form = $('#wp-entry-index-form');
        const $modalTitle = $('#wp-entry-index-modal-title');
        const $idField = $('#wp-entry-index-id');
        const $nameField = $('#wp-entry-index-name');
        const $urlField = $('#wp-entry-index-url');
        const $tableBody = $('#wp-entry-index-table-body');
        const $notice = $('#wp-entry-index-notice');
        const rowTemplate = $('#wp-entry-index-row-template').html();
        
        // Función para mostrar notificaciones
        function showNotice(message, type) {
            $notice.removeClass('notice-success notice-error')
                  .addClass(type === 'success' ? 'notice-success' : 'notice-error')
                  .html('<p>' + message + '</p>')
                  .show();
            
            // Ocultar después de 3 segundos
            setTimeout(function() {
                $notice.hide();
            }, 3000);
        }
        
        // Función para abrir el modal
        function openModal(title) {
            $modalTitle.text(title);
            $modal.show();
        }
        
        // Función para cerrar el modal
        function closeModal() {
            $form[0].reset();
            $idField.val('');
            $modal.hide();
        }
        
        // Función para renderizar una fila
        function renderRow(entry) {
            let row = rowTemplate;
            
            // Reemplazar placeholders con datos
            row = row.replace(/\{\{id\}\}/g, entry.id);
            row = row.replace(/\{\{name\}\}/g, entry.name);
            row = row.replace(/\{\{url\}\}/g, entry.url);
            row = row.replace(/\{\{created_by_name\}\}/g, entry.created_by_name || '');
            row = row.replace(/\{\{created_at\}\}/g, entry.created_at || '');
            row = row.replace(/\{\{modified_by_name\}\}/g, entry.modified_by_name || '');
            row = row.replace(/\{\{modified_at\}\}/g, entry.modified_at || '');
            
            return row;
        }
        
        // Evento: Clic en botón Agregar
        $('#wp-entry-index-add-button').on('click', function() {
            openModal(wp_entry_index_vars.messages.add_title || 'Agregar Entrada');
        });
        
        // Evento: Clic en botón Editar
        $tableBody.on('click', '.wp-entry-index-edit', function() {
            const entryId = $(this).data('id');
            
            // Obtener datos de la entrada
            $.ajax({
                url: wp_entry_index_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_entry_index_get',
                    nonce: wp_entry_index_vars.nonce,
                    id: entryId
                },
                success: function(response) {
                    if (response.success) {
                        const entry = response.data.entry;
                        
                        // Llenar formulario
                        $idField.val(entry.id);
                        $nameField.val(entry.name);
                        $urlField.val(entry.url);
                        
                        // Abrir modal
                        openModal(wp_entry_index_vars.messages.edit_title || 'Editar Entrada');
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice(wp_entry_index_vars.messages.error, 'error');
                }
            });
        });
        
        // Evento: Clic en botón Borrar
        $tableBody.on('click', '.wp-entry-index-delete', function() {
            const entryId = $(this).data('id');
            
            // Confirmar eliminación
            if (confirm(wp_entry_index_vars.messages.confirm_delete)) {
                // Eliminar entrada
                $.ajax({
                    url: wp_entry_index_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wp_entry_index_delete',
                        nonce: wp_entry_index_vars.nonce,
                        id: entryId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Eliminar fila de la tabla
                            $tableBody.find('tr[data-id="' + entryId + '"]').remove();
                            
                            // Mostrar mensaje
                            showNotice(response.data.message, 'success');
                            
                            // Si no hay más filas, mostrar mensaje de tabla vacía
                            if ($tableBody.find('tr').length === 0) {
                                $tableBody.html('<tr><td colspan="8">No hay entradas disponibles.</td></tr>');
                            }
                        } else {
                            showNotice(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotice(wp_entry_index_vars.messages.error, 'error');
                    }
                });
            }
        });
        
        // Evento: Cerrar modal (X)
        $('.wp-entry-index-modal-close').on('click', closeModal);
        
        // Evento: Cerrar modal (botón Cancelar)
        $('.wp-entry-index-modal-cancel').on('click', closeModal);
        
        // Evento: Cerrar modal (clic fuera del contenido)
        $modal.on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Evento: Enviar formulario
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const id = $idField.val();
            const name = $nameField.val();
            const url = $urlField.val();
            
            // Validar campos
            if (!name || !url) {
                showNotice(wp_entry_index_vars.messages.error, 'error');
                return;
            }
            
            // Determinar si es agregar o editar
            const action = id ? 'wp_entry_index_edit' : 'wp_entry_index_add';
            
            // Enviar datos
            $.ajax({
                url: wp_entry_index_vars.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: wp_entry_index_vars.nonce,
                    id: id,
                    name: name,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        // Cerrar modal
                        closeModal();
                        
                        // Mostrar mensaje
                        showNotice(response.data.message, 'success');
                        
                        // Actualizar tabla
                        if (id) {
                            // Editar: Reemplazar fila existente
                            $tableBody.find('tr[data-id="' + id + '"]').replaceWith(renderRow(response.data.entry));
                        } else {
                            // Agregar: Añadir nueva fila al principio
                            if ($tableBody.find('tr td[colspan]').length) {
                                // Si hay mensaje de tabla vacía, eliminarlo
                                $tableBody.empty();
                            }
                            $tableBody.prepend(renderRow(response.data.entry));
                        }
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice(wp_entry_index_vars.messages.error, 'error');
                }
            });
        });
    });
})(jQuery);