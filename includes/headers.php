<?php
if (headers_sent()) {
    return;
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com 'unsafe-inline'; " .
    "style-src 'self' cdn.jsdelivr.net fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' fonts.gstatic.com data:; " .
    "img-src 'self' data:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self';"
);
