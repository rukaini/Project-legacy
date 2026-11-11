<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Declined - Al Amin HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #eef9f2 0%, #e0f2e9 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .decline-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .btn-home {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="decline-card">
        <div class="success-icon">📝</div>
        <h1 class="text-danger">Offer Declined</h1>
        <p>You have declined the offer letter.</p>
        <p>We appreciate your interest in Al Amin HR. Feel free to apply again in the future.</p>
        <a href="{{ url('/') }}" class="btn-home">Back to Home</a>
    </div>
</body>
</html>