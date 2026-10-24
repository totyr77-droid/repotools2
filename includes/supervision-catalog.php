<?php
declare(strict_types=1);
require_once __DIR__.'/reports.php';
// The workbook is untrusted. Only explicit, non-formula columns become proposals.
function supervisor_catalog_columns(array $columns): array {
    $result=['name'=>'','uxb'=>'','uxc'=>''];
    foreach($columns as $column){$label=mb_strtolower(trim(preg_replace('/^[A-Z]+ · /u','',$column['label'])));
        $key=match($label){'descripcion','descripción','producto','nombre','descripción del producto'=>'name','uxb','u/b','unidades por bulto'=>'uxb','uxc','u/c','unidades por caja'=>'uxc',default=>null};
        if($key&&$result[$key]==='')$result[$key]=$column['value'];
    }return $result;
}
function supervisor_catalog_candidates(string $bytes,array $parsed,array $mapping): array {
    $used=[$parsed['ean_column']??$parsed['suggested']];
    foreach(['name','uxb','uxc']as$key){$column=$mapping[$key]??'';if($column!==''&&(!preg_match('/^[A-Z]{1,2}$/D',$column)||in_array($column,$used,true)))throw new CatalogException('Elegí columnas distintas para EAN, descripción, UXB y UXC.',422);if($column!=='')$used[]=$column;}
    return supervisor_archive($bytes,function($zip)use($parsed,$mapping){[, $rows,$max]=supervisor_cells($zip,$parsed['sheet_path']);foreach($mapping as$column)if($column!==''&&supervisor_column_number($column)>$max)throw new CatalogException('La columna elegida no existe en esta hoja.',422);$items=[];$invalid=[];$conflicts=[];
        foreach($parsed['ean_rows']as$row=>$ean){$fields=[];
            foreach(['name','uxb','uxc']as$key){$column=$mapping[$key]??'';if($column==='')continue;$cell=$rows[$row][$column]??['value'=>'','formula'=>false];$value=trim($cell['value']);if($value==='')continue;
                if($cell['formula']){$invalid[$ean]=true;continue;}
                if($key==='name'){if(mb_strlen($value)>120||preg_match('/[<>\p{C}]/u',$value)){$invalid[$ean]=true;continue;}$fields[$key]=$value;}
                else {if(preg_match('/^[1-9][0-9]*(?:[.,]0+)?$/D',$value))$value=(string)(int)$value;try{$fields[$key]=catalog_pack_value($value);}catch(CatalogException $e){$invalid[$ean]=true;}}
            }
            if(!$fields)continue;
            foreach($fields as$key=>$value){if(isset($items[$ean][$key])&&$items[$ean][$key]!==$value)$conflicts[$ean]=true;else $items[$ean][$key]=$value;}
        }
        foreach($invalid+$conflicts as$ean=>$ignore)unset($items[$ean]);
        return ['items'=>$items,'invalid'=>count($invalid),'conflicts'=>count($conflicts)];
    });
}
function supervisor_catalog_propose(PDO $db,int $user,array $items,string $listName): int {
    supervisor_require($db,$user);report_require_submission($db,['id'=>$user]);
    if(count($items)>200)throw new CatalogException('Podés proponer hasta 200 productos por carga. Dividí el archivo o guardá la lista sin aportar al catálogo.',422);
    $recent=(int)stock_query($db,'SELECT COUNT(*) FROM product_reports WHERE user_id=? AND submitted_at>?',[$user,time()-86400])->fetchColumn();
    if($recent+count($items)>200)throw new CatalogException('Alcanzaste el límite diario de propuestas. Podés guardar la lista sin aportar al catálogo.',429);
    $count=0;foreach($items as$ean=>$fields){$ean=(string)$ean;$current=catalog_product($db,$ean);$proposal=report_fields($current??[]);$proposal=array_replace($proposal,$fields);
        if(($proposal['name']??'')===''||!report_proposed_changes($current,$proposal))continue;
        if(stock_query($db,"SELECT 1 FROM product_reports WHERE user_id=? AND ean=? AND status='pending'",[$user,$ean])->fetchColumn())continue;
        $key=bin2hex(random_bytes(16));$uuid=substr($key,0,8).'-'.substr($key,8,4).'-'.substr($key,12,4).'-'.substr($key,16,4).'-'.substr($key,20);
        stock_query($db,'INSERT INTO product_reports(submission_key,owner_key,user_id,ean,description,proposed_data,original_data,submitted_at) VALUES(?,?,?,?,?,?,?,?)',[$uuid,'user:'.$user,$user,$ean,'Importado de Excel · '.catalog_text($listName,100),json_encode([...$proposal,'_origin'=>'supervision_excel'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($current,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time()]);$count++;
    }return $count;
}
