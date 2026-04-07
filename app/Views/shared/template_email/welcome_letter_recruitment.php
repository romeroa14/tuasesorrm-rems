<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        p {
            color: #555;
        }

        .button {
            width: 100% !important;
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border-radius: 4px;
            border: none;
        }

        .button:hover {
            background-color: #45a049;
        }
        </style>
    </head>
    <body>
        <div
        style="
            width: 100%;
            background-color: black;
        "
        >
            <center>
                <img style="width:400px; padding:40px;" src="https://tuasesorrm.com.ve/assets/images/asesores-rm-new-white.webp" alt="">
            </center>
        </div>
        <div class="container">
        <h1>Le damos la bienvenida a captaciones</h1>
        <p>Hola, <strong><?= $user_full_name ?></strong></p>
        <p>En Asesores RM nos sentimos complacidos en prestar nuestros servicios, le daremos una atención de calidad profesional, nuestro agente <strong><?= $user_agent ?></strong> estará apoyándo durante todo el proceso inmobiliario.</p>
        <p>Sin mas que decir adjuntamos en este correo un documento PDF <strong>(Carta de bienvenida)</strong>, el cual contiene información detallada del proceso que se sometera su propiedad.</p>
        </div>
        <div
        style="
            width: 100%;
            background-color: black;
        "
        >
            <center>
                <p style="color: white !important; padding: 15px;">All rights © <?= $date_footer ?> | <a 
                    href="https://tuasesorrm.com.ve/home"
                    style="color: #17a2b8!important;"
                >Asesores RM.</a></p>
            </center>
        </div>
    </body>
</html>