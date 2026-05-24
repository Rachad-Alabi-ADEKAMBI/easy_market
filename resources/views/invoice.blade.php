<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>__DOCUMENT_TITLE__ __SALE_NUMBER__ - EasyMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root {
            --primary: #193b32;
            --accent: #f5b84b;
            --ink: #17211b;
            --muted: #3f5048;
            --line: #dfe7e2;
            --paper: #f6faf8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: 'Poppins', Arial, sans-serif; line-height: 1.45; }
        .toolbar { position: sticky; top: 0; background: var(--primary); color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .btn { border: 0; border-radius: 8px; padding: 10px 14px; background: var(--accent); color: white; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .sheet { width: min(920px, calc(100% - 24px)); min-height: calc(100vh - 48px); margin: 24px auto; background: white; border: 1px solid var(--line); border-radius: 10px; padding: 34px; box-shadow: 0 20px 55px rgba(25,59,50,.12); display: flex; flex-direction: column; }
        .header { display: grid; grid-template-columns: 1fr auto; gap: 24px; border-bottom: 2px solid var(--primary); padding-bottom: 20px; }
        .brand { display: flex; gap: 12px; align-items: center; }
        .logo { width: 54px; height: 54px; border-radius: 10px; display: grid; place-items: center; background: linear-gradient(135deg, #3f8f7b, var(--accent)); color: var(--primary); font-weight: 500; }
        .logo i { font-size: 22px; }
        .brand-logo-img { width: 54px; height: 54px; border-radius: 10px; object-fit: cover; border: 1px solid var(--line); }
        .brand-details { margin-top: 8px; display: grid; gap: 4px; }
        .brand-details p { margin: 0; }
        .doc-detail { display: flex; align-items: center; gap: 12px; }
        .doc-detail i { width: 18px; text-align: center; flex: 0 0 18px; }
        h1, h2, p { margin-top: 0; }
        h2, .totals-row span, .meta-card strong { display: inline-flex; align-items: center; gap: 7px; }
        th .th-label { display: inline-flex; align-items: center; gap: 7px; }
        h2 i, th i, .meta-card i, .totals-row i, .footer i { color: var(--primary); }
        .muted { color: var(--muted); }
        .invoice-box { text-align: right; min-width: 210px; }
        .invoice-box h2 { justify-content: flex-end; margin-bottom: 8px; }
        .invoice-box p { margin-bottom: 8px; }
        .invoice-box .qrcode { display: block; width: 112px; height: 112px; margin-left: auto; margin-top: 8px; border: 1px solid var(--line); border-radius: 8px; padding: 5px; background: white; object-fit: contain; }
        .meta { margin-top: 22px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .meta-card { border: 1px solid var(--line); border-radius: 8px; padding: 12px; }
        .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 28px; table-layout: fixed; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .items-table th, .items-table td { border-bottom: 1px solid var(--line); padding: 13px 14px; vertical-align: middle; }
        .items-table th { background: #f6faf8; color: var(--primary); font-size: 13px; font-weight: 700; text-align: left; white-space: nowrap; }
        .items-table tbody tr:last-child td { border-bottom: 0; }
        .items-table td { font-size: 15px; }
        .col-product { width: 48%; text-align: left; }
        .col-qty { width: 14%; text-align: right; }
        .col-price, .col-total { width: 19%; text-align: right; }
        .items-table .amount { font-weight: 600; white-space: nowrap; }
        .totals { margin-top: 18px; display: grid; justify-content: end; }
        .totals-row { min-width: 290px; display: flex; justify-content: space-between; gap: 20px; padding: 8px 0; }
        .grand { border-top: 2px solid var(--primary); font-size: 20px; font-weight: 500; }
        .footer { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); display: grid; gap: 12px; justify-items: center; color: var(--muted); text-align: center; font-size: 12px; }
        .footer p { display: inline-flex; align-items: center; justify-content: center; gap: 7px; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { width: 100%; min-height: 100vh; margin: 0; border: 0; box-shadow: none; border-radius: 0; }
        }
        @media (max-width: 680px) {
            .header, .meta { grid-template-columns: 1fr; }
            .invoice-box { text-align: left; }
            .sheet { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong><i class="fa-solid fa-file-invoice"></i> __DOCUMENT_TITLE__ __SALE_NUMBER__</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>

    <main class="sheet">
        <section class="header">
            <div>
                __BUSINESS_HEADER__
            </div>
            <div class="invoice-box">
                <h2><i class="fa-solid fa-file-invoice-dollar"></i>__DOCUMENT_TITLE__</h2>
                <p><strong>__SALE_NUMBER__</strong><br>__SALE_DATE__</p>
                <img class="qrcode" src="__QR_IMAGE__" alt="QR code de vérification">
            </div>
        </section>

        __PAYMENT_META__

        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-product"><span class="th-label"><i class="fa-solid fa-box"></i>Produit</span></th>
                    <th class="col-qty"><span class="th-label"><i class="fa-solid fa-cubes-stacked"></i>Qté</span></th>
                    <th class="col-price"><span class="th-label"><i class="fa-solid fa-tag"></i>PU</span></th>
                    <th class="col-total"><span class="th-label"><i class="fa-solid fa-coins"></i>Total</span></th>
                </tr>
            </thead>
            <tbody>
                __ITEM_ROWS__
            </tbody>
        </table>

        <section class="totals">
            <div class="totals-row"><span><i class="fa-solid fa-calculator"></i>Sous-total</span><strong>__SUBTOTAL__</strong></div>
            <div class="totals-row grand"><span><i class="fa-solid fa-coins"></i>Total</span><strong>__TOTAL__</strong></div>
        </section>

        <section class="footer">
            <p><i class="fa-solid fa-circle-check"></i> Document généré par l'application EasyMarket.</p>
        </section>
    </main>
</body>
</html>
