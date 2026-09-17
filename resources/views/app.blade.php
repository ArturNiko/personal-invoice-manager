<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ env('APP_NAME') }}</title>
    <meta name="description" content="A short description of your page.">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('pim-theme');
                var theme =
                    stored === 'light' || stored === 'dark' ? stored : 'dark';
                var root = document.documentElement;
                root.classList.add(theme);
                root.style.colorScheme = theme;
            } catch (error) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body
    data-authenticated="{{ auth()->check() ? '1' : '0' }}"
    data-verified="{{ auth()->check() && auth()->user()->hasVerifiedEmail() ? '1' : '0' }}"
>

<div id="app"></div>

</body>
</html>