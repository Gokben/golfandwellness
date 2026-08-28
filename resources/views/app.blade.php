<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>golfandwellness</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="app-page">
    <div id="app" data-user-name="Golf Yöneticisi" data-login-url="{{ route('login') }}"></div>
</body>
</html>
