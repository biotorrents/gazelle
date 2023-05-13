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

class UserEntityRepository implements PublicKeyCredentialUserEntityRepository
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
        $query = "select uuid from users where username = ?";
        $ref = $app->dbNew->single($query, [$username]);

        if (!$ref) {
            return null;
        }

        # get the user entity from the uuid v7
        $query = "select json from webauthn_users where userId = ?";
        $ref = $app->dbNew->single($query, [$ref]);

        if (!$ref) {
            return null;
        }

        $data = json_decode($ref, true);
        if (!$data) {
            return null;
        }

        return self::createFromArray($data);
    }


    /**
     * findOneByUserHandle
     *
     * This method tries to find out a user entity from the user handle, i.e., the user id.
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $app = \Gazelle\App::go();

        $query = "select json from webauthn_users where userId = ?";
        $ref = $app->dbNew->single($query, [$userHandle]);

        if (!$ref) {
            return null;
        }

        $data = json_decode($ref, true);
        if (!$data) {
            return null;
        }

        return self::createFromArray($data);
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
        $app = \Gazelle\App::go();

        # does it already exist?
        $query = "select 1 from webauthn_users where userId = ?";
        $bad = $app->dbNew->single($query, [ $userEntity->getId() ]);

        if ($bad) {
            throw new \Exception("user entity already exists");
        }

        # insert the user entity
        $query = "
            insert into webauthn_users (userId, displayName, json)
            values (:userId, :displayName, :json)
        ";

        $variables = [
            "userId" => $userEntity->getId(),
            "displayName" => $userEntity->getDisplayName(),
            "json" => $userEntity->jsonSerialize(),
        ];

        # massage some of the variables
        $variables["json"] = json_encode($variables["json"]);

        $app->dbNew->do($query, $variables);
    }
} # class
