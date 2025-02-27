<?php

namespace OCA\PeppolNext\Service\Peppol;

use Exception;

use phpseclib3\Crypt\{RSA, Random};
use phpseclib3\File\X509;

use OCP\IUserSession;

use OCA\PeppolNext\AppInfo\Application;
use OCA\PeppolNext\Service\Helper\FolderManager;
use OCA\PeppolNext\Db\PeppolIdentityMapper;
use OCA\PeppolNext\Db\PeppolIdentity;

class AS4DirectService implements IPeppolService {

	public const SERVICE_NAME = 'AS4DirectService';

	private const IDENTITY_FILE = 'AS4DirectIdentity';
	private const KEYSTORE_FILE = 'AS4DirectIdentity.p12';

	/** @var IUserSession */
    private $userSession;

	/** @var FolderManager */
	private $folderManager;

	/** @var PeppolIdentityMapper */
	private $peppolIdentityMapper;

	public function __construct(IUserSession $userSession
		, FolderManager $folderManager
		, PeppolIdentityMapper $peppolIdentityMapper) {
		$this->userSession = $userSession;
		$this->folderManager = $folderManager;
		$this->peppolIdentityMapper = $peppolIdentityMapper;
	}

	public function getServiceName(): string {
		return self::SERVICE_NAME;
	}

	public function getIdentity(): ?PeppolIdentity {
		$user = $this->userSession->getUser();
		$user_id = $user->getUID();

		try {
			$peppolIdentity = $this->peppolIdentityMapper->findUserIdentity($user_id, self::SERVICE_NAME);
			return $peppolIdentity;

			return [
				'scheme' => $peppolIdentity->getScheme(),
				'id' => $peppolIdentity->getPeppolId(),
				'certificate' => $peppolIdentity->getCertificate()
			];
		} catch(Exception $e) {
			return null;
		}
	}

	public function getPrivateKeyAndCertificate(PeppolIdentity $identity): ?array {
		$file = $this->folderManager->getForUser(self::KEYSTORE_FILE, $identity->getUserId());
		$cert_store = $file->getContent();
		$passphrase = $identity->getUserId();

		if (!openssl_pkcs12_read($cert_store, $cert_info, $passphrase)) {
			echo "Error: Unable to read the cert store.\n";
			return [null, null];
		}

		$private_key = RSA::loadPrivateKey($cert_info['pkey']);

		$cert = new X509();
		$cert->loadX509($cert_info['cert']);

		return [$private_key, $cert];
	}

	public function generateIdentity(): PeppolIdentity {
		$user = $this->userSession->getUser();
		$user_id = $user->getUID();
		$name = $user->getDisplayName();

		$privateKey = RSA::createKey(2048)->withPadding(RSA::ENCRYPTION_OAEP);
		$publicKey = $privateKey->getPublicKey();
		
		$subject = new X509();
		$subject->setPublicKey($publicKey);
		$subject->setDN("/CN=$name");

		$issuer = new X509();
		$issuer->setPrivateKey($privateKey);
		$issuer->setDN("/CN=$name");

		$x509 = new X509();
		$result = $x509->sign($issuer, $subject); 
		$certificate = $x509->saveX509($result);

		$keystore_password = $user->getUID();
		$keystore_content = null;

		if (!openssl_pkcs12_export($certificate, $keystore_content, $privateKey->__toString(), $keystore_password)) {
			throw new Exception("Error Processing Request", 1);
		}

		$this->folderManager->createFile(self::KEYSTORE_FILE, $keystore_content);

		try {
			$peppolIdentity = $this->peppolIdentityMapper->findUserIdentity($user_id, self::SERVICE_NAME);
			$peppolIdentity->setUserId($user_id);
			$peppolIdentity->setScheme('iso6523-actorid-upis');
			$peppolIdentity->setPeppolId(uniqid('as4direct-'));
			$peppolIdentity->setCertificate($certificate);
			$this->peppolIdentityMapper->update($peppolIdentity);
		} catch(Exception $e) {
			$peppolIdentity = new PeppolIdentity();
			$peppolIdentity->setUserId($user_id);
			$peppolIdentity->setScheme('iso6523-actorid-upis');
			$peppolIdentity->setPeppolId(uniqid('as4direct-'));
			$peppolIdentity->setCertificate($certificate);
			$peppolIdentity->setServiceName(self::SERVICE_NAME);
			$this->peppolIdentityMapper->insert($peppolIdentity);
		}

		return $peppolIdentity;
	}

}
