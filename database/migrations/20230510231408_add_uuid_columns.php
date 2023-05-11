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

        # big old loop
        foreach ($ref as $row) {
            /*
            try {
                # drop the primary key
                $query = "
                    alter table {$row}
                    drop index if exists `PRIMARY`
                ";

                $app->dbNew->do($query, []);
                #!d($query);
            } catch (Throwable $e) {
                !d($row . ": " . $e->getMessage());
                continue;
            }
            */

            try {
                # add a bigint column to each table
                $query = "
                    alter table {$row}
                    add column if not exists id bigint unsigned
                    not null first
                ";

                $app->dbNew->do($query, []);
                #!d($query);
            } catch (Throwable $e) {
                !d($row . ": " . $e->getMessage());
                continue;
            }

            /*
            try {
                # normalize the auto_increment id columns
                $query = "
                    alter table {$row}
                    modify column if exists id bigint unsigned
                    not null auto_increment
                ";

                $app->dbNew->do($query, []);
                #!d($query);
            } catch (Throwable $e) {
                !d($row . ": " . $e->getMessage());
                continue;
            }
            */

            try {
                # using a default value, though manual uuid v7's are preferred
                # https://gist.github.com/happycatsmiles/e528dd9184874d2193ad3c7306b68f27
                $query = "
                    alter table {$row}
                    add column if not exists uuid binary(16)
                    not null unique key after id
                ";

                /*
                $query = "
                    alter table {$row}
                    add column if not exists uuid binary(16)
                    not null default unhex(replace(uuid(), '-', '')) unique key first
                ";
                */

                $app->dbNew->do($query, []);
                !d($query);
            } catch (Throwable $e) {
                !d($row . ": " . $e->getMessage());
                continue;
            }

            # now populate the uuid v7's
            $query = "select * from {$row}";
            $miniRef = $app->dbNew->multi($query, []);

            foreach ($miniRef as $miniRow) {
                $miniRow["ID"] ??= null;
                if ($miniRow["ID"]) {
                    $miniRow["id"] = $miniRow["ID"];
                    unset($miniRow["ID"]);
                }

                $query = "update {$row} set uuid = ? where id = ?";
                $app->dbNew->do($query, [ $app->dbNew->uuid(), $miniRow["id"] ]);
                !d($query);
            }
        }
    }
}
