<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUuidColumns extends AbstractMigration
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

        # development or production?
        if ($app->env->dev) {
            $query = "show tables";
            $ref = $app->dbNew->column("Tables_in_gazelle_development", $query, []);
        } else {
            # quick idiot-proofing
            throw new Exception("just being careful for now");

            $query = "show tables";
            $ref = $app->dbNew->column("Tables_in_gazelle_production", $query, []);
        }

        foreach ($ref as $row) {
            # add a bigint column to each table
            $query = "
                alter table {$row}
                add column if not exists id bigint unsigned
                not null auto_increment primary key first
            ";
            $app->dbNew->do($query, []);

            # normalize the auto_increment id columns
            $query = "
                alter table {$row}
                modify column id bigint unsigned
                not null auto_increment
            ";
            $app->dbNew->do($query, []);

            # using a default value, though manual uuid v7's are preferred
            # https://gist.github.com/happycatsmiles/e528dd9184874d2193ad3c7306b68f27
            $query = "
                alter table {$row}
                add column if not exists uuid binary(16)
                not null default unhex(replace(uuid(), '-', '')) unique key after id
            ";
            $app->dbNew->do($query, []);
        }
    }
}
