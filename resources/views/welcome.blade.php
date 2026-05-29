<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EasyMarket - Gestion commerciale intelligente</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/logo.png">
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
            --danger: #b42318;
            --blue: #2667ff;
            --ink: #17211b;
            --muted: #3f5048;
            --line: #dfe7e2;
            --paper: #fbfdfb;
            --white: #ffffff;
            --shadow: 0 20px 60px rgba(16, 37, 31, .13);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; margin: 0; padding: 0; }
        body {
            margin: 0;
            padding: 0;
            color: var(--ink);
            background: var(--paper);
            font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        button, input, textarea { font: inherit; }
        .container { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
        .topbar {
            position: fixed;
            inset: 0 0 auto;
            z-index: 20;
            margin: 0;
            border-bottom: 1px solid rgba(255,255,255,.12);
            background: rgba(16, 37, 31, .94);
            color: white;
            backdrop-filter: blur(14px);
        }
        main { padding-top: 0; }
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
        .nav-menu { display: flex; align-items: center; gap: 22px; }
        .navlinks { display: flex; align-items: center; gap: 18px; font-size: 14px; color: rgba(255,255,255,.78); }
        .navlinks a, .footer-links a, .footer-links button { display: inline-flex; align-items: center; gap: 7px; }
        .navlinks i, .footer-links i { width: 15px; text-align: center; }
        .navlinks a:hover, .footer-links button:hover { color: white; }
        .actions { display: flex; align-items: center; gap: 10px; }
        .home-menu-toggle { display: none; width: 42px; height: 42px; border: 1px solid rgba(255,255,255,.16); border-radius: 8px; background: rgba(255,255,255,.1); color: white; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; }
        .logout-inline { margin: 0; display: inline-flex; }
        .logout-inline button { border: 0; background: transparent; color: inherit; cursor: pointer; padding: 0; font: inherit; }
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
            white-space: nowrap;
        }
        .btn-primary { background: var(--gold); color: var(--primary-950); box-shadow: 0 12px 30px rgba(245,184,75,.25); }
        .btn-dark { background: var(--primary-950); color: white; }
        .btn-soft { background: rgba(255,255,255,.1); color: white; border-color: rgba(255,255,255,.14); }
        .btn-danger { background: var(--danger); color: white; box-shadow: 0 12px 30px rgba(180,35,24,.22); }
        .logout-inline button.btn-danger { background: var(--danger); color: white; padding: 10px 16px; border-radius: 8px; box-shadow: 0 12px 30px rgba(180,35,24,.22); }
        .hero {
            background:
                linear-gradient(110deg, rgba(16,37,31,.95), rgba(25,59,50,.82)),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Crect width='1400' height='900' fill='%232e6557'/%3E%3Cg opacity='.25'%3E%3Cpath fill='%23f5b84b' d='M132 138h230v150H132zM1000 92h260v180h-260zM760 554h380v190H760z'/%3E%3Cpath fill='%23ffffff' d='M430 220h420v260H430zM170 470h430v240H170z'/%3E%3C/g%3E%3Cg stroke='%23ffffff' stroke-width='3' opacity='.22'%3E%3Cpath d='M180 184h126M180 222h96M470 276h320M470 330h250M214 536h300M214 590h230M806 624h260M806 676h190'/%3E%3C/g%3E%3C/svg%3E");
            background-size: cover;
            background-position: center;
            color: white;
            padding-top: 70px;
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
        section { padding: 84px 0; scroll-margin-top: 70px; }
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
        .install-band { padding-top: 0; }
        .install-panel {
            display: grid;
            grid-template-columns: 1.05fr 1.45fr;
            gap: 22px;
            align-items: center;
            border: 1px solid rgba(47,125,105,.18);
            border-radius: 12px;
            padding: 24px;
            background: linear-gradient(135deg, #ffffff, #eef8f4);
            box-shadow: 0 22px 70px rgba(16,37,31,.10);
        }
        .install-panel h2 { margin: 0 0 10px; font-size: clamp(28px, 3.6vw, 44px); line-height: 1.08; }
        .install-panel p { margin: 0; color: var(--muted); }
        .install-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .install-card {
            display: grid;
            gap: 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 18px;
            background: white;
        }
        .install-card i { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 8px; background: var(--mint-100); color: var(--primary-800); font-size: 20px; }
        .install-card .install-platform-icons { display: flex; gap: 8px; align-items: center; }
        .install-card .install-platform-icons i { width: 34px; height: 34px; border-radius: 8px; background: #f2f7f4; color: var(--primary-800); font-size: 17px; }
        .install-card h3 { margin: 0; font-size: 18px; }
        .install-card p { font-size: 14px; }
        .install-card .btn { justify-content: center; margin-top: 4px; }
        .install-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: none;
            place-items: center;
            padding: 18px;
            background: rgba(16,37,31,.54);
        }
        .install-modal.open { display: grid; }
        .install-modal-card {
            width: min(560px, 100%);
            border-radius: 12px;
            border: 1px solid var(--line);
            background: white;
            padding: 22px;
            box-shadow: var(--shadow);
        }
        .install-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 14px; }
        .install-modal-head h3 { margin: 0; font-size: 24px; }
        .install-modal-head p { margin: 4px 0 0; color: var(--muted); }
        .install-steps { margin: 0; padding-left: 20px; display: grid; gap: 9px; color: var(--muted); }
        .install-steps strong { color: var(--primary-950); }
        .install-modal-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .install-modal-actions .btn-light {
            background: #eef5f2;
            border-color: #d6e4de;
            color: var(--primary-950);
        }
        .install-modal-actions .btn-light:hover {
            background: #e1f3ee;
        }
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
        .demo-user-chip { display: inline-flex; align-items: center; gap: 10px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: #f8fcfa; color: var(--ink); font-weight: 600; }
        .demo-user-chip i { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 8px; background: #e1f3ee; color: var(--primary-800); }
        .demo-user-chip small { display: block; color: var(--muted); font-weight: 600; }
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
        .price {
            position: relative;
            overflow: hidden;
            padding: 24px;
            display: grid;
            align-content: start;
            gap: 14px;
            min-height: 420px;
            border-color: #d7e7df;
        }
        .price::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-700), var(--gold));
        }
        .price h3 { font-size: 22px; }
        .price-subtitle { color: var(--muted); margin: -6px 0 0; font-size: 14px; }
        .amount { font-size: 42px; line-height: 1; font-weight: 700; margin: 0; color: var(--primary-900); }
        .amount span { font-size: 15px; font-weight: 600; color: var(--muted); margin-left: 4px; }
        .price-period { margin: -8px 0 2px; color: var(--muted); font-weight: 600; }
        .price ul, .check-list { margin: 8px 0 0; padding: 0; list-style: none; display: grid; gap: 10px; color: var(--muted); }
        .price li { display: flex; gap: 8px; align-items: flex-start; }
        .price li::before, .check-list li::before { content: "✓"; color: var(--primary-700); font-weight: 700; margin-right: 0; }
        .check-list li { display: flex; align-items: flex-start; gap: 10px; }
        .price .btn { margin-top: auto !important; width: 100%; }
        .highlight { border-color: var(--primary-700); box-shadow: 0 24px 60px rgba(63,143,123,.18); transform: translateY(-8px); }
        .highlight::before { height: 7px; background: linear-gradient(90deg, var(--gold), var(--primary-700)); }
        .price-title { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
        .price-title h3 { margin:0; }
        .recommended-tag { border-radius: 999px; padding: 6px 10px; display: inline-flex; align-items: center; gap: 6px; background: var(--gold); color: #193b32; font-size: 12px; font-weight: 700; white-space: nowrap; }
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
        .faq-answer { padding: 0 20px 20px; color: var(--muted); display: grid; gap: 12px; }
        .faq-answer p { margin: 0; padding: 0; color: var(--muted); }
        .faq-answer ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
        .faq-answer li { display: grid; grid-template-columns: 14px 1fr; gap: 9px; align-items: flex-start; }
        .faq-answer li::before { content: "✓"; color: var(--primary-700); font-weight: 800; }
        .faq-answer strong { color: var(--primary-950); }
        .faq-highlight { padding: 14px 16px; border-radius: 8px; background: rgba(67,155,132,.09); color: var(--primary-950) !important; font-weight: 650; }
        .faq-benefits { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px !important; }
        .faq-benefits li { display: grid; gap: 5px; padding: 13px; border: 1px solid rgba(15,66,50,.12); border-radius: 8px; background: #fff; }
        .faq-benefits li::before { display: none; }
        .faq-benefits strong { display: block; font-size: 15px; }
        .faq-benefits span { display: block; color: var(--muted); line-height: 1.55; }
        .faq-cta { color: var(--primary-950) !important; }
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
        .footer-phone {
            width: max-content;
            max-width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            background: var(--gold);
            color: var(--primary-950) !important;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(245,184,75,.22);
        }
        .footer-phone i { color: var(--primary-950); }
        .footer-bottom { margin-top: 28px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.12); display: flex; justify-content: space-between; gap: 14px; flex-wrap: wrap; font-size: 13px; }
        .footer-signature { color: rgba(255,255,255,.72); }
        .footer-signature a { color: inherit; font-weight: 800; text-decoration: none; }
        .footer-signature a:hover { color: white; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes floatSoft { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes barPulse { 0%, 100% { filter: brightness(1); } 50% { filter: brightness(1.12); } }
        .hero .eyebrow, .hero h1, .hero p, .hero-actions, .hero-metrics { animation: fadeUp .7s ease both; }
        .hero h1 { animation-delay: .08s; }
        .hero p { animation-delay: .16s; }
        .hero-actions { animation-delay: .24s; }
        .hero-metrics { animation-delay: .32s; }
        .device { animation: floatSoft 5s ease-in-out infinite; }
        .btn, .navlinks a, .footer-links a, .footer-links button, .side-link { transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease; }
        .btn:hover, .navlinks a:hover, .footer-links a:hover, .footer-links button:hover, .side-link:hover { transform: translateY(-2px); }
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
            .nav { position: relative; min-height: 64px; }
            .brand .logo { display: grid; }
            .home-menu-toggle { display: inline-flex; margin-left: auto; }
            .nav-menu {
                position: absolute;
                top: calc(100% + 1px);
                left: 0;
                right: 0;
                display: none;
                padding: 14px;
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 0 0 12px 12px;
                background: rgba(16,37,31,.98);
                box-shadow: 0 18px 42px rgba(0,0,0,.28);
            }
            body.home-menu-open .nav-menu { display: grid; gap: 12px; }
            .navlinks, .actions { display: grid; gap: 8px; align-items: stretch; }
            .navlinks a, .actions .btn, .actions .logout-inline button { width: 100%; justify-content: flex-start; min-height: 42px; padding: 10px 12px; border-radius: 8px; white-space: nowrap; }
            .actions .logout-inline { width: 100%; }
            .hero-inner, .app-layout, .seller, .contact, .footer-grid, .install-panel { grid-template-columns: 1fr; }
            .hero-inner { min-height: auto; }
            .features, .platforms, .prices, .steps, .kpis { grid-template-columns: repeat(2, 1fr); }
            .sidebar { position: static; display: grid; grid-template-columns: repeat(2, 1fr); }
            .screen { min-height: auto; }
        }

        @media (max-width: 640px) {
            .container { width: min(100% - 24px, 1180px); }
            .hero-inner { padding-top: 42px; gap: 28px; }
            .hero-metrics, .features, .platforms, .prices, .steps, .kpis, .module-grid, .stat-grid, .install-options { grid-template-columns: 1fr; }
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
                <span class="logo">EM</span>
                <span>EasyMarket</span>
            </a>
            <button class="home-menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="nav-menu">
                <nav class="navlinks" aria-label="Navigation principale">
                    <a href="#fonctionnalites"><i class="fa-solid fa-list-check"></i>Fonctionnalités</a>
                    <a href="#application"><i class="fa-solid fa-display"></i>Application</a>
                    <a href="#tarifs"><i class="fa-solid fa-tags"></i>Tarifs</a>
                    <a href="#faq"><i class="fa-solid fa-circle-question"></i>FAQ</a>
                    <a href="#contact"><i class="fa-solid fa-headset"></i>Contact</a>
                </nav>
                <div class="actions">
                    __HOME_AUTH_ACTIONS__
                </div>
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
                        __HOME_HERO_SIGNUP__
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
                            <a href="#application"><i class="fa-solid fa-chart-pie"></i>Tableau de bord</a>
                            <a href="#application"><i class="fa-solid fa-cash-register"></i>Ventes</a>
                            <a href="#application"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                            <a href="#application"><i class="fa-solid fa-users"></i>Clients & Créances</a>
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

        <section class="install-band" id="installer">
            <div class="container">
                <div class="install-panel">
                    <div>
                        <div class="eyebrow" style="color:var(--primary-700)">Utilisation rapide</div>
                        <h2>Utilisez EasyMarket encore plus vite</h2>
                        <p>Gardez EasyMarket à portée de main : un clic depuis votre téléphone ou votre ordinateur, et vous êtes directement prêt à gérer votre boutique.</p>
                    </div>
                    <div class="install-options">
                        <article class="install-card">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                            <h3>Version mobile</h3>
                            <div class="install-platform-icons" aria-label="Android et iPhone">
                                <i class="fa-brands fa-android" title="Android" aria-label="Android"></i>
                                <i class="fa-brands fa-apple" title="iPhone iOS" aria-label="iPhone iOS"></i>
                            </div>
                            <p>Sur Android ou iPhone, vos vendeurs encaissent plus vite, consultent les stocks et suivent les alertes sans quitter le comptoir.</p>
                            <button class="btn btn-primary" type="button" data-install-target="mobile"><i class="fa-solid fa-download"></i>Utiliser sur mobile</button>
                        </article>
                        <article class="install-card">
                            <i class="fa-solid fa-desktop"></i>
                            <h3>Version desktop</h3>
                            <div class="install-platform-icons" aria-label="Windows">
                                <i class="fa-brands fa-windows" title="Windows" aria-label="Windows"></i>
                            </div>
                            <p>Sur Windows, ouvrez EasyMarket dans une fenêtre dédiée pour gérer la caisse, les factures et les rapports avec plus de confort.</p>
                            <button class="btn btn-dark" type="button" data-install-target="desktop"><i class="fa-brands fa-windows"></i>Utiliser sur Windows</button>
                        </article>
                    </div>
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
                    <article class="feature"><div class="icon"><i class="fa-solid fa-user-group"></i></div><h3>Personnel & paie</h3><p>Suivez les avances, salaires et fiches de paie de votre personnel sans feuille Excel compliquée .</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-truck-field"></i></div><h3>Fournisseurs & Dettes</h3><p>Enregistrez vos fournisseurs, suivez les dettes, les échéances et les paiements pour garder une vision claire de ce que la boutique doit régler.</p></article>
                    <article class="feature"><div class="icon"><i class="fa-solid fa-chart-column"></i></div><h3>Rapports & impôts</h3><p>Exportez les ventes, dépenses, créances, dettes et documents utiles pour votre comptable en PDF ou en Excel.</p></article>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-route title-icon"></i>Comment ça marche ?</h2>
                    <p>Après l'inscription, vous ajoutez les informations de votre boutique, puis vous commencez à configurer votre activité : produits, stocks, vendeurs, clients, fournisseurs et premières ventes. Ensuite, votre tableau de bord vous aide à suivre vos chiffres et vos opérations au quotidien.</p>
                </div>
                <div class="grid steps">
                    <article class="step"><div class="icon"><i class="fa-solid fa-user-pen"></i></div><h3>Créez votre compte</h3><p>Indiquez votre nom, téléphone, email et mot de passe pour ouvrir votre espace EasyMarket.</p></article>
                    <article class="step"><div class="icon"><i class="fa-solid fa-store"></i></div><h3>Ajoutez votre boutique</h3><p>Renseignez le nom, le téléphone, l'adresse, l'IFU et les informations qui apparaîtront sur vos factures.</p></article>
                    <article class="step"><div class="icon"><i class="fa-solid fa-gauge-high"></i></div><h3>Accédez au tableau de bord</h3><p>Tout est ok, la vente peut commencer.
                        Pensez à ajouter vos charges, dettes et créances au fur et à mesure.</p></article>
                </div>
            </div>
        </section>

        <section id="application" class="app-band">
            <div class="container">
                <div class="section-head">
                    <h2><i class="fa-solid fa-mobile-screen-button title-icon"></i>Une interface simple, même sur téléphone</h2>
                    <p>L’interface a été pensée pour rester claire et agréable sur tous les types d’écran : téléphone, tablette, ordinateur portable ou poste de caisse.</p>
                </div>
                <div class="app-layout">
                    <aside class="sidebar">
                        <a class="side-link active" href="#application"><i class="fa-solid fa-chart-line"></i>Tableau de bord</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-file-invoice-dollar"></i>Ventes & Factures</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-users"></i>Clients & Créances</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-bell"></i>Notifications</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-truck"></i>Fournisseurs & Dettes</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-user-tie"></i>Personnel</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-wallet"></i>Charges</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-chart-column"></i>Rapports</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-file-invoice"></i>Impôts</a>
                        <a class="side-link" href="#application"><i class="fa-solid fa-gear"></i>Paramètres</a>
                    </aside>
                    <div class="dashboard">
                        <div class="dash-top">
                            <div>
                                <span class="tag">Boutique personnalisée</span>
                                <h2 style="margin:10px 0 0"><i class="fa-solid fa-gauge-high title-icon"></i>Tableau de bord</h2>
                            </div>
                            <div class="demo-user-chip">
                                <i class="fa-solid fa-user-shield"></i>
                                <span>John MIGAN<small>Administrateur - Boutique Alafia</small></span>
                            </div>
                        </div>
                        <div class="dash-content">
                            <div class="grid kpis">
                                <div class="mini-card"><small>CA ce mois</small><b>400 000</b><small>F CFA</small></div>
                                <div class="mini-card"><small>Nouveaux clients</small><b>126</b><small>+18%</small></div>
                                <div class="mini-card"><small>Dettes fournisseurs</small><b>200 000</b><small>F CFA</small></div>
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
                        <p class="price-subtitle">Pour démarrer sans engagement.</p>
                        <div class="amount">5 000<span>FCFA</span></div>
                        <p class="price-period">Par mois</p>
                        <ul><li>Renouvellement mensuel</li><li>Tous les modules essentiels</li><li>Support standard</li></ul>
                        __HOME_PRICING_CTA_MONTHLY__
                    </article>
                    <article class="price highlight">
                        <div class="price-title"><h3>Annuel</h3><span class="recommended-tag"><i class="fa-solid fa-circle-check"></i>Recommandé</span></div>
                        <p class="price-subtitle">Le meilleur choix pour une boutique active.</p>
                        <div class="amount">50 000<span>FCFA</span></div>
                        <p class="price-period">Par an, soit 4 167 FCFA/mois</p>
                        <ul><li>Économie de 10 000 FCFA</li><li>Rapports et exports inclus</li><li>Support prioritaire</li></ul>
                        __HOME_PRICING_CTA_YEARLY__
                    </article>
                    <article class="price">
                        <h3>Licence à vie</h3>
                        <p class="price-subtitle">Pour éviter les renouvellements.</p>
                        <div class="amount">100 000<span>FCFA</span></div>
                        <p class="price-period">Paiement unique</p>
                        <ul><li>Toutes mises à jour incluses</li><li>Sans renouvellement mensuel</li><li>Accès durable</li></ul>
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
                    <details class="faq"><summary>À quoi sert réellement cette application ?</summary><div class="faq-answer"><p class="faq-highlight">EasyMarket vous aide à gérer votre boutique plus simplement, sans disperser vos ventes, vos stocks, vos factures, vos clients et vos dépenses dans plusieurs carnets, fichiers ou conversations.</p><ul class="faq-benefits"><li><strong>Vous centralisez</strong><span>vos produits, stocks, ventes, factures, tickets, proformas, clients et créances dans un seul espace.</span></li><li><strong>Vous suivez</strong><span>vos fournisseurs, dettes, charges, membres du personnel, avances et paies avec des informations faciles à retrouver.</span></li><li><strong>Vous décidez mieux</strong><span>grâce aux chiffres, alertes, rapports, exports comptables et indicateurs de performance de votre activité.</span></li></ul><p class="faq-cta">Vous créez votre espace, ajoutez votre boutique, puis pilotez votre commerce depuis le web, votre téléphone ou votre ordinateur.</p></div></details>
                    <details class="faq"><summary>Puis-je l'utiliser sur téléphone et ordinateur ?</summary><div class="faq-answer"><p>Oui. EasyMarket est prévu pour être utilisé sur <strong>web, mobile et ordinateur</strong>. Vous pouvez commencer une action sur un support et retrouver les mêmes informations ailleurs.</p><ul><li><strong>Sur téléphone :</strong> le vendeur peut enregistrer rapidement les ventes au comptoir.</li><li><strong>Sur ordinateur :</strong> l’administrateur peut analyser les rapports, gérer les stocks et imprimer les documents.</li><li><strong>Sur web :</strong> les données restent accessibles depuis un navigateur, sans fichier à transporter.</li></ul><p>Cette continuité permet de garder une seule version des chiffres, même lorsque plusieurs personnes travaillent dans la boutique.</p></div></details>
                    <details class="faq"><summary>Que se passe-t-il après l'inscription ?</summary><div class="faq-answer"><p>Le démarrage se fait en trois étapes simples : vous créez votre espace avec les informations de la boutique, vous activez votre accès depuis le tableau de bord, puis vous complétez la configuration pour commencer à vendre.</p><ul><li><strong>Étape 1 :</strong> vous vous inscrivez avec vos informations personnelles et celles de votre boutique : nom, téléphone, adresse, IFU et informations utiles pour les documents.</li><li><strong>Étape 2 :</strong> vous accédez au tableau de bord et effectuez votre premier paiement ou l’activation de la formule choisie.</li><li><strong>Étape 3 :</strong> vous ajoutez vos produits, vos stocks, vos vendeurs, vos clients, vos fournisseurs et les derniers paramètres nécessaires à votre activité.</li></ul><p>Une fois ces éléments renseignés, votre boutique est prête pour enregistrer les ventes, suivre les stocks et contrôler les chiffres depuis le tableau de bord.</p></div></details>
                    <details class="faq"><summary>Les factures peuvent-elles être partagées ?</summary><div class="faq-answer"><p>Oui. EasyMarket permet de générer des documents propres, faciles à remettre au client ou à archiver.</p><ul><li><strong>Factures :</strong> pour les ventes validées et encaissées ou à crédit.</li><li><strong>Proformas :</strong> pour envoyer une proposition avant la vente définitive, sans déstocker.</li><li><strong>Tickets et PDF :</strong> pour imprimer, télécharger ou partager les documents.</li><li><strong>WhatsApp :</strong> le vendeur peut envoyer le document au client si son numéro est renseigné.</li></ul><p>Les documents comportent les informations essentielles de la boutique, les lignes produits, les quantités, les prix et le total.</p></div></details>
                    <details class="faq"><summary>Comment recevoir les alertes ?</summary><div class="faq-answer"><p>Vous pouvez configurer vos coordonnées et vos préférences dans les paramètres de la boutique. EasyMarket vous aide ensuite à repérer les situations à surveiller.</p><ul><li><strong>Stock bas :</strong> lorsqu’un produit atteint ou dépasse son seuil d’alerte.</li><li><strong>Créances :</strong> lorsqu’un client doit encore payer ou qu’une échéance approche.</li><li><strong>Dettes fournisseurs :</strong> pour suivre les montants à régler.</li><li><strong>Rapports WhatsApp :</strong> vous pouvez choisir une heure d’envoi et le type de rapport à recevoir.</li></ul><p>Ces alertes évitent d’attendre la fin du mois pour découvrir un problème de stock, de caisse ou de paiement.</p></div></details>
                    <details class="faq"><summary>Comment EasyMarket m'aide pour la comptabilité ?</summary><div class="faq-answer"><p>EasyMarket regroupe les informations que votre comptable vous demande souvent et les présente sous forme de rapports exploitables.</p><ul><li><strong>Ventes validées :</strong> montants, dates, vendeurs, moyens de paiement et factures.</li><li><strong>Charges :</strong> dépenses classées par date et catégorie.</li><li><strong>Personnel :</strong> salaires, avances et paies nettes.</li><li><strong>Situation commerciale :</strong> créances clients, dettes fournisseurs et valeur du stock.</li></ul><p>L’application ne remplace pas le comptable. Elle prépare un dossier plus propre, plus complet et plus facile à contrôler avant validation.</p></div></details>
                    <details class="faq"><summary>Est-ce que l'application prépare les informations pour les impôts ?</summary><div class="faq-answer"><p>Oui. La page <strong>Impôts & comptabilité</strong> prépare une synthèse de travail pour aider à organiser les chiffres avant une déclaration ou un échange avec le comptable.</p><ul><li><strong>Recettes :</strong> total des ventes validées sur la période choisie.</li><li><strong>Coût d’achat vendu :</strong> estimation du coût des produits réellement vendus.</li><li><strong>Marge et résultat :</strong> marge brute, charges, salaires bruts et résultat estimé.</li><li><strong>Bilan estimatif :</strong> stock valorisé, créances ouvertes et dettes fournisseurs.</li></ul><p>Vous choisissez la période couverte, imprimez le bilan et rassemblez les documents à transmettre. Ces documents restent des supports de préparation et doivent être vérifiés avant toute déclaration officielle.</p></div></details>
                    <details class="faq"><summary>Puis-je suivre les créances et dettes dans mes rapports ?</summary><div class="faq-answer"><p>Oui. EasyMarket distingue clairement ce que les clients doivent encore payer et ce que la boutique doit aux fournisseurs.</p><ul><li><strong>Créances clients :</strong> montant initial, montant payé, solde restant, échéance et historique des paiements.</li><li><strong>Dettes fournisseurs :</strong> fournisseur concerné, montant dû, paiements effectués et échéance.</li><li><strong>Facture liée :</strong> lorsqu’une créance vient d’une vente à crédit, vous pouvez retrouver la facture correspondante.</li></ul><p>Cette séparation aide à comprendre la trésorerie réelle : une vente à crédit augmente le chiffre d’affaires, mais l’argent n’est pas encore en caisse tant que le client n’a pas payé.</p></div></details>
                    <details class="faq"><summary>Les rapports peuvent-ils être imprimés ou envoyés automatiquement ?</summary><div class="faq-answer"><p>Oui. EasyMarket permet d’imprimer ou d’exporter les principaux rapports, puis de configurer certains envois automatiques.</p><ul><li><strong>Exports disponibles :</strong> ventes, produits, charges, créances, dettes fournisseurs, paies et bilan estimatif.</li><li><strong>Formats :</strong> impression/PDF et exports Excel selon les pages.</li><li><strong>WhatsApp automatique :</strong> vous choisissez une heure d’envoi et le type de rapport : global, ventes, stock, créances, dettes, charges ou impôts & comptabilité.</li></ul><p>Le but est de recevoir régulièrement les chiffres importants, sans devoir ouvrir chaque page une par une.</p></div></details>
                </div>
            </div>
        </section>

        <section id="contact">
            <div class="container contact">
                <div>
                    <div class="eyebrow">Contact</div>
                    <h2 style="margin:10px 0 12px"><i class="fa-solid fa-headset title-icon"></i>Contactez nous</h2>
                    <p style="color:rgba(255,255,255,.75)">Envoyez-nous votre message ou contactez le support sur WhatsApp.</p>
                    <a class="btn btn-primary" href="https://wa.me/229019662860" style="margin-top:18px"><i class="fa-brands fa-whatsapp"></i>01 96 62 86 0</a>
                </div>
                <form class="form" method="post" action="/contact">
                    <input type="hidden" name="_token" value="__CSRF_TOKEN__">
                    <input class="field" name="name" type="text" placeholder="Nom et prénom" required>
                    <input class="field" name="phone" type="tel" placeholder="Téléphone">
                    <input class="field" name="email" type="email" placeholder="Email" required>
                    <textarea class="field" name="message" placeholder="Votre question ou votre besoin" required></textarea>
                    <p id="contact-status" style="display:none;margin:0;color:white;font-weight:600"></p>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i>Envoyer</button>
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
                        <a href="/conditions-generales-utilisation"><i class="fa-solid fa-file-contract"></i>Conditions générales</a>
                        <a href="/politique-confidentialite"><i class="fa-solid fa-shield-halved"></i>Politique de confidentialité</a>
                        __HOME_FOOTER_AUTH_LINKS__
                    </div>
                </div>
                <div>
                    <strong style="color:white">Contact</strong>
                    <div class="footer-links" style="margin-top:12px">
                        <a class="footer-phone" href="https://wa.me/229019662860"><i class="fa-brands fa-whatsapp"></i> 01 96 62 86 0</a>
                        <span>Cliquez sur le numéro pour nous contacter sur WhatsApp</span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2026 EasyMarket. Tous droits réservés.</span>
                <span class="footer-signature">Built with Blood, Sweat and Tears by <a href="https://rachad-alabi-adekambi.github.io/portfolio/" target="_blank" rel="noopener">RA</a></span>
            </div>
        </div>
    </footer>
    <div class="install-modal" id="install-modal" aria-hidden="true">
        <section class="install-modal-card" role="dialog" aria-modal="true" aria-labelledby="install-modal-title">
            <div class="install-modal-head">
                <div>
                    <h3 id="install-modal-title">Installer EasyMarket</h3>
                    <p id="install-modal-subtitle">Confirmez pour lancer l’installation.</p>
                </div>
                <button class="table-icon" type="button" id="install-modal-close" title="Fermer" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p id="install-modal-message" style="margin:0;color:var(--muted);line-height:1.6"></p>
            <ol class="install-steps" id="install-ios-steps" style="display:none;margin-top:14px">
                <li>Ouvrez EasyMarket avec <strong>Safari</strong> sur votre iPhone.</li>
                <li>Touchez l’icône <strong>Partager</strong> en bas de l’écran.</li>
                <li>Faites défiler les options puis choisissez <strong>Sur l’écran d’accueil</strong>.</li>
                <li>Touchez <strong>Ajouter</strong>. L’icône EasyMarket apparaîtra sur l’écran d’accueil.</li>
            </ol>
            <div class="install-modal-actions">
                <button class="btn btn-light" type="button" id="install-modal-cancel"><i class="fa-solid fa-xmark"></i>Annuler</button>
                <button class="btn btn-primary" type="button" id="install-modal-confirm"><i class="fa-solid fa-download"></i>Installer</button>
            </div>
        </section>
    </div>
    <script>
        document.querySelectorAll('section .section-head, .feature, .platform-card, .install-panel, .install-card, .step, .module, .price, .faq, .seller, .dashboard, .contact').forEach((item) => item.classList.add('reveal'));
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
            navigator.serviceWorker.register('/sw.js?v=10');
        }
        const contactStatus = document.getElementById('contact-status');
        const contactResult = new URLSearchParams(window.location.search).get('contact');
        if (contactStatus && contactResult) {
            contactStatus.style.display = 'block';
            contactStatus.textContent = contactResult === 'sent'
                ? 'Message envoyé. Nous vous répondrons rapidement.'
                : 'Le message n’a pas pu être envoyé. Vérifiez les champs puis réessayez.';
        }
        const homeMenuToggle = document.querySelector('.home-menu-toggle');
        if (homeMenuToggle) {
            homeMenuToggle.addEventListener('click', () => {
                document.body.classList.toggle('home-menu-open');
                const open = document.body.classList.contains('home-menu-open');
                homeMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                homeMenuToggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
                homeMenuToggle.querySelector('i').className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            });
            document.querySelectorAll('.nav-menu a, .nav-menu button').forEach((item) => {
                item.addEventListener('click', () => {
                    document.body.classList.remove('home-menu-open');
                    homeMenuToggle.setAttribute('aria-expanded', 'false');
                    homeMenuToggle.setAttribute('aria-label', 'Ouvrir le menu');
                    homeMenuToggle.querySelector('i').className = 'fa-solid fa-bars';
                });
            });
        }
        const installModal = document.getElementById('install-modal');
        const installModalTitle = document.getElementById('install-modal-title');
        const installModalSubtitle = document.getElementById('install-modal-subtitle');
        const installModalMessage = document.getElementById('install-modal-message');
        const installIosSteps = document.getElementById('install-ios-steps');
        const installModalClose = document.getElementById('install-modal-close');
        const installModalCancel = document.getElementById('install-modal-cancel');
        const installModalConfirm = document.getElementById('install-modal-confirm');
        const mobileAppDownloadUrl = 'https://easy-market.xo.je/app_mobile.apk';
        const desktopAppDownloadUrl = 'https://easy-market.xo.je/app_desktop.exe';
        let deferredInstallPrompt = null;
        let selectedInstallTarget = 'mobile';
        const isIosDevice = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isSafariBrowser = /^((?!chrome|android|crios|fxios|edgios).)*safari/i.test(navigator.userAgent);
        const installCopy = {
            mobile: {
                title: 'Confirmer le téléchargement mobile',
                subtitle: 'Application EasyMarket',
                message: 'Confirmez pour lancer le téléchargement de l’application EasyMarket.'
            },
            desktop: {
                title: 'Confirmer le téléchargement Windows',
                subtitle: 'Windows',
                message: 'Confirmez pour lancer le téléchargement de l’application EasyMarket pour Windows.'
            }
        };

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredInstallPrompt = event;
        });

        window.addEventListener('appinstalled', () => {
            deferredInstallPrompt = null;
            closeInstallConfirm();
        });

        function openInstallConfirm(type) {
            const copy = installCopy[type] || installCopy.mobile;
            selectedInstallTarget = type;
            installModalConfirm.style.display = '';
            installModalConfirm.innerHTML = type === 'mobile'
                ? '<i class="fa-solid fa-download"></i>Télécharger'
                : '<i class="fa-solid fa-download"></i>Installer';
            installIosSteps.style.display = 'none';
            installModalTitle.textContent = copy.title;
            installModalSubtitle.textContent = copy.subtitle;
            installModalMessage.textContent = copy.message;

            installModal.classList.add('open');
            installModal.setAttribute('aria-hidden', 'false');
        }

        function closeInstallConfirm() {
            if (!installModal) return;
            installModal.classList.remove('open');
            installModal.setAttribute('aria-hidden', 'true');
        }

        async function confirmInstall() {
            if (selectedInstallTarget === 'mobile') {
                window.location.href = mobileAppDownloadUrl;
                closeInstallConfirm();
                return;
            }

            if (selectedInstallTarget === 'desktop') {
                window.location.href = desktopAppDownloadUrl;
                closeInstallConfirm();
                return;
            }

            if (deferredInstallPrompt) {
                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
                closeInstallConfirm();
                return;
            }

            installModalTitle.textContent = 'Installation manuelle requise';
            installModalSubtitle.textContent = selectedInstallTarget === 'desktop' ? 'Option non disponible dans ce navigateur' : 'Option non disponible automatiquement';
            installModalMessage.textContent = isIosDevice
                ? 'Sur iPhone, Safari ne permet pas d’ajouter l’icône automatiquement par bouton. Ouvrez le partage Safari puis choisissez “Sur l’écran d’accueil”.'
                : 'Votre navigateur ne propose pas l’ajout direct pour le moment. Utilisez le menu du navigateur puis “Installer” ou “Ajouter à l’écran d’accueil”.';
            installIosSteps.style.display = isIosDevice ? 'grid' : 'none';
            installModalConfirm.style.display = 'none';
        }

        document.querySelectorAll('[data-install-target]').forEach((button) => {
            button.addEventListener('click', () => openInstallConfirm(button.dataset.installTarget));
        });
        installModalClose?.addEventListener('click', closeInstallConfirm);
        installModalCancel?.addEventListener('click', closeInstallConfirm);
        installModalConfirm?.addEventListener('click', confirmInstall);
        installModal?.addEventListener('click', (event) => {
            if (event.target === installModal) closeInstallConfirm();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeInstallConfirm();
        });
    </script>
    <script src="/logout-confirm.js"></script>
    <script src="/cookie-consent.js"></script>
</body>
</html>
