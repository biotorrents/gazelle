<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LotteryBadges extends AbstractMigration
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

        $query = "insert into badges (id, icon, name, description) values (?, ?, ?, ?)";

        $app->dbNew->do($query, [50, "🎲", "Game Die", "Odds of 0.9"]);
        $app->dbNew->do($query, [51, "🎰", "Slot Machine", "Odds of 0.09"]);
        $app->dbNew->do($query, [52, "🎱", "Pool 8 Ball", "Odds of 0.009"]);
        $app->dbNew->do($query, [53, "🃏", "Joker", "Odds of 0.0009"]);
        $app->dbNew->do($query, [54, "☘️", "Shamrock", "Odds of 9.0E-5"]);
        $app->dbNew->do($query, [55, "🪩", "Mirror Ball", "Odds of 9.0E-6"]);
        $app->dbNew->do($query, [56, "🥂", "Clinking Glasses", "Odds of 9.0E-7"]);
        $app->dbNew->do($query, [57, "🎩", "Top Hat", "Odds of 9.0E-8"]);
        $app->dbNew->do($query, [58, "💃", "Woman Dancing", "Odds of 9.0E-9"]);
        $app->dbNew->do($query, [59, "👺", "Goblin", "Odds of 9.0E-10"]);
    }
}
