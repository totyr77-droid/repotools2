<?php
require_once __DIR__.'/../includes/push-health.php';
foreach([
 'could not open certificate file /root/.postgresql/postgresql.crt: Permission denied'=>'permission_denied',
 'password authentication failed for user private-user'=>'authentication_failed',
 'could not translate host name private-host to address'=>'dns_failed',
 'connection timed out'=>'connection_timeout',
 'Connection refused'=>'connection_refused',
 'SSL error: certificate verify failed'=>'tls_failed',
 'unknown private error'=>null,
]as$message=>$expected){if(push_connection_reason(new PDOException($message))!==$expected)throw new RuntimeException('Incorrect safe category');}
echo "OK: fixed connection categories, no raw exception data returned.\n";
