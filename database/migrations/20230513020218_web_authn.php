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
         * return [
         *   'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded($this->publicKeyCredentialId),
         *   'type' => $this->type,
         *   'transports' => $this->transports,
         *   'attestationType' => $this->attestationType,
         *   'trustPath' => $this->trustPath->jsonSerialize(),
         *   'aaguid' => $this->aaguid->__toString(),
         *   'credentialPublicKey' => Base64UrlSafe::encodeUnpadded($this->credentialPublicKey),
         *   'userHandle' => Base64UrlSafe::encodeUnpadded($this->userHandle),
         *   'counter' => $this->counter,
         *   'otherUI' => $this->otherUI,
         * ];
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

            ->addColumn("relyingPartyId", "string", ["limit" => 64, "default" => $app->env->siteDomain])

            # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#supported-attestation-statement-types
            ->addColumn("attestationType", "enum", [
                "androidKey",
                "androidSafetyNet",
                "apple",
                "fido-u2f",
                "none",
                "packed",
                "trustedPlatformModule",
            ], ["default" => "none"])

            ->addColumn("credentialId", "string", ["limit" => 128])
            ->addColumn("credentialPublicKey", "text")
            ->addColumn("certificateChain", "text", ["null" => true])
            ->addColumn("certificate", "text")
            ->addColumn("certificateIssuer", "string", ["limit" => 128])
            ->addColumn("certificateSubject", "string", ["limit" => 128])
            ->addColumn("signatureCounter", "smallinteger")
            ->addColumn("aaguid", "binary", ["length" => 16, "null" => true])
            ->addColumn("rootValid", "boolean", ["default" => false])
            ->addColumn("json", "json")

            # add datetimes (phinx uses timestamps by default)
            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            # add indices
            ->addIndex("uuid", ["unique" => true])
            ->addIndex("userId", ["userId", "credentialId", "aaguid"], ["unique" => true])

            ->create();
    }
}
