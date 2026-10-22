<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/push.php';
try{$db=database();if(!push_library())exit(0);push_prepare($db);push_dispatch($db);}catch(Throwable $e){error_log('Repotools push worker unavailable: '.get_class($e));exit(1);}
