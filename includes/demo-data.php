<?php
// Fictitious examples only. No account, stock or catalog queries.
function repotools_demo_data(): array {
    return [
        'summary'=>['Palets activos'=>3,'Productos'=>148,'Por vencer'=>6,'Vencidos'=>2],
        'pallets'=>[
            ['key'=>'demo-centro','code'=>'001','branch'=>'Supermercado Centro','count'=>42,'products'=>[['name'=>'Leche entera 1 L','amount'=>'12 unidades','expiry'=>'Vence en 2 días','state'=>'soon'],['name'=>'Yogur frutilla','amount'=>'8 unidades','expiry'=>'Vence mañana','state'=>'soon'],['name'=>'Galletas chocolate','amount'=>'6 unidades','expiry'=>'Vencido','state'=>'expired']]],
            ['key'=>'demo-norte','code'=>'002','branch'=>'Sucursal Norte','count'=>63,'products'=>[['name'=>'Arroz 1 kg','amount'=>'24 unidades','expiry'=>'Vence en 90 días','state'=>'normal']]],
            ['key'=>'demo-sur','code'=>'003','branch'=>'Sucursal Sur','count'=>43,'products'=>[['name'=>'Agua mineral 1,5 L','amount'=>'18 unidades','expiry'=>'Vence en 60 días','state'=>'normal']]],
        ],
        'list'=>[['name'=>'Gaseosa 2,25 L','ean'=>'779…001'],['name'=>'Vainillas 148 g','ean'=>'779…002'],['name'=>'Yogur vainilla','ean'=>'779…003'],['name'=>'Arroz 1 kg','ean'=>'779…004']],
    ];
}
