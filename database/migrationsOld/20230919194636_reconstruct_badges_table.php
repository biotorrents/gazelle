<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ReconstructBadgesTable extends AbstractMigration
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

        # drop the table if it exists
        $query = "drop table if exists badges";
        $app->dbNew->do($query, []);

        # create the table
        $query = <<<SQL
CREATE TABLE `badges` (
  `id` bigint(20) UNSIGNED NOT NULL DEFAULT uuid_short(),	
  `uuid` binary(16) NOT NULL DEFAULT unhex(replace(uuid(),'-','')),
  `icon` varchar(64) NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
SQL;

        $app->dbNew->do($query, []);

        # insert the existing data
        $query = <<<SQL
INSERT INTO `badges` (`id`, `icon`, `name`, `description`) VALUES

(10, '🥖', 'Baguette Bread', '16 GiB Downloaded'),
(11, '🍜', 'Steaming Bowl', '32 GiB Downloaded'),
(12, '🍷', 'Wine Glass', '64 GiB Downloaded'),
(13, '🍲', 'Pot of Food', '128 GiB Downloaded'),
(14, '🥩', 'Cut of Meat', '256 GiB Downloaded'),
(15, '🥗', 'Green Salad', '512 GiB Downloaded'),
(16, '☕', 'Hot Beverage', '1024 GiB Downloaded'),
(17, '🍨', 'Ice Cream', '2048 GiB Downloaded'),
(18, '🥧', 'Pie', '4096 GiB Downloaded'),
(19, '🥡', 'Takeout Box', '8192 GiB Downloaded'),

(20, '🔮', 'Crystal Ball', '16 GiB Uploaded'),
(21, '🧮', 'Abacus', '32 GiB Uploaded'),
(22, '⚗️', 'Alembic', '64 GiB Uploaded'),
(23, '🔬', 'Microscope', '128 GiB Uploaded'),
(24, '🔭', 'Telescope', '256 GiB Uploaded'),
(25, '☎️', 'Telephone', '512 GiB Uploaded'),
(26, '📺', 'Television', '1024 GiB Uploaded'),
(27, '🖥️', 'Desktop Computer', '2048 GiB Uploaded'),
(28, '🚀', 'Rocket', '4096 GiB Uploaded'),
(29, '🛰️', 'Satellite', '8192 GiB Uploaded'),

(30, '🥜', 'Peanuts', '10 Forum Posts'),
(31, '🎺', 'Trumpet', '20 Forum Posts'),
(32, '🐸', 'Frog', '50 Forum Posts'),
(33, '📢', 'Loudspeaker', '100 Forum Posts'),
(34, '🍆', 'Eggplant', '200 Forum Posts'),
(35, '🎙️', 'Studio Microphone', '500 Forum Posts'),
(36, '🍝', 'Spaghetti', '1,000 Forum Posts'),
(37, '📯', 'Postal Horn', '2,000 Forum Posts'),
(38, '🎪', 'Circus Tent', '5,000 Forum Posts'),
(39, '💩', 'Pile of Poo', '10,000 Forum Posts'),

(40, '🧠', 'Brain', '1% Chance by Login'),
(41, '🩸', 'Drop of Blood', '1% Chance by Login'),
(42, '🥽', 'Goggles', '1% Chance by Login'),
(43, '🏥', 'Hospital', '1% Chance by Login'),
(44, '🥼', 'Lab Coat', '1% Chance by Login'),
(45, '🦠', 'Microbe', '1% Chance by Login'),
(46, '🐒', 'Monkey', '1% Chance by Login'),
(47, '🐀', 'Rat', '1% Chance by Login'),
(48, '🩺', 'Stethoscope', '1% Chance by Login'),
(49, '🧪', 'Test Tube', '1% Chance by Login'),

(50, '🏵️', 'Rosette', '1,000 Bonus Points'),
(51, '🏆', 'Trophy', '2,000 Bonus Points'),
(52, '🐎', 'Horse', '5,000 Bonus Points'),
(53, '💰', 'Money Bag', '10,000 Bonus Points'),
(54, '🌷', 'Tulip', '20,000 Bonus Points'),
(55, '💍', 'Ring', '50,000 Bonus Points'),
(56, '🏺', 'Amphora', '100,000 Bonus Points'),
(57, '👑', 'Crown', '200,000 Bonus Points'),
(58, '🏰', 'Castle', '500,000 Bonus Points'),
(59, '🐲', 'Dragon Face', '1,000,000 Bonus Points'),

(60, '🎲', 'Game Die', 'Odds of 0.9'),
(61, '🎰', 'Slot Machine', 'Odds of 0.09'),
(62, '🎱', 'Pool 8 Ball', 'Odds of 0.009'),
(63, '🃏', 'Joker', 'Odds of 0.0009'),
(64, '☘️', 'Shamrock', 'Odds of 9.0E-5'),
(65, '🪩', 'Mirror Ball', 'Odds of 9.0E-6'),
(66, '🥂', 'Clinking Glasses', 'Odds of 9.0E-7'),
(67, '🎩', 'Top Hat', 'Odds of 9.0E-8'),
(68, '💃', 'Woman Dancing', 'Odds of 9.0E-9'),
(69, '👺', 'Goblin', 'Odds of 9.0E-10'),

(70, '🧸', 'Teddy Bear', 'Auction Winner'),

(80, '🪙', 'Coin', 'Early Investor'),

(100, '🎂', 'Birthday Cake', '1st Birthday Award'),
(110, '🍱', 'Bento Box', '3rd Birthday Award');
SQL;

        $app->dbNew->do($query, []);

        # delete some special badges (sorry guys)
        $query = "delete from users_badges where id in (40, 41, 42, 43, 44, 45, 46, 47, 48, 49)";

    }
}
