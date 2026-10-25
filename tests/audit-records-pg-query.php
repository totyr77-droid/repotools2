<?php
require 'includes/database.php';require 'includes/stock.php';require 'includes/audit-read.php';
class AuditProbeStatement extends PDOStatement {
 public function __construct(private string $sql,private AuditProbe $db){}
 public function execute(?array $params=null):bool{if(str_starts_with($this->sql,'WITH matched'))$this->db->query=['sql'=>$this->sql,'params'=>$params];return true;}
 public function fetchColumn(int $column=0):mixed{return 'manager';}
 public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{return [];}
}
class AuditProbe extends PDO {
 public array $query=[];public function __construct(){}public function getAttribute(int $attribute):mixed{return 'pgsql';}
 public function prepare(string $query,array $options=[]):PDOStatement|false{return new AuditProbeStatement($query,$this);}
}
$db=new AuditProbe;audit_read($db,5,['grouped'=>'1','section'=>'goods']);$sql=$db->query['sql'];foreach($db->query['params'] as $value)$sql=preg_replace('/\?/',"'".str_replace("'","''",$value)."'",$sql,1);
$fixtures="audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,branch_id,pallet_id,pallet_code,ean,target_user,before_data,after_data) AS (VALUES ('a',1,'Persona','usuario',0,'delete','movements','confirmed','2026-10-22','2026-10-22','batch:1',NULL::bigint,1,'00000001','96385074',NULL::bigint,'{\"owner_id\":1}','{\"transaction_id\":\"batch\",\"released\":1}'),('b',1,'Persona','usuario',0,'delete','movements','confirmed','2026-10-22','2026-10-22','batch:2',NULL::bigint,2,'00000002','96385074',NULL::bigint,'{\"owner_id\":1}','{\"transaction_id\":\"batch\",\"released\":1}'),('c',1,'Persona','usuario',0,'save_product','movements','confirmed','2026-10-22','2026-10-22','other',NULL::bigint,3,'00000003','96385074',NULL::bigint,'{}','{}')),users(id,public_name,role) AS (VALUES(1,'Persona','usuario')),stock_branches(id,name) AS (VALUES(1,'Sucursal')),";
echo substr_replace($sql,'WITH '.$fixtures,0,5);
