<?php

use Phalcon\Crypt;
use Phalcon\DI\FactoryDefault;
use phpseclib\Crypt\RSA;

class Cryptor
{
	/**
	 * Create a secure key to encrypt AES 
	 *
	 * @author salvipascual
	 * @return String
	 */
	public static function createAESKey()
	{
		return openssl_random_pseudo_bytes(128);
//		return "T4\xb1\x8d\xa9\x98\x05\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3";
	}

	/**
	 * Encript using AES
	 *
	 * @author salvipascual
	 * @param String $key
	 * @param String $plainText
	 * @return String
	 */
	public static function encryptAES($key, $plainText)
	{	
		$crypt = new Crypt('aes-256-ctr', true);
		return $crypt->encrypt($plainText, $key);
	}

	/**
	 * Decript using AES
	 *
	 * @author salvipascual
	 * @param String $key
	 * @param String $cryptedText
	 * @return String
	 */
	public static function decryptAES($key, $cryptedText)
	{	
		$crypt = new Crypt('aes-256-ctr', true);
		return $crypt->decrypt($cryptedText, $key);
	}

	/**
	 * Generate Private/Public keys for a user
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @return Boolean: true if new keys were created
	 */
	public static function createRSAKeys($userId)
	{
		// do not allow empty users
		if(empty($userId)) return false;

		// do not recreate the key if the file exist
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$filePrivateKey = "$wwwroot/keys/{$userId}_private";
		$filePublicKey = "$wwwroot/keys/{$userId}_public";
		if(file_exists($filePrivateKey) && file_exists($filePublicKey)) return false;

		// create keys
		$rsa = new RSA();
		$rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS1);
		$rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS1);
		$keys = $rsa->createKey();

		// replace keys on the file system
		file_put_contents($filePrivateKey, $keys['privatekey']);
		file_put_contents($filePublicKey, $keys['publickey']);
		return true;
	}

	/**
	 * Get the Public Key for a user
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @return String
	 */
	public static function getRSAPublicKey($userId)
	{
		// do not allow empty users
		if(empty($userId)) return false;

		// create the key if it does not exist
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$filePublicKey = "$wwwroot/keys/{$userId}_public";
		if( ! file_exists($filePublicKey)) self::createRSAKeys($userId);

		// get the content of the Key file
		return file_get_contents($filePublicKey);
	}

	/**
	 * Get the Private Key for a user
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @return String
	 */
	public static function getRSAPrivateKey($userId)
	{
		// do not allow empty users
		if(empty($userId)) return false;

		// create the key if it does not exist
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$filePrivateKey = "$wwwroot/keys/{$userId}_private";
		if( ! file_exists($filePrivateKey)) self::createRSAKeys($userId);

		// get the content of the Key file
		return file_get_contents($filePrivateKey);
	}

	/**
	 * Encript using RSA
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @param String $plainText
	 * @return String
	 */
	public static function encryptRSA($userId, $plainText)
	{
		// do not allow empty users or texts
		if(empty($userId) || empty($plainText)) return "";

		// load the public key
		$rsa = new RSA();
		$rsa->loadKey(self::getRSAPublicKey($userId));

		// encript the text
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		return $rsa->encrypt($plainText);
	}

	/**
	 * Decript using RSA
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @param String $cipherText
	 * @return String
	 */
	public static function decryptRSA($userId, $cipherText)
	{
		// do not allow empty users or texts
		if(empty($userId) || empty($cipherText)) return "";

		// load the public key
		$rsa = new RSA();
		$rsa->loadKey(self::getRSAPrivateKey($userId));

		// decript the text
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		return $rsa->decrypt($cipherText);
	}
}