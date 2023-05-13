<?php

declare(strict_types=1);

namespace Gazelle\WebAuthn;

use ParagonIE\ConstantTime\Base64UrlSafe;
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
use Cose\Algorithms;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gazelle\WebAuthn\Base
 *
 * WebAuthn server for FIDO2 authentication.
 * I really hope someone uses this feature.
 *
 * @see https://en.wikipedia.org/wiki/WebAuthn
 * @see https://github.com/web-auth/webauthn-lib
 * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way
 * @see https://webauthn.guide
 */

class Base
{
    # the relying party
    # https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party
    private $relyingParty = null;

    # public key credential source repository
    # https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#public-key-credential-source-repository
    private $publicKeyCredentialSourceRepository = null;
    private $UserEntityRepository = null;

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

    # cryptographic challenges
    # https://www.w3.org/TR/webauthn-2/#sctn-cryptographic-challenges
    private $challengeLength = 32;

    # public key credential request timeout
    # if the user verification is preferred or required, the range is 300 to 600 seconds (5 to 10 minutes)
    # https://www.w3.org/TR/webauthn-2/#dom-publickeycredentialcreationoptions-timeout
    private $timeout = 300;


    /**
     * __construct
     */
    public function __construct(array $options = [])
    {
        $app = \Gazelle\App::go();

        # the relying party
        # the relying party corresponds to the application that will ask for the user to interact with the authenticator
        $this->relyingParty = PublicKeyCredentialRpEntity::create(
            $app->env->siteName, # the application name
            $app->env->siteDomain, # the application id = the domain
            null # the application icon = data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V...
        );

        # public key credential source repository
        # you can implement the required methods the way you want: Doctrine ORM, file storage...
        $this->publicKeyCredentialSourceRepository = new \Gazelle\WebAuthn\CredentialSourceRepository();
        $this->UserEntityRepository = new \Gazelle\WebAuthn\UserEntityRepository();

        # token binding handler
        # at the time of writing, we recommend to ignore this feature
        $this->tokenBindingHandler = null;

        # attestation statement support manager
        # you should not ask for the attestation statement unless you are working on an application that requires a high level of trust
        $this->attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $this->attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

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


    /** register authenticators */


    /**
     * creationRequest
     *
     * To associate a device to a user, you need to instantiate a Webauthn\PublicKeyCredentialCreationOptions object.
     *
     * It will need:
     *
     * - the relying party
     * - the user data
     * - a challenge (random binary string)
     * - a list of supported public key parameters, i.e., an algorithm list (at least one)
     *
     * Optionally, you can customize the following parameters:
     *
     * - a timeout
     * - a list of public key credential to exclude from the registration process
     * - the authenticator selection criteria
     * - attestation conveyance preference
     * - extensions
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration#creation-request
     */
    public function creationRequest(): string
    {
        $app = \Gazelle\App::go();

        # not logged in
        if (!$app->user->isLoggedIn()) {
            throw new \Exception("you must be logged in to register a security device");
        }

        # create a user entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            $app->user->core["username"], # name
            $app->dbNew->uuidBinary($app->user->core["uuid"]), # id
            $app->user->core["username"], # display name
            null # icon
        );

        # challenge
        $challenge = random_bytes($this->challengeLength);

        # public key credential parameters
        $publicKeyCredentialParametersList = [
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ES256K),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ES384),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ES512),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_RS384),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_RS512),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_PS256),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_PS384),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_PS512),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ED256),
            PublicKeyCredentialParameters::create("public-key", Algorithms::COSE_ALGORITHM_ED512),
        ];

        # https://webauthn-doc.spomky-labs.com/pure-php/advanced-behaviours/authentication-without-username
        $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create()
            ->setUserVerification(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED)
            ->setResidentKey(AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED);

        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $this->relyingParty,
                $userEntity,
                $challenge,
                $publicKeyCredentialParametersList,
            )
            ->setAuthenticatorSelection($authenticatorSelectionCriteria);

        # the options object can be converted into JSON and sent to the authenticator using the API
        # https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API

        # it is important to store the user entity and the options object (e.g., in the session) for the next step
        # the data will be needed to check the response from the device
        $_SESSION["publicKeyCredentialCreationOptions"] = $publicKeyCredentialCreationOptions;
        return json_encode($publicKeyCredentialCreationOptions->jsonSerialize());
    }


    /**
     * creationResponse
     *
     * What you receive must be a JSON object that looks like as follows:
     *
     * {
     *   "id": "KVb8CnwDjpgAo[...]op61BTLaa0tczXvz4JrQ23usxVHA8QJZi3L9GZLsAtkcVvWObA",
     *   "type": "public-key",
     *   "rawId": "KVb8CnwDjpgAo[...]rQ23usxVHA8QJZi3L9GZLsAtkcVvWObA==",
     *   "response": {
     *     "clientDataJSON": "eyJjaGFsbGVuZ2UiOiJQbk1hVjBVTS[...]1iUkdHLUc4Y3BDSdGUifQ==",
     *     "attestationObject": "o2NmbXRmcGFja2VkZ2F0dFN0bXSj[...]YcGhf"
     *   }
     * }
     *
     * There are two steps to perform with this object:
     *
     * - load the data
     * - verify it with the creation options set above
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration#creation-response
     */
    public function creationResponse($creationRequest)
    {
        $app = \Gazelle\App::go();

        # data loading
        # https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration#data-loading
        $publicKeyCredential = $this->publicKeyCredentialLoader->load($creationRequest);
        $publicKeyCredentialCreationOptions = $_SESSION["publicKeyCredentialCreationOptions"];

        # response verification
        # https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration#response-verification
        $authenticatorAttestationResponse = $publicKeyCredential->getResponse();
        if (!$authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            # e.g., process here with a redirection to the public key creation page
            throw new \Exception("unable to instantiate an AuthenticatorAttestationResponse object");
        }

        # the authenticator attestation response validator service will check everything for you:
        # challenge, origin, attestation statement, and much more
        $publicKeyCredentialSource = $this->authenticatorAttestationResponseValidator->check(
            $authenticatorAttestationResponse,
            $publicKeyCredentialCreationOptions,
            $app->env->siteDomain # "my-application.com"
        );

        # create a user entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            $app->user->core["username"], # name
            $app->dbNew->uuidBinary($app->user->core["uuid"]), # id
            $app->user->core["username"], # display name
            null # icon
        );

        # if no exception is thrown, the response is valid
        # you can store the public key credential source and associate it to the user entity
        $this->publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
        #$this->UserEntityRepository->saveUserEntity($userEntity);

        return $publicKeyCredentialSource;
    }


    /** authenticate your users */


    /**
     * assertionRequest
     *
     * To perform a user authentication using a security device, you need to instantiate a Webauthn\PublicKeyCredentialRequestOptions object.
     *
     * Let's say you want to authenticate the user we used earlier.
     * This options object will need:
     *
     * - a challenge (random binary string)
     * - the list with the allowed credentials (may be an option in certain circumstances)
     *
     * Optionally, you can customize the following parameters:
     *
     * - a timeout
     * - the relying party id, i.e., your application domain
     * - the user verification requirement
     * - extensions
     *
     * The PublicKeyCredentialRequestOptions object is designed to be easily serialized into a JSON object.
     * This will ease the integration into an HTML page or through an API endpoint.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users#assertion-request
     */
    public function assertionRequest(PublicKeyCredentialUserEntity $userEntity): string
    {
        $app = \Gazelle\App::go();

        # allowed credentials
        # https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users#allowed-credentials

        # list of registered PublicKeyCredentialDescriptor classes associated to the user
        $registeredAuthenticators = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $registeredAuthenticators
        );

        # public key credential request options
        $publicKeyCredentialRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                random_bytes($this->challengeLength) # challenge
            )
            ->allowCredentials(...$allowedCredentials) # important!
            ->setTimeout($this->timeout)
            ->setUserVerification(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
            );

        # again, save this shared object in the session
        $_SESSION["publicKeyCredentialRequestOptions"] = $publicKeyCredentialRequestOptions;
        return json_encode($publicKeyCredentialRequestOptions->jsonSerialize());
    }


    /**
     * assertionResponse
     *
     * What you receive must be a JSON object that looks like as follows:
     *
     * {
     *   "id": "KVb8CnwDjpgAo[...]op61BTLaa0tczXvz4JrQ23usxVHA8QJZi3L9GZLsAtkcVvWObA",
     *   "type": "public-key",
     *   "rawId": "KVb8CnwDjpgAo[...]rQ23usxVHA8QJZi3L9GZLsAtkcVvWObA==",
     *   "response": {
     *     "clientDataJSON": "eyJjaGFsbGVuZ2UiOiJQbk1hVjBVTS[...]1iUkdHLUc4Y3BDSdGUifQ==",
     *     "authenticatorData": "Y0EWbxTqi9hWTO[...]4aust69iUIzlwBfwABDw==",
     *     "signature": "MEQCIHpmdruQLs[...]5uwbtlPNOFM2oTusx2eg==",
     *     "userHandle": ""
     *   }
     * }
     *
     * There are two steps to perform with this object:
     *
     * - load the data
     * - verify the loaded data against the assertion options set above
     */
    public function assertionResponse(string $assertionRequest)
    {
        $app = \Gazelle\App::go();

        # data loading
        # https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users#data-loading
        $publicKeyCredential = $this->publicKeyCredentialLoader->load($assertionRequest);
        $publicKeyCredentialRequestOptions = $_SESSION["publicKeyCredentialRequestOptions"];

        # response verification
        # https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users#response-verification
        $authenticatorAssertionResponse = $publicKeyCredential->getResponse();
        if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            # e.g., process here with a redirection to the public key login/MFA page
            throw new \Exception("unable to instantiate an AuthenticatorAssertionResponse object");
        }

        # get the userHandle
        $query = "select userHandle from webauthn where credentialId = ?";
        $userHandle = $app->dbNew->single($query, [ Base64UrlSafe::encodeUnpadded($publicKeyCredential->getRawId()) ]);

        # if no exception is thrown, the response is valid and you can continue the authentication of the user
        $publicKeyCredentialSource = $this->authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $authenticatorAssertionResponse,
            $publicKeyCredentialRequestOptions,
            $app->env->siteDomain, # "my-application.com"
            Base64UrlSafe::decode($userHandle) ?? null
        );

        return $publicKeyCredentialSource;
    }
} # class
