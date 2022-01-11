# BioTorrents.de Gazelle

This software is twice removed from the original
[What.cd Gazelle](https://github.com/WhatCD/Gazelle).
It's based on the security hardened PHP7 fork
[Oppaitime Gazelle](https://git.oppaiti.me/Oppaitime/Gazelle).
It shares several features with
[Orpheus Gazelle](https://github.com/OPSnet/Gazelle).
The goal is to organize a functional database with pleasant interfaces,
and render insightful views using data from robust external sources.

# Changelog: OT → Bio

## Bearer token authorization

[API Docs](https://docs.biotorrents.de).
API tokens can be generated in the
[user security settings](sections/user/token.php)
and used with the JSON API.

## Good typography

BioTorrents.de supports an array of
[unobtrusive fonts](static/styles/assets/scss/fonts.scss)
with the appropriate bold/italic glyphs and monospace.
These options are available to every theme.
Font Awesome 5 is also universally available.
[Download the fonts](https://torrents.bio/fonts.tgz).

## Markdown support

[SimpleMDE markdown editor](https://simplemde.com)
with extended custom editor interface.
All the Markdown Extra features supported by
[Parsedown Extra](https://github.com/erusev/parsedown-extra)
are documented and the useful ones exposed in the editor interface.
Support for the default Gazelle recursive regex BBcode parser.

## $ENV recursive singleton

[The site configuration](classes/config.template.php)
is being migrated to a format govered by the
[ENV special class](classes/env.class.php)
for modified recursive ArrayObjects.

## Twig template system

Similar to ENV, the
[Twig interface](classes/twig.class.php)
operates as a singleton because it's an external module with its own cache.
Twig provides a security benefit by escaping rendered output,
and a secondary benefit of clarifying the PHP running the site sections.
Several custom filters are available from OPS.

## Active data minimization

BioTorrents.de has
[real lawyer-vetted policies](templates/legal).
In the process of matching the tech to the legal word,
we dropped support for a number of compromising features:

- Bitcoin, PayPal, and currency exchange API and system calls;
- Bitcoin addresses, user donation history, and similar metadata; and
- IP address and geolocation, email address, passphrase, and passkey history.

Besides that, BioTorrents has several passive developments in progress:

- prepare all queries with parameterized statements;
- declare strict mode at the top of every PHP and JS file;
- check strict equality and strong typing, including function arguments;
- run all files through generic formatters such as PHP-CS-Fixer; and
- move all external libraries to uncomplicated package management.

## Proper application layout

Bio Gazelle takes cues from the best-of-breed PHP framework Laravel.
The source code is reorganized along Laravel's lines while maintaining the comfy familiarity of OT/WCD Gazelle.
The app logic, config, and Git repo lies outside the web root for enhanced security.
An ongoing project involves modernizing the app based on Laravel's excellent tools.

## Decent debugging

Bio Gazelle seeks to be easy and fun to develop.
We're collecting the old debug class monstrosity into a nice little bar.
There's also no more `DEBUG_MODE` or random permissions.
There's just a dev mode that spits everything out, and a prod mode that doesn't.

## Minor changes

- Database crypto bumped up to AES-256
- Good subresource integrity support
- Configurable HTTP status code errors
- Integrated diceware passphrase generator
- TLS database connections
- Semantic HTML5 themes (WIP)
- Single entry point for app init

# Changelog: WCD → OT

## Integrated Database Encryption

Using a database key [provided by staff](sections/tools/misc/database_key.php) and only ever stored as a hash in memory (via APCu), the [integrated database encryption](classes/crypto.class.php) is used to encrypt sensitive user data like IP addresses, emails, and private messages regardless of the underlying system gazelle is running on.

The rest of gazelle must be aware that some of the data it fetches from the DB is encrypted, and must have a fallback if that data is unavailable (the key is not in memory). You will see plenty of `if (!apcu_exists('DBKEY')) {` in this codebase.

## Two-Factor Authentication

Despite our other (less intrusive) methods of protecting user accounts being more than sufficient for virtually all feasible attacks, we also ship optional 2FA should users feel the need to enable it.

## Universal 2nd Factor

Support for physical U2F tokens has also been added as an optional alternative to normal 2FA. U2F allows users to protect their account with something less likely to be lost or erased than 2FA keys stored on a phone.

## Unique Infohashes

Upon upload, torrent files are modified to contain a "source" field in the info dict containing the concatination of the site name and some generated junk data (unique per-torrent). This prevents infohash collisions with torrents cross-seeded from other sites in the same client, and also helps protect against some not particularly likely peer-leaking attacks.

## Resource Proxying

All external resources that may appear on a page are fetched and served by the server running gazelle. This prevents the leak of user information to third parties hosting content that has been included on a page through an image tag or similar.

## Scheduler

The [scheduler](sections/schedule) has been broken up into more manageable parts and has additional selective runtime features for manual execution.

## Bonus Points

Like most gazelle forks, we've added a [bonus point system](sections/schedule/hourly/bonus_points.php) and [store](sections/store).

## Modern password hashing

We use modern PHP password hashing features that automatically rehash your password when a better hashing algorithm is made available and employ prehashing to allow you to use a secure password of any length. Original gazelle would effectively truncate your password after around 72 characters (if the tracker even allowed you to use a password that long). This codebase does not have the same problem, and allows passwords of virtually unlimited length (over 30,000 characters by default) that remain useful after a few tens of characters.

## Minor Changes

- When a torrent is trumped, the new torrent is made freeleech to users who snatched the old torrent for a few days.
- Sends headers to tell cloudflare to use HTTP/2 Server Push for most resources.
- Support for optional per-user stylesheet additions and tweaks
- This codebase expects to run over https only.

# Mascot

![Gracie Gazelle](public/images/mascot.png)

**Gracie Gazelle**

Gracie is a veteran pirate of the Digital Ocean. On land, predators form companies to hunt down prey. But in the lawless water, prey attack the predators' transports. Gracies steals resources from the rich and shares them with the poor and isolated people. Her great eyesight sees through the darkest corners of the Internet for her next target. Her charisma attracts countless salty goats to join her fleet. She proudly puts the forbidden share symbols on her hat and belt, and is now one of the most wanted women in the world.

High resolution downloads [here](https://git.oppaiti.me/Oppaitime/Gazelle/issues/34#issuecomment-99)

Character design and bio by Tyson Tan, who offers mascot design services for free and open source software, free of charge, under a free license.

Contact: [tysontan.com](https://tysontan.com) / <tysontan@mail.com>
