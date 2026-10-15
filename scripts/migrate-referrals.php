<?php
// Explicit referral migration command; the unified schema migration also installs it.
require_once __DIR__.'/../includes/referrals.php';
if(PHP_SAPI!=='cli'||!referrals_enabled()){http_response_code(503);exit("Referidos está deshabilitado o el comando no se ejecutó desde CLI.\n");}
require_once __DIR__.'/../includes/database.php';
referral_schema(database());echo "Esquema de referidos preparado.\n";
