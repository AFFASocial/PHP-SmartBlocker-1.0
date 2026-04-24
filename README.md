# PHP SmartBlocker 1.0
PHP SmartBlocker 1.0 - Zero dependency attack and bot protection with drag-and-drop CAPTCHA

I originally created this code for "Sngine Social Network" but this code can be used
on any PHP website.  For Sngine users thius is a must have for you hosted website.

Just download the RAW file PHP_SmartBlocker_1.0_Installation_Guide.html then click it to
open it in your browser, thenj ust follow the installation guide and in minutes you will
competely block all bad attacks, scrapers, and unwanted visitors to your website.

Universal protection:

Works on any PHP site ✅
Zero external dependencies ✅
Zero ongoing cost ✅
Two files only ✅
One .htaccess line ✅

What it stops:

All automated bots ✅
All scrapers ✅
All content theft ✅
All fake traffic ✅
GA noise ✅
Server resource abuse ✅

What it allows:

Real humans ✅
Google crawling freely ✅
Legitimate traffic ✅

Real world proven results:

Server at 2% CPU ✅
GA traffic clean ✅
Zero bot registrations ✅
Zero scraping ✅

Combined with invitation only on my website

Zero bot spam ✅
Zero human spam ✅
Trusted community only ✅

The code does exactly what it was designed to do, costs nothing, requires no maintenance, works on any PHP site, and has been proven in production.

One thing to note that only a human who sollves the puzzle and registers can then post spam. In my case I made registration Invitation only so no
one without an invitation code can register thus putting and end to the constant ATTACKS all dead in the water.


# PHP SmartBlocker 1.0

A lightweight, zero-dependency PHP bot protection system that stops scrapers, bots, and fake traffic dead in their tracks — all running locally on your server with no external APIs, no paid services, and zero ongoing cost.

## How It Works

PHP SmartBlocker runs as a PHP auto-prepend file, executing before every single page on your site. Every request is filtered through multiple layers in this order:

- Empty or missing user agents are hard blocked with a 403 immediately
- Known legitimate search bots like Googlebot, Bingbot and DuckDuckBot are whitelisted and pass through freely — your SEO is completely unaffected
- Known scraper and bot tools like curl, wget, scrapy, selenium, puppeteer and hundreds more are hard blocked with a 403
- Outdated Chrome versions below 110 used as bot fingerprints are hard blocked
- Any IP previously permanently banned is hard blocked immediately
- Every other visitor is presented with a drag-and-drop puzzle CAPTCHA they must solve before accessing any page

## The Puzzle CAPTCHA

A randomly generated landscape image is split into three columns with one piece missing. Five different visual themes are used — night sky, ocean, forest, desert and mountain — chosen randomly on every visit. The visitor must drag the floating piece into the correct empty slot to proceed. The correct slot position is stored server-side and never exposed in the HTML source, making it impossible for automated tools to bypass without sophisticated computer vision. Works on both desktop with mouse drag and mobile with touch drag.

## After Solving

A 24-hour cookie is set so real visitors only see the puzzle once per day. Three failed attempts results in a permanent IP ban written to blocked_ips.json — banned IPs are rejected before any other processing on future visits.

## Logging

All blocked requests and CAPTCHA challenges are logged to alist.txt with full details including IP, user agent, URL and timestamp. Allowed traffic is not logged to keep the file small. The log automatically trims itself to 5,000 lines when it reaches 1MB so it never grows out of control.

## Real World Results

- Server CPU usage drops to near idle
- Google Analytics data becomes clean real traffic only
- Bot registrations drop to zero
- Content scraping stops completely
- GA noise from fake traffic eliminated completely
- Proven in production on a live social network

## Requirements

- PHP 7.4 or higher
- Apache with .htaccess support
- Two files uploaded to your web root
- One line added to .htaccess

## Optional

- APCu enabled — only needed for the 3-strike permanent IP ban counter. If APCu is not available everything else works perfectly

## Files

- `blocks.php` — the main bot blocker, upload to your web root
- `verify_overlay.php` — the drag-and-drop puzzle CAPTCHA page, upload to your web root

## Installation

Add this one line to your .htaccess file:

```
php_value auto_prepend_file /home/YOUR_USERNAME/public_html/blocks.php
```

Replace `YOUR_USERNAME` with your own cPanel username.

That's it. PHP SmartBlocker is now protecting every page on your site.

## License

Free to use, modify and share.
