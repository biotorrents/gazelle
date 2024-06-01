<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class HydrateSlugColumns extends AbstractMigration
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

        # collages
        $query = "select id, title from collages";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $slug = Illuminate\Support\Str::slug($row["title"]);
            $query = "update collages set slug = ? where id = ?";
            $app->dbNew->do($query, [ $slug, $row["id"] ]);
        }

        # creators
        $query = "select id, name from creators";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $slug = Illuminate\Support\Str::slug($row["name"]);
            $query = "update creators set slug = ? where id = ?";
            $app->dbNew->do($query, [ $slug, $row["id"] ]);
        }

        # requests
        $query = "select id, title from requests";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $slug = Illuminate\Support\Str::slug($row["title"]);
            $query = "update requests set slug = ? where id = ?";
            $app->dbNew->do($query, [ $slug, $row["id"] ]);
        }

        # torrents_group
        $query = "select id, title from torrents_group";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $slug = Illuminate\Support\Str::slug($row["title"]);
            $query = "update torrents_group set slug = ? where id = ?";
            $app->dbNew->do($query, [ $slug, $row["id"] ]);
        }

        # wiki_articles
        $query = "select id, title from wiki_articles";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $slug = Illuminate\Support\Str::slug($row["title"]);
            $query = "update wiki_articles set slug = ? where id = ?";
            $app->dbNew->do($query, [ $slug, $row["id"] ]);
        }
    }
}
