<?php

/**
 * This class extends the core Encrypt class, and allows you
* to use encrypted strings in your URLs.
*/

namespace CodePi\Base\Libraries;

class MyEncrypt {

	function my_simple_crypt($string, $action = 'e') {
		// you may change these values to your own
		$secret_key = 'TYEHDJHDHDYJDIDIUJDOIDUJ';
		$secret_iv = '1701355330172243|4Z8GxOpc5CVEFcgHeUoVv7z0zRI';

		$output = false;
		$encrypt_method = "AES-256-CBC";
		$key = hash('sha256', $secret_key);
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		if ($action == 'e') {
			$output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
		} else if ($action == 'd') {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}

	function str_encode($text) {
		return $this->my_simple_crypt($text,'e');
		/* $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		 $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		 $key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
		 return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv)); */

		$key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
		// Remove the base64 encoding from our key
		$encryption_key = base64_decode($key);
		// Generate an initialization vector
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		// Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
		$encrypted = openssl_encrypt($text, 'aes-256-cbc', $encryption_key, 0, $iv);
		// The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
		return base64_encode($encrypted . '::' . $iv);
	}

	function str_decode($text) {
		return $this->my_simple_crypt($text,'d');
		/* $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		 $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		 $key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
		 //I used trim to remove trailing spaces
		 return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($text), MCRYPT_MODE_ECB, $iv)); */

		$key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
		$encryption_key = base64_decode($key);
		// To decrypt, split the encrypted data from our IV - our unique separator used was "::"
		list($encrypted_data, $iv) = explode('::', base64_decode($text), 2);
		return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
	}

	/**
	 * Encodes a string.
	 *
	 * @param string $string
	 *        	The string to encrypt.
	 * @param string $key[optional]
	 *        	The key to encrypt with.
	 * @param bool $url_safe[optional]
	 *        	Specifies whether or not the
	 *        	returned string should be url-safe.
	 * @return string
	 */
	function encode($string, $key = "", $url_safe = TRUE) {

		// $ret = parent::encode($string, $key);
		$ret = $this->str_encode($string);
		// $ret = encrypt($string);
		if ($url_safe) {
			$ret = strtr($ret, array(
					'+' => '.',
					'=' => '-',
					'/' => '~'
			));
		}

		return $ret;
	}

	/**
	 * Decodes the given string.
	 *
	 * @access public
	 * @param string $string
	 *        	The encrypted string to decrypt.
	 * @param string $key[optional]
	 *        	The key to use for decryption.
	 * @return string
	 */
	function decode($string, $key = "") {
		$string = strtr($string, array(
				'.' => '+',
				'-' => '=',
				'~' => '/'
		));

		// return parent::decode($string, $key);
		return $this->str_decode($string);
		//return decrypt($string);
	}

	function decrypt_blowfish($data, $key) {
		$iv = @pack("H*", substr($data, 0, 16));
		$x = @pack("H*", substr($data, 16));
		$res = @mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $x, MCRYPT_MODE_CBC, $iv);
		return $res;
	}

	function encrypt_blowfish($data, $key) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_CBC, $iv);
		return bin2hex($iv . $crypttext);
	}

}
