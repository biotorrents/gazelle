<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CleanUserExtra extends AbstractMigration
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
    public function change()
    {
        $app = \Gazelle\App::go();

        $query = "
            alter table users_info
            drop column if exists viewAvatars,
            drop column if exists disableAvatar,
            drop column if exists disableInvites,
            drop column if exists disablePosting,
            drop column if exists disableForums,
            drop column if exists disableIrc,
            drop column if exists disableTagging,
            drop column if exists disableUpload,
            drop column if exists disableWiki,
            drop column if exists disablePm,
            drop column if exists disablePoints,
            drop column if exists disablePromotion,
            drop column if exists disableRequests,
            drop column if exists restrictedForums,
            drop column if exists permittedForums,
            drop column if exists unseededAlerts
        ";

        # we have permissions, dude
        $app->dbNew->do($query, []);
    }
}
