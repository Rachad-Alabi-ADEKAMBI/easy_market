<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EasyMarket - Gestion commerciale intelligente</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#2f7d69">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root {
            --primary-950: #10251f;
            --primary-900: #193b32;
            --primary-800: #2e6557;
            --primary-700: #3f8f7b;
            --primary-600: #56ad96;
            --mint-100: #e1f3ee;
            --mint-50: #f4faf8;
            --gold: #f5b84b;
            --coral: #f26d5b;
            --blue: #2667ff;
            --ink: #17211b;
            --muted: #3f5048;
            --line: #dfe7e2;
            --paper: #fbfdfb;
            --white: #ffffff;
            --shadow: 0 20px 60px rgba(16, 37, 31, .13);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--paper);
            font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        button, input, textarea { font: inherit; }
        .container { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid rgba(255,255,255,.12);
            background: rgba(16, 37, 31, .94);
            color: white;
            backdrop-filter: blur(14px);
        }
        .nav {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 600; }
        .logo {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-600), var(--gold));
            color: white;
            font-weight: 500;
        }
        .navlinks { display: flex; align-items: center; gap: 18px; font-size: 14px; color: rgba(255,255,255,.78); }
        .navlinks a, .footer-links a { display: inline-flex; align-items: center; gap: 7px; }
        .navlinks i, .footer-links i { width: 15px; text-align: center; }
        .navlinks a:hover { color: white; }
        .actions { display: flex; align-items: center; gap: 10px; }
        .btn {
            min-height: 42px;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            cursor: pointer;
            color: white;
        }
        .btn-primary { background: var(--gold); color: var(--primary-950); box-shadow: 0 12px 30px rgba(245,184,75,.25); }
        .btn-dark { background: var(--primary-950); color: white; }
        .btn-soft { background: rgba(255,255,255,.1); color: white; border-color: rgba(255,255,255,.14); }
        .hero {
            background:
                linear-gradient(110deg, rgba(16,37,31,.95), rgba(25,59,50,.82)),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Crect width='1400' height='900' fill='%232e6557'/%3E%3Cg opacity='.25'%3E%3Cpath fill='%23f5b84b' d='M132 138h230v150H132zM1000 92h260v180h-260zM760 554h380v190H760z'/%3E%3Cpath fill='%23ffffff' d='M430 220h420v260H430zM170 470h430v240H170z'/%3E%3C/g%3E%3Cg stroke='%23ffffff' stroke-width='3' opacity='.22'%3E%3Cpath d='M180 184h126M180 222h96M470 276h320M470 330h250M214 536h300M214 590h230M806 624h260M806 676h190'/%3E%3C/g%3E%3C/svg%3E");
            background-size: cover;
            background-position: center;
            color: white;
        }
        .hero-inner {
            min-height: calc(100vh - 70px);
            padding: 64px 0 34px;
            display: grid;
            grid-template-columns: 1fr minmax(360px, 520px);
            align-items: center;
            gap: 44px;
        }
        .eyebrow { color: var(--mint-100); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: .08em; }
        h1 {
            margin: 14px 0 18px;
            font-size: clamp(42px, 7vw, 86px);
            line-height: .96;
            letter-spacing: 0;
        }
        .hero p { max-width: 650px; font-size: 18px; color: rgba(255,255,255,.84); }
        .hero-actions { margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap; }
        .hero-metrics { margin-top: 34px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; max-width: 650px; }
        .metric {
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.09);
            border-radius: 8px;
            padding: 14px;
        }
        .metric strong { display: block; font-size: 24px; }
        .metric span { font-size: 12px; color: rgba(255,255,255,.74); }
        .device {
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.13);
            border-radius: 18px;
            padding: 14px;
            box-shadow: var(--shadow);
        }
        .screen {
            background: #f7fbf8;
            color: var(--ink);
            border-radius: 12px;
            overflow: hidden;
            min-height: 560px;
        }
        .screen-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--primary-950);
            color: white;
        }
        .store { display: flex; align-items: center; gap: 10px; font-weight: 600; }
        .avatar { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: var(--gold); color: var(--primary-950); }
        .screen-body { padding: 16px; }
        .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .mini-card { background: white; border: 1px solid var(--line); border-radius: 8px; padding: 12px; }
        .mini-card b { display: block; font-size: 20px; margin-top: 3px; }
        .tag { display: inline-flex; border-radius: 999px; padding: 4px 9px; font-size: 12px; font-weight: 600; background: var(--mint-100); color: var(--primary-800); }
        .panel-title { display: flex; justify-content: space-between; align-items: center; margin: 18px 0 10px; font-weight: 600; }
        .sale-row, .alert-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }
        .sale-row small, .alert-row small { color: var(--muted); display: block; }
        .bottom-nav {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border-top: 1px solid var(--line);
            background: white;
        }
        .bottom-nav a { padding: 10px 4px; text-align: center; font-size: 11px; color: var(--muted); }
        .bottom-nav i { display: block; margin-bottom: 4px; font-size: 15px; }
        .bottom-nav a:first-child { color: var(--primary-700); font-weight: 600; }
        section { padding: 84px 0; }
        .section-head { max-width: 760px; margin-bottom: 30px; }
        .section-head h2 { margin: 0 0 10px; font-size: clamp(30px, 4vw, 52px); line-height: 1.05; }
        .title-icon { color: var(--primary-700); margin-right: 10px; font-size: .82em; }
        .section-head p { margin: 0; color: var(--muted); font-size: 17px; }
        .grid { display: grid; gap: 18px; }
        .features { grid-template-columns: repeat(3, 1fr); }
        .platforms { grid-template-columns: repeat(3, 1fr); margin-top: 22px; }
        .feature, .price, .faq, .module, .step {
            background: white;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(16,37,31,.04);
        }
        .icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            margin-bottom: 14px;
            background: var(--mint-100);
            color: var(--primary-800);
            font-weight: 500;
        }
        .feature h3, .price h3, .module h3, .step h3 { margin: 0 0 8px; font-size: 19px; }
        .feature p, .module p, .step p { color: var(--muted); margin: 0; }
        .platform-card {
            background: var(--primary-950);
            color: white;
            border-radius: 8px;
            padding: 20px;
            display: grid;
            gap: 10px;
            min-height: 178px;
        }
        .platform-card i { font-size: 30px; color: var(--gold); }
        .platform-card h3 { margin: 0; font-size: 20px; }
        .platform-card p { margin: 0; color: rgba(255,255,255,.84); }
        .app-band { background: #eef7f1; border-block: 1px solid var(--line); }
        .app-layout { display: grid; grid-template-columns: 260px 1fr; gap: 18px; align-items: start; }
        .sidebar {
            position: sticky;
            top: 88px;
            background: var(--primary-950);
            color: white;
            border-radius: 8px;
            padding: 16px;
        }
        .side-link { display: flex; gap: 10px; align-items: center; padding: 11px 10px; border-radius: 8px; color: rgba(255,255,255,.76); font-size: 14px; }
        .side-link.active, .side-link:hover { background: rgba(255,255,255,.12); color: white; }
        .side-link i { width: 18px; text-align: center; }
        .dashboard { background: white; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .dash-top { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 20px; border-bottom: 1px solid var(--line); }
        .dash-content { padding: 20px; }
        .kpis { grid-template-columns: repeat(4, 1fr); }
        .chart {
            height: 250px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 18px;
            display: flex;
            gap: 12px;
            align-items: end;
            background: linear-gradient(#ffffff, #f7fbf8);
        }
        .bar { flex: 1; border-radius: 6px 6px 0 0; background: var(--primary-700); min-height: 42px; }
        .bar:nth-child(2) { height: 50%; background: var(--gold); }
        .bar:nth-child(3) { height: 72%; background: var(--primary-600); }
        .bar:nth-child(4) { height: 44%; background: var(--coral); }
        .bar:nth-child(5) { height: 84%; background: var(--blue); }
        .bar:nth-child(6) { height: 64%; background: var(--primary-800); }
        .module-grid { grid-template-columns: repeat(2, 1fr); margin-top: 18px; }
        .prices { grid-template-columns: repeat(3, 1fr); }
        .amount { font-size: 34px; font-weight: 500; margin: 8px 0; color: var(--primary-900); }
        .price ul, .check-list { margin: 16px 0 0; padding: 0; list-style: none; display: grid; gap: 9px; color: var(--muted); }
        .price li::before, .check-list li::before { content: "✓"; color: var(--primary-700); font-weight: 500; margin-right: 8px; }
        .highlight { border-color: var(--primary-700); box-shadow: 0 24px 60px rgba(63,143,123,.16); }
        .price-title { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
        .price-title h3 { margin:0; }
        .recommended-tag { border-radius: 999px; padding: 5px 10px; display: inline-flex; align-items: center; gap: 6px; background: #e1f3ee; color: #193b32; font-size: 12px; font-weight: 500; white-space: nowrap; }
        .steps { grid-template-columns: repeat(3, 1fr); }
        .seller {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            align-items: center;
        }
        .pos {
            background: var(--primary-950);
            color: white;
            border-radius: 12px;
            padding: 16px;
        }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 12px; }
        .key { background: rgba(255,255,255,.1); border-radius: 8px; min-height: 52px; display: grid; place-items: center; font-weight: 600; }
        .receipt { background: white; color: var(--ink); border-radius: 8px; padding: 14px; }
        .faq-list { display: grid; gap: 10px; max-width: 860px; }
        details.faq { padding: 0; overflow: hidden; }
        details.faq summary {
            list-style: none;
            cursor: pointer;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-weight: 600;
        }
        details.faq summary::-webkit-details-marker { display: none; }
        details.faq summary::after { content: "+"; color: var(--primary-700); font-size: 24px; line-height: 1; }
        details.faq[open] summary::after { content: "-"; }
        details.faq p { margin: 0; padding: 0 20px 18px; color: var(--muted); }
        .contact {
            background: var(--primary-950);
            color: white;
            border-radius: 8px;
            padding: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }
        .form { display: grid; gap: 10px; }
        .field { width: 100%; min-height: 46px; border: 1px solid rgba(255,255,255,.18); border-radius: 8px; background: rgba(255,255,255,.08); color: white; padding: 11px 12px; }
        textarea.field { min-height: 112px; resize: vertical; }
        .field::placeholder { color: rgba(255,255,255,.58); }
        .site-footer {
            background: linear-gradient(135deg, var(--primary-950), #071510);
            color: rgba(255,255,255,.76);
            padding: 46px 0 24px;
        }
        .footer-grid { display: grid; grid-template-columns: 1.4fr .8fr .8fr; gap: 24px; align-items: start; }
        .footer-title { display: flex; align-items: center; gap: 10px; color: white; font-weight: 500; margin-bottom: 10px; }
        .footer-links { display: grid; gap: 8px; }
        .footer-links a:hover { color: white; }
        .footer-bottom { margin-top: 28px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.12); display: flex; justify-content: space-between; gap: 14px; flex-wrap: wrap; font-size: 13px; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes floatSoft { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes barPulse { 0%, 100% { filter: brightness(1); } 50% { filter: brightness(1.12); } }
        .hero .eyebrow, .hero h1, .hero p, .hero-actions, .hero-metrics { animation: fadeUp .7s ease both; }
        .hero h1 { animation-delay: .08s; }
        .hero p { animation-delay: .16s; }
        .hero-actions { animation-delay: .24s; }
        .hero-metrics { animation-delay: .32s; }
        .device { animation: floatSoft 5s ease-in-out infinite; }
        .btn, .navlinks a, .footer-links a, .side-link { transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease; }
        .btn:hover, .navlinks a:hover, .footer-links a:hover, .side-link:hover { transform: translateY(-2px); }
        .feature, .price, .faq, .module, .step, .platform-card, .mini-card, .metric { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .feature:hover, .price:hover, .module:hover, .step:hover, .platform-card:hover, .mini-card:hover, .metric:hover { transform: translateY(-4px); box-shadow: 0 18px 44px rgba(16,37,31,.10); }
        .bar { animation: barPulse 2.8s ease-in-out infinite; }
        .bar:nth-child(2) { animation-delay: .2s; }
        .bar:nth-child(3) { animation-delay: .4s; }
        .bar:nth-child(4) { animation-delay: .6s; }
        .bar:nth-child(5) { animation-delay: .8s; }
        .bar:nth-child(6) { animation-delay: 1s; }
        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .55s ease, transform .55s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        @media (prefers-reduced-motion: reduce) { *, .reveal { animation: none !important; transition: none !important; transform: none !important; opacity: 1 !important; } }
        @media (max-width: 980px) {
            .navlinks { display: none; }
            .hero-inner, .app-layout, .seller, .contact, .footer-grid { grid-template-columns: 1fr; }
            .hero-inner { min-height: auto; }
            .features, .platforms, .prices, .steps, .kpis { grid-template-columns: repeat(2, 1fr); }
            .sidebar { position: static; display: grid; grid-template-columns: repeat(2, 1fr); }
            .screen { min-height: auto; }
        }

        @media (max-width: 640px) {
            .container { width: min(100% - 24px, 1180px); }
            .actions .btn-soft { display: none; }
            .hero-inner { padding-top: 42px; gap: 28px; }
            .hero-metrics, .features, .platforms, .prices, .steps, .kpis, .module-grid, .stat-grid { grid-template-columns: 1fr; }
            section { padding: 58px 0; }
            .dash-top { align-items: flex-start; flex-direction: column; }
            .sidebar { grid-template-columns: 1fr; }
            .contact { padding: 20px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="#accueil">
                <span>EasyMarket</span>
            </a>
            <nav class="navlinks" aria-label="Navigation principale">
                <a href="#fonctionnalites"><i class="fa-solid fa-list-check"></i>Fonctionnalités</a>
                <a href="#application"><i class="fa-solid fa-display"></i>Application</a>
                <a href="#tarifs"><i class="fa-solid fa-tags"></i>Tarifs</a>
                <a href="#faq"><i class="fa-solid fa-circle-question"></i>FAQ</a>
                <a href="#contact"><i class="fa-solid fa-headset"></i>Contact</a>
            </nav>
            <div class="actions">
                <a class="btn btn-soft" href="#application"><i class="fa-solid fa-chart-line"></i>Voir la démo</a>
                __HOME_AUTH_ACTIONS__
            </div>
        </div>
    </header>

    <main id="accueil">
        <section class="hero">
            <div class="container hero-inner">
                <div>
                    <div class="eyebrow">Application web, mobile et desktop connectée</div>
                    <h1>EasyMarket</h1>
                    <p>Une seule application pour gérer votre commerce depuis le web, votre téléphone et votre ordinateur. Vos ventes, stocks, factures, clients, dépenses et rapports restent connectés et synchronisés.</p>
                    <div class="hero-actions">
                        <a class="btn btn-primary" href="/inscription"><i class="fa-solid fa-user-plus"></i>Inscription</a>
                        <a class="btn btn-soft" href="#fonctionnalites"><i class="fa-solid fa-list-check"></i>Voir les fonctionnalités</a>
                    </div>
                    <div class="hero-metrics" aria-label="Indicateurs produit">
                        <div class="metric"><strong>5 000</strong><span>FCFA / mois</span></div>
                        <div class="metric"><strong>1 min</strong><span>pour enregistrer une vente</span></div>
                        <div class="metric"><strong>3 écrans</strong><span>web, mobile et desktop</span></div>
                    </div>
                </div>
                <div class="device" aria-label="Aperçu mobile EasyMarket">
                    <div class="screen">
                        <div class="screen-head">
                            <div class="store"><span class="avatar">EM</span><span>Ma Boutique</span></div>
                            <span class="tag">En ligne</span>
                        </div>
                        <div class="screen-body">
                            <div class="stat-grid">
                                <div class="mini-card"><small>CA aujourd'hui</small><b>428 500</b><small>FCFA</small></div>
                                <div class="mini-card"><small>Marge</small><b>31%</b><small>+6% semaine</small></div>
                                <div class="mini-card"><small>Créances</small><b>7</b><small>2 en retard</small></div>
                                <div class="mini-card"><small>Stock bas</small><b>12</b><small>articles</small></div>
                            </div>
                            <div class="panel-title"><span>Ventes récentes</span><span class="tag">Temps réel</span></div>
                            <div class="sale-row"><div><strong>Facture FAC-2026-0048</strong><small>Espèces - Client comptoir</small></div><b>38 000</b></div>
                            <div class="sale-row"><div><strong>Proforma PRF-2026-0012</strong><small>Convertible en facture</small></div><b>92 500</b></div>
                            <div class="sale-row"><div><strong>Crédit client</strong><small>Échéance : 30/05/2026</small></div><b>56 000</b></div>
                            <div class="panel-title"><span>Alertes</span><span class="tag">WhatsApp</span></div>
                            <div class="alert-row"><div><strong>Huile 5L</strong><small>Stock sous le seuil configuré</small></div><span>3 restants</span></div>
                        </div>
                        <nav class="bottom-nav" aria-label="Menu bas mobile">
                            <a href="#application"><i class="fa-solid fa-chart-pie"></i>Dashboard</a>
                            <a href="#application"><i class="fa-solid fa-cash-register"></i>Ventes</a>
                            <a href="#application"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                            <a href="#application"><i class="fa-solid fa-users"></i>Clients</a>
                            <a href="#application"><i class="fa-solid fa-bell"></i>Notifs</a>
                        </nav>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-layer-group title-icon"></i>Web, mobile et desktop : tout reste connecté</h2>
                    <p>EasyMarket suit votre activité partout. Vous pouvez enregistrer une vente sur téléphone, consulter le tableau de bord sur ordinateur, puis retrouver les mêmes informations dans l'application desktop.</p>
                </div>
                <div class="grid platforms">
                    <article class="platform-card"><i class="fa-solid fa-globe"></i><h3>Application web</h3><p>Accédez à votre espace depuis un navigateur pour piloter la boutique, les rapports et les paramètres.</p></article>
                    <article class="platform-card"><i class="fa-solid fa-mobile-screen-button"></i><h3>Application mobile</h3><p>Enregistrez les ventes, consultez les stocks et suivez les alertes directement depuis votre téléphone.</p></article>
                    <article class="platform-card"><i class="fa-solid fa-desktop"></i><h3>Application desktop</h3><p>Travaillez confortablement au comptoir sur ordinateur, avec les mêmes données que sur mobile et web.</p></article>
                </div>
            </div>
        </section>

        <section id="fonctionnalites">
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-gears title-icon"></i>Tout ce qu'il faut pour piloter votre commerce</h2>
                    <p>EasyMarket rassemble les ventes, le stock, les clients, les dépenses et les rapports dans un seul espace clair. Vous ouvrez l'application, vous choisissez l'action, vous enregistrez, c'est terminé.</p>
                </div>
                <div class="grid features">
                    <article class="feature"><div class="icon"><i class="fa-solid fa-boxes-stacked"></i></div><h3>Stocks & inventaire</h3><p>Ajoutez vos produits, fixez un seuil d'alerte et suivez automatiquement les entrées, sorties et ruptures.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-file-invoice"></i></div><h3>Factures & tickets</h3><p>Enregistrez une vente, choisissez le paiement, puis générez automatiquement une facture, un ticket ou un devis.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-handshake"></i></div><h3>Clients & créances</h3><p>Gardez les fiches clients, les achats, les crédits et les remboursements au même endroit.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-user-group"></i></div><h3>Personnel & paie</h3><p>Suivez les présences, avances, salaires et fiches de paie sans feuille Excel compliquée.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div><h3>Conseiller IA</h3><p>Posez une question simple et obtenez des conseils sur les produits rentables, les ruptures et les prix.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-chart-column"></i></div><h3>Rapports & impôts</h3><p>Exportez les ventes, dépenses, créances, dettes et documents utiles pour votre comptable en PDF ou en Excel.</p></article>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-route title-icon"></i>Comment ça marche ?</h2>
                    <p>L'inscription se fait en deux parties : vos informations personnelles, puis celles de votre boutique. Ensuite, vous accédez à votre tableau de bord. Le premier paiement se fera depuis ce tableau de bord.</p>
                </div>
                <div class="grid steps">
                    <article class="step"><div class="icon"><i class="fa-solid fa-user-pen"></i></div><h3>Créez votre compte</h3><p>Indiquez votre nom, téléphone, email et mot de passe pour ouvrir votre espace EasyMarket.</p></article>
                    <article class="step"><div class="icon"><i class="fa-solid fa-store"></i></div><h3>Ajoutez votre boutique</h3><p>Renseignez le nom, le téléphone, l'adresse, l'IFU et les informations qui apparaîtront sur vos factures.</p></article>
                    <article class="step"><div class="icon"><i class="fa-solid fa-gauge-high"></i></div><h3>Accédez au tableau de bord</h3><p>Tout est ok, vos vendeurs vendent et vous vous chargez juste du contrôle et de la gestion.</p></article>
                </div>
            </div>
        </section>

        <section id="application" class="app-band">
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-mobile-screen-button title-icon"></i>Une interface simple, même sur téléphone</h2>
                    <p>Les actions les plus utilisées restent toujours visibles en bas : tableau de bord, ventes, stocks, clients et notifications. Le reste est rangé proprement dans le menu.</p>
                </div>
                <div class="app-layout">
                    <aside class="sidebar">
                        <a class="side-link active" href="#application"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-file-invoice-dollar"></i>Ventes & Factures</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-users"></i>Clients</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-bell"></i>Notifications</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-truck"></i>Fournisseurs & Dettes</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-user-tie"></i>Personnel</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-wallet"></i>Charges</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-wand-magic-sparkles"></i>Conseiller IA</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-chart-column"></i>Rapports & Impôts</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-gear"></i>Paramètres</a>
                    </aside>
                    <div class="dashboard">
                        <div class="dash-top">
                            <div>
                                <span class="tag">Boutique personnalisée</span>
                                <h2 style="margin:10px 0 0"><i class="fa-solid fa-gauge-high title-icon"></i>Tableau de bord</h2>
                            </div>
                            <a class="btn btn-dark" href="/inscription"><i class="fa-solid fa-user-plus"></i>Créer mon espace</a>
                        </div>
                        <div class="dash-content">
                            <div class="grid kpis">
                                <div class="mini-card"><small>CA mois</small><b>8,42 M</b><small>FCFA</small></div>
                                <div class="mini-card"><small>Nouveaux clients</small><b>126</b><small>+18%</small></div>
                                <div class="mini-card"><small>Dettes fournisseurs</small><b>1,1 M</b><small>FCFA</small></div>
                                <div class="mini-card"><small>Rapports</small><b>PDF + Excel</b><small>prêts</small></div>
                            </div>
                            <div class="panel-title"><span>Évolution des ventes</span><span class="tag">Mensuel</span></div>
                            <div class="chart" aria-label="Graphique ventes">
                                <div class="bar" style="height:38%"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                            </div>
                            <div class="grid module-grid">
                                <article class="module"><h3><i class="fa-solid fa-qrcode"></i> Facture PDF avec QR Code</h3><p>Vos documents sont propres, imprimables et prêts à être partagés.</p></article>
                                <article class="module"><h3><i class="fa-brands fa-whatsapp"></i> Alertes automatiques</h3><p>Stock bas, créances, dettes, paie et rapport journalier via l'application et WhatsApp.</p></article>
                                <article class="module"><h3><i class="fa-solid fa-palette"></i> Paramètres boutique</h3><p>Logo, IFU, slogan, couleur principale, sécurité et abonnement.</p></article>
                                <article class="module"><h3><i class="fa-solid fa-file-export"></i> Exports comptables</h3><p>Recettes, dépenses, ventes, créances, dettes et fiches de paie.</p></article>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="container seller">
                <div>
                    <div class="section-head">
                        <h2><i class="fa-solid fa-cash-register title-icon"></i>Vendre devient naturel</h2>
                        <p>La caisse est faite pour aller vite : sélectionnez les articles, indiquez la quantité, choisissez le mode de paiement et validez. EasyMarket met à jour le stock et garde l'historique.</p>
                    </div>
                    <ul class="check-list">
                        <li>Nouvelle vente avec produits, quantités et mode de paiement.</li>
                        <li>Facture proforma convertible en vente définitive.</li>
                        <li>Catalogue facile à consulter pendant que le client attend.</li>
                        <li>Historique clair pour retrouver une vente, un ticket ou une proforma.</li>
                    </ul>
                </div>
                <div class="pos">
                    <div class="receipt">
                        <strong>Caisse rapide</strong>
                        <div class="sale-row"><div><small>Riz premium x2</small></div><b>24 000</b></div>
                        <div class="sale-row"><div><small>Huile 5L x1</small></div><b>8 500</b></div>
                        <div class="sale-row"><div><strong>Total</strong></div><b>32 500</b></div>
                    </div>
                    <div class="keypad" aria-label="Modes de paiement">
                        <div class="key">Espèces</div><div class="key">Mobile</div><div class="key">Crédit</div>
                        <div class="key">Devis</div><div class="key">Ticket</div><div class="key">PDF</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tarifs" class="app-band">
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-tags title-icon"></i>Choisissez la formule qui vous convient</h2>
                    <p>Commencez petit, passez à l'annuel quand votre équipe est à l'aise, ou prenez la licence à vie pour éviter les renouvellements.</p>
                </div>
                <div class="grid prices">
                    <article class="price">
                        <h3>Mensuel</h3>
                        <div class="amount">5 000</div>
                        <p>FCFA / mois</p>
                        <ul><li>Renouvellement mensuel</li><li>Tous les modules essentiels</li><li>Support standard</li></ul>
                        __HOME_PRICING_CTA_MONTHLY__
                    </article>
                    <article class="price highlight">
                        <div class="price-title"><h3>Annuel</h3><span class="recommended-tag"><i class="fa-solid fa-circle-check"></i>Recommandé</span></div>
                        <div class="amount">50 000</div>
                        <p>FCFA / an, soit 4 167 FCFA/mois</p>
                        <ul><li>Économie de 10 000 FCFA</li><li>Rapports et exports inclus</li><li>Support prioritaire</li></ul>
                        __HOME_PRICING_CTA_YEARLY__
                    </article>
                    <article class="price">
                        <h3>Licence à vie</h3>
                        <div class="amount">100 000</div>
                        <p>FCFA paiement unique</p>
                        <ul><li>Toutes mises à jour incluses</li><li>Personnalisation boutique</li><li>Accès durable</li></ul>
                        __HOME_PRICING_CTA_LIFETIME__
                    </article>
                </div>
            </div>
        </section>

        <section id="faq">
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-circle-question title-icon"></i>Questions fréquentes</h2>
                    <p>Cliquez sur une question pour afficher la réponse.</p>
                </div>
                <div class="faq-list">
                    <details class="faq"><summary>À quoi sert réellement cette application ?</summary><p><strong>Attention :</strong> EasyMarket centralise toute la gestion d'une boutique dans un seul espace. <strong>Intérêt :</strong> vous ajoutez vos produits, suivez les stocks, enregistrez les ventes, éditez les factures, tickets et proformas, gérez les clients, les créances, les fournisseurs, les dettes, les charges, le personnel, les avances et la paie. <strong>Désir :</strong> vos vendeurs peuvent vendre pendant que vous gardez une vision claire sur les chiffres, les alertes, les rapports, les exports comptables et les performances de l'activité. <strong>Action :</strong> créez votre espace, ajoutez votre boutique, puis commencez à piloter votre commerce depuis le web, le téléphone ou l'ordinateur.</p></details>
                    <details class="faq"><summary>Puis-je l'utiliser sur téléphone et ordinateur ?</summary><p>Oui. EasyMarket est prévu pour le web, le mobile et le desktop. Les informations restent connectées entre les supports pour garder la même vision de votre commerce.</p></details>
                    <details class="faq"><summary>Que se passe-t-il après l'inscription ?</summary><p>Vous renseignez vos informations et celles de votre boutique, puis vous accédez à votre tableau de bord. Le premier paiement se fera ensuite depuis ce tableau de bord.</p></details>
                    <details class="faq"><summary>Les factures peuvent-elles être partagées ?</summary><p>Oui. Les factures, proformas, tickets, rapports et fiches de paie sont prévus pour être exportés en PDF, imprimés ou partagés.</p></details>
                    <details class="faq"><summary>Comment recevoir les alertes ?</summary><p>Vous configurez votre numéro WhatsApp. EasyMarket peut ensuite vous signaler les stocks bas, les créances, les dettes et les rapports importants.</p></details>
                </div>
            </div>
        </section>

        <section id="contact">
            <div class="container contact">
                <div>
                    <div class="eyebrow">Contact</div>
                    <h2 style="margin:10px 0 12px"><i class="fa-solid fa-headset title-icon"></i>Prêt à créer votre espace ?</h2>
                    <p style="color:rgba(255,255,255,.75)">Une question ? Contactez notre support sur WhatsApp en cliquant sur le bouton ci-dessous.</p>
                    <a class="btn btn-primary" href="https://wa.me/2290196228860" style="margin-top:18px"><i class="fa-brands fa-whatsapp"></i>0196228860</a>
                </div>
                <form class="form">
                    <input class="field" type="text" placeholder="Nom et prénom">
                    <input class="field" type="tel" placeholder="Téléphone">
                    <input class="field" type="email" placeholder="Email">
                    <textarea class="field" placeholder="Votre question ou votre besoin"></textarea>
                    <a class="btn btn-primary" href="/inscription"><i class="fa-solid fa-user-plus"></i>Créer mon compte</a>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-title"><span class="logo">EM</span><span>EasyMarket</span></div>
                    <p>Une application simple pour suivre vos ventes, votre stock, vos clients, vos dépenses et vos rapports depuis un seul tableau de bord.</p>
                </div>
                <div>
                    <strong style="color:white">Navigation</strong>
                    <div class="footer-links" style="margin-top:12px">
                        <a href="#fonctionnalites"><i class="fa-solid fa-list-check"></i>Fonctionnalités</a>
                        <a href="#application"><i class="fa-solid fa-display"></i>Aperçu application</a>
                        <a href="#tarifs"><i class="fa-solid fa-tags"></i>Tarifs</a>
                        __HOME_FOOTER_AUTH_LINKS__
                    </div>
                </div>
                <div>
                    <strong style="color:white">Contact</strong>
                    <div class="footer-links" style="margin-top:12px">
                        <a href="https://wa.me/2290196228860"><i class="fa-brands fa-whatsapp"></i> 0196228860</a>
                        <span>Cliquez sur le numéro pour nous contacter sur WhatsApp</span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2026 EasyMarket. Tous droits réservés.</span>
                <span>Gestion commerciale · Facturation · Stocks · Rapports</span>
            </div>
        </div>
    </footer>
    <script>
        document.querySelectorAll('section .section-head, .feature, .platform-card, .step, .module, .price, .faq, .seller, .dashboard, .contact').forEach((item) => item.classList.add('reveal'));
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach((item) => revealObserver.observe(item));
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
