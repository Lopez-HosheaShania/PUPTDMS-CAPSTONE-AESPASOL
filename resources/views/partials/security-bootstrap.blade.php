{{-- Loaded before page scripts that build HTML. Values are data, never executable handlers. --}}
<meta name="csp-nonce" property="csp-nonce" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" content="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    window.uiAttribute = function(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    };
</script>
@vite(['resources/css/csp-utilities.css', 'resources/js/security.js'])
