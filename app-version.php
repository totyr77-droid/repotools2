<?php
require_once __DIR__.'/includes/app-release.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode(['version'=>app_release()['version']]);
