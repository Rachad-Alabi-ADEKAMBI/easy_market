<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bilan fiscal - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:__PRIMARY_COLOR__; --accent:__SECONDARY_COLOR__; --ink:#17211b; --muted:#3f5048; --line:#dfe7e2; --paper:#f6faf8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Poppins', Arial, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; background: var(--primary); color: white; padding: 12px 20px; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: var(--accent); color: white; font-weight: 600; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .sheet { width: min(900px, calc(100% - 24px)); min-height: calc(100vh - 48px); margin: 24px auto; background: white; border: 1px solid var(--line); border-radius: 10px; padding: 34px; box-shadow: 0 20px 55px rgba(25,59,50,.12); display: flex; flex-direction: column; }
        h1, h2, p { margin-top: 0; }
        .head { border-bottom: 2px solid var(--primary); padding-bottom: 18px; margin-bottom: 22px; display: flex; justify-content: space-between; gap: 20px; }
        .doc-brand { display: flex; align-items: flex-start; gap: 14px; }
        .doc-brand img { width: 62px; height: 62px; object-fit: contain; border: 1px solid var(--line); border-radius: 8px; padding: 5px; }
        .doc-brand h1 { margin-bottom: 6px; }
        .doc-brand p { margin: 2px 0; }
        .doc-meta { text-align: right; color: var(--muted); font-weight: 700; }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 14px; }
        .card span { display: block; color: var(--muted); font-size: 13px; }
        .card strong { font-size: 20px; }
        .balance-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin: 12px 0 24px; }
        .balance-box { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .balance-box h2 { margin: 0; padding: 12px 14px; background: var(--primary); color: white; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--primary); font-size: 13px; }
        td:last-child, th:last-child { text-align: right; white-space: nowrap; }
        tfoot td { border-top: 2px solid var(--primary); border-bottom: 0; font-weight: 800; }
        .statement { margin: 0 0 24px; }
        .statement h2 { margin-bottom: 8px; }
        ul { line-height: 1.8; }
        .footer { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); color: var(--muted); text-align: center; font-size: 12px; }
        @media print { body { background:white; } .toolbar { display:none; } .sheet { width:100%; min-height:100vh; margin:0; border:0; box-shadow:none; border-radius:0; } }
        @media (max-width: 680px) { .grid, .head, .balance-grid { grid-template-columns: 1fr; display: grid; } .doc-meta { text-align: left; } .sheet { padding: 20px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong><i class="fa-solid fa-scale-balanced"></i> Bilan fiscal</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>
    <main class="sheet">
        <section class="head">
            __BUSINESS_HEADER__
            <div class="doc-meta">Bilan auto-généré<br>__PERIOD__</div>
        </section>
        <section class="grid">
            <div class="card"><span>Résultat net estimé</span><strong>__NET__</strong></div>
            <div class="card"><span>Valeur du stock</span><strong>__STOCK_VALUE__</strong></div>
            <div class="card"><span>Créances clients</span><strong>__RECEIVABLES__</strong></div>
            <div class="card"><span>Dettes fournisseurs</span><strong>__DEBTS__</strong></div>
        </section>
        <section class="balance-grid">
            <article class="balance-box">
                <h2>Actif</h2>
                <table>
                    <tbody>
                        <tr><td>Stock valorisé au coût d'achat</td><td>__STOCK_VALUE__</td></tr>
                        <tr><td>Créances clients à encaisser</td><td>__RECEIVABLES__</td></tr>
                    </tbody>
                    <tfoot><tr><td>Total actif estimé</td><td>__ASSETS_TOTAL__</td></tr></tfoot>
                </table>
            </article>
            <article class="balance-box">
                <h2>Passif & situation nette</h2>
                <table>
                    <tbody>
                        <tr><td>Dettes fournisseurs à payer</td><td>__DEBTS__</td></tr>
                        <tr><td>Situation nette estimée</td><td>__EQUITY_ESTIMATE__</td></tr>
                    </tbody>
                    <tfoot><tr><td>Total passif + situation nette</td><td>__ASSETS_TOTAL__</td></tr></tfoot>
                </table>
            </article>
        </section>
        <section class="statement">
            <h2>Compte de résultat de la période</h2>
            <table>
                <tbody>
                    <tr><td>Recettes / ventes encaissées</td><td>__SALES__</td></tr>
                    <tr><td>Charges d'exploitation</td><td>__EXPENSES__</td></tr>
                    <tr><td>Paies nettes</td><td>__PAYROLLS__</td></tr>
                </tbody>
                <tfoot><tr><td>Résultat net estimé</td><td>__NET__</td></tr></tfoot>
            </table>
        </section>
        <section class="grid">
            <div class="card"><span>Produits en stock</span><strong>__PRODUCTS_COUNT__</strong></div>
            <div class="card"><span>Créances ouvertes</span><strong>__RECEIVABLES_COUNT__</strong></div>
            <div class="card"><span>Dettes ouvertes</span><strong>__DEBTS_COUNT__</strong></div>
            <div class="card"><span>Période analysée</span><strong>__PERIOD__</strong></div>
        </section>
        <section>
            <h2>Documents à transmettre au comptable</h2>
            <ul>
                <li>Rapport des recettes</li>
                <li>Rapport des dépenses détaillées</li>
                <li>Journal des ventes</li>
                <li>Bilan des créances clients et dettes fournisseurs</li>
                <li>Fiches de paie du personnel</li>
            </ul>
        </section>
        <p class="footer">Document généré par l'application Easy_Market.</p>
    </main>
</body>
</html>
