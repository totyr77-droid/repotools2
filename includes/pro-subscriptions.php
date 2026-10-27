<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

// Test integration only. Live credentials cannot enable live billing in this release.
function pro_config(): array {
    $amount=getenv('REPOTOOLS_MP_TEST_AMOUNT_CENTS')?:'';
    $url=rtrim(getenv('APP_URL')?:'','/');
    $config=['token'=>getenv('REPOTOOLS_MP_ACCESS_TOKEN')?:'', 'secret'=>getenv('REPOTOOLS_MP_WEBHOOK_SECRET')?:'',
        'buyer'=>getenv('REPOTOOLS_MP_TEST_BUYER_EMAIL')?:'', 'seller'=>getenv('REPOTOOLS_MP_TEST_SELLER_ID')?:'',
        'amount'=>ctype_digit($amount)?(int)$amount:0,'url'=>$url,'plan'=>trim(getenv('REPOTOOLS_MP_TEST_PLAN_ID')?:''),'public_key'=>trim(getenv('REPOTOOLS_MP_PUBLIC_KEY')?:'')];
    $config['enabled']=getenv('REPOTOOLS_BILLING_MODE')==='test'
        &&$url==='https://repotools-desarrollo.onrender.com'
        &&$config['token']!==''&&strlen($config['secret'])>=16&&ctype_digit($config['seller'])
        &&preg_match('/^test_user_[a-z0-9_]+@testuser\.com$/iD',$config['buyer'])===1
        &&$config['amount']>0&&$config['amount']<=100000000;
    return $config;
}
// Temporary monthly quotes for the beta catalogue. Team quotes never alter PRO billing.
function pro_beta_plan_quotes(): array {
    $config=pro_config();
    return ['pro'=>(int)($config['amount']?:10000),'supervision'=>20000,'supermercado'=>30000];
}
function pro_require_tester(PDO $db,int $user): void {
    $role=stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn();
    if(!$role||($role!=='manager'&&rtrim(getenv('APP_URL')?:'','/')!=='https://repotools-desarrollo.onrender.com'))throw new CatalogException('Las pruebas de suscripción están disponibles en beta.',403);
}
function pro_state(PDO $db,int $user): array {
    $row=stock_query($db,'SELECT status,paid_until FROM pro_subscriptions WHERE user_id=?',[$user])->fetch();
    $config=pro_config();
    return ['mode'=>'test','enabled'=>(bool)$config['enabled'],'amount_cents'=>$config['amount'],
        'card_required'=>($config['plan']??'')!=='','public_key'=>$config['enabled']?($config['public_key']??''):'','test_buyer'=>$config['enabled']?$config['buyer']:'','status'=>$row['status']??'free','pro'=>(int)($row['paid_until']??0)>time(),
        'paid_until'=>(int)($row['paid_until']??0),'enforced'=>true];
}
function pro_provider_failure(int $status,?array $data,int $network=0): array {
    $message=strtolower((string)($data['message']??''));
    $kind=match(true){
        $network!==0||$status===0=>'network',
        str_contains($message,'card_token')||str_contains($message,'card token')=>'card_token',
        str_contains($message,'payer')&&str_contains($message,'collector')=>'payer_seller',
        str_contains($message,'credential')||$status===401||$status===403=>'credentials',
        str_contains($message,'preapproval_plan')||str_contains($message,'plan')=>'plan',
        default=>'provider'
    };
    // Only allowlisted categories and numeric status: never log provider bodies/card tokens.
    $notice=match($kind){
        'network'=>'No pudimos conectar con Mercado Pago.',
        'card_token'=>'Mercado Pago rechazó el token de tarjeta de prueba.',
        'payer_seller'=>'Mercado Pago rechazó la combinación de comprador y vendedor de prueba.',
        'credentials'=>'Mercado Pago rechazó las credenciales de esta prueba.',
        'plan'=>'Mercado Pago rechazó la configuración del plan.',
        default=>'Mercado Pago rechazó la solicitud de prueba.'
    };
    return ['category'=>$kind,'message'=>$notice.' Código HTTP '.$status.'.'];
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
        $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$network=curl_errno($ch);curl_close($ch);
        $data=is_string($raw)?json_decode($raw,true):null;
        if($status===401&&is_array($data)&&($data['message']??'')==="This app can't create preapprovals without a plan")throw new CatalogException('Mercado Pago requiere un plan asociado para esta aplicación. La configuración de prueba debe actualizarse.',503);
        if($status<200||$status>=300||!is_array($data)){$failure=pro_provider_failure((int)$status,is_array($data)?$data:null,$network);error_log('RepoTools Pro provider http='.(int)$status.' category='.$failure['category'].' network='.(int)$network);throw new CatalogException($failure['message'],503);}
        return $data;
    }
    private function seller(): void {
        $seller=$this->request('GET','/users/me');
        if((string)($seller['id']??'')!==$this->config['seller']||!in_array('test_user',$seller['tags']??[],true))throw new CatalogException('Configurá una cuenta vendedora de prueba.',503);
        if(!empty($seller['email'])&&strcasecmp(trim($seller['email']),trim($this->config['buyer']))===0)throw new CatalogException('El comprador de prueba debe ser una cuenta distinta del vendedor. Revisá el correo configurado.',422);
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
        if(($this->config['plan']??'')!==''&&($remote['preapproval_plan_id']??'')!==$this->config['plan'])throw new CatalogException('El plan de suscripción no coincide.',409);
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
    public function checkout(int $user,string $cardToken=''): string {
        if(($this->config['plan']??'')!==''&&!preg_match('/^[a-zA-Z0-9_-]{16,256}$/D',$cardToken))throw new CatalogException('Completá la tarjeta de prueba para continuar.',422);
        $this->seller();
        // A persistent reference/idempotency key survives network timeouts and repeated clicks.
        $ref=bin2hex(random_bytes(24));
        stock_query($this->db,"INSERT INTO pro_subscriptions(reference,user_id,amount_cents) VALUES(?,?,?) ON CONFLICT(user_id) DO NOTHING",[$ref,$user,$this->config['amount']]);
        $create=$this->transaction(function()use($user){
            $row=$this->lock($this->row($user)['reference']);
            if($row['status']==='beta_trial'&&$row['provider_id']===null&&$row['checkout_url']===''){stock_query($this->db,"UPDATE pro_subscriptions SET status='new',amount_cents=? WHERE reference=?",[$this->config['amount'],$row['reference']]);$row['status']='new';$row['amount_cents']=$this->config['amount'];}
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
                ...(($this->config['plan']??'')!==''?['preapproval_plan_id'=>$this->config['plan'],'card_token_id'=>$cardToken]:[]),
                'reason'=>'RepoTools Pro · prueba','external_reference'=>$row['reference'],'payer_email'=>$this->config['buyer'],
                'auto_recurring'=>['frequency'=>1,'frequency_type'=>'months','transaction_amount'=>(int)$row['amount_cents']/100,'currency_id'=>'ARS'],
                'back_url'=>$this->config['url'].'/pro.php','status'=>($this->config['plan']??'')!==''?'authorized':'pending'
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
            if(($remote['status']??'')==='authorized')return '';
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
    public function reconcile(int $user): void {
        $row=$this->row($user);
        if(!$row||!$row['provider_id'])return;
        $result=$this->request('GET','/authorized_payments/search?preapproval_id='.rawurlencode($row['provider_id']).'&limit=5&offset=0');
        if(!isset($result['results'])||!is_array($result['results']))throw new CatalogException('No pudimos comprobar los pagos.',503);
        foreach(array_slice($result['results'],0,5) as $invoice){
            if((string)($invoice['preapproval_id']??'')!==$row['provider_id'])throw new CatalogException('La cuota no corresponde a tu suscripción.',409);
            $id=(string)($invoice['id']??'');
            if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',$id))throw new CatalogException('Cuota no válida.',409);
            if(!empty($invoice['payment']['id']))$this->invoice($id,(string)$row['provider_id']);
        }
    }
    public function invoice(string $id,?string $expectedParent=null): void {
        $invoice=$this->request('GET','/authorized_payments/'.rawurlencode($id));
        $parent=(string)($invoice['preapproval_id']??'');
        if($expectedParent!==null&&$parent!==$expectedParent)throw new CatalogException('La cuota no corresponde a tu suscripción.',409);
        if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',$parent))throw new CatalogException('Suscripción no válida.',409);
        $this->subscription($parent); // Recover webhooks arriving before the checkout response was saved.
        $row=stock_query($this->db,'SELECT reference FROM pro_subscriptions WHERE provider_id=?',[(string)($invoice['preapproval_id']??'')])->fetch();
        if(!$row)return;
        $this->transaction(function()use($row,$id){
            $current=$this->lock($row['reference']);
            $invoice=$this->request('GET','/authorized_payments/'.rawurlencode($id));
            if((string)($invoice['preapproval_id']??'')!==$current['provider_id']||(string)($invoice['id']??'')!==$id)throw new CatalogException('La cuota no coincide.',409);
            $subscription=$this->request('GET','/preapproval/'.rawurlencode($current['provider_id']));$this->validate($subscription,$current);
            $paymentId=(string)($invoice['payment']['id']??'');
            if(!ctype_digit($paymentId))return; // Pending invoice without a payment grants nothing.
            $payment=$this->request('GET','/v1/payments/'.$paymentId);
            if(($payment['live_mode']??null)===true){
                // Test accounts may return live_mode=true: verify identity, never trust that flag alone.
                $this->seller();
                if(($this->config['url']??'')!=='https://repotools-desarrollo.onrender.com'
                    ||!ctype_digit((string)($subscription['payer_id']??''))
                    ||(string)($payment['payer']['id']??'')!==(string)$subscription['payer_id']
                    ||(string)$subscription['payer_id']===$this->config['seller']
                    ||strcasecmp((string)($payment['payer']['email']??''),$this->config['buyer'])!==0)throw new CatalogException('El comprador no corresponde a esta prueba.',409);
            }elseif(($payment['live_mode']??null)!==false)throw new CatalogException('No pudimos verificar el modo del pago.',409);
            if((string)($payment['collector_id']??'')!==$this->config['seller']
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
