<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş | golfandwellness</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="login-page login-page--classic">
    <main class="classic-login-wrap">
        <section class="classic-login-card">
            <img class="classic-login-logo" src="{{ asset('images/golf-and-wellness-logo.png') }}" alt="Golf and Wellness Turkey">
            <h1>Kullanıcı Girişi</h1>
            <p>Golf acente yönetim sistemine erişmek için giriş yapın.</p>
            @if ($errors->any())
                <p role="alert" data-vox-login-error>{{ $errors->first() }}</p>
            @endif
            <form class="login-form classic-login-form" action="{{ route('login.submit') }}" method="POST">
                @csrf
                <label><span>Kullanıcı adı</span><input name="username" type="text" autocomplete="username" value="{{ old('username') }}" placeholder="Kullanıcı adınız" required autofocus></label>
                <label>
                    <span>Şifre</span>
                    <span class="password-wrap">
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Şifrenizi girin" required>
                        <button type="button" class="password-toggle" aria-label="Şifreyi göster" onclick="togglePassword(this)"><span aria-hidden="true">◉</span></button>
                    </span>
                </label>
                <div class="login-options">
                    <span class="secure-session"><i></i> Güvenli oturum</span>
                </div>
                <button class="login-submit" type="submit"><span>Giriş Yap</span></button>
            </form>
        </section>
    </main>
    <script>
        function togglePassword(button) {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
            button.classList.toggle('is-visible');
        }
    </script>
</body>
</html>
