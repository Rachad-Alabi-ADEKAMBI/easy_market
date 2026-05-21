<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="__CSRF_TOKEN__">
    <title>Tableau de bord - EasyMarket</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#2f7d69">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    __APP_CSS__
</head>
<body>
    <div
        id="dashboard-app"
        data-business-id="__BUSINESS_ID__"
        data-csrf-token="__CSRF_TOKEN__"
        data-section="__DASHBOARD_SECTION__"
    ></div>
    __APP_JS__
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
