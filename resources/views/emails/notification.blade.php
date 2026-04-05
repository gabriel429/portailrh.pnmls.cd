<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; }
        .header { border-bottom: 3px solid #0077B5; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0077B5; font-size: 20px; margin: 0; }
        .content { color: #333; line-height: 1.6; }
        .btn { display: inline-block; background: #0077B5; color: #fff; padding: 10px 24px; border-radius: 4px; text-decoration: none; margin-top: 15px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $titre }}</h1>
        </div>
        <div class="content">
            {!! nl2br(e($contenu)) !!}

            @if($lien)
                <br>
                <a href="{{ url($lien) }}" class="btn">Voir les détails</a>
            @endif
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} E-PNMLS — Programme National Multisectoriel de Lutte contre le Sida
        </div>
    </div>
</body>
</html>
