<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gazelle\WebAuthn\CredentialSourceRepository
 *
 * @see https://webauthn-doc.spomky-labs.com/prerequisites/credential-source-repository
 */

class CredentialSourceRepository
{
    /**
     * findOneByCredentialId
     *
     * This method retreives a key source object from the credential id.
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $app = \Gazelle\App::go();

        $query = "select * from webauthn where publicKey = ?";
        $ref = $app->dbNew->single($query, [$publicKeyCredentialId]);

        return $ref;
    }


    /**
     * findAllForUserEntity
     *
     * This method lists all key sources associated to the user entity.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $app = \Gazelle\App::go();

        $query = "select * from webauthn where userId = ?";
        $ref = $app->dbNew->multi($query, [ $publicKeyCredentialUserEntity->getId() ]);

        return $ref;
    }


    /**
     * saveCredentialSource
     *
     * This method saves the key source in your storage.
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $app = \Gazelle\App::go();

        $query = "
            insert into webauthn (foo, bar, baz)
            values (:foo, :bar, :baz)
        ";

        $variables = [
            "foo" => "bar",
            "bar" => "baz",
            "baz" => "qux",
        ];

        $app->dbNew->do($query, $variables);
    }
} # class
