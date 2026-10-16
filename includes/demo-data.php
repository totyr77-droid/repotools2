<?php
// Fictitious examples only. No account, stock or catalog queries.
function repotools_demo_data(): array {
    return [
        'summary'=>['Palets activos'=>3,'Productos'=>5,'Por vencer'=>2,'Vencidos'=>1],
        'pallets'=>[
            ['key'=>'demo-centro','code'=>'10000001','branch'=>'Supermercado Centro','count'=>3,'products'=>[['name'=>'Leche entera 1 L','amount'=>'12 unidades','expiry'=>'Vence en 2 días','state'=>'soon'],['name'=>'Yogur frutilla','amount'=>'8 unidades','expiry'=>'Vence mañana','state'=>'soon'],['name'=>'Galletas chocolate','amount'=>'6 unidades','expiry'=>'Vencido','state'=>'expired']]],
            ['key'=>'demo-norte','code'=>'10000002','branch'=>'Sucursal Norte','count'=>1,'products'=>[['name'=>'Arroz 1 kg','amount'=>'24 unidades','expiry'=>'Vence en 90 días','state'=>'normal']]],
            ['key'=>'demo-sur','code'=>'10000003','branch'=>'Sucursal Sur','count'=>1,'products'=>[['name'=>'Agua mineral 1,5 L','amount'=>'18 unidades','expiry'=>'Vence en 60 días','state'=>'normal']]],
        ],
        'list'=>[['name'=>'Gaseosa 2,25 L','ean'=>'779…001'],['name'=>'Vainillas 148 g','ean'=>'779…002'],['name'=>'Yogur vainilla','ean'=>'779…003'],['name'=>'Arroz 1 kg','ean'=>'779…004']],
    ];
}
