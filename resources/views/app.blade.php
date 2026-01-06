<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Pro Loco Venticanese - L'evoluzione della tradizione.">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <title inertia>{{ config('app.name', 'Pro Loco Venticanese') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
        
        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="manifest" href="/manifest.webmanifest">

        <script>
            (function () {
                try {
                    const key = "plv_theme";
                    const stored = localStorage.getItem(key);
                    const systemDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
                    const theme = stored || (systemDark ? "dark" : "light");
                    document.documentElement.classList.toggle("dark", theme === "dark");
                } catch (e) {}
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased h-full">
        @inertia
    </body>
</html>
