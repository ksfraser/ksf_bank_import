<!DOCTYPE html>
<html>
<head>
    <title>Unauthorized Access</title>
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            font-family: Arial, sans-serif;
        }
        .error-code {
            font-size: 72px;
            color: #e74c3c;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            color: #2c3e50;
            margin: 20px 0;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-link:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">403</h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'Unauthorized Access') ?></p>
        <a href="/" class="back-link">Return to Homepage</a>
    </div>
</body>
</html>