<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.security-bootstrap')
    @vite('resources/css/vendor-fonts.css')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing out...</title>

    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(ellipse at center, #7a1a00 0%, #3d0000 40%, #1A0505 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }

        .card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 2.5rem;
            backdrop-filter: blur(15px);
            max-width: 400px;
            width: 90%;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top: 4px solid #F0C040;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        h2 {
            font-weight: 800;
            margin-bottom: 0.8rem;
        }

        p {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.85);
        }
    </style>
</head>
<body>

<div class="card">
    <div class="spinner"></div>
    <h2>Signing you out...</h2>
    <p>Please wait while we securely close your sign-in session.</p>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    window.addEventListener('load', async function () {
        const logoutTargets = @json($logoutTargets ?? []);
        const redirectUrl = @json($redirectUrl);
        const loginRedirectUrl = @json($loginRedirectUrl ?? null);

        const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        const hiddenFrameLogout = (url) => new Promise((resolve) => {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.referrerPolicy = 'no-referrer';
            iframe.onload = () => resolve();
            iframe.onerror = () => resolve();
            document.body.appendChild(iframe);
            iframe.src = url;

            setTimeout(resolve, 1200);
        });

        for (const url of logoutTargets) {
            try {
                await hiddenFrameLogout(url);
                await wait(400);
            } catch (e) {
                console.error('Background IdP logout attempt failed:', e);
            }
        }

        await wait(500);
        window.location.replace(redirectUrl || loginRedirectUrl || '/login');
    });
</script>

</body>
</html>
