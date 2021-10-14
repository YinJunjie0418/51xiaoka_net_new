<?php
class Card{
	
    private $keys = "";
 
    /**
     * 构造，传递二个已经进行base64_encode的KEY与IV
     *
     * @param string $key
     * @param string $iv
     */
    function __construct($key)
    {
        $this->keys = $key;
    }
 
    public function encrypt($card_pass){
        @$keys=hex2bin($this->keys);//把16进制转ASCII
        $data = openssl_encrypt($card_pass, 'AES-128-ECB',$keys, 0);//AES/ECB/PKCS5P
        return bin2hex(base64_decode($data));//把结果base64解码转16进制
    }

    public function decrypt($card_pass){
        @$keys=hex2bin($this->keys);
        $decrypted = openssl_decrypt(base64_encode(pack("H*",$card_pass)), 'AES-128-ECB', $keys, 0);
        return $decrypted;
    }
}