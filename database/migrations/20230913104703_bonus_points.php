<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BonusPoints extends AbstractMigration
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
        $table = $this->table("bonus_points");

        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("key", "string", ["limit" => 128, "null" => false])
            ->addColumn("value", "string", ["limit" => 255, "null" => false])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->addIndex("uuid", ["unique" => true])
            ->addIndex(["id", "uuid", "key"], ["unique" => true])

            ->create();
    }
}
