// js/admin.js

// Funciones generales para el panel de administración

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips si es necesario
    inicializarTooltips();
    
    // Configurar eventos de filtros
    configurarFiltros();
    
    // Cargar estadísticas iniciales
    cargarEstadisticasAdmin();
});

function inicializarTooltips() {
    // Inicializar tooltips de Bootstrap si están disponibles
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
}

function configurarFiltros() {
    // Configurar fecha máxima para filtros de fecha
    const hoy = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.max = hoy;
    });
}

function cargarEstadisticasAdmin() {
    // Cargar estadísticas generales para el dashboard admin
    fetch('ajax/estadisticas_admin.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contadores en el menú si existen
                const badges = document.querySelectorAll('.badge');
                badges.forEach(badge => {
                    const badgeId = badge.id;
                    if (badgeId && data[badgeId.replace('badge-', '')]) {
                        badge.textContent = data[badgeId.replace('badge-', '')];
                    }
                });
            }
        })
        .catch(error => console.error('Error cargando estadísticas:', error));
}

// Función para exportar datos a CSV
function exportarCSV(tablaId, nombreArchivo = 'datos.csv') {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    
    let csv = [];
    const filas = tabla.querySelectorAll('tr');
    
    for (let fila of filas) {
        const celdas = fila.querySelectorAll('td, th');
        const filaDatos = [];
        
        for (let celda of celdas) {
            // Excluir columnas de acciones
            if (!celda.querySelector('.btn-accion')) {
                filaDatos.push(`"${celda.innerText.replace(/"/g, '""')}"`);
            }
        }
        
        csv.push(filaDatos.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, nombreArchivo);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = nombreArchivo;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Función para imprimir tabla
function imprimirTabla(tablaId) {
    const printContents = document.getElementById(tablaId).outerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <html>
            <head>
                <title>Imprimir</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                ${printContents}
            </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

// Función para asignar múltiples tickets
function asignarMultiple() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    const ticketIds = [];
    
    checkboxes.forEach(checkbox => {
        if (checkbox.value) {
            ticketIds.push(checkbox.value);
        }
    });
    
    if (ticketIds.length === 0) {
        alert('Selecciona al menos un ticket');
        return;
    }
    
    document.getElementById('modal-asignar-multiple').style.display = 'block';
    document.getElementById('tickets-ids-asignar').value = ticketIds.join(',');
}

// Validación de formularios
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valido = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#e74c3c';
            valido = false;
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    return valido;
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'success') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <span>${mensaje}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${tipo === 'success' ? '#2ecc71' : '#e74c3c'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        if (notificacion.parentElement) {
            notificacion.remove();
        }
    }, 5000);
}

// Agregar estilos para notificaciones
const estilosNotificacion = document.createElement('style');
estilosNotificacion.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .notificacion button {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 10px;
    }
`;
document.head.appendChild(estilosNotificacion);

// Teclas rápidas para administrador
document.addEventListener('keydown', function(e) {
    // Ctrl + E: Exportar
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportarCSV('tabla-tickets', 'tickets.csv');
    }
    
    // Ctrl + P: Imprimir
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        imprimirTabla('tabla-tickets');
    }
    
    // Ctrl + N: Nuevo usuario
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        document.getElementById('form-crear-usuario').scrollIntoView();
    }
});
