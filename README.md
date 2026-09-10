# arutiun.ru — GitHub Pages build

This repository publishes the rebuilt Russian-language website at:

https://webproduksjon-ops.github.io/arutiun/

The public root is a Vite-built static site using client-side routes under `/arutiun/`. `404.html` is an SPA fallback for GitHub Pages refreshes. `robots.txt` and `sitemap.xml` are included for the current deployment URL.

The legacy `php/` and `data/` directories are retained for reference. GitHub Pages does not execute PHP; the contact form in this static build is a front-end presentation and needs a separate secure form endpoint before production lead capture.
