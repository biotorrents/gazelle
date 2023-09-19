<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RandomBadges extends AbstractMigration
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

        $app->dbNew->do($query, [40, "🧠", "Brain", "1% Chance by Login"]);
        $app->dbNew->do($query, [41, "🩸", "Drop of Blood", "1% Chance by Login"]);
        $app->dbNew->do($query, [42, "🥽", "Goggles", "1% Chance by Login"]);
        $app->dbNew->do($query, [43, "🏥", "Hospital", "1% Chance by Login"]);
        $app->dbNew->do($query, [44, "🥼", "Lab Coat", "1% Chance by Login"]);
        $app->dbNew->do($query, [45, "🦠", "Microbe", "1% Chance by Login"]);
        $app->dbNew->do($query, [46, "🐒", "Monkey", "1% Chance by Login"]);
        $app->dbNew->do($query, [47, "🐀", "Rat", "1% Chance by Login"]);
        $app->dbNew->do($query, [48, "🩺", "Stethoscope", "1% Chance by Login"]);
        $app->dbNew->do($query, [49, "🧪", "Test Tube", "1% Chance by Login"]);
    }
}
