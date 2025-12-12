<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="$OfflineBackgroundColor">
    <title>$OfflineTitle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: $OfflineBackgroundColor;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: $OfflineTextColor;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 400px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 24px;
            opacity: 0.9;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
            color: $OfflineAccentColor;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.8;
            margin-bottom: 32px;
        }
        .retry-btn {
            display: inline-block;
            padding: 12px 32px;
            background: $OfflineAccentColor;
            color: $OfflineTextColor;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .retry-btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">$OfflineIcon</div>
        <h1>$OfflineTitle</h1>
        <p>$OfflineMessage</p>
        <button class="retry-btn" onclick="window.location.reload()">$OfflineButtonText</button>
    </div>
</body>
</html>
