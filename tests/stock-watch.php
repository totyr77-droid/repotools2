<?php
require __DIR__.'/stock-saved-reference.php';
$db->exec("UPDATE pallets SET released=0 WHERE id=".(int)$p['id']);
$branch=(int)stock_query($db,"INSERT INTO stock_branches(owner_id,name,address,locality,province) VALUES(2,'Prueba','Calle','Ciudad','Provincia') RETURNING id")->fetchColumn();
$workspace=(int)stock_query($db,"INSERT INTO branch_workspaces(owner_id,name) VALUES(2,'Equipo') RETURNING id")->fetchColumn();
stock_query($db,'INSERT INTO branch_workspace_locations(branch_id,workspace_id) VALUES(?,?)',[$branch,$workspace]);
stock_query($db,"INSERT INTO branch_team_members(branch_id,user_id,role) VALUES(?,1,'operator')",[$branch]);
stock_query($db,'UPDATE pallets SET branch_id=? WHERE id=?',[$branch,$p['id']]);
stock_query($db,'DELETE FROM pallet_access WHERE pallet_id=?',[$p['id']]);
$check(stock_saved_reference($db,'96385074',1)==='Nombre del palet compartido','Vínculo de sucursal habilita referencia');
$first=stock_watch($db,'12345678',1);$check($first['pallet']['can_edit']&&!$first['unchanged'],'Primera consulta incluye detalle autorizado');
$same=stock_watch($db,'12345678',1,$first['watch_stamp']);$check($same['unchanged']&&!isset($same['pallet']),'Sin cambios no vuelve a descargar productos');
$other=stock_watch($db,'12345678',2,$first['watch_stamp']);$check(!$other['unchanged']&&$other['pallet']['is_owner'],'La firma queda ligada al usuario');
$product=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=?',[$p['id']])->fetch();
stock_deduct_product($db,2,['pallet_id'=>$p['id'],'product_id'=>$product['id'],'revision'=>$product['revision'],'amount'=>1,'kind'=>'units','pack_size'=>1]);
$changed=stock_watch($db,'12345678',1,$first['watch_stamp']);$check(!$changed['unchanged']&&(int)$changed['pallet']['products'][0]['units']===23,'Retiro de otra cuenta actualiza detalle');
stock_query($db,'UPDATE branch_team_members SET active=0 WHERE user_id=1');
$revoked=stock_watch($db,'12345678',1,$changed['watch_stamp']);$check(!$revoked['unchanged']&&!$revoked['pallet']['can_edit']&&!isset($revoked['pallet']['owner_id']),'Revocación refresca sin filtrar datos privados aunque no cambie revisión');
$check(stock_saved_reference($db,'96385074',1)==='Mi referencia guardada','Revocación impide reutilizar referencias de la sucursal');
stock_query($db,'UPDATE pallets SET released=1 WHERE id=?',[$p['id']]);stock_query($db,'UPDATE pallet_labels SET pallet_id=NULL,reserved_by=2,reserved_until=? WHERE code=?',[time()+86400,'12345678']);
$released=stock_watch($db,'12345678',1,$revoked['watch_stamp']);$check($released['pallet']['available']&&!$released['pallet']['can_claim']&&!isset($released['pallet']['products']),'Etiqueta liberada no conserva mercadería anterior');
try{stock_watch($db,'12345678',1,'invalid');throw new RuntimeException('Bad stamp accepted');}catch(CatalogException $error){$check($error->status===422,'Firma inválida');}
try{stock_watch($db,'12345678',0);throw new RuntimeException('Guest accepted');}catch(CatalogException $error){$check($error->status===401,'Solo cuenta autenticada');}
echo "OK: compact per-user watch, remote quantity, team reference, revocation without revision, public sanitization and released label.\n";
