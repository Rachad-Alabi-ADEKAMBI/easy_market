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
        .doc-brand img, .doc-logo { width: 62px; height: 62px; object-fit: contain; border: 1px solid var(--line); border-radius: 8px; padding: 5px; }
        .doc-logo { display: grid; place-items: center; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; font-size: 24px; }
        .doc-brand h1 { margin-bottom: 6px; }
        .doc-brand p { margin: 2px 0; }
        .doc-detail { display: flex; align-items: center; gap: 12px; }
        .doc-detail i { width: 18px; text-align: center; flex: 0 0 18px; }
        .doc-detail i, .doc-meta i, .card i, .balance-box h2 i, .statement h2 i, .detail-box h2 i, section > h2 i, th i, .footer i, li i { color: var(--primary); }
        .doc-meta { text-align: right; color: var(--muted); font-weight: 700; }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 14px; }
        .card i { display: inline-flex; margin-bottom: 8px; font-size: 18px; }
        .card span { display: block; color: var(--muted); font-size: 13px; }
        .card strong { font-size: 20px; }
        .balance-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin: 12px 0 24px; }
        .balance-box { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .balance-box h2 { margin: 0; padding: 12px 14px; background: var(--primary); color: white; font-size: 18px; display:flex; align-items:center; gap:8px; }
        .balance-box h2 i { color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--primary); font-size: 13px; }
        td:last-child, th:last-child { text-align: right; white-space: nowrap; }
        tfoot td { border-top: 2px solid var(--primary); border-bottom: 0; font-weight: 800; }
        .statement { margin: 0 0 24px; }
        .statement h2 { margin-bottom: 8px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-bottom: 24px; }
        .detail-box { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .detail-box.full { grid-column: 1 / -1; }
        .detail-box h2, .statement h2 { display:flex; align-items:center; gap:8px; }
        .detail-box h2 { margin: 0; padding: 12px 14px; background: #f6faf8; color: var(--primary); font-size: 17px; border-bottom: 1px solid var(--line); }
        .tax-detail-section { margin-top: 26px; break-inside: avoid; page-break-inside: avoid; }
        .tax-detail-section h2 { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .tax-detail-section table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .tax-detail-section th, .tax-detail-section td { padding: 9px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        .tax-detail-section th { color: var(--primary); font-size: 13px; }
        .tax-detail-section td:last-child, .tax-detail-section th:last-child { text-align: right; }
        .disclaimer { display:flex; gap:10px; align-items:flex-start; border:1px solid #f4c7a1; border-radius:8px; background:#fff8ed; color:#7a4d00; padding:12px 14px; margin:0 0 22px; font-weight:600; }
        .disclaimer i { color:#b86412; margin-top:3px; }
        ul { line-height: 1.8; }
        .footer { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); color: var(--muted); text-align: center; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 7px; }
        @media print { body { background:white; } .toolbar { display:none; } .sheet { width:100%; min-height:100vh; margin:0; border:0; box-shadow:none; border-radius:0; } }
        @media (max-width: 680px) { .grid, .head, .balance-grid, .detail-grid { grid-template-columns: 1fr; display: grid; } .detail-box.full { grid-column: auto; } .doc-meta { text-align: left; } .sheet { padding: 20px; } }
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
            <div class="doc-meta"><i class="fa-solid fa-file-circle-check"></i> Bilan estimatif auto-généré<br><i class="fa-solid fa-calendar-days"></i> __PERIOD__</div>
        </section>
        <p class="disclaimer"><i class="fa-solid fa-triangle-exclamation"></i> Ce bilan est un document de travail généré par EasyMarket. Il ne constitue pas un document légal, fiscal ou comptable officiel et doit être vérifié par un comptable ou l’administration compétente avant toute déclaration.</p>
        <section class="grid">
            <div class="card"><i class="fa-solid fa-chart-line"></i><span>Résultat net estimé</span><strong>__NET__</strong></div>
            <div class="card"><i class="fa-solid fa-boxes-stacked"></i><span>Valeur du stock</span><strong>__STOCK_VALUE__</strong></div>
            <div class="card"><i class="fa-solid fa-hand-holding-dollar"></i><span>Créances clients</span><strong>__RECEIVABLES__</strong></div>
            <div class="card"><i class="fa-solid fa-truck-field"></i><span>Dettes fournisseurs</span><strong>__DEBTS__</strong></div>
        </section>
        <section class="balance-grid">
            <article class="balance-box">
                <h2><i class="fa-solid fa-vault"></i>Actif</h2>
                <table>
                    <tbody>
                        <tr><td>Stock valorisé au coût d'achat</td><td>__STOCK_VALUE__</td></tr>
                        <tr><td>Créances clients à encaisser</td><td>__RECEIVABLES__</td></tr>
                    </tbody>
                    <tfoot><tr><td>Total actif estimé</td><td>__ASSETS_TOTAL__</td></tr></tfoot>
                </table>
            </article>
            <article class="balance-box">
                <h2><i class="fa-solid fa-scale-balanced"></i>Passif & situation nette</h2>
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
            <h2><i class="fa-solid fa-chart-column"></i>Compte de résultat de la période</h2>
            <table>
                <tbody>
                    <tr><td>Recettes / ventes validées</td><td>__SALES__</td></tr>
                    <tr><td>Coût d'achat des produits vendus</td><td>__COGS__</td></tr>
                    <tr><td>Marge brute estimée</td><td>__GROSS_MARGIN__</td></tr>
                    <tr><td>Charges d'exploitation</td><td>__EXPENSES__</td></tr>
                    <tr><td>Salaires bruts générés</td><td>__PAYROLLS_GROSS__</td></tr>
                    <tr><td>Avances sur salaire remises (suivi de trésorerie)</td><td>__SALARY_ADVANCES__</td></tr>
                    <tr><td>Paies nettes versées (suivi de règlement)</td><td>__PAYROLLS__</td></tr>
                </tbody>
                <tfoot><tr><td>Résultat net estimé (ventes - coût d’achat vendu - charges - salaires bruts)</td><td>__NET__</td></tr></tfoot>
            </table>
        </section>
        <section class="detail-grid">
            <article class="detail-box">
                <h2><i class="fa-solid fa-wallet"></i>Ventes par moyen de paiement</h2>
                <table>
                    <thead><tr><th><i class="fa-solid fa-credit-card"></i> Moyen</th><th><i class="fa-solid fa-hashtag"></i> Nombre</th><th><i class="fa-solid fa-coins"></i> Total</th></tr></thead>
                    <tbody>__SALES_BY_PAYMENT_ROWS__</tbody>
                </table>
            </article>
            <article class="detail-box">
                <h2><i class="fa-solid fa-money-bill-wave"></i>Charges par catégorie</h2>
                <table>
                    <thead><tr><th><i class="fa-solid fa-tags"></i> Catégorie</th><th><i class="fa-solid fa-hashtag"></i> Nombre</th><th><i class="fa-solid fa-coins"></i> Total</th></tr></thead>
                    <tbody>__EXPENSES_BY_CATEGORY_ROWS__</tbody>
                </table>
            </article>
        </section>
        <section class="grid">
            <div class="card"><i class="fa-solid fa-box"></i><span>Produits en stock</span><strong>__PRODUCTS_COUNT__</strong></div>
            <div class="card"><i class="fa-solid fa-triangle-exclamation"></i><span>Produits en alerte</span><strong>__LOW_STOCK_COUNT__</strong></div>
            <div class="card"><i class="fa-solid fa-file-invoice-dollar"></i><span>Créances ouvertes</span><strong>__RECEIVABLES_COUNT__</strong></div>
            <div class="card"><i class="fa-solid fa-truck"></i><span>Dettes ouvertes</span><strong>__DEBTS_COUNT__</strong></div>
        </section>
        __FULL_TAX_DOCUMENTS__
        <p class="footer"><i class="fa-solid fa-circle-check"></i> Document généré par l'application EasyMarket.</p>
    </main>
</body>
</html>
