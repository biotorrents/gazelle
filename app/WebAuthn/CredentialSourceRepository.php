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
    }


    /**
     * findAllForUserEntity
     *
     * This method lists all key sources associated to the user entity.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
    }


    /**
     * saveCredentialSource
     *
     * This method saves the key source in your storage.
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
    }
} # class
