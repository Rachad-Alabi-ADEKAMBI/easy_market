<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page introuvable - EasyMarket</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/logo.png">
    <meta name="theme-color" content="#2f7d69">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
        :root { --primary:#2f7d69; --ink:#17211b; --muted:#46574f; --line:#dfe7e2; --gold:#f5b84b; --paper:#f7fbf9; }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            display:grid;
            place-items:center;
            padding:24px;
            background:
                radial-gradient(circle at 18% 12%, rgba(47,125,105,.14), transparent 28%),
                linear-gradient(135deg, #f7fbf9 0%, #eef7f3 52%, #fff8e9 100%);
            color:var(--ink);
            font-family:'Poppins', system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .error-page {
            width:min(560px, 100%);
            position:relative;
            overflow:hidden;
            padding:32px;
            border:1px solid var(--line);
            border-radius:16px;
            background:rgba(255,255,255,.94);
            box-shadow:0 24px 70px rgba(23,33,27,.14);
        }
        .error-page::before {
            content:"";
            position:absolute;
            inset:0 0 auto 0;
            height:7px;
            background:linear-gradient(90deg, var(--primary), var(--gold));
        }
        .brand {
            display:inline-flex;
            align-items:center;
            gap:10px;
            color:var(--ink);
            text-decoration:none;
            font-weight:700;
            margin-bottom:24px;
        }
        .logo {
            width:42px;
            height:42px;
            display:grid;
            place-items:center;
            border-radius:9px;
            background:linear-gradient(135deg, var(--primary), var(--gold));
            color:#10251f;
        }
        .status {
            width:58px;
            height:58px;
            display:grid;
            place-items:center;
            border-radius:14px;
            background:#f4faf8;
            color:var(--primary);
            font-size:24px;
            margin-bottom:18px;
        }
        h1 {
            margin:0 0 10px;
            font-size:clamp(34px, 8vw, 54px);
            line-height:1;
            letter-spacing:0;
        }
        p {
            margin:0;
            color:var(--muted);
            line-height:1.6;
            font-size:15px;
        }
        .actions {
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:26px;
        }
        .btn {
            border:0;
            min-height:44px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            padding:0 16px;
            border-radius:8px;
            background:var(--gold);
            color:#10251f;
            font:inherit;
            font-weight:700;
            text-decoration:none;
            cursor:pointer;
        }
        .btn.secondary {
            border:1px solid var(--line);
            background:#f4faf8;
            color:var(--primary);
        }
        .code {
            position:absolute;
            right:28px;
            bottom:20px;
            color:rgba(47,125,105,.09);
            font-size:74px;
            font-weight:800;
            line-height:1;
            pointer-events:none;
        }
        @media (max-width: 520px) {
            body { padding:16px; }
            .error-page { padding:26px 22px; border-radius:12px; }
            .actions { display:grid; }
            .btn { width:100%; }
            .code { font-size:54px; right:18px; bottom:16px; }
        }
    </style>
</head>
<body>
    <main class="error-page">
        <a class="brand" href="/">
            <span class="logo">EM</span>
            <span>EasyMarket</span>
        </a>

        <div class="status" aria-hidden="true">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>

        <h1>Page introuvable</h1>
        <p>{{ $message ?? "Cette page est introuvable ou vous n'avez pas l'autorisation d'y accéder." }}</p>

        <div class="actions">
            <button class="btn secondary" type="button" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i>Retour</button>
            <a class="btn" href="/"><i class="fa-solid fa-house"></i>Accueil</a>
            <a class="btn secondary" href="/connexion"><i class="fa-solid fa-right-to-bracket"></i>Connexion</a>
        </div>

        <div class="code">{{ $code ?? '404' }}</div>
    </main>
</body>
</html>
