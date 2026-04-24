# PHP SmartBlocker 1.0

Keep in mind I designed this to work with Sngine Social Network. This code will work on any PHP website. Keep in mind that a real spammer human can solve the puzzle
register and post spam, So I suggest you website have new user approval, Posts approval or an option to make the site by invitation only whick Sngine Socual Network
does all of this. The code was made due to the onslot of attacks Sngine Social Network is hit with 24/7. In my case I use this code along with Invitation Only enabled.

In any case on a normal PHP website with registration enabled this will stop every automated attack and registration period. Keep in mind they the web is loaded with
click farms where a user is paid pennies to create a spam account on your server. BASICALLY you can say 99% is all stopped that 1% is thos humans being paid to spam your
website. An example you might get hundreds of fake visits per hour NONE WILL EVER GET THROUGHT except that 1% I would recomment every php website use this code period.

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
- APCu PHP extension enabled (see instructions below)
- Two files uploaded to your web root
- One line added to .htaccess

## Enabling APCu in cPanel

APCu is required for the 3-strike permanent IP ban counter. Without it the puzzle still works but failed attempts won't be tracked per IP.

1. Log into cPanel
2. Go to Software → Select PHP Version
3. Make sure you are on the correct PHP version your site uses
4. Click PHP Extensions
5. Find apcu in the list and check the box to enable it
6. Click Save

If you do not see APCu in the list contact your hosting provider and ask them to enable the APCu PHP extension for your account.

> **Note:** Make sure you enable APCu on the same PHP version your site is actually running. You can verify your PHP version in cPanel → Select PHP Version → the current version is shown at the top.

## Files

- `blocks.php` — the main bot blocker, upload to your web root
- `verify_overlay.php` — the drag-and-drop puzzle CAPTCHA page, upload to your web root

## Installation

1. Upload both files to your website root directory
2. Enable APCu in cPanel as described above
3. Add this one line to your .htaccess file:

```
php_value auto_prepend_file /home/YOUR_USERNAME/public_html/blocks.php
```

Replace `YOUR_USERNAME` with your own cPanel username. You can find your exact path in cPanel → File Manager → navigate to public_html → the address bar shows your full path.

4. Open your site in an incognito window — the drag-and-drop puzzle should appear immediately

That's it. PHP SmartBlocker is now protecting every page on your site.

## Managing Bans

Banned IPs are stored in `blocked_ips.json` in your web root. This file is created automatically when the first ban occurs.

**To clear all bans:**
- Open cPanel → File Manager
- Navigate to your web root
- Delete `blocked_ips.json`
- PHP will auto-create a fresh empty one on the next request

**To remove a single IP ban:**
- Open `blocked_ips.json` in File Manager editor
- Find the IP entry and delete it
- Make sure the JSON stays valid (no trailing commas)
- Save the file

## License

Free to use, modify and share.
