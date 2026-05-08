// tecnico.js

// Variables globales
let ticketsFiltrados = [];
let modalAbierto = null;

// Cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar estadísticas
    cargarEstadisticasIniciales();
    
    // Configurar filtros
    document.querySelector('.btn-filtrar').addEventListener('click', aplicarFiltros);
    document.querySelector('.btn-refresh').addEventListener('click', recargarTickets);
    
    // Configurar formulario de resolución
    document.getElementById('form-resolver').addEventListener('submit', function(e) {
        e.preventDefault();
        resolverTicketConfirmado();
    });
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('modal-resolver').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
});

// Función para cargar estadísticas iniciales
function cargarEstadisticasIniciales() {
    fetch('ajax/estadisticas_tecnico.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar badges
                document.getElementById('badge-tickets').textContent = data.total_asignados;
                document.getElementById('badge-nuevos').textContent = data.tickets_nuevos;
                
                // Actualizar tarjetas de estadísticas
                document.querySelector('.stat-card.nuevo .stat-number').textContent = data.nuevos;
                document.querySelector('.stat-card.proceso .stat-number').textContent = data.en_proceso;
                document.querySelector('.stat-card.pendiente .stat-number').textContent = data.pendientes;
                document.querySelector('.stat-card.cerrado .stat-number').textContent = data.cerrados_hoy;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Función para aplicar filtros
function aplicarFiltros() {
    const filtroEstado = document.getElementById('filtro-estado').value;
    const filtroPrioridad = document.getElementById('filtro-prioridad').value;
    
    // Ocultar todos los tickets primero
    document.querySelectorAll('.ticket-item').forEach(ticket => {
        ticket.style.display = 'none';
    });
    
    // Mostrar solo los que coincidan con los filtros
    document.querySelectorAll('.ticket-item').forEach(ticket => {
        const estado = ticket.getAttribute('data-estado');
        const prioridad = ticket.getAttribute('data-prioridad');
        
        const coincideEstado = (filtroEstado === 'todos' || estado === filtroEstado);
        const coincidePrioridad = (filtroPrioridad === 'todas' || prioridad === filtroPrioridad);
        
        if (coincideEstado && coincidePrioridad) {
            ticket.style.display = 'block';
        }
    });
}

// Función para recargar tickets
function recargarTickets() {
    location.reload();
}

// Función para ver detalle de ticket
function verDetalle(ticketId) {
    window.open(`detalle_ticket.php?id=${ticketId}`, '_blank');
}

// Función para cambiar estado de ticket
function cambiarEstado(ticketId, nuevoEstado) {
    if (!confirm('¿Estás seguro de cambiar el estado del ticket?')) return;
    
    const formData = new FormData();
    formData.append('accion', 'cambiar_estado');
    formData.append('ticket_id', ticketId);
    formData.append('estado', nuevoEstado);
    
    fetch('acciones_tecnico.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            recargarTickets();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Función para mostrar modal de resolución
function resolverTicket(ticketId) {
    document.getElementById('ticket-id-resolver').value = ticketId;
    document.getElementById('modal-resolver').style.display = 'block';
    modalAbierto = 'resolver';
}

// Función para cerrar modal
function cerrarModal() {
    if (modalAbierto) {
        document.getElementById('modal-' + modalAbierto).style.display = 'none';
        modalAbierto = null;
    }
}

// Función para resolver ticket confirmado
function resolverTicketConfirmado() {
    const ticketId = document.getElementById('ticket-id-resolver').value;
    const solucion = document.getElementById('solucion').value;
    const tiempoResolucion = document.getElementById('tiempo-resolucion').value;
    
    if (!solucion || !tiempoResolucion) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'resolver_ticket');
    formData.append('ticket_id', ticketId);
    formData.append('solucion', solucion);
    formData.append('tiempo_resolucion', tiempoResolucion);
    
    fetch('acciones_tecnico.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cerrarModal();
            recargarTickets();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Función para cargar tickets nuevos disponibles
function cargarTicketsNuevos() {
    fetch('ajax/tickets_disponibles.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarTicketsDisponibles(data.tickets);
            } else {
                alert('Error al cargar tickets disponibles');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Función para aceptar ticket disponible
function aceptarTicket(ticketId) {
    if (!confirm('¿Aceptar este ticket?')) return;
    
    const formData = new FormData();
    formData.append('accion', 'aceptar_ticket');
    formData.append('ticket_id', ticketId);
    
    fetch('acciones_tecnico.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarTicketsNuevos(); // Recargar lista
            cargarEstadisticasIniciales(); // Actualizar estadísticas
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Función para mostrar estadísticas detalladas
function cargarEstadisticas() {
    // Aquí puedes implementar una vista de estadísticas más detallada
    alert('Esta funcionalidad se implementará en la siguiente versión');
}

// Teclas rápidas
document.addEventListener('keydown', function(e) {
    // Ctrl + R: Recargar
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        recargarTickets();
    }
    
    // Escape: Cerrar modal
    if (e.key === 'Escape' && modalAbierto) {
        cerrarModal();
    }
});
