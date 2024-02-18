# 🧪 BioGazelle

This software is twice removed from the original [What.cd Gazelle](https://github.com/WhatCD/Gazelle).
It's based on the security hardened PHP7 fork [Oppaitime Gazelle](https://github.com/biotorrents/oppaiMirror).
It shares several features with [Orpheus Gazelle](https://github.com/OPSnet/Gazelle) and incorporates certain innovations by [AnimeBytes](https://github.com/anniemaybytes).
The goal is to organize a functional database with pleasant interfaces, and render insightful views using data from robust external sources.

# Changelog: Bio ← OT

Please find a running list of major software improvements below.
This list is by no means exhaustive; it's a best hits compilation.
The points are presented in no particular order.

## Built to scale, micro or macro

BioGazelle is pretty fast out of the box, on a single budget VPS.
If you want to scale horizontally, the software supports both [Redis clusters](app/Cache.php) and [database server replication](app/Database.php).
Please note that Redis clusters expect at least three nodes.
This lower limit is inherent to Redis' [cluster implementation](https://redis.io/docs/management/scaling/).

### Universal database id's

BioGazelle is in the process of migrating to [UUID v7 primary keys](https://uuid.ramsey.dev/en/stable/rfc4122/version7.html) to enable useful content-agnostic operations such as tagging and AI integration.
This will consolidate the database and allow for powerful cross-object association.
The UUIDs are stored as binary strings for index speed and to minimize disk usage.
By the way, *all* binary data is transparently converted by the [database wrapper](app/Database.php).

## Full stack search engine rewrite

Data indexing is important, so BioGazelle has upgraded to [Manticore Search](https://manticoresearch.com), the successor to Sphinx.
This upgrade also involved a [rewrite of the search configuration](utilities/config/manticore.conf) from scratch, based on AnimeBytes' example.
The Gazelle frontend itself uses a [rewritten browse.php controller](sections/torrents/browse.php) and a [brand new Twig template](templates/torrents/search.twig).
Oh yeah, the [PHP backend class](app/Manticore.php) is also completely rewritten, replacing at least four legacy classes.

## Secure authentication system

The user handling, including registration, logins, etc., has been rewritten into a unified system in the [Auth class](app/Auth.php).
The system acts as an oracle that takes inputs and returns messages.
Passphrase hashing is all done with `PASSWORD_DEFAULT`, ready for Argon2id.

I tested this extensively and determined that prehashing passphrases was no good.
Not only it is impossible upgrade the algorithm, e.g., from `sha256` to `sha3-512`, but prehashing lowers the total entropy of long strings even if binary is used throughout.
Test it yourself with 72 bytes of random binary data (the `bcrypt` max) and an entropy calculator.

BioGazelle enforces a 15-character minimum passphrase length and imposes no other limitations.
This is consistent with the list of [OWASP best practices](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html).
In fact, the whole class is informed by this document.

### Bearer token authorization

[Read the API documentation.](https://docs.torrents.bio)
API tokens can be generated in the [user security settings](templates/user/settings/settings.twig) and used with the JSON API.
[Internal API calls](app/Api/Internal.php) for Ajax and such use a special token that can safely be exposed to the frontend.
It's based on hashing a [rotating server secret](utilities/crontab/hourly/siteApiSecret.php) concatenated with a secure session cookie.

The session cookies themselves are tight, btw.
No JavaScript access, scoped to the same site, long length, etc.
This kind of stuff is in the [low level Http class](app/Http.php).

### WebAuthn security tokens

BioGazelle has always supported hardware keys thanks to Oppaitime.
But we took it up a notch by upgrading this system to use the [modern WebAuthn standard](app/WebAuthn/Base.php) instead of the deprecated FIDO U2F standard.
[This specification](https://webauthn.guide) is well supported in all major browsers, and it doesn't require a $50 dongle:
use a hardware key, a smartphone fingerprint or QR code reader, or just generate a key in the browser.
The underlying library is the canonical [web-auth/webauthn-lib](https://github.com/web-auth/webauthn-lib).

## OpenAI integration

One of BioGazelle's goals is to place data in context using [OpenAI's completions API](app/OpenAI.php) to generate tl;dr summaries and tags from content descriptions.
Just paste your abstract into the torrent group description and get a succinct natural language summary with tags.
It's possible to disable AI content display in the user settings.

## Twig template system

[BioGazelle's Twig interface](app/Twig.php) takes cues from OPS's extended filters and functions.
Twig provides a security benefit by escaping rendered output, and a secondary benefit of clarifying the PHP running the site sections.
Everything you could need is a globally available template variable.

A quick note about template inheritance: everything extends a clean HTML5 base template.
Torrent, collections, requests, etc., and their respective sidebars are implemented as semantic HTML5 in easily digestible chunks of content.
No more mixed PHP code and HTML markup, at least in new development!

### Markdown and BBcode support

BioGazelle uses the [SimpleMDE markdown editor](https://simplemde.com) with a reasonably extended [custom editor interface](templates/_base/textarea.twig).
All the Markdown Extra features supported by [Parsedown Extra](https://github.com/erusev/parsedown-extra) are documented and the useful ones are exposed in the editor.
The default recursive regex BBcode parser (yuck) is replaced by [Vanilla NBBC](https://github.com/vanilla/nbbc).
Parsed texts are cached for speed, using both Redis and the Twig disk cache.

### Literate programming in the wiki

BioGazelle uses [Starboard Notebook](https://starboard.gg) to support [Jupyter Notebooks](https://jupyter.org) in the browser!
This lets users document technical topics such as data processing workflows complete with executable code examples and Latex expressions.
Our secure implementation leverages sanboxed iframes on a dedicated subdomain to ensure no cookie or local storage leaks.

### Good typography

BioGazelle supports an array of [unobtrusive fonts](resources/scss/assets/fonts.scss) with the appropriate glyphs for bold, italic, and monospace.
These options are available to every theme.
Font Awesome 5 is also universally available, as is the [entire Material Design color palette](resources/scss/assets/colors.scss).
[Download the fonts to get started.](https://torrents.bio/fonts.tgz)
Also, there are two simple color modes, [calm mode and dark mode](resources/scss/global/colors.scss), that I like to think are pleasing to the eye.

## Active data minimization

BioGazelle has [real lawyer-vetted policies](templates/siteText/legal).
In the process of matching the tech to the legal word, I dropped support for a number of compromising features:

- Bitcoin, PayPal, and currency exchange API and system calls;
- Bitcoin addresses, user donation history, and similar metadata; and
- IP address and geolocation, email address, passphrase, and passkey history.

The software license is also updated to [OpenBSD's license template](https://www.openbsd.org/policy.html) instead of the potentially unenforceable Unlicense.
We seek to make our original code available to all with as few restrictions as possible.
Besides that, BioGazelle has several passive developments in progress:

- prepare all queries with parameterized statements;
- declare strict mode at the top of every PHP and JS file;
- check strict equality and strong typing, including function arguments;
- run all files through generic formatters such as PHP-CS-Fixer; and
- move all external libraries to uncomplicated package management.

## Proper application layout

BioGazelle takes cues from the best-of-breed PHP framework Laravel, to a carefully measured extent.
The source code is reorganized along Laravel's lines while maintaining the comfy familiarity of OT/WCD Gazelle.
The app logic, config, cron jobs, Git repo, etc., lie outside the web root for better security.

BioGazelle uses the Flight router to define app routes, implementing clean URIs and centralized middleware.
An ongoing project involves modernizing the app based on Laravel's tools, with help from lighter personally-vetted libraries.

### App singleton

[The main site configuration](config/public.php) implements recursive [Laravel Collections](https://laravel.com/docs/master/collections) with the [ENV special class](app/ENV.php).
Also, the whole app is always instantly available: the config, database, cache, current user, Twig engine, etc., are accessible with a simple call to `Gazelle\App::go()`.
All such objects use the same quick and easy go → factory → thing API, just in case you need to extend some core object without headaches.

### Expressive object-oriented API

Core site data are also implemented as `RecursiveCollection` objects that adhere to the JSON:API specification.
So not only are data accessed with a powerfully expressive API, their representation is standardized across clients.

### Decent debugging

BioGazelle seeks to be easy and fun to develop.
I collected the old debug class monstrosity into a nice little bar.
There's also no more `DEBUG_MODE` or random permissions, just a simple toggle on `$app->env->dev`.

The entire app is also available on the command line for cron jobs, development, and fun.
Just run `php shell` from the repository root to get up and running.
This is based on Laravel Tinker and in fact uses the same REPL under the hood.

## Minor changes

- database crypto bumped up to AES-256
- good subresource integrity support
- configurable HTTP status code errors
- integrated diceware passphrase generator
- semantic HTML5 templates and layouts (WIP)
- dead simple PDO database wrapper, fully parameterized
- polite copy; the site says "please" and "thank you"
- the codebase runs on PHP8 with minimal warnings
- all database queries that are rewritten are usually simpler
- no need to think about cache collisions across environments
- a small amount of Eloquent models for core schema objects
- authenticated email over STARTTLS with external server support

## Features inherited from Oppaitime

- [integrated database encryption via APCu](app/Crypto.php)
- 2FA (QR code) and U2F (hardware key) support for user accounts
- unique torrent `info_hash` support with a randomized `source`
- [resource proxying](https://github.com/biotorrents/image-host) that's expanded to support WebP and JPEG XL
- [site schedule](sections/schedule) system for running certain tasks via cron
- bonus points and the [corresponding store section](sections/store)
- native HTTP/2 support with the expectation of TLSv1.3+
- custom stylesheet modifications on a per-user basis

# Gracie Gazelle

![Gracie Gazelle](public/images/mascot.webp)

Gracie is a veteran pirate of the digital ocean.
On land, predators form companies to hunt down prey.
But in the lawless water, the prey attacks the predators' transports.
Gracie steals resources from the rich and shares them with the poor and isolated people.
Her great eyesight sees through the darkest corners of the internet for her next target.
Her charisma attracts countless salty goats to join her fleet.
She proudly puts the forbidden share symbols on her hat and belt, and is now one of the most wanted women in the world.

## Tyson Tan

Character design and bio by Tyson Tan, who offers mascot design services for free and open source software, free of charge, under a free license.
[Download the high resolution version.](public/images/mascotFullVersion.webp)

[tysontan.com](https://tysontan.com) / <tysontan@tysontan.com> / [@TysonTanX](https://twitter.com/tysontanx)
