<?php
// test_final.php - Prueba completa
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Final Asignación</title>
</head>
<body>
    <h1>Prueba de Asignación Directa</h1>
    
    <div id="resultado"></div>
    
    <script>
    // Probar directamente
    const datos = {
        accion: 'asignar',
        ticket_id: 1,  // Cambia esto por un ID real
        tecnico_id: 2, // Cambia esto por un ID real de técnico
        prioridad: 'media'
    };
    
    const resultado = document.getElementById('resultado');
    resultado.innerHTML = '<p>Probando asignación...</p>';
    
    fetch('procesar_ticket.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(datos).toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultado.innerHTML = `
                <p style="color: green;">✅ Éxito: ${data.message}</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } else {
            resultado.innerHTML = `
                <p style="color: red;">❌ Error: ${data.message}</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        }
    })
    .catch(error => {
        resultado.innerHTML = `<p style="color: red;">❌ Error de conexión: ${error}</p>`;
    });
    </script>
</body>
</html>
