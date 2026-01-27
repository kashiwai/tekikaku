<?php
	// ===============================================================================
	/* 
	 * Note:
	 * 加解密
	 * 
	 */
	// ===============================================================================
	class Crypt3Des {
		private $key = "";
    private $iv = "";

    /**
     * 构造，传递二个已经进行base64_encode的KEY与IV
     *
     * @param string $key
     * @param string $iv
     */
    function __construct ($key, $iv)
    {
        if (empty($key) || empty($iv)) {
            echo 'key and iv is not valid';
            exit();
        }
        $this->key = $key;
        $this->iv = $iv;//8
        //$this->iv = $iv.'00000000000';//16

    }

    /**
     * @title 加密
     * @param string $value 要传的参数
     * @ //OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING //AES-128-ECB|AES-256-CBC|BF-CBC
     * @return json
     * */
    public function encrypt ($value) {

        //参考地址：https://stackoverflow.com/questions/41181905/php-mcrypt-encrypt-to-openssl-encrypt-and-openssl-zero-padding-problems#
        $value = $this->PaddingPKCS7($value);
        $key = base64_decode($this->key);
        $iv  = base64_decode($this->iv);
        //AES-128-ECB|不能用 AES-256-CBC|16 AES-128-CBC|16 BF-CBC|8 aes-128-gcm|需要加$tag  DES-EDE3-CBC|8
        $cipher = "DES-EDE3-CBC";
        if (in_array($cipher, openssl_get_cipher_methods())) {
            //$ivlen = openssl_cipher_iv_length($cipher);
            // $iv = openssl_random_pseudo_bytes($ivlen);
            $result = openssl_encrypt($value, $cipher, $key, OPENSSL_SSLV23_PADDING, $iv);
            //$result = base64_encode($result); //为3的时间要用
            //store $cipher, $iv, and $tag for decryption later
            /* $original_plaintext = openssl_decrypt($result, $cipher, $key, OPENSSL_SSLV23_PADDING, $iv);
             echo $original_plaintext."\n";*/
        }
        return $result;



    }

    /**
     * @title 解密
     * @param string $value 要传的参数
     * @return json
     * */
    public function decrypt ($value) {
        $key       = base64_decode($this->key);
        $iv        = base64_decode($this->iv);
        $decrypted = openssl_decrypt($value, 'DES-EDE3-CBC', $key, OPENSSL_SSLV23_PADDING, $iv);
        $ret = $this->UnPaddingPKCS7($decrypted);
        return $ret;
    }


    private function PaddingPKCS7 ($data) {
        //$block_size = mcrypt_get_block_size('tripledes', 'cbc');//获取长度
        //$block_size = openssl_cipher_iv_length('tripledes', 'cbc');//获取长度
        $block_size = 8;
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }
    private function UnPaddingPKCS7($text) {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }
  }

?>
