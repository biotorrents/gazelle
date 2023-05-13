<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WebAuthn extends AbstractMigration
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
    public function change(): void
    {
        $app = \Gazelle\App::go();

        /**
         * webauthn
         *
         * return new self(
         *   Base64::decodeUrlSafe($data['publicKeyCredentialId']),
         *   $data['type'],
         *   $data['transports'],
         *   $data['attestationType'],
         *   TrustPathLoader::loadTrustPath($data['trustPath']),
         *   $uuid,
         *   Base64::decodeUrlSafe($data['credentialPublicKey']),
         *   Base64::decodeUrlSafe($data['userHandle']),
         *   $data['counter'],
         *   $data['otherUI'] ?? null
         * );
         *
         * @see https://github.com/web-auth/webauthn-lib/blob/v4.0/src/PublicKeyCredentialSource.php
         */
        $table = $this->table("webauthn");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("userId", "binary", [
                "length" => 16,
                "null" => false,
            ])

            # https://github.com/web-auth/webauthn-lib/blob/v4.0/src/PublicKeyCredentialSource.php
            ->addColumn("credentialId", "string", ["limit" => 128, "null" => false])
            ->addColumn("type", "string", ["limit" => 64, "null" => true])
            ->addColumn("transports", "json", ["null" => true])

            # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#supported-attestation-statement-types
            ->addColumn("attestationType", "enum", ["values" => [
                "androidKey",
                "androidSafetyNet",
                "apple",
                "fido-u2f",
                "none",
                "packed",
                "trustedPlatformModule",
            ], "default" => "none", "null" => true])

            # https://github.com/web-auth/webauthn-lib/blob/v4.0/src/PublicKeyCredentialSource.php
            ->addColumn("trustPath", "text", ["null" => true])
            ->addColumn("aaguid", "binary", ["length" => 16, "null" => false])
            ->addColumn("credentialPublicKey", "text", ["null" => false])
            ->addColumn("userHandle", "string", ["limit" => 128, "null" => false])
            ->addColumn("counter", "smallinteger", ["null" => true])
            ->addColumn("json", "json", ["null" => false])

            # add datetimes (phinx uses timestamps by default)
            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            # add indices
            ->addIndex("uuid", ["unique" => true])
            ->addIndex(["uuid", "userId", "credentialId", "aaguid"], ["unique" => true])

            ->create();
    }
}
