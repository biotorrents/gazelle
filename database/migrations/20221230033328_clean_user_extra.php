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
        $app = App::go();

        $query = "
            alter table users_info
            drop column viewAvatars,
            drop column disableAvatar,
            drop column disableInvites,
            drop column disablePosting,
            drop column disableForums,
            drop column disableIrc,
            drop column disableTagging,
            drop column disableUpload,
            drop column disableWiki,
            drop column disablePm,
            drop column disablePoints,
            drop column disablePromotion,
            drop column disableRequests,
            drop column restrictedForums,
            drop column permittedForums,
            drop column unseededAlerts
        ";

        # we have permissions, dude
        $app->dbNew->do($query, []);
    }
}
