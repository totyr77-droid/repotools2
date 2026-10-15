<?php
// Optional deploy command. Uses the same versioned migrations as the application.
if(PHP_SAPI!=='cli'){http_response_code(405);exit;}
require_once __DIR__.'/../includes/database.php';
try{database();echo 'Esquema listo: '.REPOTOOLS_SCHEMA_VERSION.PHP_EOL;}catch(Throwable $e){fwrite(STDERR,'No se pudo completar la migración. Revisá la conexión y los permisos; no se publicaron credenciales.'.PHP_EOL);exit(1);}
