<?php
// One registry for filter keys/labels; additional event/action pairs can be added here.
function report_log_filters(): array {
    return ['session_started'=>['label'=>'Sesión iniciada','type'=>'audit','action'=>'session_started'],'session_ended'=>['label'=>'Sesión cerrada','type'=>'audit','action'=>'session_ended'],'password_changed'=>['label'=>'Contraseña actualizada','type'=>'audit','action'=>'password_changed'],'password_recovered'=>['label'=>'Contraseña recuperada','type'=>'audit','action'=>'password_recovered'],'sessions_revoked'=>['label'=>'Sesiones revocadas','type'=>'audit','action'=>'sessions_revoked'],'transfer_commit'=>['label'=>'Transferencia','type'=>'audit','action'=>'transfer_commit'],'deduct_product'=>['label'=>'Mercadería retirada','type'=>'pallet_admin','action'=>'deduct_product'],'generate'=>['label'=>'QR generados','type'=>'audit','action'=>'generate'],'account_created'=>['label'=>'Cuenta creada','type'=>'audit','action'=>'account_created'],'email_verified'=>['label'=>'Correo verificado','type'=>'audit','action'=>'email_verified'],'referral_credited'=>['label'=>'Referido acreditado','type'=>'audit','action'=>'referral_credited'],'branch_share'=>['label'=>'Enlace de Stock creado','type'=>'audit','action'=>'branch_share'],'branch_revoke'=>['label'=>'Enlace de Stock revocado','type'=>'audit','action'=>'branch_revoke'],'library_shared'=>['label'=>'Colección compartida','type'=>'audit','action'=>'library_shared'],'branches'=>['label'=>'Gestión de sucursales','type'=>'branch'],'edited'=>['label'=>'Producto editado','type'=>'product_edit','action'=>'edited'],
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
    'roles'=>['label'=>'Cambio de rango','type'=>'user_role','action'=>'role_changed'],
    'profiles'=>['label'=>'Datos de cuenta actualizados','type'=>'user_profile','action'=>'account_updated']];
}
