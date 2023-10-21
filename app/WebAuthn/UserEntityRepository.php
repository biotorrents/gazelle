<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gazelle\WebAuthn\UserEntityRepository
 *
 * @see https://webauthn-doc.spomky-labs.com/prerequisites/user-entity-repository#user-entity-repository
 */

class UserEntityRepository # implements PublicKeyCredentialUserEntityRepository
{
    /**
     * findOneByUsername
     *
     * This method tries to find out a user entity from the username.
     */
    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $app = \Gazelle\App::go();

        # get the uuid v7 from the username
        $query = "select uuid from users where username = ? and deleted_at is null";
        $userId = $app->dbNew->single($query, [$username]);

        if (!$userId) {
            return null;
        }

        return PublicKeyCredentialUserEntity::create(
            $username, # name
            $app->dbNew->binaryUuid($userId), # id
            $username, # display name
            null # icon
        );
    }


    /**
     * findOneByUserHandle
     *
     * This method tries to find out a user entity from the user handle, i.e., the user id.
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $app = \Gazelle\App::go();

        # get the userId from the userHandle
        $query = "select userId from webauthn where userHandle = ? and deleted_at is null";
        $userId = $app->dbNew->single($query, [$userHandle]);

        if (!$userId) {
            return null;
        }

        # get the username from the userId
        $query = "select username from users where uuid = ?";
        $username = $app->dbNew->single($query, [ $app->dbNew->binaryUuid($userId) ]);

        if (!$username) {
            return null;
        }

        return PublicKeyCredentialUserEntity::create(
            $username, # name
            $app->dbNew->binaryUuid($userId), # id
            $username, # display name
            null # icon
        );
    }


    /**
     * generateNextUserEntityId
     *
     * This method creates a user entity id.
     * Note that this method *shall not* save that id.
     * Its main purpose generate a unique id that could be used for a user entity object at a later stage.
     */
    public function generateNextUserEntityId(): string
    {
        $app = \Gazelle\App::go();

        return $app->dbNew->uuid();
    }


    /**
     * saveUserEntity
     *
     * This method saves the user entity.
     * If the user entity already exists, it should throw an exception.
     */
    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity): void
    {
        throw new \Exception("not implemented");
    }
} # class
