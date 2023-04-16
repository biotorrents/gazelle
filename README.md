# 🧪 BioGazelle

This software is twice removed from the original
[What.cd Gazelle](https://github.com/WhatCD/Gazelle).
It's based on the security hardened PHP7 fork
[Oppaitime Gazelle](https://github.com/biotorrents/oppaiMirror).
It shares several features with
[Orpheus Gazelle](https://github.com/OPSnet/Gazelle).
The goal is to organize a functional database with pleasant interfaces,
and render insightful views using data from robust external sources.

# Changelog: Bio ← OT

## Built to scale

BioGazelle is pretty fast out of the box, on a single budget VPS.
If you want to scale horizontally, the software supports both
[Redis clusters](app/Cache.php) and
[database server replication](app/Database.php).
Please note that Redis clusters expect at least three nodes.
This lower limit is inherent to Redis' cluster implementation.

## Full stack search engine rewrite

Data indexing is important, so BioGazelle has upgraded to
[Manticore Search](https://manticoresearch.com),
the successor to Sphinx.
This upgrade also involved a
[rewrite of the search configuration](utilities/config/manticore.conf)
from scratch, based on AnimeBytes' example.
The Gazelle frontend itself uses a
[rewritten browse.php controller](sections/torrents/browse.php) and a
[brand new Twig template](templates/torrents/search.twig).

## Bearer token authorization

[API Docs](https://docs.torrents.bio).
API tokens can be generated in the
[user security settings](sections/user/token.php)
and used with the JSON API.

## Routing system

BioGazelle uses the Flight router to define app routes.
Features include clean URIs and centralized middleware.

## OpenAI integration

One of BioGazelle's major goals is to place data in context using
[OpenAI's completions API](https://beta.openai.com/docs/api-reference/completions)
to generate tl;dr summaries and tags from torrent descriptions.
Just paste your abstract into the torrent group description
and get a succinct natural language summary with torrent and SEO tags.
It's possible to disable AI content display in the user settings, btw.

## Good typography

BioTorrents.de supports an array of
[unobtrusive fonts](resources/scss/assets/fonts.scss)
with the appropriate bold/italic glyphs and monospace.
These options are available to every theme.
Font Awesome 5 is also universally available.
[Download the fonts](https://torrents.bio/fonts.tgz).
Also, there are two simple color modes,
[calm mode and dark mode](resources/scss/global/colors.scss),
that I like to think are pleasing to the eye.

## Markdown and BBcode support

[SimpleMDE markdown editor](https://simplemde.com)
with extended custom editor interface.
All the Markdown Extra features supported by
[Parsedown Extra](https://github.com/erusev/parsedown-extra)
are documented and the useful ones exposed in the editor interface.
The default recursive regex BBcode parser is replaced by
[Vanilla NBBC](https://github.com/vanilla/nbbc).
Parsed texts are cached for speed.

## App singleton

[The main site configuration](config/public.php)
uses extensible ArrayObjects with by the
[ENV special class](app/ENV.php).
Also, the whole app is always instantly available:
the config, database, cache, current user, Twig engine, etc.,
are accessible with a simple call to `Gazelle\App::go()`.

## Twig template system

[BioGazelle's Twig interface](app/Twig.php)
takes cues from OPS's extended filters and functions.
Twig provides a security benefit by escaping rendered output,
and a secondary benefit of clarifying the PHP running the site sections.
Everything you could need is a globally available template variable.

A quick note about template inheritance.
Everything extends a clean HTML5 base template.
Torrent, collections, requests, etc., and their respective sidebars
are implemented as semantic HTML5 in easily digestible chunks of content.
No more mixed PHP code and HTML markup!

## Active data minimization

BioTorrents.de has
[real lawyer-vetted policies](templates/siteText/legal).
In the process of matching the tech to the legal word,
we dropped support for a number of compromising features:

- Bitcoin, PayPal, and currency exchange API and system calls;
- Bitcoin addresses, user donation history, and similar metadata; and
- IP address and geolocation, email address, passphrase, and passkey history.

Besides that, BioGazelle has several passive developments in progress:

- prepare all queries with parameterized statements;
- declare strict mode at the top of every PHP and JS file;
- check strict equality and strong typing, including function arguments;
- run all files through generic formatters such as PHP-CS-Fixer; and
- move all external libraries to uncomplicated package management.

## Proper application layout

BioGazelle takes cues from the best-of-breed PHP framework Laravel.
The source code is reorganized along Laravel's lines while maintaining the comfy familiarity of OT/WCD Gazelle.
The app logic, config, and Git repo lies outside the web root for enhanced security.
An ongoing project involves modernizing the app based on Laravel's excellent tools.

## Decent debugging

BioGazelle seeks to be easy and fun to develop.
We're collecting the old debug class monstrosity into a nice little bar.
There's also no more `DEBUG_MODE` or random permissions.
There's just a dev mode that spits everything out, and a prod mode that doesn't.

## Minor changes

- database crypto bumped up to AES-256
- good subresource integrity support
- configurable HTTP status code errors
- integrated diceware passphrase generator
- semantic HTML5 templates and layouts (WIP)
- single entry point for app init
- Laravel-inspired shell (`php shell`)
- dead simple PDO database wrapper, fully parameterized
- polite copy; the site says "please" and "thank you"
- the codebase runs on PHP8 with minimal warnings

## Features inherited from Oppaitime

- [integrated database encryption via APCu](app/Crypto.php)
- 2FA (QR code) and U2F (hardware key) support for user accounts
- unique torrent `info_hash` support with a randomized `source`
- [resource proxying](https://github.com/biotorrents/image-host) that's expanded to support WebP and JPEG XL
- [site schedule](sections/schedule) system for running certain tasks via cron
- bonus points and the [corresponding store section](sections/store)
- native HTTP/2 support with the expectation of TLSv1.2+
- custom stylesheet modifications on a per-user basis

# Gracie Gazelle

![Gracie Gazelle](public/images/mascot.png)

Gracie is a veteran pirate of the digital ocean.
On land, predators form companies to hunt down prey.
But in the lawless water, the prey attack the predators' transports.
Gracie steals resources from the rich and shares them with the poor and isolated people.
Her great eyesight sees through the darkest corners of the internet for her next target.
Her charisma attracts countless salty goats to join her fleet.
She proudly puts the forbidden share symbols on her hat and belt, and is now one of the most wanted women in the world.

## Tyson Tan

Character design and bio by Tyson Tan, who offers mascot design services for free and open source software, free of charge, under a free license.
[Download the high resolution version.](public/images/mascotFullVersion.png)

[tysontan.com](https://tysontan.com) / <tysontan@tysontan.com> / [@tysontanx](https://twitter.com/tysontanx)
