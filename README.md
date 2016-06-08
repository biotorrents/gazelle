This is Oppaitime's version of Gazelle

Below is a list of differences between this version of Gazelle and What.cd's. Please note that these lists are far from complete.

## Major Changes

#### Integrated Database Encryption

Using a database key [provided by staff](sections/tools/misc/database_key.php) and only ever stored as a hash in memory (via APC), the [integrated database encryption](classes/dbcrypt.class.php) is used to encrypt sensitive user data like IP addresses, emails, and private messages regardless of the underlying system gazelle is running on.

The rest of gazelle must be aware that some of the data it fetches from the DB is encrypted, and must have a fallback if that data is unavailable (the key is not in memory). You will see plenty of `if (!apc_exists('DBKEY')) {` in this codebase.

#### Expunge Requests

Users are able to view the data kept on them and [issue requests for the deletion of old information](sections/delete) to staff through a simple interface.

#### Resource Proxying

All external resources that may appear on a page are fetched and served by the server running gazelle. This prevents the leak of user information to third parties hosting content that has been included on a page through an image tag or similar.

#### Scheduler

The [scheduler](sections/schedule) has been broken up into more manageable parts and has additional selective runtime features for manual execution.

#### Bonus Points

Like most gazelle forks, we've added a [bonus point system](sections/schedule/hourly/bonus_points.php) and [store](sections/store).

#### Modern password hashing

We use new PHP password hashing features that automatically rehash your password when a better hashing algorithm is made available and employ prehashing to allow you to use a secure password of any length. Original gazelle would effectively truncate your password after around 72 characters (if the tracker even allowed you to use a password that long). This codebase does not have the same problem, and allows passwords of virtually unlimited length (over 30,000 characters by default) that remain useful after a few tens of characters.


## Minor Changes

* When a torrent is trumped, the new torrent is made freeleech to users who snatched the old torrent for a few days
* An attempt has been made to support magnet links. This has shown partial success.
* This codebase is expected to run over https only.

## Bug Fixes

* Count of torrent results is accurate from first page for much larger databases, but could still be improved to scale automatically.
