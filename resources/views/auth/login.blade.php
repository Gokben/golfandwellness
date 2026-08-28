<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş | golfandwellness</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/css/app.css'])
</head>
<body class="login-page login-page--classic">
    <main class="classic-login-wrap">
        <section class="classic-login-card">
            <img class="classic-login-logo" src="{{ asset('images/golf-and-wellness-logo.png') }}" alt="Golf and Wellness Turkey">
            <h1>Kullanıcı Girişi</h1>
            <p>Golf acente yönetim sistemine erişmek için giriş yapın.</p>
            <form class="login-form classic-login-form" onsubmit="event.preventDefault(); window.location.assign('{{ route('preview') }}')">
                <label><span>E-posta adresi</span><input type="email" autocomplete="username" placeholder="ad.soyad@acente.com" required autofocus></label>
                <label>
                    <span>Şifre</span>
                    <span class="password-wrap">
                        <input id="password" type="password" autocomplete="current-password" placeholder="Şifrenizi girin" required>
                        <button type="button" class="password-toggle" aria-label="Şifreyi göster" onclick="togglePassword(this)"><span aria-hidden="true">◉</span></button>
                    </span>
                </label>
                <div class="login-options">
                    <label class="remember"><input type="checkbox"><span>Kullanıcı adını hatırla</span></label>
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
