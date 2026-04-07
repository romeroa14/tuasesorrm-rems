/**
 * Lógica para la Tabla de Actividades
 * Maneja los cálculos en tiempo real, validaciones y envío del formulario
 * para evaluar agentes internos
 */

$(document).ready(function() {
    // Verificar que estamos en la página correcta
    if (!$('#activity-form').length) {
        return; // Si no está el formulario de actividades, no ejecutar nada
    }

    // Obtener datos originales desde el HTML de manera más específica
    let originalAcquisitionCommission = 0;
    let originalClosingCommission = 0;
    
    // Buscar en la sección del Agente Captador con regex mejorado
    $('.card').each(function() {
        const cardHeader = $(this).find('.card-header').text();
        if (cardHeader.includes('Agente Captador')) {
            const commissionText = $(this).find('p:contains("Comisión Original")').text();
            // Regex mejorado para capturar números con comas y decimales
            const match = commissionText.match(/\$([0-9,]+\.?[0-9]*)/);
            if (match) {
                originalAcquisitionCommission = parseFloat(match[1].replace(/,/g, ''));
                console.log('✓ Comisión captador extraída:', originalAcquisitionCommission);
            } else {
                console.warn('⚠ No se pudo extraer comisión captador');
            }
        }
        if (cardHeader.includes('Agente Cerrador')) {
            const commissionText = $(this).find('p:contains("Comisión Original")').text();
            // Regex mejorado para capturar números con comas y decimales
            const match = commissionText.match(/\$([0-9,]+\.?[0-9]*)/);
            if (match) {
                originalClosingCommission = parseFloat(match[1].replace(/,/g, ''));
                console.log('✓ Comisión cerrador extraída:', originalClosingCommission);
            } else {
                console.warn('⚠ No se pudo extraer comisión cerrador');
            }
        }
    });
    
    // Debug: mostrar valores extraídos
    console.log('Comisión original captador:', originalAcquisitionCommission);
    console.log('Comisión original cerrador:', originalClosingCommission);
    
    // Debug: mostrar estado inicial
    console.log('🔍 Estado inicial:');
    console.log(`Actividades captador: ${$('.acquisition-activity').length} (marcadas: ${$('.acquisition-activity:checked').length})`);
    console.log(`Actividades cerrador: ${$('.closing-activity').length} (marcadas: ${$('.closing-activity:checked').length})`);
    
    /**
     * Función para actualizar cálculos en tiempo real
     */
    function updateCalculations() {
        // Verificar que tenemos los valores originales
        if (originalAcquisitionCommission === 0 && originalClosingCommission === 0) {
            console.warn('No se pudieron extraer las comisiones originales');
            return;
        }
        
        // LÓGICA CORREGIDA: Suma directa de porcentajes (como debe ser)
        // Para captador: suma directa de porcentajes de actividades seleccionadas
        let acquisitionPercentage = 0;
        $('.acquisition-activity:checked').each(function() {
            acquisitionPercentage += parseFloat($(this).data('percentage')) || 0;
        });
        const acquisitionCalculated = originalAcquisitionCommission * (acquisitionPercentage / 100);
        
        // Para cerrador: suma directa de porcentajes de actividades seleccionadas
        let closingPercentage = 0;
        $('.closing-activity:checked').each(function() {
            closingPercentage += parseFloat($(this).data('percentage')) || 0;
        });
        const closingCalculated = originalClosingCommission * (closingPercentage / 100);
        
        // Debug CORREGIDO - Lógica de suma directa
        console.log('✅ CÁLCULO CORREGIDO (SUMA DIRECTA):');
        console.log(`📈 Captador: ${acquisitionPercentage}% → $${acquisitionCalculated.toFixed(2)}`);
        console.log(`📈 Cerrador: ${closingPercentage}% → $${closingCalculated.toFixed(2)}`);
        
        // Mostrar actividades seleccionadas si hay alguna
        if (acquisitionPercentage > 0 || closingPercentage > 0) {
            console.log('☑️ ACTIVIDADES MARCADAS:');
            $('.acquisition-activity:checked').each(function() {
                const percentage = $(this).data('percentage');
                const name = $(this).closest('.form-check').find('label').text().trim();
                console.log(`   🔸 Captador: "${name}" = ${percentage}%`);
            });
            $('.closing-activity:checked').each(function() {
                const percentage = $(this).data('percentage');
                const name = $(this).closest('.form-check').find('label').text().trim();
                console.log(`   🔸 Cerrador: "${name}" = ${percentage}%`);
            });
        }
        
        // Actualizar UI - Agente Captador
        $('#acquisition_percentage').text(acquisitionPercentage.toFixed(2) + '%');
        $('#acquisition_calculated_commission').text('$' + acquisitionCalculated.toFixed(2));
        
        // Actualizar UI - Agente Cerrador
        $('#closing_percentage').text(closingPercentage.toFixed(2) + '%');
        $('#closing_calculated_commission').text('$' + closingCalculated.toFixed(2));
        
        // Actualizar totales en el resumen
        const totalOriginal = originalAcquisitionCommission + originalClosingCommission;
        const totalCalculated = acquisitionCalculated + closingCalculated;
        const difference = totalOriginal - totalCalculated;
        
        $('#total_original').text(totalOriginal.toFixed(2));
        $('#total_calculated').text(totalCalculated.toFixed(2));
        $('#total_difference').text(Math.abs(difference).toFixed(2));
        
        // Cambiar color según si hay pérdida o ganancia
        const $differenceElement = $('#total_difference');
        $differenceElement.removeClass('text-danger text-success text-warning');
        
        if (difference > 0) {
            $differenceElement.addClass('text-danger'); // Pérdida (rojo)
        } else if (difference < 0) {
            $differenceElement.addClass('text-success'); // Ganancia (verde)
        } else {
            $differenceElement.addClass('text-warning'); // Sin cambio (amarillo)
        }
        
        // Actualizar colores de porcentajes según el cumplimiento
        updatePercentageColors(acquisitionPercentage, closingPercentage);
    }
    
    /**
     * Actualizar colores de los porcentajes según el nivel de cumplimiento
     */
    function updatePercentageColors(acquisitionPercentage, closingPercentage) {
        // Agente Captador
        const $acquisitionBadge = $('#acquisition_percentage');
        $acquisitionBadge.removeClass('text-danger text-warning text-success text-primary');
        
        if (acquisitionPercentage >= 80) {
            $acquisitionBadge.addClass('text-success'); // Verde para excelente
        } else if (acquisitionPercentage >= 60) {
            $acquisitionBadge.addClass('text-warning'); // Amarillo para bueno
        } else if (acquisitionPercentage > 0) {
            $acquisitionBadge.addClass('text-danger'); // Rojo para deficiente
        } else {
            $acquisitionBadge.addClass('text-primary'); // Azul para sin evaluar
        }
        
        // Agente Cerrador
        const $closingBadge = $('#closing_percentage');
        $closingBadge.removeClass('text-danger text-warning text-success text-primary');
        
        if (closingPercentage >= 80) {
            $closingBadge.addClass('text-success');
        } else if (closingPercentage >= 60) {
            $closingBadge.addClass('text-warning');
        } else if (closingPercentage > 0) {
            $closingBadge.addClass('text-danger');
        } else {
            $closingBadge.addClass('text-primary');
        }
    }
    
    /**
     * Event listeners para cambios en checkboxes
     */
    $('.acquisition-activity, .closing-activity').change(function() {
        updateCalculations();
        
        // Agregar efecto visual al cambiar
        $(this).closest('.form-check').addClass('bg-light').delay(200).queue(function() {
            $(this).removeClass('bg-light').dequeue();
        });
    });
    
    /**
     * Botón para limpiar todas las selecciones
     */
    $('#reset-btn').click(function() {
        // Mostrar confirmación
        Swal.fire({
            title: '¿Limpiar todas las selecciones?',
            text: 'Se deseleccionarán todas las actividades marcadas.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-undo mr-1"></i>Sí, limpiar todo',
            cancelButtonText: '<i class="fas fa-times mr-1"></i>Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Deseleccionar todos los checkboxes
                $('.acquisition-activity, .closing-activity').prop('checked', false);
                updateCalculations();
                
                // Mostrar confirmación de limpieza
                Swal.fire({
                    title: '¡Limpiado!',
                    text: 'Todas las selecciones han sido removidas.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
            }
        });
    });
    
    /**
     * Validación y confirmación antes de enviar el formulario
     */
    $('#activity-form').submit(function(e) {
        e.preventDefault(); // Prevenir envío inicial
        
        const checkedActivities = $('.acquisition-activity:checked, .closing-activity:checked').length;
        const totalActivities = $('.acquisition-activity, .closing-activity').length;
        
        // Recopilar información para la confirmación
        const acquisitionChecked = $('.acquisition-activity:checked').length;
        const closingChecked = $('.closing-activity:checked').length;
        const acquisitionTotal = $('.acquisition-activity').length;
        const closingTotal = $('.closing-activity').length;
        
        let confirmationMessage = '';
        let warningLevel = 'info';
        
        if (checkedActivities === 0) {
            // Sin actividades seleccionadas
            confirmationMessage = `
                <div class="text-left">
                    <p><strong>⚠️ Sin actividades seleccionadas</strong></p>
                    <p>No has marcado ninguna actividad para ningún agente.</p>
                    <p><strong>Resultado:</strong> 0% de comisión para todos los agentes internos.</p>
                </div>
            `;
            warningLevel = 'warning';
            
        } else if (checkedActivities < totalActivities / 2) {
            // Pocas actividades seleccionadas
            confirmationMessage = `
                <div class="text-left">
                    <p><strong>Evaluación con bajo cumplimiento</strong></p>
                    <p>Captador: ${acquisitionChecked} de ${acquisitionTotal} actividades</p>
                    <p>Cerrador: ${closingChecked} de ${closingTotal} actividades</p>
                    <p><strong>Esto resultará en comisiones reducidas.</strong></p>
                </div>
            `;
            warningLevel = 'warning';
            
        } else {
            // Confirmación normal
            confirmationMessage = `
                <div class="text-left">
                    <p><strong>Confirmar aplicación de tabla de actividades</strong></p>
                    <p>Captador: ${acquisitionChecked} de ${acquisitionTotal} actividades</p>
                    <p>Cerrador: ${closingChecked} de ${closingTotal} actividades</p>
                    <p>Las comisiones se ajustarán según el cumplimiento evaluado.</p>
                </div>
            `;
            warningLevel = 'info';
        }
        
        // Mostrar confirmación
        Swal.fire({
            title: checkedActivities === 0 ? '¿Continuar sin actividades?' : '¿Aplicar tabla de actividades?',
            html: confirmationMessage,
            icon: warningLevel,
            showCancelButton: true,
            confirmButtonColor: warningLevel === 'warning' ? '#f39c12' : '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: '<i class="fas fa-save mr-1"></i>Sí, aplicar cambios',
            cancelButtonText: '<i class="fas fa-times mr-1"></i>Cancelar',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Simular un pequeño delay para mostrar el loading
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve();
                    }, 500);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading mientras se procesa
                Swal.fire({
                    title: 'Procesando...',
                    html: `
                        <div class="text-center">
                            <p>Aplicando tabla de actividades</p>
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
                
                // Enviar el formulario
                this.submit();
            }
        });
        
        return false;
    });
    
    /**
     * Funciones auxiliares para mejorar la experiencia de usuario
     */
    
    // Tooltip para explicar cada actividad
    $('.form-check-label').attr('title', 'Clic para marcar/desmarcar esta actividad');
    
    // Efecto hover en las actividades
    $('.form-check').hover(
        function() {
            $(this).addClass('bg-light');
        },
        function() {
            $(this).removeClass('bg-light');
        }
    );
    
    // Contador de actividades seleccionadas
    function updateActivityCounters() {
        const acquisitionSelected = $('.acquisition-activity:checked').length;
        const acquisitionTotal = $('.acquisition-activity').length;
        const closingSelected = $('.closing-activity:checked').length;
        const closingTotal = $('.closing-activity').length;
        
        // Actualizar contadores si existen elementos para mostrarlos
        if ($('#acquisition-counter').length) {
            $('#acquisition-counter').text(`${acquisitionSelected}/${acquisitionTotal}`);
        }
        if ($('#closing-counter').length) {
            $('#closing-counter').text(`${closingSelected}/${closingTotal}`);
        }
    }
    
    // Event listener para actualizar contadores
    $('.acquisition-activity, .closing-activity').change(updateActivityCounters);
    
    /**
     * Inicialización
     */
    
    // Calcular porcentaje total disponible (suma de actividades únicas, no duplicadas)
    let totalAvailablePercentage = 0;
    const uniqueActivities = new Set();
    const activityDetails = [];
    
    $('.acquisition-activity, .closing-activity').each(function() {
        const activityId = $(this).val();
        const percentage = parseFloat($(this).data('percentage')) || 0;
        const activityName = $(this).closest('.form-check').find('label').text().trim();
        
        // Solo contar cada actividad una vez (evitar duplicación entre captador/cerrador)
        if (!uniqueActivities.has(activityId)) {
            uniqueActivities.add(activityId);
            totalAvailablePercentage += percentage;
            activityDetails.push({
                id: activityId,
                name: activityName,
                percentage: percentage
            });
        }
    });
    
    // Debug simplificado de actividades
    console.log('🎯 SISTEMA TABLA DE ACTIVIDADES CARGADO:');
    console.log(`📊 Total actividades: ${uniqueActivities.size}`);
    console.log(`📊 Suma total porcentajes: ${totalAvailablePercentage}%`);
    
    // Mensaje de bienvenida (opcional)
    if (totalAvailablePercentage === 0) {
        console.warn('No hay actividades configuradas en el sistema.');
    } else {
        console.info(`✅ Sistema de actividades listo`);
    }
    
    // Ejecutar cálculos iniciales y cargar estado anterior con delay para asegurar que el DOM esté listo
    setTimeout(function() {
                // Ejecutar cálculos iniciales primero
        updateCalculations();
        updateActivityCounters();
        
        // LUEGO cargar estado anterior desde base de datos si existe
        if (typeof window.previousActivities !== 'undefined' && window.previousActivities) {
            console.log('📋 Cargando actividades previas de BD...');
            
            // Marcar actividades del captador
            if (window.previousActivities.acquisition_activities && Array.isArray(window.previousActivities.acquisition_activities)) {
                window.previousActivities.acquisition_activities.forEach(activityId => {
                    const checkbox = $(`#acquisition_activity_${activityId}`);
                    if (checkbox.length > 0) {
                        checkbox.prop('checked', true);
                    }
                });
            }
            
            // Marcar actividades del cerrador
            if (window.previousActivities.closing_activities && Array.isArray(window.previousActivities.closing_activities)) {
                window.previousActivities.closing_activities.forEach(activityId => {
                    const checkbox = $(`#closing_activity_${activityId}`);
                    if (checkbox.length > 0) {
                        checkbox.prop('checked', true);
                    }
                });
            }
        
            // Forzar actualización final de cálculos después de marcar checkboxes
            setTimeout(function() {
                updateCalculations();
                updateActivityCounters();
            }, 100);
            
            console.log('✅ Actividades previas aplicadas');
        } else {
            // Para fichas nuevas/sin datos, asegurar que TODO esté desmarcado
            $('.acquisition-activity, .closing-activity').prop('checked', false);
            
            // Forzar recálculo para mostrar 0%
            updateCalculations();
            updateActivityCounters();
            
            console.log('✅ Nueva ficha lista');
        }
    }, 1000); // Tiempo de espera aumentado para asegurar DOM completamente cargado
    
    // LIMPIAR CUALQUIER DATO RESIDUAL DE localStorage
    const keysToRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.includes('activity_table')) {
            keysToRemove.push(key);
        }
    }
    keysToRemove.forEach(key => {
        localStorage.removeItem(key);
        console.log(`🧹 Eliminado localStorage residual: ${key}`);
    });
    
    // Event listeners para cálculos en tiempo real
    $('.acquisition-activity, .closing-activity').change(function() {
        updateCalculations();
        updateActivityCounters();
    });
    
    // Botón "Limpiar Todo" - solo desmarcar checkboxes
    $('#reset-btn').click(function() {
        console.log('🧹 Limpiando todas las actividades marcadas');
        $('.acquisition-activity, .closing-activity').prop('checked', false);
        updateCalculations();
        updateActivityCounters();
        console.log('✅ Todas las actividades desmarcadas');
    });
    
    // Debug del envío del formulario
    $('#activity-form').on('submit', function() {
        console.log('📤 ENVIANDO FORMULARIO:');
        
        const acquisitionActivities = [];
        $('.acquisition-activity:checked').each(function() {
            acquisitionActivities.push($(this).val());
        });
        
        const closingActivities = [];
        $('.closing-activity:checked').each(function() {
            closingActivities.push($(this).val());
        });
        
        console.log('Actividades captador a enviar:', acquisitionActivities);
        console.log('Actividades cerrador a enviar:', closingActivities);
        console.log('Total actividades a enviar:', acquisitionActivities.length + closingActivities.length);
    });
}); 