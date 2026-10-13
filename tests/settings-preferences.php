<?php
require __DIR__.'/../includes/database.php';
require __DIR__.'/../includes/preferences.php';
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
$url=getenv('DATABASE_URL')?:'';
if($url){
    if(getenv('REPOTOOLS_SETTINGS_TEST_BRANCH')!=='1')throw new RuntimeException('Use an isolated settings test branch.');
    $connection=postgres_connection($url);
    // The local XAMPP libpq predates SNI; production's driver is unchanged.
    $endpoint=explode('.',parse_url($url,PHP_URL_HOST))[0];
    $connection['dsn'].=";options='endpoint={$endpoint}'";
    $db=new PDO($connection['dsn'],$connection['user'],$connection['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}else{
    $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    initialize_database($db);
    // Simulate the previous schema only in this disposable in-memory fixture.
    $db->exec('ALTER TABLE users DROP COLUMN sound_enabled');$db->exec('ALTER TABLE users DROP COLUMN vibration_enabled');$db->exec('DELETE FROM app_schema_migrations WHERE version=25');
}
$insert=$db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?) RETURNING id');
$insert->execute(['Existing Fixture','settings-'.bin2hex(random_bytes(8)).'@example.invalid','test-only']);$existing=(int)$insert->fetchColumn();$insert->closeCursor();
initialize_database($db);$p=account_preferences($db,$existing);check($p['sound']&&$p['vibration'],'Existing defaults');check($p['expiry_days']===30,'Expiry default unchanged');
$insert->execute(['New Fixture','settings-'.bin2hex(random_bytes(8)).'@example.invalid','test-only']);$new=(int)$insert->fetchColumn();$insert->closeCursor();$p=account_preferences($db,$new);check($p['sound']&&$p['vibration'],'New defaults');
save_account_preferences($db,$existing,['sound'=>'0','handedness'=>'left','expiry_days'=>'25']);
save_account_preferences($db,$existing,['vibration'=>'0']);save_account_preferences($db,$existing,['expiry_days'=>'20']);
$p=account_preferences($db,$existing);check(!$p['sound']&&!$p['vibration']&&$p['expiry_days']===20&&$p['handedness']==='left','Partial saves preserve other fields');
check(account_preferences($db,$new)['sound'],'Account isolation');
foreach([['sound'=>'false'],['vibration'=>['1']],['expiry_days'=>'366'],['expiry_days'=>'-1'],['expiry_days'=>'1.5'],['expiry_days'=>''],['handedness'=>'up'],['sound'=>'1','expiry_days'=>'bad']] as $invalid){try{save_account_preferences($db,$existing,$invalid);throw new RuntimeException('Invalid value accepted');}catch(InvalidArgumentException $expected){}}
check(!account_preferences($db,$existing)['sound'],'Invalid mixed save is atomic');
foreach(['0','365'] as $days){save_account_preferences($db,$existing,['expiry_days'=>$days]);check(account_preferences($db,$existing)['expiry_days']===(int)$days,'Expiry boundaries');}
save_account_preferences($db,$existing,['sound'=>'1','vibration'=>'1','handedness'=>'right']);$p=account_preferences($db,$existing);check($p['sound']&&$p['vibration']&&$p['handedness']==='right','Re-enable');
initialize_database($db);check((int)$db->query('SELECT COUNT(*) FROM app_schema_migrations WHERE version=25')->fetchColumn()===1,'Idempotent migration');
echo 'OK: '.$db->getAttribute(PDO::ATTR_DRIVER_NAME).' migration25, existing/new defaults, partial saves, isolation, validation, atomicity and boundaries.'.PHP_EOL;
