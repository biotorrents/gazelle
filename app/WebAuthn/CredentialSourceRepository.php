<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use ParagonIE\ConstantTime\Base64UrlSafe;
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

        $query = "select json from webauthn where credentialId = ? and deleted_at is null";
        $ref = $app->dbNew->single($query, [ Base64UrlSafe::encodeUnpadded($publicKeyCredentialId) ]);

        if (!$ref) {
            return null;
        }

        $data = json_decode($ref, true);
        if (!$data) {
            return null;
        }

        return PublicKeyCredentialSource::createFromArray($data);
    }


    /**
     * findAllForUserEntity
     *
     * This method lists all key sources associated to the user entity.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $app = \Gazelle\App::go();

        $query = "select json from webauthn where userId = ? and deleted_at is null";
        $ref = $app->dbNew->multi($query, [ $publicKeyCredentialUserEntity->getId() ]);

        $return = [];
        foreach ($ref as $row) {
            $data = json_decode($row["json"], true);
            $return[] = PublicKeyCredentialSource::createFromArray($data);
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

        # use the jsonSerialize method to get the data
        $publicKeyCredentialSource = $publicKeyCredentialSource->jsonSerialize();

        # prevent zeroing out the userId on login
        if ($app->user->isLoggedIn()) {
            # yes userId: create
            $query = "
                insert into webauthn
                    (uuid, userId, credentialId, type, transports, attestationType,
                    trustPath, aaguid, publicKey, userHandle, counter, json)
                values
                    (:uuid, :userId, :credentialId, :type, :transports, :attestationType,
                    :trustPath, :aaguid, :publicKey, :userHandle, :counter, :json)
            ";

            $variables = [
                "uuid" => $app->dbNew->uuid() ?? null,
                "userId" => $app->dbNew->uuidBinary($app->user->core["uuid"]) ?? null,
                "credentialId" => $publicKeyCredentialSource["publicKeyCredentialId"] ?? null,
                "type" => $publicKeyCredentialSource["type"] ?? null,
                "transports" => $publicKeyCredentialSource["transports"] ?? null,
                "attestationType" => $publicKeyCredentialSource["attestationType"] ?? null,
                "trustPath" => $publicKeyCredentialSource["trustPath"] ?? null,
                "aaguid" => $publicKeyCredentialSource["aaguid"] ?? null,
                "publicKey" => $publicKeyCredentialSource["credentialPublicKey"] ?? null,
                "userHandle" => $publicKeyCredentialSource["userHandle"] ?? null,
                "counter" => $publicKeyCredentialSource["counter"] ?? null,
                "json" => $publicKeyCredentialSource ?? null,
            ];

            # massage some of the variables
            $variables["transports"] = json_encode($variables["transports"]);
            $variables["trustPath"] = json_encode($variables["trustPath"]);
            $variables["aaguid"] = $app->dbNew->uuidBinary($variables["aaguid"]);
            $variables["json"] = json_encode($variables["json"]);

            $app->dbNew->do($query, $variables);
        } else {
            # no userId: update
            $query = "
                update webauthn set
                    type = :type, transports = :transports, attestationType = :attestationType,
                    trustPath = :trustPath, aaguid = :aaguid, publicKey = :publicKey,
                    userHandle = :userHandle, counter = :counter, json = :json
                where credentialId = :credentialId
            ";

            $variables = [
                "credentialId" => $publicKeyCredentialSource["publicKeyCredentialId"] ?? null,
                "type" => $publicKeyCredentialSource["type"] ?? null,
                "transports" => $publicKeyCredentialSource["transports"] ?? null,
                "attestationType" => $publicKeyCredentialSource["attestationType"] ?? null,
                "trustPath" => $publicKeyCredentialSource["trustPath"] ?? null,
                "aaguid" => $publicKeyCredentialSource["aaguid"] ?? null,
                "publicKey" => $publicKeyCredentialSource["credentialPublicKey"] ?? null,
                "userHandle" => $publicKeyCredentialSource["userHandle"] ?? null,
                "counter" => $publicKeyCredentialSource["counter"] ?? null,
                "json" => $publicKeyCredentialSource ?? null,
            ];

            # massage some of the variables
            $variables["transports"] = json_encode($variables["transports"]);
            $variables["trustPath"] = json_encode($variables["trustPath"]);
            $variables["aaguid"] = $app->dbNew->uuidBinary($variables["aaguid"]);
            $variables["json"] = json_encode($variables["json"]);

            $app->dbNew->do($query, $variables);
        }
    }


    /** custom methods */


    /**
     * findAllByUserUuid
     */
    public function findAllByUserUuid(string $userId): array
    {
        $app = \Gazelle\App::go();

        $query = "select json from webauthn where userId = ? and deleted_at is null";
        $ref = $app->dbNew->multi($query, [ $app->dbNew->uuidBinary($userId) ]);

        $return = [];
        foreach ($ref as $row) {
            $data = json_decode($row["json"], true);
            $return[] = PublicKeyCredentialSource::createFromArray($data);
        }

        return $return;
    }


    /**
     * findMetadataByUserUuid
     */
    public function findMetadataByUserUuid(string $userId): array
    {
        $app = \Gazelle\App::go();

        $query = "select credentialId, userHandle, created_at from webauthn where userId = ? and deleted_at is null";
        $ref = $app->dbNew->multi($query, [ $app->dbNew->uuidBinary($userId) ]);

        return $ref;
    }


    /**
     * deleteCredentialSource
     */
    public function deleteCredentialSource(string $publicKeyCredentialId): void
    {
        $app = \Gazelle\App::go();

        $query = "update webauthn set deleted_at = now() where credentialId = ?";
        $app->dbNew->do($query, [ $publicKeyCredentialId ]);
    }
} # class
