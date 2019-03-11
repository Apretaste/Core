<?php

use Phalcon\DI\FactoryDefault;
use phpseclib\Crypt\RSA;

class Cryptor{
	private const IV = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
	/**
	 * Create a secure key to encrypt AES 
	 *
	 * @author salvipascual
	 * @return String
	 */
	public static function createAESKey(){
		return openssl_random_pseudo_bytes(32);
//		return "T4\xb1\x8d\xa9\x98\x05\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3";
	}

	/**
	 * Encript using AES
	 *
	 * @author ricardo
	 * @param String $key
	 * @param String $plainText
	 * @return String
	 */
	public static function encryptAES($key, $rawData){
		return openssl_encrypt($rawData, "AES-256-CTR", $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, self::IV);
	}

	/**
	 * Decript using AES
	 *
	 * @author ricardo
	 * @param String $key
	 * @param String $cryptedText
	 * @return String
	 */
	public static function decryptAES($key, $AEScrypted){
		return openssl_decrypt($AEScrypted, "AES-256-CTR", $key, true, self::IV);
	}

	/**
	 * Generate Private/Public keys for a user
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @return Boolean: true if new keys were created
	 */
	public static function createRSAKeys($userId){
		// do not allow empty users
		if(empty($userId)) return false;

		// do not recreate the key if the file exist
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		$filePrivateKey = "$wwwroot/keys/server_{$userId}_private";
		$filePublicKey = "$wwwroot/keys/server_{$userId}_public";
		if(file_exists($filePrivateKey) && file_exists($filePublicKey)) return false;

		exec("openssl genrsa -out $filePrivateKey 2048 && openssl rsa -in $filePrivateKey -pubout > $filePublicKey");
		return true;
	}

	/**
	 * Get the Public Key for a user
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @return String
	 */
	public static function getRSAPublicKey($userId, $keySource){
		// do not allow empty users
		if(empty($userId)) return false;

		// create the key if it does not exist
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		$filePublicKey = "$wwwroot/keys/{$keySource}_{$userId}_public";
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
	public static function getRSAPrivateKey($userId, $initKey){
		// do not allow empty users
		if(empty($userId)) return false;

		// create the key if it does not exist
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		$filePrivateKey = $initKey ? "$wwwroot/configs/RSAPrivate.key" : "$wwwroot/keys/server_{$userId}_private";
		if( ! file_exists($filePrivateKey)) self::createRSAKeys($userId);

		// get the content of the Key file
		return file_get_contents($filePrivateKey);
	}

	/**
	 * Save user app public key
	 * 
	 * @author ricardo
	 * @param String $userId
	 * @param String $key
	 */

	public static function saveAppRSAKey($userId, $key){
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		file_put_contents("$wwwroot/keys/app_{$userId}_public", $key);
	}

	/**
	 * Encript using RSA
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @param String $plainText
	 * @return String
	 */
	public static function encryptRSA($userId, $plainText, $keySource = 'app'){
		// do not allow empty users or texts
		if(empty($userId) || empty($plainText)) return "";

		// load the public key
		$rsa = new RSA();
		$rsa->loadKey(self::getRSAPublicKey($userId, $keySource));

		// encript the text
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		return $rsa->encrypt($plainText);
	}

	/**
	 * Decript using RSA
	 *
	 * @author salvipascual
	 * @param String $userId
	 * @param String $RSAencrypted
	 * @return String
	 */
	public static function decryptRSA($userId, $RSAencrypted){
		// do not allow empty users or texts
		if(empty($userId) || empty($RSAencrypted)) return "";

		// load server key in first init
		$initKey = substr($RSAencrypted, 0, 6) == "reload";
		if($initKey) $RSAencrypted = substr($RSAencrypted, 6);

		// load the public key
		$rsa = new RSA();
		$rsa->loadKey(self::getRSAPrivateKey($userId, $initKey));

		// decript the text
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		return $rsa->decrypt($RSAencrypted);
	}
}