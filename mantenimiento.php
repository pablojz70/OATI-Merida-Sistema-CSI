<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            text-align: center;
            padding: 40px 20px;
        }
        .container img {
            max-width: 300px;
            width: 90%;
            height: auto;
            margin-bottom: 40px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .container h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0 0 20px 0;
            line-height: 1.3;
        }
        .container p {
            font-size: 1.2em;
            color: #6c757d;
            margin: 0;
        }
        @media (max-width: 600px) {
            .container h1 { font-size: 1.8em; }
            .container img { max-width: 200px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="imagen/trabajo.jpg" alt="En mantenimiento">
        <h1>Estamos en Mantenimiento</h1>
        <p>Disculpe las molestias, en pocos minutos restablecemos el servicio.</p>
    </div>
</body>
</html>
