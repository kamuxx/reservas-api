<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>API Documentation</title>
    <link rel="stylesheet" href="{{ asset('vendor/swagger-ui/swagger-ui.css') }}">
    <style>
        body { margin: 0; padding: 0; }
        #swagger-ui { height: 100vh; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="{{ asset('vendor/swagger-ui/swagger-ui-bundle.js') }}"></script>
    <script src="{{ asset('vendor/swagger-ui/swagger-ui-standalone-preset.js') }}"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ asset('api-docs.yaml') }}", // Apuntamos a tu archivo YAML
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout"
            });
            window.ui = ui;
        };
    </script>
</body>
</html>