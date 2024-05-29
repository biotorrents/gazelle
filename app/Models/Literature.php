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
        "semanticScholarId" => "semanticScholarId",
        "title" => "title",
        "venue" => "venue",
        "journal" => "journal", # json
        "year" => "year",
        "publicationDate" => "publicationDate",
        "abstract" => "abstract",
        "tldr" => "tldr", # json
        "bibtex" => "bibtex",
        "influentialCitationCount" => "influentialCitationCount",
        "citationCount" => "citationCount",
        "referenceCount" => "referenceCount",
        "isOpenAccess" => "isOpenAccess", # bool
        "openAccessPdf" => "openAccessPdf",
        "failCount" => "failCount",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** relationships */


    /**
     * relationships
     *
     * @return array
     */
    public function relationships(): array
    {
        return [
            Creators::$type => $this->relatedCreators(),
            TorrentGroups::$type => $this->relatedTorrentGroups(),
            Requests::$type => $this->relatedRequests(),
        ];
    }


    /**
     * relatedCreators
     *
     * @return array
     */
    private function relatedCreators(): array
    {
        $app = App::go();

        $query = "select creatorId from literature_creators where literatureId = ? and deleted_at is null";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Creators::$type];
        }

        return $data;
    }



    /**
     * relatedTorrentGroups
     *
     * @return array
     */
    private function relatedTorrentGroups(): array
    {
        $app = App::go();

        $query = "select groupId from literature_groups where literatureId = ? and deleted_at is null";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => TorrentGroups::$type];
        }

        return $data;
    }


    /**
     * relatedRequests
     *
     * @return array
     */
    private function relatedRequests(): array
    {
        $app = App::go();

        $query = "select requestId from literature_requests where literatureId = ? and deleted_at is null";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Requests::$type];
        }

        return $data;
    }


    /** methods */


    /**
     * hydrateFromSemanticScholar
     *
     * Searches for a paper in the Semantic Scholar database and hydrates the object.
     *
     * @return ?array what's written to the database
     */
    public function hydrateFromSemanticScholar(): ?array
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
                "userId" => $this->attributes->userId ?? 0, # system
                "doi" => $response["externalIds"]["DOI"] ?? null,
                "semanticScholarId" => $response["paperId"] ?? null,
                "title" => $response["title"] ?? null,
                "venue" => $response["venue"] ?? null,
                "journal" => json_encode($response["journal"] ?? []),
                "year" => $response["year"] ?? null,
                "publicationDate" => $response["publicationDate"] ?? null,
                "abstract" => $response["abstract"] ?? null,
                "tldr" => json_encode($response["tldr"] ?? []),
                "bibtex" => $response["citationStyles"]["bibtex"] ?? null,
                "influentialCitationCount" => $response["influentialCitationCount"] ?? null,
                "citationCount" => $response["citationCount"] ?? null,
                "referenceCount" => $response["referenceCount"] ?? null,
                "isOpenAccess" => boolval($response["isOpenAccess"] ?? null),
                "openAccessPdf" => $response["openAccessPdf"]["url"] ?? null,
            ];

            # update the Literature object
            $this->update($literatureData);

            # loop through the creator data, if any
            foreach ($response["authors"] as $creator) {
                $query = "select id from creators where semanticScholarId = ?";
                $creatorId = $app->dbNew->single($query, [$creator["authorId"]]);

                # add the existing creator record
                if ($creatorId) {
                    $query = "insert ignore into literature_creators (literatureId, creatorId) values (?, ?)";
                    $app->dbNew->do($query, [$literatureData["id"], $creatorId]);

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
                $query = "insert ignore into literature_creators (literatureId, creatorId) values (?, ?)";
                $app->dbNew->do($query, [ $literatureData["id"], $creatorData["id"] ]);

            }

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
