<!DOCTYPE html>

<html lang="en">
<head>
    @include('partials.security-bootstrap')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Dental System...</title>

```
<style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background: linear-gradient(135deg, #8B0000, #660000);
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-family: 'Inter', sans-serif;
        overflow: hidden;
    }

    .logo {
        width: 110px;
        margin-bottom: 20px;
        animation: pulse 2s ease-in-out infinite;
        filter: drop-shadow(0 0 15px rgba(255,255,255,0.25));
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.85; }
        50% { transform: scale(1.08); opacity: 1; }
        100% { transform: scale(1); opacity: 0.85; }
    }

    .title {
        color: #FFD700;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: 1px;
    }

    .loading-text {
        color: #ffffff;
        font-size: 14px;
        letter-spacing: 2px;
        opacity: 0.8;
        animation: fade 1.5s ease-in-out infinite;
    }

    @keyframes fade {
        0% { opacity: 0.4; }
        50% { opacity: 1; }
        100% { opacity: 0.4; }
    }
</style>
```

</head>
<body>

```
<img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="PUP Dental" class="logo">

<div class="title">PUP Dental Management System</div>
<div class="loading-text">Redirecting...</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    setTimeout(function () {
        window.location.href = "{{ $redirectUrl }}";
    }, 1500);
</script>
```

</body>
</html>
