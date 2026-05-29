<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>__PERIOD_LABEL__ - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:__PRIMARY_COLOR__; --accent:__SECONDARY_COLOR__; --ink:#17211b; --muted:#3f5048; --line:#dfe7e2; --paper:#f6faf8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Poppins', Arial, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; background: var(--primary); color: white; padding: 12px 20px; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: var(--accent); color: white; font-weight: 600; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .sheet { width: min(960px, calc(100% - 24px)); min-height: calc(100vh - 48px); margin: 24px auto; background: white; border: 1px solid var(--line); border-radius: 10px; padding: 34px; box-shadow: 0 20px 55px rgba(25,59,50,.12); display: flex; flex-direction: column; }
        .head { border-bottom: 2px solid var(--primary); padding-bottom: 18px; display: flex; justify-content: space-between; gap: 20px; }
        .doc-brand { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: start; gap: 14px; }
        .doc-brand-content { min-width: 0; }
        .doc-brand img, .doc-logo { width: 62px; height: 62px; object-fit: contain; border: 1px solid var(--line); border-radius: 8px; padding: 5px; }
        .doc-logo { display: grid; place-items: center; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; font-size: 24px; }
        .doc-brand h1 { margin: 2px 0 10px; font-size: 32px; line-height: 1.08; }
        .doc-brand p { margin: 3px 0; }
        .doc-detail { display: grid; grid-template-columns: 20px minmax(0, 1fr); align-items: center; gap: 10px; }
        .doc-detail i { width: 20px; text-align: center; }
        .doc-meta { text-align: right; color: var(--muted); font-weight: 700; }
        h1, h2, p { margin-top: 0; }
        h2, th, .doc-meta { align-items: center; gap: 8px; }
        h2, th { display: inline-flex; }
        h2 i, th i, .card i, .doc-detail i, .doc-meta i, .footer i { color: var(--primary); }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 22px 0; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 14px; }
        .card i { display: inline-flex; margin-bottom: 8px; font-size: 18px; }
        .card span { color: var(--muted); display: block; font-size: 13px; }
        .card strong { font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid var(--line); padding: 12px 8px; text-align: left; }
        th { color: var(--primary); font-size: 13px; }
        .footer { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); color: var(--muted); text-align: center; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 7px; }
        @media print { body { background:white; } .toolbar { display:none; } .sheet { width:100%; min-height:100vh; margin:0; border:0; box-shadow:none; border-radius:0; } }
        @media (max-width: 720px) { .grid, .head { grid-template-columns: 1fr; display: grid; } .doc-meta { text-align: left; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong><i class="fa-solid fa-chart-column"></i> __PERIOD_LABEL__</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>
    <main class="sheet">
        <section class="head">
            __BUSINESS_HEADER__
            <div class="doc-meta"><i class="fa-solid fa-calendar-days"></i> __PERIOD_LABEL__<br><i class="fa-solid fa-clock"></i> __RANGE__</div>
        </section>
        <section class="grid">
            <div class="card"><i class="fa-solid fa-receipt"></i><span>Ventes</span><strong>__SALES_TOTAL__</strong></div>
            <div class="card"><i class="fa-solid fa-money-bill-wave"></i><span>Charges</span><strong>__EXPENSES_TOTAL__</strong></div>
            <div class="card"><i class="fa-solid fa-chart-line"></i><span>Résultat net estimé</span><strong>__NET_RESULT__</strong></div>
            <div class="card"><i class="fa-solid fa-hand-holding-dollar"></i><span>Créances restantes</span><strong>__RECEIVABLES__</strong></div>
            <div class="card"><i class="fa-solid fa-truck-field"></i><span>Dettes fournisseurs</span><strong>__DEBTS__</strong></div>
            <div class="card"><i class="fa-solid fa-file-invoice-dollar"></i><span>Paies nettes</span><strong>__PAYROLLS__</strong></div>
        </section>
        <section>
            <h2><i class="fa-solid fa-trophy"></i>Top produits</h2>
            <table>
                <thead><tr><th><i class="fa-solid fa-box"></i> Produit</th><th><i class="fa-solid fa-cubes-stacked"></i> Quantité vendue</th><th><i class="fa-solid fa-coins"></i> Total</th></tr></thead>
                <tbody>__TOP_PRODUCTS__</tbody>
            </table>
        </section>
        <p class="footer"><i class="fa-solid fa-circle-check"></i> Document généré par l'application EasyMarket.</p>
    </main>
</body>
</html>
