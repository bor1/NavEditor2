<?php
$p = 'caiziying';
echo(crypt($p, base64_encode(CRYPT_STD_DES)));
?>