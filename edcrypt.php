<?php 

function encrypt($text) 
{ 
	$salty = substr(AUTH_KEY, -10);
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salty, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)))); 
} 

function decrypt($text) 
{ 
	$salty = substr(AUTH_KEY, -10);
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salty, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))); 
} 

?>