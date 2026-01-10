<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #3b82f6;
        }
        .content {
            padding: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pro Loco Venticanese</h1>
    </div>
    
    <div class="content">
        <h2>Reset Password</h2>
        <p>Hai ricevuto questa email perché abbiamo ricevuto una richiesta di reset della password per il tuo account.</p>
        
        <p style="text-align: center;">
            <a href="{{ $url }}" class="button">Reimposta Password</a>
        </p>
        
        <p>Questo link scadrà tra {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minuti.</p>
        
        <p>Se non hai richiesto il reset della password, puoi ignorare questa email.</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #6b7280;">
            Se hai problemi a cliccare il pulsante "Reimposta Password", copia e incolla il seguente URL nel tuo browser:<br>
            <a href="{{ $url }}" style="color: #3b82f6;">{{ $url }}</a>
        </p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Pro Loco Venticanese. Tutti i diritti riservati.</p>
    </div>
</body>
</html>
