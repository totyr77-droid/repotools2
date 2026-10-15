<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/public-link-aliases.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);initialize_database($db);
function check_alias(bool $condition):void{if(!$condition)throw new RuntimeException('Alias regression');}
$first=public_link_alias($db,'p','12345678',fn()=>'Ab12Cd34');check_alias($first==='Ab12Cd34');
check_alias(public_link_alias($db,'p','12345678',fn()=>'XXXXXXXX')===$first);
$sequence=['Ab12Cd34','Z9y8X7w6'];$next=public_link_alias($db,'s',str_repeat('a',64),function()use(&$sequence){return array_shift($sequence);});check_alias($next==='Z9y8X7w6');
foreach(['p','s','b'] as $kind)for($i=0;$i<100;$i++){ $code=public_link_alias($db,$kind,'fixture-'.$i);check_alias((bool)preg_match('/^[A-Za-z0-9]{8}$/D',$code)); }
check_alias((int)$db->query('SELECT COUNT(DISTINCT code) FROM public_link_aliases')->fetchColumn()===302);
try{public_link_alias($db,'x','bad');throw new RuntimeException('Invalid kind accepted');}catch(InvalidArgumentException $expected){}
echo "OK: 8-character random aliases, uniqueness, collision retry, stable reuse and type validation.\n";
