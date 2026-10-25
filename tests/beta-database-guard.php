<?php
require_once __DIR__.'/../includes/database.php';
$previous=[getenv('REPOTOOLS_EXPECTED_DATABASE'),getenv('REPOTOOLS_EXPECTED_DATABASE_USER')];
function denied(string $url): void {
    try { postgres_connection($url); } catch (PDOException $error) {
        if(str_contains($error->getMessage(),'fixture-password'))throw new RuntimeException('Credential disclosed');
        return;
    }
    throw new RuntimeException('Connection outside deployment was accepted');
}
try {
    putenv('REPOTOOLS_EXPECTED_DATABASE=');putenv('REPOTOOLS_EXPECTED_DATABASE_USER=');
    postgres_connection('postgresql://fixture:fixture-password@db.invalid/production_fixture');
    putenv('REPOTOOLS_EXPECTED_DATABASE=beta_fixture');putenv('REPOTOOLS_EXPECTED_DATABASE_USER=beta_fixture_app');
    denied('postgresql://fixture:fixture-password@db.invalid/production_fixture');
    denied('postgresql://beta_fixture_app:fixture-password@db.invalid/production_fixture');
    denied('postgresql://fixture:fixture-password@db.invalid/beta_fixture');
    $connection=postgres_connection('postgresql://beta_fixture_app:fixture-password@db.invalid/beta_fixture');
    if(!str_contains($connection['dsn'],'sslmode=require')||str_contains($connection['dsn'],'fixture-password'))throw new RuntimeException('Unsafe DSN');
    echo "OK: beta accepts its own database/role, rejects production/mismatched credentials, keeps TLS and default compatibility.\n";
} finally {
    foreach(['REPOTOOLS_EXPECTED_DATABASE','REPOTOOLS_EXPECTED_DATABASE_USER'] as $index=>$key)putenv($previous[$index]===false?$key:$key.'='.$previous[$index]);
}
