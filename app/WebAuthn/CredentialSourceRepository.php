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

        $query = "select json from webauthn where credentialId = ? and deleted_at is not null";
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

        $query = "select json from webauthn where userId = ? and deleted_at is not null";
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
            # yes userId
            $query = "
                insert into webauthn
                    (uuid, userId, credentialId, type, transports, attestationType,
                    trustPath, aaguid, credentialPublicKey, userHandle, counter, json)
                values
                    (:uuid, :userId, :credentialId, :type, :transports, :attestationType,
                    :trustPath, :aaguid, :credentialPublicKey, :userHandle, :counter, :json)
            ";
        } else {
            # no userId
            $query = "
                replace into webauthn
                    (uuid, credentialId, type, transports, attestationType,
                    trustPath, aaguid, credentialPublicKey, userHandle, counter, json)
                values
                    (:uuid, :credentialId, :type, :transports, :attestationType,
                    :trustPath, :aaguid, :credentialPublicKey, :userHandle, :counter, :json)
            ";
        }

        $variables = [
            "uuid" => $app->dbNew->uuid() ?? null,
            "credentialId" => $publicKeyCredentialSource["publicKeyCredentialId"] ?? null,
            "type" => $publicKeyCredentialSource["type"] ?? null,
            "transports" => $publicKeyCredentialSource["transports"] ?? null,
            "attestationType" => $publicKeyCredentialSource["attestationType"] ?? null,
            "trustPath" => $publicKeyCredentialSource["trustPath"] ?? null,
            "aaguid" => $publicKeyCredentialSource["aaguid"] ?? null,
            "credentialPublicKey" => $publicKeyCredentialSource["credentialPublicKey"] ?? null,
            "userHandle" => $publicKeyCredentialSource["userHandle"] ?? null,
            "counter" => $publicKeyCredentialSource["counter"] ?? null,
            "json" => $publicKeyCredentialSource ?? null,
        ];

        # massage some of the variables
        $variables["transports"] = json_encode($variables["transports"]);
        $variables["trustPath"] = json_encode($variables["trustPath"]);
        $variables["aaguid"] = $app->dbNew->uuidBinary($variables["aaguid"]);
        $variables["json"] = json_encode($variables["json"]);

        # check login status and set userId
        if ($app->user->isLoggedIn()) {
            $variables["userId"] = $app->dbNew->uuidBinary($variables["userId"]);
        } else {
            unset($variables["userId"]);
        }

        $app->dbNew->do($query, $variables);
    }
} # class
