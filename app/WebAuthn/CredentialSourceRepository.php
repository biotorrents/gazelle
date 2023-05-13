<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gazelle\WebAuthn\CredentialSourceRepository
 *
 * @see https://webauthn-doc.spomky-labs.com/prerequisites/credential-source-repository
 */

class CredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * findOneByCredentialId
     *
     * This method retreives a key source object from the credential id.
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $app = \Gazelle\App::go();

        $query = "select json from webauthn_sources where credentialId = ?";
        $ref = $app->dbNew->single($query, [$publicKeyCredentialId]);

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
     * findAllForUserEntity
     *
     * This method lists all key sources associated to the user entity.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $app = \Gazelle\App::go();

        $query = "select json from webauthn_sources where userId = ?";
        $ref = $app->dbNew->multi($query, [ $publicKeyCredentialUserEntity->getId() ]);

        $return = [];
        foreach ($ref as $row) {
            $data = json_decode($row, true);
            $return[] = self::createFromArray($data);
        }

        return $return;
    }


    /**
     * saveCredentialSource
     *
     * This method saves the key source in your storage.
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $app = \Gazelle\App::go();

        # get the descriptor (contains the userId, etc.)
        $publicKeyCredentialDescriptor = $publicKeyCredentialSource->getPublicKeyCredentialDescriptor();

        # insert the credential source
        $query = "
            insert into webauthn_sources
                (uuid, userId, credentialId, type, transports, attestationType,
                trustPath, aaguid, credentialPublicKey, userHandle, counter, json)
            values
                (:uuid, :userId, :credentialId, :type, :transports, :attestationType,
                :trustPath, :aaguid, :credentialPublicKey, :userHandle, :counter, :json)
        ";

        $variables = [
            "uuid" => $app->dbNew->uuid() ?? null,
            "userId" => $publicKeyCredentialDescriptor->getId() ?? null,
            "credentialId" => $publicKeyCredentialSource->getPublicKeyCredentialId() ?? null,
            "type" => $publicKeyCredentialSource->getType() ?? null,
            "transports" => json_encode($publicKeyCredentialId->getTransports() ?? null),
            "attestationType" => $publicKeyCredentialSource->getAttestationType() ?? null,
            "trustPath" => $publicKeyCredentialSource->getTrustPath() ?? null,
            "aaguid" => $publicKeyCredentialSource->getAaguid()->toBinary() ?? null,
            "credentialPublicKey" => $publicKeyCredentialSource->getCredentialPublicKey() ?? null,
            "userHandle" => $publicKeyCredentialSource->getUserHandle() ?? null,
            "counter" => $publicKeyCredentialSource->getCounter() ?? null,
            "json" => json_encode($publicKeyCredentialSource->jsonSerialize() ?? null),
        ];

        $app->dbNew->do($query, $variables);
    }
} # class
