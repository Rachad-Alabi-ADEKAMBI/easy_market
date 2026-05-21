<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublie - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:#2f7d69; --dark:#10251f; --ink:#17211b; --muted:#46574f; --line:#dfe7e2; --gold:#f5b84b; --paper:#f7fbf9; --success:#e1f3ee; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:22px; background:radial-gradient(circle at top left, rgba(47,125,105,.16), transparent 34%), var(--paper); color:var(--ink); font-family:'Poppins', system-ui, "Segoe UI", sans-serif; }
        .card { width:min(460px, 100%); background:white; border:1px solid var(--line); border-radius:14px; padding:30px; box-shadow:0 24px 70px rgba(23,33,27,.12); }
        .brand { width:46px; height:46px; display:grid; place-items:center; border-radius:10px; background:linear-gradient(135deg, var(--primary), var(--gold)); color:var(--dark); font-weight:800; margin-bottom:18px; }
        h1 { margin:0 0 10px; font-size:31px; line-height:1.1; letter-spacing:0; }
        p { margin:0 0 20px; color:var(--muted); line-height:1.6; }
        form { display:grid; gap:15px; }
        label { display:grid; gap:8px; color:#193b32; font-weight:600; font-size:14px; }
        label span:first-child { display:inline-flex; align-items:center; gap:8px; }
        label i { color:var(--primary); }
        .input-wrap { position:relative; display:block; }
        .input-wrap i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--muted); }
        input { width:100%; border:1px solid var(--line); border-radius:9px; padding:13px 13px 13px 40px; color:var(--ink); font:inherit; outline:0; background:#fbfdfc; }
        input:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(47,125,105,.12); background:white; }
        button, .btn { min-height:46px; border:0; border-radius:9px; background:var(--gold); color:var(--dark); font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; cursor:pointer; }
        button:hover, .btn:hover { filter:brightness(.98); transform:translateY(-1px); }
        .link { margin-top:16px; min-height:42px; border:1px solid var(--line); background:#f4faf8; color:var(--primary); }
        .sent { display:none; margin-bottom:16px; background:var(--success); color:#193b32; border:1px solid #bfe3d7; border-radius:9px; padding:11px 12px; font-weight:600; line-height:1.45; }
        body:has(.card[data-sent="1"]) .sent { display:block; }
        @media (max-width:460px) { .card { padding:24px; } h1 { font-size:27px; } }
    </style>
</head>
<body>
    <main class="card" data-sent="">
        <div class="brand">EM</div>
        <h1>Mot de passe oublie</h1>
        <p>Renseignez votre email ou votre numero de telephone, un lien de reinitialisation de mot de passe vous sera automatiquement envoye.</p>
        <div class="sent"><i class="fa-solid fa-circle-check"></i> Si le compte existe, un lien de reinitialisation a ete genere.</div>
        <form method="post" action="/mot-de-passe-oublie">
            <input type="hidden" name="_token" value="__CSRF_TOKEN__">
            <label>
                <span><i class="fa-solid fa-user"></i>Email ou telephone</span>
                <span class="input-wrap">
                    <i class="fa-solid fa-at"></i>
                    <input name="login" type="text" required autocomplete="username" placeholder="vous@exemple.com ou 0196228860">
                </span>
            </label>
            <button type="submit"><i class="fa-solid fa-paper-plane"></i>Envoyer le lien</button>
        </form>
        <a class="btn link" href="/connexion"><i class="fa-solid fa-arrow-left"></i>Retour connexion</a>
    </main>
    <script>
        if (new URLSearchParams(location.search).has('envoye')) {
            document.querySelector('.card').dataset.sent = '1';
        }
    </script>
</body>
</html>
