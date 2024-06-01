# Contributing to BioGazelle

Thanks for your interest in improving BioGazelle's codebase.

## General application layout

The application as a whole is object-oriented at a rather low level where objects *do stuff.*
The custom model system has a sensible amount of helpers but you're responsible for many things:

- custom CRUD operations, e.g., transparently handling JSON and arrays
- defining relationships to active, e.g., `$request->loadTorrentGroups()`
- all database logic, especially fors linking tables and API integrations

The core objects all follow the [JSON:API specification format](https://jsonapi.org/format/1.2/) from instantiation.
Relationships can be loaded one at a time, with `["id" => "string", "type" => "string"]` pairs the default.
These objects are available to supported clients (e.g., `$app->env->executionContext`) to use as needed.

## Request timeline breakdown

A typical request starts in `/public/index.php` to bootstrap the correct client.
Web and API requests each have their own bootstrap logic (so does the CLI).
In either case, the application makes more checks and starts the Flight router.
This maps routes to `require` statements, e.g., `/sections/torrentGroups/browse.php`.
These files call methods, e.g., `Gazelle\TorrentGroups->loadTorrents()` to query data.
This data goes to either a Twig template or a JSON response; both use JSON:API objects.