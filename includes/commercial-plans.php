<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';
require_once __DIR__.'/audit.php';
function commercial_require_manager(PDO $db,int $user): void {
    if(stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()!=='manager')throw new CatalogException('Solo managers pueden configurar planes.',403);
}
function commercial_plans(PDO $db,int $user): array {
    commercial_require_manager($db,$user);
    return stock_query($db,"SELECT scope,name,max_users,max_branches,max_pallets,max_products,revision FROM commercial_plans ORDER BY CASE scope WHEN 'user' THEN 1 WHEN 'branch' THEN 2 ELSE 3 END")->fetchAll();
}
function commercial_save(PDO $db,int $user,array $input): void {
    commercial_require_manager($db,$user);
    $scope=$input['scope']??null;$name=$input['name']??null;$revision=$input['revision']??null;
    if(!is_string($scope)||!in_array($scope,['user','branch','company'],true)||!is_string($name)||!is_scalar($revision)||!preg_match('/^[1-9][0-9]{0,9}$/D',(string)$revision))throw new CatalogException('Revisá el plan seleccionado.',422);
    $name=trim($name);if($name===''||mb_strlen($name)>80)throw new CatalogException('Ingresá un nombre de hasta80 caracteres.',422);
    $limits=[];foreach(['max_users','max_branches','max_pallets','max_products'] as $key){$value=$input[$key]??'';if($value===null||$value===''){$limits[$key]=null;continue;}if(!is_scalar($value)||!preg_match('/^[1-9][0-9]{0,6}$/D',(string)$value)||(int)$value>1000000)throw new CatalogException('Usá límites entre1 y1.000.000, o dejá el campo vacío.',422);$limits[$key]=(int)$value;}
    if($scope==='user'&&($limits['max_users']!==null||$limits['max_branches']!==null)||$scope==='branch'&&$limits['max_branches']!==null)throw new CatalogException('Ese límite no corresponde al alcance del plan.',422);
    $db->beginTransaction();try{
        commercial_require_manager($db,$user);
        $before=stock_query($db,'SELECT * FROM commercial_plans WHERE scope=?',[$scope])->fetch();
        if(!$before||(int)$before['revision']!==(int)$revision)throw new CatalogException('El plan cambió. Cerrá y volvé a abrir para actualizarlo.',409);
        if(!stock_query($db,'UPDATE commercial_plans SET name=?,max_users=?,max_branches=?,max_pallets=?,max_products=?,revision=revision+1,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE scope=? AND revision=?',[$name,...array_values($limits),$user,$scope,(int)$revision])->rowCount())throw new CatalogException('El plan cambió. Cerrá y volvé a abrir para actualizarlo.',409);
        audit_event($db,$user,'commercial_plan_saved','commercial-plan:'.$scope.':'.((int)$revision+1),['category'=>'accounts'],['scope'=>$scope,'name'=>$before['name'],...array_intersect_key($before,$limits)],['scope'=>$scope,'name'=>$name,...$limits]);
        $db->commit();
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}
