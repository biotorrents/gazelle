<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gazelle\WebAuthn\UserEntityRepository
 *
 * @see https://webauthn-doc.spomky-labs.com/prerequisites/user-entity-repository#user-entity-repository
 */

class UserEntityRepository
{
    /**
     * findOneByUsername
     *
     * This method tries to find out a user entity from the username.
     */
    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
    }


    /**
     * findOneByUserHandle
     *
     * This method tries to find out a user entity from the user handle, i.e., the user id.
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
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
        return \Ramsey\Uuid\Uuid::uuid7()->toString();
    }


    /**
     * saveUserEntity
     *
     * This method saves the user entity.
     * If the user entity already exists, it should throw an exception.
     */
    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity): void
    {
    }
} # class
