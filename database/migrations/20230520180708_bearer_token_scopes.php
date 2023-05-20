<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BearerTokenScopes extends AbstractMigration
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
        $app = \Gazelle\App::go();

        # change the `created` column to `created_at`
        $query = "select * from api_user_tokens";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "update api_user_tokens set created_at = ? where id = ?";
            $app->dbNew->do($query, [$row["Created"], $row["ID"]]);
        }

        # update the table definition
        $table = $this->table("api_user_tokens");
        $table
            # rename the table
            ->rename("api_tokens")

            # drop these columns
            ->removeColumn("scope")
            ->removeColumn("appId")
            ->removeColumn("created")
            ->removeColumn("revoked")

            # add these columns
            ->addColumn("permissions", "json", ["null" => true, "after" => "token"])

            # rename these columns
            ->renameColumn("ID", "id")
            ->renameColumn("UserID", "userId")

            # done
            ->update();

        # drop api_applications
        $this->table("api_applications")->drop()->update();
    }
}
