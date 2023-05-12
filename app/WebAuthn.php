<?php

declare(strict_types=1);

namespace Gazelle;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;

/**
 * Gazelle\WebAuthn
 *
 * WebAuthn server for FIDO2 authentication.
 * I really hope someone uses this feature.
 *
 * @see https://en.wikipedia.org/wiki/WebAuthn
 * @see https://github.com/web-auth/webauthn-lib
 * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way
 * @see https://webauthn.guide
 */

class WebAuthn
{
    # public key credential source repository
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#public-key-credential-source-repository
    private $publicKeyCredentialSourceRepository = null;

    # token binding handler
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#token-binding-handler
    private $tokenBindingHandler = null;

    # attestation statement support manager
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#attestation-statement-support-manager
    private $attestationStatementSupportManager = null;

    # attestation object loader
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#attestation-object-loader
    private $attestationObjectLoader = null;

    # public key credential loader
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#public-key-credential-loader
    private $publicKeyCredentialLoader = null;

    # extension output checker handler
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#extension-output-checker-handler
    private $extensionOutputCheckerHandler = null;

    # algorithm manager
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#algorithm-manager
    private $algorithmManager = null;

    # authenticator attestation response validator
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-attestation-response-validator
    private $authenticatorAttestationResponseValidator = null;

    # authenticator assertion response validator
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-assertion-response-validator
    private $authenticatorAssertionResponseValidator = null;


    /**
     * __construct
     */
    public function __construct(array $options = [])
    {
        # public key credential source repository
        # you can implement the required methods the way you want: Doctrine ORM, file storage...
        $this->publicKeyCredentialSourceRepository = "get it from the database";

        # token binding handler
        # at the time of writing, we recommend to ignore this feature
        $this->tokenBindingHandler = null;

        # attestation statement support manager
        # you should not ask for the attestation statement unless you are working on an application that requires a high level of trust
        $this->attestationStatementSupportManager = AttestationStatementSupportManager::create()
            ->add(NoneAttestationStatementSupport::create());

        # attestation object loader
        # this object will load the attestation statements received from the devices
        $this->attestationObjectLoader = AttestationObjectLoader::create(
            $this->attestationStatementSupportManager
        );

        # public key credential loader
        # this object will load the public key using from the attestation object
        $this->publicKeyCredentialLoader = PublicKeyCredentialLoader::create(
            $this->attestationObjectLoader
        );

        # extension output checker handler
        # if you use extensions, you may need to check the value returned by the security devices
        $this->extensionOutputCheckerHandler = ExtensionOutputCheckerHandler::create();

        # algorithm manager
        # we recommend the use of the following algorithms to cover all types of authenticators
        $this->algorithmManager = Manager::create()
            ->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),
                RS256::create(),
                RS384::create(),
                RS512::create(),
                PS256::create(),
                PS384::create(),
                PS512::create(),
                Ed256::create(),
                Ed512::create(),
            );

        # authenticator attestation response validator
        # this object is what you will directly use when receiving attestation responses (authenticator registration)
        $this->authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->attestationStatementSupportManager,
            $this->publicKeyCredentialSourceRepository,
            $this->tokenBindingHandler,
            $this->extensionOutputCheckerHandler
        );

        # authenticator assertion response validator
        # this object is what you will directly use when receiving assertion responses (user authentication)
        $this->authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->publicKeyCredentialSourceRepository,
            $this->tokenBindingHandler,
            $this->extensionOutputCheckerHandler,
            $this->algorithmManager
        );
    }
} # class
