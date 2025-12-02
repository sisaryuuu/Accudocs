<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Logging Out - Accudocs</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .logout-container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            margin: 0 auto 1.5rem;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        h2 {
            color: #333;
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
        }
        
        p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner"></div>
        <h2>Logging Out</h2>
        <p>Securing your session...</p>
    </div>

    <script>
        // Simple cleanup and redirect
        setTimeout(function() {
            // Clear any sensitive session storage
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith('autoComplete_')) {
                    sessionStorage.removeItem(key);
                }
            });
            
            // Redirect securely
            window.location.replace('{{ $redirectUrl }}');
        }, 1000);
    </script>
</body>
</html>