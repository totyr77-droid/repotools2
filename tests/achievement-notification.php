<?php
require_once __DIR__.'/../includes/achievements.php';
$row=achievement_notification(['type'=>'achievement','event_key'=>'achievement:1:escaneos:3']);
if($row['badge']!=='img/achievements/escaneos/escaneos_03_oro.svg'||$row['body']!=='Ojo de águila · Oro'||!str_contains($row['achievement_detail'],'100'))throw new RuntimeException('Achievement detail mismatch');
$invalid=['type'=>'achievement','event_key'=>'achievement:1:../../private:3'];if(achievement_notification($invalid)!==$invalid)throw new RuntimeException('Invalid category accepted');
echo "OK: badge category/level, goal and rule, unsafe identifiers rejected.\n";
