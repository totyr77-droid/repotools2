<?php
declare(strict_types=1);
require_once __DIR__.'/supervision-xlsx.php';
// Normalize only new formats. Existing XLSX uploads are kept byte-for-byte.
function supervisor_import_file(string $bytes,string $extension,string $delimiter='auto'):array {
 if($bytes===''||strlen($bytes)>3000000)throw new CatalogException('Elegí un archivo de hasta 3 MB.',422);
 if($extension==='xlsx')return ['bytes'=>$bytes,'format'=>'xlsx','notice'=>''];
 if($extension==='csv')return ['bytes'=>supervisor_import_csv($bytes,$delimiter),'format'=>'csv','notice'=>'CSV convertido a XLSX. Conservamos los valores como texto, incluidos los ceros iniciales.'];
 if($extension==='xls')return ['bytes'=>supervisor_import_xls($bytes),'format'=>'xls','notice'=>'XLS convertido a XLSX para comparar. No se importan macros ni objetos incrustados.'];
 throw new CatalogException('Elegí un archivo XLSX, XLS o CSV.',422);
}
function supervisor_import_csv(string $bytes,string $delimiter):string {
 $allowed=['auto'=>null,'comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];if(!array_key_exists($delimiter,$allowed))throw new CatalogException('Separador no válido.',422);
 if(str_starts_with($bytes,"\xFF\xFE"))$bytes=mb_convert_encoding(substr($bytes,2),'UTF-8','UTF-16LE');elseif(str_starts_with($bytes,"\xFE\xFF"))$bytes=mb_convert_encoding(substr($bytes,2),'UTF-8','UTF-16BE');elseif(str_starts_with($bytes,"\xEF\xBB\xBF"))$bytes=substr($bytes,3);
 if(str_contains($bytes,"\0"))throw new CatalogException('El CSV contiene datos binarios. Guardalo como CSV UTF-8.',422);if(!mb_check_encoding($bytes,'UTF-8'))$bytes=mb_convert_encoding($bytes,'UTF-8','Windows-1252');
 if(preg_match('/^sep=([;,|\t])(?:\r\n|\n|\r)/i',$bytes,$m)){$bytes=substr($bytes,strlen($m[0]));$hint=$m[1];}
 $separator=$allowed[$delimiter];if($separator===null){if(isset($hint))$separator=$hint;else{$best=-1;$separator=',';foreach([',',';',"\t",'|']as$candidate){$stream=fopen('php://temp','w+');fwrite($stream,$bytes);rewind($stream);$counts=[];$samples=0;while($samples++<20&&($row=fgetcsv($stream,0,$candidate,'"',''))!==false){if(count($row)>1)$counts[count($row)]=($counts[count($row)]??0)+1;}fclose($stream);$score=0;foreach($counts as$width=>$count)$score=max($score,$count*100+$width);if($score>$best){$best=$score;$separator=$candidate;}}}}
 $stream=fopen('php://temp','w+');fwrite($stream,$bytes);rewind($stream);$rows=[];$cells=0;try{while(($row=fgetcsv($stream,0,$separator,'"',''))!==false){if(count($rows)>=20000||count($row)>256||($cells+=count($row))>300000)throw new CatalogException('La lista es demasiado grande. Usá hasta 20.000 filas y 256 columnas.',422);$values=[];foreach($row as$value){$value=$value??'';if(mb_strlen($value)>32767||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value))throw new CatalogException('El CSV tiene una celda con texto no compatible.',422);$values[]=$value;}$rows[]=$values;}}finally{fclose($stream);}
 if(!$rows)throw new CatalogException('El CSV está vacío.',422);$result=supervisor_write_xlsx($rows);if(strlen($result)>3000000)throw new CatalogException('La lista convertida supera 3 MB. Dividila en archivos más chicos.',422);return $result;
}
function supervisor_import_xls(string $bytes):string {
 if(!str_starts_with($bytes,"\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"))throw new CatalogException('El archivo no es un XLS válido. Si es texto, guardalo como CSV.',422);
 require_once __DIR__.'/../vendor/autoload.php';$path=tempnam(sys_get_temp_dir(),'rt-xls-');$out=$path.'.xlsx';file_put_contents($path,$bytes);chmod($path,0600);touch($out);chmod($out,0600);$book=null;
 try{$reader=new \PhpOffice\PhpSpreadsheet\Reader\Xls();$reader->setReadEmptyCells(false);$info=$reader->listWorksheetInfo($path);$budget=0;if(!$info||count($info)>12)throw new CatalogException('El XLS admite hasta 12 hojas.',422);foreach($info as$sheet){$budget+=max(1,$sheet['totalRows'])*max(1,$sheet['totalColumns']);if($sheet['totalRows']>20000||$sheet['totalColumns']>256||$budget>300000)throw new CatalogException('El XLS es demasiado grande. Reducí filas, columnas u hojas.',422);}$book=$reader->load($path);if($book->hasMacros())$book->setHasMacros(false);foreach($book->getAllSheets() as$sheet){$sheet->getDrawingCollection()->exchangeArray([]);foreach($sheet->getCellCollection()->getCoordinates()as$coordinate){$cell=$sheet->getCell($coordinate);$value=$cell->getValue();if(is_string($value)&&mb_strlen($value)>32767)throw new CatalogException('El XLS contiene una celda demasiado larga.',422);}}
 $writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book);$writer->setPreCalculateFormulas(false);$writer->save($out);$result=file_get_contents($out);if(strlen($result)>3000000)throw new CatalogException('La lista convertida supera 3 MB. Dividila en archivos más chicos.',422);return $result;
 }catch(CatalogException $e){throw $e;}catch(Throwable $e){throw new CatalogException('No pudimos leer el XLS. Guardalo sin contraseña o convertí una copia a XLSX.',422);}finally{$book?->disconnectWorksheets();if(is_file($path))unlink($path);if(is_file($out))unlink($out);}
}
