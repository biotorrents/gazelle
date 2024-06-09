# Contributing to BioGazelle

Thanks for your interest in improving BioGazelle's codebase.

## General application layout

The application as a whole is object-oriented at a rather low level where objects *do stuff.*
The custom model system has a sensible amount of helpers but you're responsible for many things:

- custom CRUD operations, e.g., transparently handling JSON and arrays
- defining relationships to active, e.g., `$collage->torrentGroups()`
- all database logic, especially fors linking tables and API integrations

The core objects all follow the [JSON:API specification format](https://jsonapi.org/format/1.2/) from instantiation.
Relationships can be loaded one at a time, with `["id" => "string", "type" => "string"]` pairs the default.
These objects are available to supported clients (e.g., `$app->env->executionContext`) to use as needed.

### Request timeline breakdown

A typical request starts in `/public/index.php` to bootstrap the correct client.
Web and API requests each have their own bootstrap logic (so does the CLI).
In either case, the application makes more checks and starts the Flight router.
This maps routes to `require` statements, e.g., `/sections/torrentGroups/browse.php`.
These files call methods, e.g., `Gazelle\TorrentGroups->torrents()` to query data.
This data goes to either a Twig template or a JSON response; both use JSON:API objects.

## Object API

There are a set of core objects in `/app/Models` that are implemented as Laravel `LazyCollection` instances.
This has the dual benefit of making the objects memory efficient and also making them immutable (to prevent ORM creep).
The API generally follows Laravel method naming coventions, e.g., `updateOrCreate()`, but without magic.
Here's a simple example of how you'd work with an object:

```php
$id ??= null;

# read on instantiation
$torrentGroup = new Gazelle\TorrentGroups($id);
!d($torrentGroup);

# relationships are always available
$torrents = $torrentGroup->torrents();
!d($torrents);

# create or update any or all data
$data = [
    "title" => "new title",
    "subject" => "new subject",
    "object" => "new object",
];

# get the new object back
$newTorrentGroup = $torrentGroup->updateOrCreate($data);
!d($newTorrentGroup);

# use any laravel method on the instance
# https://laravel.com/docs/master/collections#the-enumerable-contract
```

### Attributes

Attributes hold the main metadata of the object, a `LazyCollection` based on the JSON:API specification.
JSON is deserialized on read, but the strings can always be obtained by, e.g., `$creator->attributes->concepts->raw()`.
Remember that `LazyCollection` instances are immutable so this code won't work:

```php
$id ??= null;

$literature = new Gazelle\Literature($id);
$literature->title = "new title";

$literature->save();
```

### Relationships

Relationships are implemented as reciprocal 1 : 1 "links" stored in one table for each object, e.g., `creators_links`.
There are no concepts of ownership, one-to-one vs. one-to-many, or any kind of hierarchy or definition involved.

As a result, the metadata ecosystem is flat so it's possible to call, e.g., `organizations()` from any object.
This lazily returns either an array of objects or an empty array, ready for loops and compatible with JSON:API.

Each object is also primed with dehydrated `relationships`, e.g., `["id" => "666", "type" => "torrrents"]`.
Twig also treats dot notation as method calls, so `{{ torrentGroup.torrents }}` works like `$torrentGroup->torrents()`.

## Search, metadata, and indexing

The search engine is Manticore and its indexing strategy is conceptually similar to "links."
It attempts to index every attribute of every object associated with each index, uniquely prefixed.
This massive amount of data is filtered into specific form fields and matched against user inputs.
At the cost of some boilerplate in code, it allows for searches like this:

```php
# map of search form fields => index fields
private array $fieldMaps = [
    "shared" => [
        "creators" => ["creators_openAlexId", "creators_orcid", "creators_scopusId", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_aliases"],
        "literature" => ["literature_doi", "literature_openAlexId", "literature_semanticScholarId", "literature_title", "literature_slug", "literature_bibtex", "literature_abstract"],

        "workgroups" => ["torrentGroups_workgroup", "creators_affiliations", "creators_affiliationsOverTime", "organizations_grid", "organizations_openAlexId", "organizations_rorId", "organizations_wikidataId", "organizations_name", "organizations_slug", "organizations_acronym", "organizations_reverseGeocode"],
        "locations" => ["torrentGroups_location", "organizations_latitude", "organizations_longitude", "organizations_reverseGeocode", "organizations_country", "organizations_state", "organizations_city", "organizations_postalCode"],

        # etc., as broad or granular as desired, for any attribute
    ],
];
```

### Remote metadata

A significant amount of metadata not for torrents, collages, and requests is programmatically acquired.
These objects are creators, literature, publications, and organizations and are called the "ecosystem."
The data comes from a variety of sources including OpenAlex, Crossref, Semantic Scholar, ROR, Google, etc.

To prevent public API abuse and the infinite growth of metadata, objects have a `degreesOfSeparation` and a `failCount`.
The `degreesOfSeparation` is configurable (default `6`) and determines how distantly related to a piece of UGC it is.
The `failCount` (default `3`) determines how many API error responses, e.g., `404`, to tolerate for the content.
Automated queries for remote metadata cease once either threshold is crossed for any particular object.

### Typeahead search

Typeahead search (autocomplete) is implemented with `corejavascript/typeahead.js`](https://github.com/corejavascript/typeahead.js).
Bloodhound uses a single remote data source that's an internal API endpoint.
It calls `Gazelle\Autocomplete->fetch()` and returns `[ ["id" => string, "text" => string, "openAlexId" => string, "isLocal" => bool] ]`.
Remote data is fetched by default, but this can be disabled with a flag.

## Utilities and developer tools

todo