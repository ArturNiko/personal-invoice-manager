<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }}</title>
    <meta name="description" content="A short description of your page.">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body>

<div id="app"></div>

</body>
</html>