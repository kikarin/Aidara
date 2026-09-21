<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $resolvedTheme ?? 'dispora' }}" @class(['dark' => ($resolvedTheme ?? 'dispora') === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2e7d32">
        <meta name="msapplication-TileColor" content="#2e7d32">
        <meta name="msapplication-TileImage" content="/mstile-150x150.png">
        <meta name="google-site-verification" content="IoRqhZpr_2AWDqs7GI9kGGcvNCXmYbsTz-QMC0DCA70">

        <!-- FontAwesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        {{-- Inline script to resolve theme immediately (no flash) --}}
        <script>
            (function() {
                const valid = ['light', 'slate', 'warm', 'sport', 'dispora', 'dark'];
                const appearance = @json($appearance ?? 'dispora');
                let resolved = appearance;

                if (appearance === 'default') {
                    resolved = 'light';
                } else if (appearance === 'system') {
                    resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'dispora';
                } else if (!valid.includes(appearance)) {
                    resolved = 'dispora';
                }

                document.documentElement.dataset.theme = resolved;

                if (resolved === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(0.97 0.018 145);
            }

            html[data-theme='light'] {
                background-color: oklch(0.985 0.002 264);
            }

            html[data-theme='slate'] {
                background-color: oklch(0.94 0.012 250);
            }

            html[data-theme='warm'] {
                background-color: oklch(0.96 0.012 85);
            }

            html[data-theme='sport'] {
                background-color: oklch(0.95 0.02 155);
            }

            html[data-theme='dispora'] {
                background-color: oklch(0.97 0.018 145);
            }

            html.dark,
            html[data-theme='dark'] {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'AIDARA') }}</title>

        <link rel="manifest" href="/site.webmanifest">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#2e7d32">

        <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
        <link rel="dns-prefetch" href="https://api.fontshare.com">
        <link rel="preload" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700,900&display=swap" as="style">
        <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700,900&display=swap" rel="stylesheet">

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
