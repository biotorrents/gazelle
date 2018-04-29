This is Oppaitime's version of Gazelle

Below are some lists of differences between this version of Gazelle and What.cd's. Please note that these lists are far from complete.

## Major Changes

#### Integrated Database Encryption

Using a database key [provided by staff](sections/tools/misc/database_key.php) and only ever stored as a hash in memory (via APCu), the [integrated database encryption](classes/crypto.class.php) is used to encrypt sensitive user data like IP addresses, emails, and private messages regardless of the underlying system gazelle is running on.

The rest of gazelle must be aware that some of the data it fetches from the DB is encrypted, and must have a fallback if that data is unavailable (the key is not in memory). You will see plenty of `if (!apcu_exists('DBKEY')) {` in this codebase.

#### Authorized Login Locations

Whenever a login occurs from a location (determined by ASN) that hasn't logged into that account before, an email is sent to the account owner requesting that they authorize that location before the login will go through.

This prevents most attacks that would be otherwise successful, as it requires an attacker to access the site from the same locations the actual user uses to login.

#### Two-Factor Authentication

Despite our other (less intrusive) methods of protecting user accounts being more than sufficient for virtually all feasible attacks, we also ship optional 2FA should users feel the need to enable it.

#### Universal 2nd Factor

Support for physical U2F tokens has also been added as an optional alternative to normal 2FA. U2F allows users to protect their account with something less likely to be lost or erased than 2FA keys stored on a phone.

#### Unique Infohashes

Upon upload, torrent files are modified to contain a "source" field in the info dict containing the concatination of the site name and some generated junk data (unique per-torrent). This prevents infohash collisions with torrents cross-seeded from other sites in the same client, and also helps protect against some not particularly likely peer-leaking attacks.

#### Expunge Requests

Users are able to view the data kept on them and [issue requests for the deletion of old information](sections/delete) to staff through a simple interface.

#### Resource Proxying

All external resources that may appear on a page are fetched and served by the server running gazelle. This prevents the leak of user information to third parties hosting content that has been included on a page through an image tag or similar.

#### Scheduler

The [scheduler](sections/schedule) has been broken up into more manageable parts and has additional selective runtime features for manual execution.

#### Bonus Points

Like most gazelle forks, we've added a [bonus point system](sections/schedule/hourly/bonus_points.php) and [store](sections/store).

#### Modern password hashing

We use modern PHP password hashing features that automatically rehash your password when a better hashing algorithm is made available and employ prehashing to allow you to use a secure password of any length. Original gazelle would effectively truncate your password after around 72 characters (if the tracker even allowed you to use a password that long). This codebase does not have the same problem, and allows passwords of virtually unlimited length (over 30,000 characters by default) that remain useful after a few tens of characters.

## Minor Changes

* When a torrent is trumped, the new torrent is made freeleech to users who snatched the old torrent for a few days.
* Sends headers to tell cloudflare to use HTTP/2 Server Push for most resources.
* BTN-style magnet link support.
* Support for optional per-user stylesheet additions and tweaks
* This codebase expects to run over https only.

## Mascot

<img align="left" alt="Gracie Gazelle" src="https://git.oppaiti.me/Oppaitime/Gazelle/raw/branch/master/static/common/mascot.png">

**Gracie Gazelle**

Gracie is a veteran pirate of the Digital Ocean. On land, predators form companies to hunt down prey. But in the lawless water, prey attack the predators' transports. Gracies steals resources from the rich and shares them with the poor and isolated people. Her great eyesight sees through the darkest corners of the Internet for her next target. Her charisma attracts countless salty goats to join her fleet. She proudly puts the forbidden share symbols on her hat and belt, and is now one of the most wanted women in the world.

<small>High resolution downloads [here](https://git.oppaiti.me/Oppaitime/Gazelle/issues/34#issuecomment-99)</small>

<small>Character design and bio by Tyson Tan, who offers mascot design services for free and open source software, free of charge, under a free license.</small>

<small>Contact: [tysontan.com](https://tysontan.com) / <tysontan@mail.com></small>
