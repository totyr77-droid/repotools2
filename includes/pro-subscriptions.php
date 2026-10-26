<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

// Test integration only. Live credentials cannot enable live billing in this release.
function pro_config(): array {
    $amount=getenv('REPOTOOLS_MP_TEST_AMOUNT_CENTS')?:'';
    $url=rtrim(getenv('APP_URL')?:'','/');
    $config=['token'=>getenv('REPOTOOLS_MP_ACCESS_TOKEN')?:'', 'secret'=>getenv('REPOTOOLS_MP_WEBHOOK_SECRET')?:'',
        'buyer'=>getenv('REPOTOOLS_MP_TEST_BUYER_EMAIL')?:'', 'seller'=>getenv('REPOTOOLS_MP_TEST_SELLER_ID')?:'',
        'amount'=>ctype_digit($amount)?(int)$amount:0,'url'=>$url];
    $config['enabled']=getenv('REPOTOOLS_BILLING_MODE')==='test'
        &&$url==='https://repotools-desarrollo.onrender.com'
        &&$config['token']!==''&&strlen($config['secret'])>=16&&ctype_digit($config['seller'])
        &&preg_match('/^test_user_[a-z0-9_]+@testuser\.com$/iD',$config['buyer'])===1
        &&$config['amount']>0&&$config['amount']<=100000000;
    return $config;
}
function pro_require_tester(PDO $db,int $user): void {
    if(stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()!=='manager')throw new CatalogException('La suscripción está en pruebas privadas.',403);
}
function pro_state(PDO $db,int $user): array {
    $row=stock_query($db,'SELECT status,paid_until FROM pro_subscriptions WHERE user_id=?',[$user])->fetch();
    $config=pro_config();
    return ['mode'=>'test','enabled'=>(bool)$config['enabled'],'amount_cents'=>$config['amount'],
        'status'=>$row['status']??'free','pro'=>(int)($row['paid_until']??0)>time(),
        'paid_until'=>(int)($row['paid_until']??0),'enforced'=>true];
}
final class ProSubscriptions {
    public function __construct(private PDO $db,private array $config,private $transport=null) {}
    private function request(string $method,string $path,?array $body=null,string $key=''): array {
        if(!$this->config['enabled'])throw new CatalogException('Las pruebas de pago todavía no están habilitadas.',503);
        if($this->transport)return ($this->transport)($method,$path,$body,$key);
        $ch=curl_init('https://api.mercadopago.com'.$path);
        $headers=['Authorization: Bearer '.$this->config['token'],'Content-Type: application/json'];
        if($key!=='')$headers[]='X-Idempotency-Key: '.$key;
        curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>$headers]);
        if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));
        $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        $data=is_string($raw)?json_decode($raw,true):null;
        if($status<200||$status>=300||!is_array($data))throw new CatalogException('No pudimos comunicarnos con Mercado Pago. Volvé a intentar.',503);
        return $data;
    }
    private function seller(): void {
        $seller=$this->request('GET','/users/me');
        if((string)($seller['id']??'')!==$this->config['seller']||!in_array('test_user',$seller['tags']??[],true))throw new CatalogException('Configurá una cuenta vendedora de prueba.',503);
    }
    private function row(int $user): array|false {
        return stock_query($this->db,'SELECT * FROM pro_subscriptions WHERE user_id=?',[$user])->fetch();
    }
    private function lock(string $reference): array {
        // Serialize provider reads and local transitions, including duplicate/out-of-order webhooks.
        stock_query($this->db,'UPDATE pro_subscriptions SET updated_at=updated_at WHERE reference=?',[$reference]);
        $row=stock_query($this->db,'SELECT * FROM pro_subscriptions WHERE reference=?',[$reference])->fetch();
        if(!$row)throw new CatalogException('Suscripción no encontrada.',404);
        return $row;
    }
    private function transaction(callable $fn): mixed {
        $this->db->beginTransaction();try{$value=$fn();$this->db->commit();return $value;}
        catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function validate(array $remote,array $row): void {
        $recurring=$remote['auto_recurring']??[];
        if(($remote['external_reference']??'')!==$row['reference']
            ||(string)($remote['collector_id']??'')!==$this->config['seller']
            ||($recurring['currency_id']??'')!=='ARS'||(int)($recurring['frequency']??0)!==1
            ||($recurring['frequency_type']??'')!=='months'
            ||(int)round((float)($recurring['transaction_amount']??0)*100)!==(int)$row['amount_cents'])throw new CatalogException('No pudimos verificar la suscripción.',409);
        if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',(string)($remote['id']??'')))throw new CatalogException('Respuesta de suscripción no válida.',503);
        if($row['provider_id']!==null&&$row['provider_id']!==$remote['id'])throw new CatalogException('La suscripción no coincide.',409);
    }
    private function apply(array $remote,array $row): void {
        $this->validate($remote,$row);
        $status=$remote['status']??'';
        if(!in_array($status,['pending','authorized','paused','cancelled'],true))throw new CatalogException('Estado de suscripción no reconocido.',409);
        $checkout=$remote['init_point']??$row['checkout_url'];
        if($checkout!==''&&(parse_url($checkout,PHP_URL_SCHEME)!=='https'||parse_url($checkout,PHP_URL_HOST)!=='www.mercadopago.com.ar'))throw new CatalogException('Enlace de pago no válido.',503);
        stock_query($this->db,'UPDATE pro_subscriptions SET provider_id=?,status=?,checkout_url=?,updated_at=? WHERE reference=?',[$remote['id'],$status,$checkout,time(),$row['reference']]);
    }
    public function checkout(int $user): string {
        $this->seller();
        // A persistent reference/idempotency key survives network timeouts and repeated clicks.
        $ref=bin2hex(random_bytes(24));
        stock_query($this->db,"INSERT INTO pro_subscriptions(reference,user_id,amount_cents) VALUES(?,?,?) ON CONFLICT(user_id) DO NOTHING",[$ref,$user,$this->config['amount']]);
        $create=$this->transaction(function()use($user){
            $row=$this->lock($this->row($user)['reference']);
            if(in_array($row['status'],['cancelled','paused'],true))throw new CatalogException('La suscripción está cancelada o pausada. Revisala antes de contratar nuevamente.',409);
            if($row['status']==='authorized')throw new CatalogException('Ya tenés una suscripción autorizada.',409);
            if($row['checkout_url']!=='')return ['url'=>$row['checkout_url']];
            $first=$row['status']==='new';
            if($first)stock_query($this->db,"UPDATE pro_subscriptions SET status='creating',updated_at=? WHERE reference=?",[time(),$row['reference']]);
            return ['row'=>$row,'first'=>$first];
        });
        if(isset($create['url']))return $create['url'];
        $row=$create['row'];
        if($create['first']){
            $remote=$this->request('POST','/preapproval',[
                'reason'=>'RepoTools Pro · prueba','external_reference'=>$row['reference'],'payer_email'=>$this->config['buyer'],
                'auto_recurring'=>['frequency'=>1,'frequency_type'=>'months','transaction_amount'=>(int)$row['amount_cents']/100,'currency_id'=>'ARS'],
                'back_url'=>$this->config['url'].'/pro.php','status'=>'pending'
            ],$row['reference']);
        }else{
            // Do not repeat POST after an ambiguous timeout: recover by reference instead.
            $search=$this->request('GET','/preapproval/search?external_reference='.rawurlencode($row['reference']));
            $matches=array_values(array_filter($search['results']??[],fn($r)=>($r['external_reference']??'')===$row['reference']));
            if(count($matches)!==1)throw new CatalogException('Estamos verificando el intento anterior. Volvé a intentar más tarde.',409);
            $remote=$this->request('GET','/preapproval/'.rawurlencode((string)$matches[0]['id']));
        }
        return $this->transaction(function()use($row,$remote){$current=$this->lock($row['reference']);
            $remote=$this->request('GET','/preapproval/'.rawurlencode((string)$remote['id']));$this->apply($remote,$current);
            if(empty($remote['init_point']))throw new CatalogException('No recibimos el enlace de pago.',503);
            return $remote['init_point'];});
    }
    public function cancel(int $user): void {
        $this->seller();$row=$this->row($user);if(!$row||!$row['provider_id'])throw new CatalogException('No hay una suscripción para cancelar.',409);
        $this->transaction(function()use($row){$current=$this->lock($row['reference']);
            $remote=$this->request('GET','/preapproval/'.rawurlencode($current['provider_id']));$this->validate($remote,$current);
            if(($remote['status']??'')!=='cancelled')$remote=$this->request('PUT','/preapproval/'.rawurlencode($current['provider_id']),['status'=>'cancelled']);
            if(($remote['status']??'')!=='cancelled')throw new CatalogException('Mercado Pago todavía no confirmó la cancelación.',503);
            $this->apply($remote,$current); // paid_until is deliberately preserved.
        });
    }
    public function subscription(string $id): void {
        $row=stock_query($this->db,'SELECT reference FROM pro_subscriptions WHERE provider_id=?',[$id])->fetch();
        if(!$row){$remote=$this->request('GET','/preapproval/'.rawurlencode($id));$row=stock_query($this->db,'SELECT reference FROM pro_subscriptions WHERE reference=?',[(string)($remote['external_reference']??'')])->fetch();if(!$row)return;}
        $this->transaction(function()use($row,$id){$current=$this->lock($row['reference']);$this->apply($this->request('GET','/preapproval/'.rawurlencode($id)),$current);});
    }
    public function invoice(string $id): void {
        $invoice=$this->request('GET','/authorized_payments/'.rawurlencode($id));
        $parent=(string)($invoice['preapproval_id']??'');
        if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',$parent))throw new CatalogException('Suscripción no válida.',409);
        $this->subscription($parent); // Recover webhooks arriving before the checkout response was saved.
        $row=stock_query($this->db,'SELECT reference FROM pro_subscriptions WHERE provider_id=?',[(string)($invoice['preapproval_id']??'')])->fetch();
        if(!$row)return;
        $this->transaction(function()use($row,$id){
            $current=$this->lock($row['reference']);
            $invoice=$this->request('GET','/authorized_payments/'.rawurlencode($id));
            if((string)($invoice['preapproval_id']??'')!==$current['provider_id']||(string)($invoice['id']??'')!==$id)throw new CatalogException('La cuota no coincide.',409);
            $this->validate($this->request('GET','/preapproval/'.rawurlencode($current['provider_id'])),$current);
            $paymentId=(string)($invoice['payment']['id']??'');
            if(!ctype_digit($paymentId))return; // Pending invoice without a payment grants nothing.
            $payment=$this->request('GET','/v1/payments/'.$paymentId);
            if(($payment['live_mode']??null)!==false||(string)($payment['collector_id']??'')!==$this->config['seller']
                ||($payment['currency_id']??'')!=='ARS'||(int)round((float)($payment['transaction_amount']??0)*100)!==(int)$current['amount_cents']
                ||(string)($payment['id']??'')!==$paymentId)throw new CatalogException('El pago no corresponde a esta prueba.',409);
            $status=(string)($payment['status']??'');$date=$invoice['debit_date']??null;
            if(!is_string($date)||!strtotime($date))throw new CatalogException('La cuota no tiene una fecha válida.',409);
            $start=new DateTimeImmutable($date);$next=$start->modify('first day of next month');
            $end=$next->setDate((int)$next->format('Y'),(int)$next->format('m'),min((int)$start->format('d'),(int)$next->format('t')))->getTimestamp();
            stock_query($this->db,'INSERT INTO pro_payments(provider_id,reference,status,period_end,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(provider_id) DO UPDATE SET status=excluded.status,period_end=excluded.period_end,updated_at=excluded.updated_at',[$paymentId,$current['reference'],$status,$end,time()]);
            $until=(int)stock_query($this->db,"SELECT COALESCE(MAX(period_end),0) FROM pro_payments WHERE reference=? AND status='approved'",[$current['reference']])->fetchColumn();
            stock_query($this->db,'UPDATE pro_subscriptions SET paid_until=?,updated_at=? WHERE reference=?',[$until,time(),$current['reference']]);
        });
    }
}
function pro_verify_webhook(string $query,string $signature,string $requestId,string $secret,int $now): string {
    // PHP normalizes data.id to data_id: read and authenticate the original query key.
    $values=[];foreach(explode('&',$query) as $pair){$part=explode('=',$pair,2);if(rawurldecode($part[0])==='data.id')$values[]=rawurldecode($part[1]??'');}
    if(count($values)!==1||!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',$values[0])||!preg_match('/^[a-zA-Z0-9_-]{1,150}$/D',$requestId))throw new CatalogException('Notificación no válida.',401);
    $parts=[];foreach(explode(',',$signature)as $piece){$p=explode('=',trim($piece),2);if(count($p)===2){if(isset($parts[$p[0]]))throw new CatalogException('Firma no válida.',401);$parts[$p[0]]=$p[1];}}
    $ts=$parts['ts']??'';$seconds=strlen($ts)>10?(int)floor((float)$ts/1000):(int)$ts;
    if(!ctype_digit($ts)||abs($now-$seconds)>300||!preg_match('/^[a-f0-9]{64}$/D',$parts['v1']??''))throw new CatalogException('Firma vencida o no válida.',401);
    $id=strtolower($values[0]);$expected=hash_hmac('sha256',"id:$id;request-id:$requestId;ts:$ts;",$secret);
    if(!hash_equals($expected,$parts['v1']))throw new CatalogException('Firma no válida.',401);
    return $values[0];
}
