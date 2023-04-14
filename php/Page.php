<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Page
{
    public static function render(string $title, string $html): string
    {
        $titleEscaped = htmlspecialchars($title);

        return <<<_html
            <!DOCTYPE html>
            <html class="h-100" lang="en">
            <head>
                <meta charset="UTF-8">
                <title>{$titleEscaped}</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐘</text></svg>">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
                <script>
                    (() => {
                        function getPreferredTheme() {
                            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                        }
            
                        function setTheme(theme) {
                            document.documentElement.setAttribute('data-bs-theme', theme);
                        }
            
                        setTheme(getPreferredTheme());
            
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                            setTheme(getPreferredTheme());
                        })
                    })();
                </script>
            </head>
            <body class="d-flex flex-column h-100">
            <header>
                <nav class="navbar bg-body-tertiary">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="/">🐘 STV results</a>
                    </div>
                </nav>
            </header>
            <main class="flex-shrink-0">
                <div class="container pt-3">{$html}</div>
            </main>
            <footer class="footer mt-auto">
                <nav class="navbar bg-body-tertiary">
                    <div class="container-fluid">
                        <span class="navbar-text">
                          Created with ❤️ by Theodore Brown
                        </span>
                        <div class="navbar-nav">
                            <a class="nav-link text-decoration-underline" href="https://github.com/theodorejb/php-stv">Source</a>
                        </div>
                    </div>
                </nav>
            </footer>
            </body>
            </html>
        _html;
    }
}
