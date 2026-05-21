<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>__PERIOD_LABEL__ - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:#193b32; --accent:#f5b84b; --ink:#17211b; --muted:#3f5048; --line:#dfe7e2; --paper:#f6faf8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Poppins', Arial, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; background: var(--primary); color: white; padding: 12px 20px; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: var(--accent); color: white; font-weight: 600; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .sheet { width: min(960px, calc(100% - 24px)); margin: 24px auto; background: white; border: 1px solid var(--line); border-radius: 10px; padding: 34px; box-shadow: 0 20px 55px rgba(25,59,50,.12); }
        .head { border-bottom: 2px solid var(--primary); padding-bottom: 18px; display: flex; justify-content: space-between; gap: 20px; }
        h1, h2, p { margin-top: 0; }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 22px 0; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 14px; }
        .card span { color: var(--muted); display: block; font-size: 13px; }
        .card strong { font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid var(--line); padding: 12px 8px; text-align: left; }
        th { color: var(--primary); font-size: 13px; }
        .footer { margin-top: 34px; color: var(--muted); }
        @media print { body { background:white; } .toolbar { display:none; } .sheet { width:100%; margin:0; border:0; box-shadow:none; border-radius:0; } }
        @media (max-width: 720px) { .grid, .head { grid-template-columns: 1fr; display: grid; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong><i class="fa-solid fa-chart-column"></i> __PERIOD_LABEL__</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>
    <main class="sheet">
        <section class="head">
            <div>
                <h1>__BUSINESS__</h1>
                <p class="muted">__PERIOD_LABEL__ · __RANGE__</p>
            </div>
            <strong>EasyMarket</strong>
        </section>
        <section class="grid">
            <div class="card"><span>Ventes</span><strong>__SALES_TOTAL__</strong></div>
            <div class="card"><span>Charges</span><strong>__EXPENSES_TOTAL__</strong></div>
            <div class="card"><span>Résultat net estimé</span><strong>__NET_RESULT__</strong></div>
            <div class="card"><span>Créances restantes</span><strong>__RECEIVABLES__</strong></div>
            <div class="card"><span>Dettes fournisseurs</span><strong>__DEBTS__</strong></div>
            <div class="card"><span>Paies nettes</span><strong>__PAYROLLS__</strong></div>
        </section>
        <section>
            <h2>Top produits</h2>
            <table>
                <thead><tr><th>Produit</th><th>Quantité vendue</th><th>Total</th></tr></thead>
                <tbody>__TOP_PRODUCTS__</tbody>
            </table>
        </section>
        <p class="footer">Document généré par l'application Easy_Market.</p>
    </main>
</body>
</html>
