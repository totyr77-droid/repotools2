<?php
function app_release(): array {static $release; if($release===null){$release=json_decode((string)file_get_contents(__DIR__.'/../release.json'),true)?:['version'=>1,'changes'=>[]];}return $release;}

function app_release_label(): string { $r=app_release(); return (int)($r['major']??1).'.'.str_pad((string)(int)($r['minor']??0),2,'0',STR_PAD_LEFT); }
