<?php

namespace Tengyue\Infra;

use Tengyue\Infra\CryptInterface;
use Tengyue\Infra\Crypt\Exception;
use Tengyue\Infra\Crypt\Mismatch;

/**
 * Tengyue\Infra\Crypt
 *
 * Provides encryption facilities to Tengyue\Infra applications.
 *
 * <code>
 * use Tengyue\Infra\Crypt;
 *
 * $crypt = new Crypt();
 *
 * $crypt->setCipher('aes-256-ctr');
 *
 * $key  = "T4\xb1\x8d\xa9\x98\x05\\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3";
 * $text = "The message to be encrypted";
 *
 * $encrypted = $crypt->encrypt($text, $key);
 *
 * echo $crypt->decrypt($encrypted, $key);
 * </code>
 */
class Crypt implements CryptInterface
{
	protected $_key;

	protected $_padding = 0;

	protected $_cipher = "aes-256-cfb";

	/**
	 * Available cipher methods.
	 * @$array
     */
	protected $availableCiphers;

	/**
	 * The cipher iv length.
	 * @$int
	 */
	protected $ivLength = 16;

	/**
	 * The name of hashing algorithm.
	 * @$string
	 */
	protected $hashAlgo = "sha256";

	/**
	 * Whether calculating message digest enabled or not.
	 * NOTE: This feature will be enabled by default in Tengyue\Infra 4.0.0
	 * @$bool
	 */
	protected $useSigning = false;

	const PADDING_DEFAULT = 0;

	const PADDING_ANSI_X_923 = 1;

	const PADDING_PKCS7 = 2;

	const PADDING_ISO_10126 = 3;

	const PADDING_ISO_IEC_7816_4 = 4;

	const PADDING_ZERO = 5;

	const PADDING_SPACE = 6;

	/**
	 * Tengyue\Infra\Crypt constructor.
	 */
	public function __construct($cipher = "aes-256-cfb", $useSigning = false)
	{
		$this->initializeAvailableCiphers();

		$this->setCipher($cipher);
		$this->useSigning($useSigning);
	}

	/**
	 * Changes the padding scheme used.
	 */
	public function setPadding($scheme)
	{
		$this->_padding = $scheme;
		return $this;
	}

	/**
	 * Sets the cipher algorithm for data encryption and decryption.
	 *
	 * The `aes-256-gcm' is the preferable cipher, but it is not usable
	 * until the openssl library is upgraded, which is available in PHP 7.1.
	 *
	 * The `aes-256-ctr' is arguably the best choice for cipher
	 * algorithm for current openssl library version.
	 */
	public function setCipher($cipher)
	{
		$this->assertCipherIsAvailable($cipher);

		$this->ivLength = $this->getIvLength($cipher);
		$this->_cipher  = $cipher;

		return $this;
	}

	/**
	 * Returns the current cipher
	 */
	public function getCipher()
	{
		return $this->_cipher;
	}

	/**
	 * Sets the encryption $key.
	 *
	 * The `$key' should have been previously generated in a cryptographically safe way.
	 *
	 * Bad $key:
	 * "le password"
	 *
	 * Better (but still unsafe):
	 * "#1dj8$=dp?.ak//j1V$~%*0X"
	 *
	 * Good $key:
	 * "T4\xb1\x8d\xa9\x98\x05\\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3"
	 *
	 * @see \Tengyue\Infra\Security\Random
	 */
	public function setKey($key)
	{
		$this->_key = $key;
		return $this;
	}

	/**
	 * Returns the encryption $key
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * Set the name of hashing algorithm.
	 *
	 * @throws \Tengyue\Infra\Crypt\Exception
	 */
	public function setHashAlgo($hashAlgo)
	{
		$this->assertHashAlgorithmAvailable($hashAlgo);

		$this->hashAlgo = $hashAlgo;

		return $this;
	}

	/**
	 * Get the name of hashing algorithm.
	 */
	public function getHashAlgo()
	{
		return $this->hashAlgo;
	}

	/**
	 * Sets if the calculating message digest must used.
	 *
	 * NOTE: This feature will be enabled by default in Tengyue\Infra 4.0.0
	 */
	public function useSigning($useSigning)
	{
		$this->useSigning = $useSigning;

		return $this;
	}

	/**
	 * Pads texts before encryption.
	 *
	 * @link http://www.di-mgt.com.au/cryptopad.html
	 */
	protected function _cryptPadText($text, $mode, $blockSize, $paddingSize)
	{
		$paddingSize = 0;
		$padding = null;

		if ($mode == "cbc" || $mode == "ecb") {

			$paddingSize = $blockSize - (strlen($text) % $blockSize);
			if ($paddingSize >= 256) {
				throw new Exception("Block size is bigger than 256");
			}

			switch ($paddingSize) {

				case self::PADDING_ANSI_X_923:
					$padding = str_repeat(chr(0), $paddingSize - 1) . chr($paddingSize);
					break;

				case self::PADDING_PKCS7:
					$padding = str_repeat(chr($paddingSize), $paddingSize);
					break;

				case self::PADDING_ISO_10126:
					$padding = "";
					foreach (range(0, $paddingSize - 2) as $i) {
						$padding .= chr(rand());
					}
					$padding .= chr($paddingSize);
					break;

				case self::PADDING_ISO_IEC_7816_4:
					$padding = chr(0x80) . str_repeat(chr(0), $paddingSize - 1);
					break;

				case self::PADDING_ZERO:
					$padding = str_repeat(chr(0), $paddingSize);
					break;

				case self::PADDING_SPACE:
					$padding = str_repeat(" ", $paddingSize);
					break;

				default:
					$paddingSize = 0;
					break;
			}
		}

		if (!$paddingSize) {
			return $text;
		}

		if ($paddingSize > $blockSize) {
			throw new Exception("Invalid padding size");
		}

		return $text . substr($padding, 0, $paddingSize);
	}

	/**
	 * Removes a padding from a text.
	 *
	 * If the function detects that the text was not padded, it will return it unmodified.
	 *
	 * @param string text Message to be unpadded
	 * @param string mode Encryption mode; unpadding is applied only in CBC or ECB mode
	 * @param int blockSize Cipher block size
	 * @param int $paddingSize Padding scheme
	 */
	protected function _cryptUnpadText($text, $mode, $blockSize, $paddingSize)
	{
		$paddingSize = 0;
		$length = strlen($text);
		if ($length > 0 && ($length % $blockSize == 0) && ($mode == "cbc" || $mode == "ecb")) {

			switch ($paddingSize) {

				case self::PADDING_ANSI_X_923:
					$last = substr($text, $length - 1, 1);
					$ord = (int) ord($last);
					if ($ord <= $blockSize) {
						$paddingSize = $ord;
						$padding = str_repeat(chr(0), $paddingSize - 1) . $last;
						if (substr($text, $length - $paddingSize) != $padding) {
							$paddingSize = 0;
						}
					}
					break;

				case self::PADDING_PKCS7:
					$last = substr($text, $length - 1, 1);
					$ord = (int) ord($last);
					if ($ord <= $blockSize) {
						$paddingSize = $ord;
						$padding = str_repeat(chr($paddingSize), $paddingSize);
						if (substr($text, $length - $paddingSize) != $padding) {
							$paddingSize = 0;
						}
					}
					break;

				case self::PADDING_ISO_10126:
					$last = substr($text, $length - 1, 1);
					$paddingSize = (int) ord($last);
					break;

				case self::PADDING_ISO_IEC_7816_4:
					$i = $length - 1;
					while ($i > 0 && $text[$i] == 0x00 && $paddingSize < $blockSize) {
						$paddingSize++;
						$i--;
					}
					if ($text[$i] == 0x80) {
						$paddingSize++;
					} else {
						$paddingSize = 0;
					}
					break;

				case self::PADDING_ZERO:
					$i = $length - 1;
					while ($i >= 0 && $text[$i] == 0x00 && $paddingSize <= $blockSize) {
						$paddingSize++;
						$i--;
					}
					break;

				case self::PADDING_SPACE:
					$i = $length - 1;
					while ($i >= 0 && $text[$i] == 0x20 && $paddingSize <= $blockSize) {
						$paddingSize++;
						$i--;
					}
					break;

				default:
					break;
			}

			if ($paddingSize && $paddingSize <= $blockSize) {

				if ($paddingSize < $length) {
					return substr($text, 0, $length - $paddingSize);
				}
				return "";

			} else {
				$paddingSize = 0;
			}

		}

		if (!$paddingSize) {
			return $text;
		}
	}

	/**
	 * Encrypts a text.
	 *
	 * <code>
	 * $encrypted = $crypt->encrypt(
	 *     "Top secret",
	 *     "T4\xb1\x8d\xa9\x98\x05\\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3"
	 * );
	 * </code>
	 */
	public function encrypt($text, $key = null)
	{
		if (empty($key)) {
			$encryptKey = $this->_key;
		} else {
			$encryptKey = $key;
		}

		if (empty($encryptKey)) {
			throw new Exception("Encryption $key cannot be empty");
		}

		$cipher = $this->_cipher;
		$mode = strtolower(substr($cipher, strrpos($cipher, "-") - strlen($cipher)));

		$this->assertCipherIsAvailable($cipher);

		$ivLength = $this->ivLength;
		if ($ivLength > 0) {
			$blockSize = $ivLength;
		} else {
			$blockSize = $this->getIvLength(str_ireplace("-" . $mode, "", $cipher));
		}

		$iv = openssl_random_pseudo_bytes($ivLength);
		$paddingSize = $this->_padding;

		if ($paddingSize != 0 && ($mode == "cbc" || $mode == "ecb")) {
			$padded = $this->_cryptPadText($text, $mode, $blockSize, $paddingSize);
		} else {
			$padded = $text;
		}

		$encrypted = openssl_encrypt($padded, $cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);

		if ($this->useSigning) {
			$hashAlgo = $this->getHashAlgo();
			$digest = hash_hmac($hashAlgo, $padded, $encryptKey, true);

			return $iv . $digest . $encrypted;
		}

		return $iv . $encrypted;
	}

	/**
	 * Decrypts an encrypted text.
	 *
	 * <code>
	 * $encrypted = $crypt->decrypt(
	 *     $encrypted,
	 *     "T4\xb1\x8d\xa9\x98\x05\\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3"
	 * );
	 * </code>
	 *
	 * @throws \Tengyue\Infra\Crypt\Mismatch
	 */
	public function decrypt($text, $key = null)
	{
		if (empty($key)) {
			$decryptKey = $this->_key;
		} else {
			$decryptKey = $key;
		}

		if (empty($decryptKey)) {
			throw new Exception("Decryption $key cannot be empty");
		}

		$cipher = $this->_cipher;
		$mode = strtolower(substr($cipher, strrpos($cipher, "-") - strlen($cipher)));

		$this->assertCipherIsAvailable($cipher);

		$ivLength = $this->ivLength;
		if ($ivLength > 0) {
			$blockSize = $ivLength;
		} else {
			$blockSize = $this->getIvLength(str_ireplace("-" . $mode, "", $cipher));
		}

		$iv = mb_substr($text, 0, $ivLength, "8bit");

		if ($this->useSigning) {
			$hashAlgo = $this->getHashAlgo();
			$hashLength = strlen(hash($hashAlgo, "", true));
			$hash = mb_substr($text, $ivLength, $hashLength, "8bit");
			$ciphertext = mb_substr($text, $ivLength + $hashLength, null, "8bit");
			$decrypted = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);

			if ($mode == "cbc" || $mode == "ecb") {
				$decrypted = $this->_cryptUnpadText($decrypted, $mode, $blockSize, $this->_padding);
			}

			/**
			 * Checkson the decrypted's message digest using the HMAC method.
			 */
			if (hash_hmac($hashAlgo, $decrypted, $decryptKey, true) !== $hash) {
				throw new Mismatch("Hash does not match.");
			}

			return $decrypted;
		}

		$ciphertext = mb_substr($text, $ivLength, null, "8bit");
		$decrypted = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);

		if ($mode == "cbc" || $mode == "ecb") {
			$decrypted = $this->_cryptUnpadText($decrypted, $mode, $blockSize, $this->_padding);
		}

		return $decrypted;
	}

	/**
	 * Encrypts a text returning the result as a base64 string.
	 */
	public function encryptBase64($text, $key = null, $safe = false)
	{
		if ($safe == true) {
			return rtrim(strtr(base64_encode($this->encrypt($text, $key)), "+/", "-_"), "=");
		}
		return base64_encode($this->encrypt($text, $key));
	}

	/**
	 * Decrypt a text that is coded as a base64 string.
	 *
	 * @throws \Tengyue\Infra\Crypt\Mismatch
	 */
	public function decryptBase64($text, $key = null, $safe = false)
	{
		if ($safe == true) {
			return $this->decrypt(base64_decode(strtr($text, "-_", "+/") . substr("===", (strlen($text) + 3) % 4)), $key);
		}
		return $this->decrypt(base64_decode($text), $key);
	}

	/**
	 * Returns a list of available ciphers.
	 */
	public function getAvailableCiphers()
	{
		$availableCiphers = $this->availableCiphers;
		if (!is_array($availableCiphers)) {
			$this->initializeAvailableCiphers();
			$availableCiphers = $this->availableCiphers;
		}

		return $availableCiphers;
	}

	/**
	 * Return a list of registered hashing algorithms suitable for hash_hmac.
	 */
	public function getAvailableHashAlgos()
	{
		if (function_exists("hash_hmac_algos")) {
			$algos = hash_hmac_algos();
		} else {
			$algos = hash_algos();
		}

		return $algos;
	}

	/**
	 * Assert the cipher is available.
	 *
	 * @throws \Tengyue\Infra\Crypt\Exception
	 */
	protected function assertCipherIsAvailable($cipher)
	{
		$availableCiphers = $this->getAvailableCiphers();

		if (!in_array($cipher, $availableCiphers)) {
			throw new Exception(
				sprintf(
					"The cipher algorithm \"%s\" is not supported on this system.",
					$cipher
				)
			);
		}
	}

	/**
	 * Assert the hash algorithm is available.
	 *
	 * @throws \Tengyue\Infra\Crypt\Exception
	 */
	protected function assertHashAlgorithmAvailable($hashAlgo)
	{
		$availableAlgorithms = $this->getAvailableHashAlgos();

		if (!in_array($hashAlgo, $availableAlgorithms)) {
			throw new Exception(
				sprintf(
					"The hash algorithm \"%s\" is not supported on this system.",
					$hashAlgo
				)
			);
		}
	}

	/**
	 * Initialize available cipher algorithms.
	 *
	 * @throws \Tengyue\Infra\Crypt\Exception
	 */
	protected function getIvLength($cipher)
	{
		if (!function_exists("openssl_cipher_iv_length")) {
			throw new Exception("openssl extension is required");
		}

		return openssl_cipher_iv_length($cipher);
	}

	/**
	 * Initialize available cipher algorithms.
	 *
	 * @throws \Tengyue\Infra\Crypt\Exception
	 */
	protected function initializeAvailableCiphers()
	{
		if (!function_exists("openssl_get_cipher_methods")) {
			throw new Exception("openssl extension is required");
		}

		$this->availableCiphers = openssl_get_cipher_methods(true);
	}
}
