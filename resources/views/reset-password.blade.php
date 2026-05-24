<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialiser le mot de passe - EasyMarket</title>
    <link rel="icon" href="/icons/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7fbf9;color:#17211b;font-family:'Poppins',system-ui,"Segoe UI",sans-serif}.card{width:min(460px,calc(100% - 32px));background:white;border:1px solid #dfe7e2;border-radius:12px;padding:28px;box-shadow:0 22px 60px rgba(23,33,27,.10)}h1{margin:0 0 8px;font-size:30px}p{color:#46574f;line-height:1.55}form{display:grid;gap:14px}label{display:grid;gap:7px;font-weight:600}input{border:1px solid #dfe7e2;border-radius:8px;padding:12px;font:inherit}button{min-height:44px;border:0;border-radius:8px;background:#f5b84b;color:#10251f;font-weight:500;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer}.error{display:none;background:#ffe6df;color:#a33824;border-radius:8px;padding:10px 12px;font-weight:600}body:has(.card[data-error="1"]) .error{display:block}.link{margin-top:16px;display:inline-block;color:#2f7d69;font-weight:600;text-decoration:none}
    </style>
</head>
<body>
    <main class="card" data-error="">
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un mot de passe d'au moins 8 caractères.</p>
        <div class="error">Lien invalide ou informations incorrectes.</div>
        <form method="post" action="/mot-de-passe-reinitialiser">
            <input type="hidden" name="_token" value="__CSRF_TOKEN__">
            <input type="hidden" name="email" value="__EMAIL__">
            <input type="hidden" name="token" value="__TOKEN__">
            <label>Nouveau mot de passe
                <input name="password" type="password" required autocomplete="new-password">
            </label>
            <label>Confirmer
                <input name="password_confirmation" type="password" required autocomplete="new-password">
            </label>
            <button type="submit"><i class="fa-solid fa-key"></i>Réinitialiser</button>
        </form>
        <a class="link" href="/connexion">Retour connexion</a>
    </main>
    <script>
        if (new URLSearchParams(location.search).has('erreur')) {
            document.querySelector('.card').dataset.error = '1';
        }
    </script>
    <script src="/cookie-consent.js"></script>
</body>
</html>
