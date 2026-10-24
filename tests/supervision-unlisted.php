<?php
require_once __DIR__.'/../includes/supervision.php';require_once __DIR__.'/../includes/supervision-xlsx.php';
function outside_ok(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$source=supervisor_write_xlsx([['EAN','Producto'],['96385074','Original'],['96385074','Repetido'],['Nota','Conservar al final']]);
$columns=supervisor_export_columns(['columns'=>'name,stock,boxes,expires,pallets,branches,members']);
$data=['96385074'=>['name'=>'Existente','stock'=>5],'01234565'=>['name'=>'Nuevo','stock'=>25,'boxes'=>'2 + 1 unidad','expires'=>"01/12/2026: 12 unidades\n15/12/2026: 13 unidades",'pallets'=>'05121614','branches'=>'Local A','members'=>'Persona A'],'4006381333931'=>['name'=>'Sin stock','stock'=>0]];
$rows=[2=>'96385074',3=>'96385074'];$totals=['96385074'=>5,'01234565'=>25];
$default=supervisor_compare_xlsx($source,'xl/worksheets/sheet1.xml',$rows,$totals,1,$columns,$data,'A');
supervisor_archive($default,function($zip){[, $rows]=supervisor_cells($zip,'xl/worksheets/sheet1.xml');outside_ok(count($rows)===4,'Disabled by default, source row count unchanged');});
$bytes=supervisor_compare_xlsx($source,'xl/worksheets/sheet1.xml',$rows,$totals,1,$columns,$data,'A',true);
supervisor_archive($bytes,function($zip){[$doc,$rows]=supervisor_cells($zip,'xl/worksheets/sheet1.xml');outside_ok($rows[2]['D']['value']==='5'&&$rows[3]['D']['value']==='5','Duplicate list rows preserved');outside_ok($rows[4]['B']['value']==='Conservar al final','Trailing source notes preserved');outside_ok($rows[6]['A']['value']==='Productos con stock fuera de la lista','Separate section after existing source');outside_ok($rows[7]['A']['value']==='EAN'&&$rows[7]['C']['value']==='Producto'&&$rows[7]['D']['value']==='Unidades','Identity and selected headers');outside_ok($rows[8]['A']['value']==='01234565'&&$rows[8]['C']['value']==='Nuevo'&&$rows[8]['D']['value']==='25','One missing EAN with leading zeros and full quantity');outside_ok(!isset($rows[9]),'Listed and zero-stock products excluded');outside_ok($rows[8]['E']['value']==='2 + 1 unidad'&&str_contains($rows[8]['F']['value'],'13 unidades')&&$rows[8]['G']['value']==='05121614','Selected stock details preserved');outside_ok($doc->getElementsByTagNameNS(SUPERVISOR_XLSX_NS,'mergeCell')->item(0)->getAttribute('ref')==='A6:I6','Section heading spans columns');});
$onlyStock=supervisor_compare_xlsx($source,'xl/worksheets/sheet1.xml',$rows,$totals,1,['stock'=>'Unidades'],$data,'A',true);
supervisor_archive($onlyStock,function($zip){[, $rows]=supervisor_cells($zip,'xl/worksheets/sheet1.xml');outside_ok($rows[8]['A']['value']==='01234565'&&$rows[8]['C']['value']==='25','EAN included even when only quantities selected');});
if(isset($argv[1]))file_put_contents($argv[1],$bytes);
echo "OK: optional outside-list block, only positive missing EAN, unique values, selected fields, leading zeros and original rows/notes preserved.\n";
