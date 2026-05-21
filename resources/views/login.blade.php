<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - EasyMarket</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#2f7d69">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:#2f7d69; --ink:#17211b; --muted:#46574f; --line:#dfe7e2; --gold:#f5b84b; --paper:#f7fbf9; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:var(--paper); color:var(--ink); font-family:'Poppins', system-ui, -apple-system, "Segoe UI", sans-serif; }
        .login { width:min(440px, calc(100% - 32px)); background:white; border:1px solid var(--line); border-radius:12px; padding:28px; box-shadow:0 22px 60px rgba(23,33,27,.10); }
        .brand { display:flex; flex-direction:column; align-items:center; gap:10px; color:var(--ink); text-align:center; text-decoration:none; font-weight:500; margin-bottom:22px; }
        .logo { width:42px; height:42px; display:grid; place-items:center; border-radius:8px; background:linear-gradient(135deg,var(--primary),var(--gold)); color:#10251f; }
        .intro { text-align:center; margin-bottom:22px; }
        h1 { margin:0 0 8px; font-size:32px; line-height:1.05; }
        h1 i { color:var(--primary); font-size:26px; }
        p { margin:0; color:var(--muted); line-height:1.55; }
        form { display:grid; gap:14px; }
        label { display:grid; gap:7px; color:#193b32; font-weight:600; font-size:14px; }
        label span:first-child { display:inline-flex; align-items:center; gap:7px; }
        label span:first-child i { color:var(--primary); }
        input { width:100%; border:1px solid var(--line); border-radius:8px; padding:12px; color:var(--ink); font:inherit; }
        .input-wrap { position:relative; display:block; }
        .input-wrap i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }
        .input-wrap input { padding-left:38px; }
        .password-wrap input { padding-right:42px; }
        .toggle-password { width:34px; height:34px; min-height:34px; padding:0; position:absolute; right:5px; top:50%; transform:translateY(-50%); background:transparent; color:var(--primary); }
        .toggle-password:hover { color:#10251f; }
        .remember { display:flex; align-items:center; gap:9px; color:var(--muted); font-weight:600; font-size:14px; }
        .remember input { width:18px; height:18px; accent-color:var(--primary); }
        button, .btn { min-height:44px; border:0; border-radius:8px; background:var(--gold); color:#10251f; font-weight:500; display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; cursor:pointer; }
        .links { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; margin-top:18px; }
        .links a { min-height:36px; border:1px solid var(--line); border-radius:8px; padding:8px 9px; display:inline-flex; align-items:center; justify-content:center; gap:6px; color:#2f7d69; background:#f4faf8; font-size:12px; font-weight:500; text-align:center; text-decoration:none; }
        .links a:nth-child(2) { color:#7a4d00; background:#fff8e9; border-color:#f3dfb5; }
        .links a:nth-child(3) { color:#3f5048; background:#f2f5f3; }
        .links a:hover { transform:translateY(-1px); box-shadow:0 8px 18px rgba(23,33,27,.08); }
        .error { display:none; margin-bottom:14px; padding:10px 12px; border-radius:8px; background:#ffe6df; color:#a33824; font-weight:600; }
        body:has(.login[data-error="1"]) .error { display:block; }
        @media (max-width: 460px) { .links { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <main class="login" data-error="">
        <a class="brand" href="/">
            <span class="logo">EM</span>
            <span>EasyMarket</span>
        </a>
        <div class="intro">
            <h1><i class="fa-solid fa-lock"></i> Connexion</h1>
            <p>Accédez à votre boutique, vos ventes, vos stocks et vos rapports.</p>
        </div>
        <div class="error"><i class="fa-solid fa-triangle-exclamation"></i> Identifiants incorrects.</div>
        <form method="post" action="/connexion">
            <input type="hidden" name="_token" value="__CSRF_TOKEN__">
            <label><span><i class="fa-solid fa-user"></i>Email ou téléphone</span>
                <span class="input-wrap"><i class="fa-solid fa-at"></i><input name="login" type="text" required autocomplete="username" placeholder="vous@exemple.com ou 0196228860"></span>
            </label>
            <label><span><i class="fa-solid fa-key"></i>Mot de passe</span>
                <span class="input-wrap password-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                    <button class="toggle-password" type="button" aria-label="Afficher le mot de passe" title="Afficher le mot de passe">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </span>
            </label>
            <button type="submit"><i class="fa-solid fa-right-to-bracket"></i>Se connecter</button>
            <label class="remember">
                <input name="remember" type="checkbox" value="1">
                <span>Se souvenir de moi</span>
            </label>
        </form>
        <div class="links">
            <a href="/inscription"><i class="fa-solid fa-user-plus"></i>Créer un compte</a>
            <a href="/mot-de-passe-oublie"><i class="fa-solid fa-circle-question"></i>Mot de passe oublié</a>
            <a href="/"><i class="fa-solid fa-house"></i>Retour accueil</a>
        </div>
    </main>
    <script>
        if (new URLSearchParams(location.search).has('erreur')) {
            document.querySelector('.login').dataset.error = '1';
        }
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const input = document.querySelector('#password');
            const icon = this.querySelector('i');
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            icon.className = showing ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
            this.setAttribute('aria-label', showing ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            this.setAttribute('title', showing ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
