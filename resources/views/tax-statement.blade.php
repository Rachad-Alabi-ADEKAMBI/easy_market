<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bilan fiscal - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root { --primary:#193b32; --accent:#f5b84b; --ink:#17211b; --muted:#3f5048; --line:#dfe7e2; --paper:#f6faf8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Poppins', Arial, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; background: var(--primary); color: white; padding: 12px 20px; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: var(--accent); color: white; font-weight: 600; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .sheet { width: min(900px, calc(100% - 24px)); margin: 24px auto; background: white; border: 1px solid var(--line); border-radius: 10px; padding: 34px; box-shadow: 0 20px 55px rgba(25,59,50,.12); }
        h1, h2, p { margin-top: 0; }
        .head { border-bottom: 2px solid var(--primary); padding-bottom: 18px; margin-bottom: 22px; }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .card { border: 1px solid var(--line); border-radius: 8px; padding: 14px; }
        .card span { display: block; color: var(--muted); font-size: 13px; }
        .card strong { font-size: 22px; }
        ul { line-height: 1.8; }
        .footer { margin-top: 34px; color: var(--muted); }
        @media print { body { background:white; } .toolbar { display:none; } .sheet { width:100%; margin:0; border:0; box-shadow:none; border-radius:0; } }
        @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } .sheet { padding: 20px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong><i class="fa-solid fa-scale-balanced"></i> Bilan fiscal</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>
    <main class="sheet">
        <section class="head">
            <h1>__BUSINESS__</h1>
            <p class="muted">Bilan auto-généré · __PERIOD__</p>
        </section>
        <section class="grid">
            <div class="card"><span>Recettes</span><strong>__SALES__</strong></div>
            <div class="card"><span>Dépenses</span><strong>__EXPENSES__</strong></div>
            <div class="card"><span>Paies nettes</span><strong>__PAYROLLS__</strong></div>
            <div class="card"><span>Résultat net estimé</span><strong>__NET__</strong></div>
            <div class="card"><span>Créances clients</span><strong>__RECEIVABLES__</strong></div>
            <div class="card"><span>Dettes fournisseurs</span><strong>__DEBTS__</strong></div>
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
