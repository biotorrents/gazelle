<?php

declare(strict_types=1);


/**
 * Gazelle\Literature
 */

namespace Gazelle;

class Literature extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "literature"; # resource name
    protected ?string $table = "literature"; # database table

    # cache settings
    private string $cachePrefix = "literature:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "doi" => "doi",
        "openAlexId" => "openAlexId",
        "semanticScholarId" => "semanticScholarId",
        "title" => "title",
        "slug" => "slug",
        "language" => "language",
        "type" => "type",
        "journal" => "journal", # json
        "bibtex" => "bibtex",
        "publicationDate" => "publicationDate",
        "license" => "license",
        "abstract" => "abstract",
        "tldr" => "tldr", # json
        "primaryTopic" => "primaryTopic", # json
        "concepts" => "concepts", # json
        "countsByYear" => "countsByYear", # json
        "influentialCitationCount" => "influentialCitationCount",
        "citationCount" => "citationCount",
        "referenceCount" => "referenceCount",
        "isOpenAccess" => "isOpenAccess", # bool
        "openAccessPdf" => "openAccessPdf",
        "isRetracted" => "isRetracted", # bool
        "failCount" => "failCount",
        "degreesOfSeparation" => "degreesOfSeparation",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * read
     *
     * @param int|string $id
     * @return void
     */
    public function read(int|string $id = null): void
    {
        $app = App::go();

        # is it a doi?
        $doi = preg_match("/{$app->env->regexDoi}/i", strval($id));
        if ($doi) {
            $query = "select id from literature where doi = ?";
            $id = $app->dbNew->single($query, [$id]);

            if (!$id) {
                throw new Exception("not found");
            }
        }

        # parent read
        parent::read($id);

        # decode the boolean fields
        $this->attributes->isOpenAccess = boolval($this->attributes->isOpenAccess ?? false);
        $this->attributes->isRetracted = boolval($this->attributes->isRetracted ?? false);

        # decode the json fields
        $this->attributes->journal = json_decode($this->attributes->journal ?? "[]");
        $this->attributes->tldr = json_decode($this->attributes->tldr ?? "[]");
        $this->attributes->primaryTopic = json_decode($this->attributes->primaryTopic ?? "[]");
        $this->attributes->concepts = json_decode($this->attributes->concepts ?? "[]");
        $this->attributes->countsByYear = json_decode($this->attributes->countsByYear ?? "[]");
    }


    /** methods */


    /**
     * getJournalAsString
     *
     * Formats a Semantic Scholar journal entry object, e.g.,
     * {"name": "The Journal of Biological Chemistry", "pages": "8028-8034", "volume": "278"}
     *
     * @param object $literature->attributes->journal
     * @return string "The Journal of Biological Chemistry 278, 8028-8034"
     */
    public static function getJournalAsString(object $journal): string
    {
        $journal = trim($journal->display_name ?? "");
        $volume = trim($journal->volume ?? "");
        $pages = trim($journal->pages ?? "");

        return "{$journal} {$volume}, {$pages}";
    }


    /**
     * hydrateFromOpenAlex
     *
     * Searches for a paper in the OpenAlex database and supplements the object with extra attributes.
     *
     * @return ?array what's written to the database
     */
    public function hydrateFromOpenAlex(): ?array
    {
        $app = App::go();

        if (!$this->attributes->doi) {
            return null;
        }

        $openAlex = new OpenAlex();
        $response = $openAlex->works($this->attributes->doi);

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            $data = [
                "id" => $this->id,
                "userId" => $app->user->core["id"] ?? 0,
                "doi" => $response["doi"] ?? null,
                "openAlexId" => $response["id"] ?? null,
                #"semanticScholarId" => $response["semanticScholarId"] ?? null,
                "title" => $response["title"] ?? null,
                "slug" => $app->dbNew->slug($response["title"] ?? null),
                "language" => $response["language"] ?? null,
                "type" => $response["type"] ?? null,
                #"journal" => json_encode($response["primary_location"]["source"] ?? []),
                #"bibtex" => $response["bibtex"] ?? null,
                "publicationDate" => $response["publication_date"] ?? null,
                "license" => $response["primary_location"]["license_id"] ?? null,
                #"abstract" => $response["abstract"] ?? null,
                #"tldr" => $response["tldr"] ?? null,
                "primaryTopic" => json_encode($response["primary_topic"] ?? []),
                "concepts" => json_encode($response["concepts"] ?? []),
                "countsByYear" => $response["counts_by_year"] ?? null,
                #"influentialCitationCount" => $response["influentialCitationCount"] ?? null,
                #"citationCount" => $response["citationCount"] ?? null,
                #"referenceCount" => $response["referenceCount"] ?? null,
                "isOpenAccess" => $response["primary_location"]["is_oa"] ?? null,
                "openAccessPdf" => $response["primary_location"]["pdf_url"] ?? null,
                "isRetracted" => $response["is_retracted"] ?? null,
            ];

            # update the Literature object
            $this->update($data);

            # literature <=> creator relationships
            $response["authorships"] ??= [];
            foreach ($response["authorships"] as $author) {
                # try to find it in the database
                $query = "select id from creators where openAlexId = ?";
                $creatorId = $app->dbNew->single($query, [ $author["author"]["id"] ]);

                # the creator already exists
                if ($creatorId) {
                    $query = "insert ignore into literature_links (objectId, contentId, contentType) values (?, ?, ?)";
                    $app->dbNew->do($query, [$this->id, $creatorId, Creators::$type]);

                    $query = "insert ignore into creators_links (objectId, contentId, contentType) values (?, ?, ?)";
                    $app->dbNew->do($query, [$creatorId, $this->id, Literature::$type]);

                    continue;
                }

                $data = [
                    "id" => $app->dbNew->shortUuid(),
                    "userId" => $app->user->core["id"] ?? 0,
                    "openAlexId" => $author["author"]["id"] ?? null,
                    "orcId" => $author["author"]["orcid"] ?? null,
                    "name" => $author["author"]["display_name"] ?? null,
                    "slug" => \Illuminate\Support\Str::slug($author["author"]["display_name"] ?? null),
                    "affiliations" => json_encode($author["author"]["raw_affiliation_strings"] ?? []),
                    "degreesOfSeparation" => intval($this->attributes->degreesOfSeparation) + 1,
                ];

                $creator = new Creators();
                $creator->updateOrCreate($data);

                # creator <=> organization relationships
                foreach ($author["institutions"] as $institution) {
                    $query = "select id from organizations where openAlexId = ?";
                    $organizationId = $app->dbNew->single($query, [ $institution["id"] ]);

                    if ($organizationId) {
                        $query = "insert ignore into organizations_links (objectId, contentId, contentType) values (?, ?, ?)";
                        $app->dbNew->do($query, [$organizationId, $data["id"], Creators::$type]);

                        $query = "insert ignore into creators_links (objectId, contentId, contentType) values (?, ?, ?)";
                        $app->dbNew->do($query, [$data["id"], $organizationId, Organizations::$type]);

                        continue;
                    }

                    $data = [
                        "id" => $app->dbNew->shortUuid(),
                        "userId" => $app->user->core["id"] ?? 0,
                        "openAlexId" => $institution["id"] ?? null,
                        "rorId" => $institution["ror"] ?? null,
                        "name" => $institution["name"] ?? null,
                        "degreesOfSeparation" => intval($this->attributes->degreesOfSeparation) + 2,
                    ];

                    $organization = new Organizations();
                    $organization->updateOrCreate($data);
                }
            } # foreach ($response["authorships"] as $author)

            # literature <=> tags relationships
            $response["keywords"] ??= [];
            foreach ($response["keywords"] as $keyword) {
                $query = "select id from tags where openAlexId = ?";
                $tagId = $app->dbNew->single($query, [ $keyword["id"] ]);

                if ($tagId) {
                    $query = "insert ignore into literature_links (objectId, contentId, contentType) values (?, ?, ?)";
                    $app->dbNew->do($query, [$this->id, $tagId, Tags::$type]);

                    $query = "insert ignore into tags_links (objectId, contentId, contentType) values (?, ?, ?)";
                    $app->dbNew->do($query, [$tagId, $this->id, Literature::$type]);

                    continue;
                }

                $data = [
                    "id" => $app->dbNew->shortUuid(),
                    "userId" => $app->user->core["id"] ?? 0,
                    "openAlexId" => $keyword["id"] ?? null,
                    "name" => Format::tag($keyword["name"] ?? null),
                    "tagType" => "openAlex",
                    "score" => $keyword["score"] ?? null,
                    "degreesOfSeparation" => intval($this->attributes->degreesOfSeparation) + 1,
                ];

                $tag = new Tags();
                $tag->updateOrCreate($data);
            } # foreach ($response["keywords"] as $keyword)

            # commit and return
            $app->dbNew->commit();
            return $literatureData;
        } catch (\Throwable $e) {
            $app->dbNew->rollBack();

            $query = "update {$this->table} set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            throw $e;
        }
    }


    /**
     * supplementFromSemanticScholar
     *
     * Searches for a paper in the Semantic Scholar database and hydrates the object.
     *
     * @return ?array what's written to the database
     */
    public function supplementFromSemanticScholar(): ?array
    {
        $app = App::go();

        if (!$this->attributes->doi) {
            return null;
        }

        $semanticScholar = new SemanticScholar(["paperId" => "DOI:{$this->attributes->doi}"]);
        $response = $semanticScholar->paper();

        if (!empty($response["error"])) {
            # increment the failCount and return
            $query = "update literature set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            return null;
        }

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # assemble the literature data
            $literatureData = [
                "id" => $this->id,
                "userId" => $app->user->core["id"] ?? 0,
                #"doi" => $response["doi"] ?? null,
                #"openAlexId" => $response["id"] ?? null,
                "semanticScholarId" => $response["paperId"] ?? null,
                #"title" => $response["title"] ?? null,
                #"slug" => $app->dbNew->slug($response["title"] ?? null),
                #"language" => $response["language"] ?? null,
                #"type" => $response["type"] ?? null,
                "journal" => json_encode($response["journal"] ?? []),
                "bibtex" => $response["citationStyles"]["bibtex"] ?? null,
                #"publicationDate" => $response["publication_date"] ?? null,
                #"license" => $response["primary_location"]["license_id"] ?? null,
                "abstract" => $response["abstract"] ?? null,
                "tldr" => json_encode($response["tldr"] ?? []),
                #"primaryTopic" => json_encode($response["primary_topic"] ?? []),
                #"concepts" => json_encode($response["concepts"] ?? []),
                #"countsByYear" => $response["counts_by_year"] ?? null,
                "influentialCitationCount" => $response["influentialCitationCount"] ?? null,
                "citationCount" => $response["citationCount"] ?? null,
                "referenceCount" => $response["referenceCount"] ?? null,
                #"isOpenAccess" => $response["primary_location"]["is_oa"] ?? null,
                #"openAccessPdf" => $response["primary_location"]["pdf_url"] ?? null,
                #"isRetracted" => $response["is_retracted"] ?? null,
            ];

            # update the Literature object
            $this->update($literatureData);

            /*
            # loop through the creator data, if any
            foreach ($response["authors"] as $creator) {
                $query = "select id from creators where semanticScholarId = ?";
                $creatorId = $app->dbNew->single($query, [$creator["authorId"]]);

                # add the existing creator record
                if ($creatorId) {
                    $query = "insert ignore into literature_links (objectId, contentId, contentType) values (?, ?, ?)";
                    $app->dbNew->do($query, [$literatureData["id"], $creatorId, Creators::$type]);

                    continue;
                }

                # hydrate the data for a new Creators object
                $creatorData = [
                    "id" => $app->dbNew->shortUuid(),
                    "orcid" => $creator["externalIds"]["ORCID"] ?? null,
                    "semanticScholarId" => $creator["authorId"] ?? null,
                    "name" => $creator["name"] ?? null,
                    "slug" => \Illuminate\Support\Str::slug($creator["name"] ?? null),
                    "aliases" => json_encode($creator["aliases"] ?? []),
                    "affiliations" => json_encode($creator["affiliations"] ?? []),
                    "homepage" => $creator["homepage"] ?? null,
                    "hIndex" => $creator["hIndex"] ?? null,
                    "paperCount" => $creator["paperCount"] ?? null,
                    "citationCount" => $creator["citationCount"] ?? null,
                ];

                # create a new Creators object
                $creator = new Creators();
                $creator->updateOrCreate($creatorData);

                # add the database link
                $query = "insert ignore into literature_links (objectId, contentId, contentType) values (?, ?, ?)";
                $app->dbNew->do($query, [$literatureData["id"], $creatorData["id"], Creators::$type]);

            }
            */

            # commit and return
            $app->dbNew->commit();
            return $literatureData;
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


} # class
