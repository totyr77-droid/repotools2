<?php
require_once __DIR__.'/includes/auth.php';require_once __DIR__.'/includes/referrals.php';
header('Cache-Control: no-store');
if(!referrals_enabled()){http_response_code(503);exit('Las invitaciones todavía no están habilitadas en este entorno.');}
if(!current_user())try{referral_limit(database(),'capture:'.hash('sha256',session_id()),60,900);referral_capture(database(),$_GET['code']??'');}catch(ReferralError $e){}

header('Location: /cuenta.php?mode=registro',true,303);exit;
