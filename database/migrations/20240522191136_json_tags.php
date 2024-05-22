<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class JsonTags extends AbstractMigration
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

        # get the tags from torrents_group
        $query = "select id, tag_list from torrents_group";
        $ref = $app->dbNew->multi($query, []);

        # update the tags in torrents_group
        foreach ($ref as $row) {
            $tags = explode(" ", $row["tag_list"]);
            $tags = array_map("trim", $tags);

            # replace _ with .
            foreach ($tags as $key => $tag) {
                $tags[$key] = str_replace("_", ".", $tag);
            }

            # get the unique tags
            $tags = array_unique($tags);

            # update the tags
            $query = "update torrents_group set tag_list = ? where id = ?";
            $app->dbNew->do($query, [ json_encode($tags), $row["id"] ]);
        }

        # now do the same for collages
        $query = "select id, tags from collages";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $tags = explode(" ", $row["tags"]);
            $tags = array_map("trim", $tags);

            # get the unique tags
            $tags = array_unique($tags);

            # update the tags
            $query = "update collages set tags = ? where id = ?";
            $app->dbNew->do($query, [ json_encode($tags), $row["id"] ]);
        }
    }
}
