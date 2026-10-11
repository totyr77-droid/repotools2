<?php
// One registry for filter keys/labels; additional event/action pairs can be added here.
function report_log_filters(): array {
    return ['edited'=>['label'=>'Producto editado','type'=>'product_edit','action'=>'edited'],
    'approved'=>['label'=>'Reporte aprobado','type'=>'report_review','action'=>'approved'],
    'rejected'=>['label'=>'Reporte rechazado','type'=>'report_review','action'=>'rejected'],
    'blocked'=>['label'=>'Reportes bloqueados','type'=>'report_block','action'=>'blocked'],
    'unblocked'=>['label'=>'Reportes habilitados','type'=>'report_block','action'=>'unblocked'],
    'pallets'=>['label'=>'Gestión de palets','type'=>'pallet_admin'],
    'save_product'=>['label'=>'Producto guardado en palet','type'=>'pallet_admin','action'=>'save_product'],
    'delete_product'=>['label'=>'Producto eliminado de palet','type'=>'pallet_admin','action'=>'delete_product'],
    'description'=>['label'=>'Descripción de palet','type'=>'pallet_admin','action'=>'description'],
    'delete'=>['label'=>'Palet eliminado','type'=>'pallet_admin','action'=>'delete'],
    'invite'=>['label'=>'Invitación a palet','type'=>'pallet_admin','action'=>'invite'],
    'revoke'=>['label'=>'Acceso a palet eliminado','type'=>'pallet_admin','action'=>'revoke'],
    'roles'=>['label'=>'Cambio de rango','type'=>'user_role'],
    'profiles'=>['label'=>'Datos de cuenta actualizados','type'=>'user_profile']];
}
