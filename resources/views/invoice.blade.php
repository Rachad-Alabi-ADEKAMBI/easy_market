<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facture __SALE_NUMBER__ - EasyMarket</title>
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
        h1, h2, p { margin-top: 0; }
        .muted { color: var(--muted); }
        .invoice-box { text-align: right; }
        .meta { margin-top: 22px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .meta-card { border: 1px solid var(--line); border-radius: 8px; padding: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border-bottom: 1px solid var(--line); padding: 12px 8px; text-align: left; }
        th { color: var(--primary); font-size: 13px; }
        .totals { margin-top: 18px; display: grid; justify-content: end; }
        .totals-row { min-width: 290px; display: flex; justify-content: space-between; gap: 20px; padding: 8px 0; }
        .grand { border-top: 2px solid var(--primary); font-size: 20px; font-weight: 500; }
        .footer { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); display: grid; gap: 12px; justify-items: center; color: var(--muted); text-align: center; font-size: 12px; }
        #qrcode { width: 120px; height: 120px; }
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
        <strong><i class="fa-solid fa-file-invoice"></i> Facture __SALE_NUMBER__</strong>
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button>
    </div>

    <main class="sheet">
        <section class="header">
            <div>
                <div class="brand">
                    <div class="logo">EM</div>
                    <div>
                        <h1>__BUSINESS__</h1>
                        <p class="muted">__SLOGAN__</p>
                    </div>
                </div>
                <p class="muted">Téléphone : __PHONE__<br>Adresse : __ADDRESS__<br>IFU : __IFU__</p>
            </div>
            <div class="invoice-box">
                <h2>Facture</h2>
                <p><strong>__SALE_NUMBER__</strong><br>__SALE_DATE__</p>
            </div>
        </section>

        <section class="meta">
            <div class="meta-card"><strong>Mode de paiement</strong><br><span class="muted">__PAYMENT__</span></div>
            <div class="meta-card"><strong>Authenticité</strong><br><span class="muted">QR Code de vérification</span></div>
            <div class="meta-card"><strong>Application</strong><br><span class="muted">EasyMarket</span></div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    <th>Remise</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                __ITEM_ROWS__
            </tbody>
        </table>

        <section class="totals">
            <div class="totals-row"><span>Sous-total</span><strong>__SUBTOTAL__</strong></div>
            <div class="totals-row"><span>Remise</span><strong>__DISCOUNT__</strong></div>
            <div class="totals-row grand"><span>Total</span><strong>__TOTAL__</strong></div>
        </section>

        <section class="footer">
            <canvas id="qrcode"></canvas>
            <p>Document généré par l'application Easy_Market.</p>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
    <script>
        QRCode.toCanvas(document.getElementById('qrcode'), decodeURIComponent('__QR_PAYLOAD__'), {
            width: 120,
            margin: 1
        });
    </script>
</body>
</html>
