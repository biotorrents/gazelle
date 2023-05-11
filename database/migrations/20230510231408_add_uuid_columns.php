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

        # using a default value, though manual uuid v7's are preferred
        # https://gist.github.com/happycatsmiles/e528dd9184874d2193ad3c7306b68f27
        foreach ($ref as $row) {
            try {
                $table = $this->table($row);

                if ($table->hasColumn("uuid")) {
                    continue;
                }

                $table->addColumn("uuid", "binary", [
                    "length" => 16,
                    "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                    "null" => false,
                    "after" => Phinx\Db\Adapter\MysqlAdapter::FIRST,
                ])
                ->addIndex(["uuid"], [
                    "unique" => true,
                    "name" => "uuid",
                ])
                ->addTimestamps()
                ->save();

            } catch (Throwable $e) {
                !d($row . ": " . $e->getMessage());
                continue;
            }
        }

        # now populate the database with uuid v7's
        foreach ($ref as $row) {
            $query = "select * from {$row}";
            $miniRef = $app->dbNew->multi($query, []);

            foreach ($miniRef as $miniRow) {
                try {
                    $query = "update {$row} set uuid = ? where uuid = ?";
                    $app->dbNew->do($query, [ $app->dbNew->uuid(), $miniRow["uuid"] ]);
                    #!d($query);
                } catch (Throwable $e) {
                    !d($row . ": " . $e->getMessage());
                    continue;
                }
            }
        }
    }
}
