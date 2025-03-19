(function($) {
    'use strict';
    
    // Cuando el DOM esté listo
    $(document).ready(function() {
        // Referencias a elementos del DOM
        const $modal = $('#wp-entry-index-modal');
        const $importModal = $('#wp-entry-index-import-modal');
        const $form = $('#wp-entry-index-form');
        const $importForm = $('#wp-entry-index-import-form');
        const $modalTitle = $('#wp-entry-index-modal-title');
        const $idField = $('#wp-entry-index-id');
        const $nameField = $('#wp-entry-index-name');
        const $urlField = $('#wp-entry-index-url');
        const $tableBody = $('#wp-entry-index-table-body');
        const $notice = $('#wp-entry-index-notice');
        const $importResults = $('#wp-entry-index-import-results');
        const $importSummary = $('#wp-entry-index-import-summary');
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
        
        // Función para abrir el modal de importación
        function openImportModal() {
            $importResults.hide();
            $importSummary.empty();
            $importForm[0].reset();
            $importModal.show();
        }
        
        // Función para cerrar el modal de importación
        function closeImportModal() {
            $importForm[0].reset();
            $importModal.hide();
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
        
        // Evento: Clic en botón Importar CSV
        $('#wp-entry-index-import-button').on('click', function() {
            openImportModal();
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
        $('.wp-entry-index-modal-close').on('click', function() {
            if ($(this).closest('.wp-entry-index-modal').is($importModal)) {
                closeImportModal();
            } else {
                closeModal();
            }
        });
        
        // Evento: Cerrar modal (botón Cancelar)
        $('.wp-entry-index-modal-cancel').on('click', function() {
            if ($(this).closest('.wp-entry-index-modal').is($importModal)) {
                closeImportModal();
            } else {
                closeModal();
            }
        });
        
        // Evento: Cerrar modal (clic fuera del contenido)
        $('.wp-entry-index-modal').on('click', function(e) {
            if (e.target === this) {
                if ($(this).is($importModal)) {
                    closeImportModal();
                } else {
                    closeModal();
                }
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
        
        // Evento: Enviar formulario de importación CSV
        $importForm.on('submit', function(e) {
            e.preventDefault();
            
            // Crear FormData para enviar el archivo
            const formData = new FormData(this);
            formData.append('action', 'wp_entry_index_import_csv');
            formData.append('nonce', wp_entry_index_vars.nonce);
            
            // Mostrar mensaje de carga
            showNotice('Importando datos, por favor espere...', 'success');
            
            // Enviar datos
            $.ajax({
                url: wp_entry_index_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Mostrar resultados
                        $importResults.show();
                        
                        // Crear resumen
                        let summary = '<p>' + response.data.message + '</p>';
                        
                        // Si hay errores, mostrarlos
                        if (response.data.stats.error > 0 && response.data.stats.errors) {
                            summary += '<p>Errores:</p><ul>';
                            response.data.stats.errors.forEach(function(error) {
                                summary += '<li>' + error + '</li>';
                            });
                            summary += '</ul>';
                        }
                        
                        $importSummary.html(summary);
                        
                        // Mostrar mensaje
                        showNotice(response.data.message, 'success');
                        
                        // Recargar la página después de 2 segundos para mostrar los nuevos datos
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Mostrar resultados con error
                        $importResults.show();
                        
                        let summary = '<p class="error">' + response.data.message + '</p>';
                        
                        // Si hay estadísticas, mostrarlas
                        if (response.data.stats) {
                            summary += '<p>Estadísticas:</p>';
                            summary += '<p>Total: ' + response.data.stats.total + ', ';
                            summary += 'Éxito: ' + response.data.stats.success + ', ';
                            summary += 'Error: ' + response.data.stats.error + '</p>';
                            
                            // Si hay errores, mostrarlos
                            if (response.data.stats.error > 0 && response.data.stats.errors) {
                                summary += '<p>Errores:</p><ul>';
                                response.data.stats.errors.forEach(function(error) {
                                    summary += '<li>' + error + '</li>';
                                });
                                summary += '</ul>';
                            }
                        }
                        
                        $importSummary.html(summary);
                        
                        // Mostrar mensaje de error
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