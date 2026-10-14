<?php
// Explicit beta-only migration; never runs as part of ordinary authentication.
require_once __DIR__.'/../includes/referrals.php';
if(PHP_SAPI!=='cli'||!referrals_enabled()){http_response_code(503);exit("Referidos requiere activación y una base aislada de beta.\n");}
require_once __DIR__.'/../includes/database.php';
referral_schema(database());echo "Esquema de referidos preparado en la base aislada.\n";
