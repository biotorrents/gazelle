<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class HydrateOrganizationsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $app = Gazelle\App::go();


        ##
        # get all the workgroups from torrents_group
        #

        $query = "select id, workgroup from torrents_group";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $row["workgroup"] ??= null;
            if (empty($row["workgroup"])) {
                continue;
            }

            # organizations
            $data = [
                "id" => $app->dbNew->shortUuid(),
                "name" => $row["workgroup"],
                "userId" => $app->user->core["id"] ?? 0,
            ];

            $query = "insert ignore into organizations (id, name, userId) values (:id, :name, :userId)";
            $app->dbNew->do($query, $data);

            # organizations_links
            $data = [
                "objectId" => $data["id"], # organizations
                "contentId" => $row["id"], # torrentGroups
                "contentType" => Gazelle\TorrentGroups::$type,
            ];

            $query = "insert ignore into organizations_links (objectId, contentId, contentType) values (:objectId, :contentId, :contentType)";
            $app->dbNew->do($query, $data);

            # torrents_group_links
            $data = [
                "objectId" => $data["contentId"], # torrentGroups
                "contentId" => $data["objectId"], # organizations
                "contentType" => Gazelle\Organizations::$type,
            ];

            $query = "insert ignore into torrents_group_links (objectId, contentId, contentType) values (:objectId, :contentId, :contentType)";
            $app->dbNew->do($query, $data);
        }


        ##
        # now get the affiliations from creators
        #

        $query = "select id, affiliations from creators";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            # try to json_decode it
            $row["affiliations"] = json_decode($row["affiliations"] ?? "{}", true);
            if (empty($row["affiliations"])) {
                continue;
            }

            # the affiliations are a json array
            foreach ($row["affiliations"] as $key => $value) {
                # organizations
                $data = [
                    "id" => $app->dbNew->shortUuid(),
                    "name" => $value,
                    "userId" => $app->user->core["id"] ?? 0,
                ];

                $query = "insert ignore into organizations (id, name, userId) values (:id, :name, :userId)";
                $app->dbNew->do($query, $data);

                # organizations_links
                $data = [
                    "objectId" => $data["id"], # organizations
                    "contentId" => $row["id"], # creators
                    "contentType" => Gazelle\Creators::$type,
                ];

                $query = "insert ignore into organizations_links (objectId, contentId, contentType) values (:objectId, :contentId, :contentType)";
                $app->dbNew->do($query, $data);

                # creators_links
                $data = [
                    "objectId" => $data["contentId"], # creators
                    "contentId" => $data["objectId"], # organizations
                    "contentType" => Gazelle\Organizations::$type,
                ];

                $query = "insert ignore into organizations_links (objectId, contentId, contentType) values (:objectId, :contentId, :contentType)";
                $app->dbNew->do($query, $data);
            }
        }
    }
}
