<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Services\Webauthn;

use Cose\Algorithms;
use CBOR\Decoder;
use CBOR\Normalizable;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Symfony\Component\Uid\Uuid;
use TikiDb;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorData;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\StringStream;

class WebauthnController
{
    private $webAuthnTable;

    private const CREDENTIAL_CREATION_TIMEOUT_MS = 60000;

    public function __construct()
    {
        $this->webAuthnTable = TikiDb::get()->table('tiki_webauthn_credentials');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function actionCreateCredential($input)
    {
        global $url_host;

        $userName = $input->username->text();

        if (empty($userName)) {
            return [
                'status' => 'error',
                'message' => tr('Username is required')
            ];
        }

        try {
            $rpEntity = PublicKeyCredentialRpEntity::create(
                'TikiWiki',
                $url_host
            );

            $uuid = \Tiki\Utilities\Identifiers::generateUUID();
            $userEntity = PublicKeyCredentialUserEntity::create(
                $userName,
                $uuid,
                $userName
            );

            $publicKeyCredentialParametersList = [
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256)
            ];

            $challenge = random_bytes(32);

            $excludedPublicKeyDescriptors = $this->getExcludedCredentials($userName);

            $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
                $rpEntity,
                $userEntity,
                $challenge,
                $publicKeyCredentialParametersList,
                AuthenticatorSelectionCriteria::create(),
                PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                $excludedPublicKeyDescriptors,
                self::CREDENTIAL_CREATION_TIMEOUT_MS
            );

            $_SESSION['publicKeyCredentialCreationOptions'] = $publicKeyCredentialCreationOptions;

            $options = [
                'rp' => [
                    'name' => $publicKeyCredentialCreationOptions->rp->name,
                    'id' => $publicKeyCredentialCreationOptions->rp->id
                ],
                'user' => [
                    'name' => $publicKeyCredentialCreationOptions->user->name,
                    'id' => base64_encode($publicKeyCredentialCreationOptions->user->id),
                    'displayName' => $publicKeyCredentialCreationOptions->user->displayName
                ],
                'challenge' => base64_encode($publicKeyCredentialCreationOptions->challenge),
                'pubKeyCredParams' => array_map(function ($param) {
                    return [
                        'type' => $param->type,
                        'alg' => $param->alg
                    ];
                }, $publicKeyCredentialCreationOptions->pubKeyCredParams),
                'timeout' => $publicKeyCredentialCreationOptions->timeout,
                'authenticatorSelection' => [
                    'residentKey'        => 'required',
                    'requireResidentKey' => true,
                    'userVerification'   => 'required',
                ],
                'attestation' => $publicKeyCredentialCreationOptions->attestation
            ];

            return ['options' => $options, 'status' => 'success'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => tr($e->getMessage())];
        }
    }

    private function getExcludedCredentials($userName)
    {
        $credentials = $this->webAuthnTable->fetchAll(['credential_id'], ['user' => $userName]);

        if (empty($credentials)) {
            return [];
        }

        $excludedDescriptors = [];

        foreach ($credentials as $credential) {
            $excludedDescriptors[] = PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                base64_decode($credential['credential_id'])
            );
        }

        return $excludedDescriptors;
    }

    public function actionRegisterResponse($input)
    {
        global $url_host;

        $attestationObject = $input->attestationObject->text();
        $clientDataJSON = $input->clientDataJSON->text();

        try {
            $manager = new AttestationStatementSupportManager();
            $attestationObjectLoader = new AttestationObjectLoader($manager);
            $validator = new AuthenticatorAttestationResponseValidator($manager);
            $attestationObject = $attestationObjectLoader->load($attestationObject);

            $collectedClientData = new CollectedClientData(
                $clientDataJSON,
                json_decode(base64_decode($clientDataJSON), true)
            );

            $authenticatorAttestationResponse = new AuthenticatorAttestationResponse(
                $collectedClientData,
                $attestationObject
            );

            if (! $authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
                return [
                    'status' => 'error',
                    'message' => tr('Invalid response type')
                ];
            }

            $publicKeyCredentialCreationOptions = $_SESSION['publicKeyCredentialCreationOptions'];

            $publicKeyCredentialSource = $validator->check(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                $url_host
            );

            $deviceName = $input->device_name->text();
            $aaguid = (string) $attestationObject->authData->attestedCredentialData->aaguid;

            $existingAuthenticator = $this->webAuthnTable->fetchRow(['authenticator_id'], [
                'authenticator_id' => $aaguid,
                'user' => $publicKeyCredentialCreationOptions->user->name
            ]);

            if ($existingAuthenticator) {
                return [
                    'status' => 'success',
                    'code' => 'AUTHENTICATOR_EXIST',
                    'message' => tr('This authenticator has already been registered.')
                ];
            }

            $this->webAuthnTable->insert([
                'credential_id' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId),
                'public_key' => base64_encode($publicKeyCredentialSource->credentialPublicKey),
                'sign_count' => $publicKeyCredentialSource->counter,
                'user' => $publicKeyCredentialCreationOptions->user->name,
                'user_handle' => $publicKeyCredentialSource->userHandle,
                'device_name' => $deviceName,
                'authenticator_id' => $aaguid,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'status' => 'success',
                'code' => 'AUTHENTICATOR_REGISTRED',
                'message' => tr('Credential registered successfully')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => tr($e->getMessage())
            ];
        }
    }

    public function actionLoginStart($input)
    {
        global $url_host;

        $challenge = random_bytes(32);

        // Empty allowCredentials = usernameless / discoverable-credential flow.
        // The browser presents all passkeys saved for this site; no username needed.
        $options = PublicKeyCredentialRequestOptions::create(
            $challenge,
            $url_host,
            [],
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
        );

        return ['options' => $options, 'status' => 'success'];
    }

    public function actionLoginFinish($input)
    {
        $credential_id = $input->rawId->text();
        $clientDataJSON = $input->clientDataJSON->text();
        $authenticatorData = $input->authenticatorData->text();
        $signature = $input->signature->text();

        if (empty($credential_id) || empty($clientDataJSON) || empty($authenticatorData) || empty($signature)) {
            return ['status' => 'error', 'message' => tr('Invalid authenticator data received')];
        }

        $authDataBinary = base64_decode($authenticatorData);

        if (strlen($authDataBinary) < 37) {
            return ['status' => 'error', 'message' => tr('Invalid authenticator data received')];
        }

        // Usernameless flow: identify the user from the stored credential, not from client input.
        $userData = $this->webAuthnTable->fetchRow(
            ['user', 'user_handle', 'public_key', 'sign_count'],
            ['credential_id' => $credential_id]
        );

        if (empty($userData)) {
            return ['status' => 'error', 'message' => tr('Passkey not recognized. Please sign in with your password.')];
        }

        $userName = $userData['user'];

        try {
            $collectedClientData = new CollectedClientData(
                $clientDataJSON,
                json_decode(base64_decode($clientDataJSON), true)
            );

            $rpIdHash = substr($authDataBinary, 0, 32);
            $flagsByte = $authDataBinary[32];
            $signCount = (int) $userData['sign_count'] + 1;

            $authenticatorData = new AuthenticatorData(
                $authDataBinary,
                $rpIdHash,
                $flagsByte,
                $signCount,
                null,
                null
            );

            $signature = base64_decode($signature);

            $authenticatorResponse = new AuthenticatorAssertionResponse(
                $collectedClientData,
                $authenticatorData,
                $signature,
                $userData['user_handle']
            );

            $credentialPublicKey = base64_decode($userData['public_key']);
            $coseKey = $this->getCoseKey($credentialPublicKey);
            $pemPubKey = $this->getPublicKey($coseKey);

            $clientDataJSON = base64_decode($authenticatorResponse->clientDataJSON->rawData);
            $authenticatorData = $authenticatorResponse->authenticatorData->authData;
            $signature = $authenticatorResponse->signature;

            $hashedClientDataJSON = hash('sha256', $clientDataJSON, true);
            $dataToVerify = $authenticatorData . $hashedClientDataJSON;

            $verificationResult = SignatureVerifier::verify($dataToVerify, $signature, $pemPubKey);
            if ($verificationResult) {
                $_SESSION['webauthn_user'] = $userName;
                $this->webAuthnTable->update([
                    'sign_count' => $signCount,
                    'last_signin' => date('Y-m-d H:i:s')
                ], [
                    'credential_id' => $credential_id,
                ]);
                return ['status' => 'success', 'message' => tr('Signature verification successful')];
            }
            return ['status' => 'error', 'message' => tr('Signature verification error: Signature is invalid')];
        } catch (\Exception $e) {
            $errorMsg = 'Signature verification error: ' . $e->getMessage();
            return ['status' => 'error', 'message' => tr($errorMsg)];
        }
    }

    private function getCoseKey($credentialPublicKey)
    {
        $stream = new StringStream($credentialPublicKey);
        $decoder = new Decoder();
        $decoded = $decoder->decode($stream);
        if (! $decoded instanceof Normalizable) {
            $errorMsg = tr('We encountered an issue while verifying your security key. Please try again or use a different key.');
            throw new \Exception($errorMsg);
        }
        return Ec2Key::create($decoded->normalize());
    }

    private function getPublicKey(Key $coseKey)
    {
        if (! $coseKey instanceof Ec2Key) {
            $errorMsg = tr('We encountered an issue with the security key format. Please try again or use a different key.');
            throw new \Exception($errorMsg);
        }
        return $coseKey->asPEM();
    }
}
