<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ $csrfToken }}">
    <title>{{ $pageTitle }} - EasyMarket</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#2f7d69">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    {!! $css !!}
</head>
<body>
    <div
        id="dashboard-app"
        data-business-id="{{ $businessId }}"
        data-csrf-token="{{ $csrfToken }}"
        data-section="{{ $section }}"
    ></div>
    {!! $js !!}
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
