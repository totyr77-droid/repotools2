<?php
function app_release(): array {static $release; if($release===null){$release=json_decode((string)file_get_contents(__DIR__.'/../release.json'),true)?:['version'=>1,'changes'=>[]];}return $release;}
