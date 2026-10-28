<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

// Test integration only. Live credentials cannot enable live billing in this release.
function billing_product(mixed $value='pro'): string {
    if(!is_string($value)||!in_array($value,['pro','supervision'],true))throw new CatalogException('Plan no válido.',422);
    return $value;
}
function pro_config(string $product='pro'): array {
    $product=billing_product($product);
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
    $config['hosted_enabled']=getenv('REPOTOOLS_MP_HOSTED_ENABLED')==='1';
    $config['product']=$product;
    if($product==='supervision'){$config['amount']=20000;$config['plan']=trim(getenv('REPOTOOLS_MP_TEST_SUPERVISION_PLAN_ID')?:'');$config['enabled']=$config['enabled']&&$config['plan']!=='';}
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
function pro_state(PDO $db,int $user,string $product='pro'): array {
    $product=billing_product($product);$table=$product==='supervision'?'supervision_subscriptions':'pro_subscriptions';
    $row=stock_query($db,"SELECT reference,status,paid_until FROM $table WHERE user_id=?",[$user])->fetch();
    $config=pro_config($product);
    $team=$product==='supervision'?stock_query($db,'SELECT name,name_pending FROM supervisor_teams WHERE owner_id=?',[$user])->fetch():false;
    return ['product'=>$product,'mode'=>'test','enabled'=>(bool)$config['enabled'],'amount_cents'=>$config['amount'],
        'can_restart'=>($row['status']??'')==='cancelled'&&(int)($row['paid_until']??0)===0&&(int)stock_query($db,'SELECT COUNT(*) FROM '.($product==='supervision'?'supervision_payments':'pro_payments').' WHERE reference=?',[$row['reference']])->fetchColumn()===0,
        'team_name'=>$team['name']??'','team_name_required'=>(int)($row['paid_until']??0)>time()&&!empty($team['name_pending']),
        'hosted_enabled'=>$config['enabled']&&$config['hosted_enabled'],'card_required'=>($config['plan']??'')!==''&&($row['status']??'')!=='pending','public_key'=>$config['enabled']?($config['public_key']??''):'','test_buyer'=>$config['enabled']?$config['buyer']:'','status'=>$row['status']??'free','pro'=>(int)($row['paid_until']??0)>time(),
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
    public function __construct(private PDO $db,private array $config,private $transport=null) {billing_product($config['product']??'pro');}
    private function query(string $sql,array $args=[]): PDOStatement {
        if(($this->config['product']??'pro')==='supervision')$sql=str_replace(['pro_subscriptions','pro_payments'],['supervision_subscriptions','supervision_payments'],$sql);
        return stock_query($this->db,$sql,$args);
    }
    public function reconcileProvider(string $provider): void {$row=$this->query('SELECT user_id FROM pro_subscriptions WHERE provider_id=?',[$provider])->fetch();if($row)$this->reconcile((int)$row['user_id']);}
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
        return $this->query('SELECT * FROM pro_subscriptions WHERE user_id=?',[$user])->fetch();
    }
    private function lock(string $reference): array {
        // Serialize provider reads and local transitions, including duplicate/out-of-order webhooks.
        $this->query('UPDATE pro_subscriptions SET updated_at=updated_at WHERE reference=?',[$reference]);
        $row=$this->query('SELECT * FROM pro_subscriptions WHERE reference=?',[$reference])->fetch();
        if(!$row)throw new CatalogException('Suscripción no encontrada.',404);
        return $row;
    }
    private function transaction(callable $fn): mixed {
        $this->db->beginTransaction();try{$value=$fn();$this->db->commit();return $value;}
        catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function restart(int $user): void {
        $row=$this->row($user);
        if(!$row||$row['status']==='new')return;
        if($row['status']!=='cancelled'||(int)$row['paid_until']>0||!$row['provider_id'])throw new CatalogException('Este plan tiene un período o un intento que debe conservarse.',409);
        $this->seller();
        $remote=$this->request('GET','/preapproval/'.rawurlencode($row['provider_id']));$this->validate($remote,$row);
        if(($remote['status']??'')!=='cancelled')throw new CatalogException('Mercado Pago todavía no confirmó la cancelación.',409);
        $invoices=$this->request('GET','/authorized_payments/search?preapproval_id='.rawurlencode($row['provider_id']).'&limit=5&offset=0');
        if(!isset($invoices['results'])||!is_array($invoices['results'])||count($invoices['results'])>=5||(int)($invoices['paging']['total']??0)>count($invoices['results']))throw new CatalogException('No pudimos verificar el intento anterior.',409);
        foreach($invoices['results'] as $invoice)if((string)($invoice['preapproval_id']??'')!==$row['provider_id']||!in_array($invoice['payment']['status']??'',['rejected','cancelled'],true))throw new CatalogException('El intento anterior tiene un pago que debe verificarse.',409);
        $this->transaction(function()use($row){$current=$this->lock($row['reference']);
            if($current['status']!=='cancelled'||(int)$current['paid_until']>0||$current['provider_id']!==$row['provider_id']||(int)$this->query('SELECT COUNT(*) FROM pro_payments WHERE reference=?',[$row['reference']])->fetchColumn()>0)throw new CatalogException('El historial de este plan debe conservarse.',409);
            stock_query($this->db,'INSERT INTO billing_cancelled_attempts(reference,user_id,product,snapshot,archived_at) VALUES(?,?,?,?,?)',[$current['reference'],$current['user_id'],$this->config['product']??'pro',json_encode($current,JSON_THROW_ON_ERROR),time()]);
            $this->query('DELETE FROM pro_subscriptions WHERE reference=?',[$current['reference']]);
            $this->query('INSERT INTO pro_subscriptions(reference,user_id,amount_cents) VALUES(?,?,?)',[bin2hex(random_bytes(24)),$current['user_id'],$this->config['amount']]);
        });
    }
    public function nameTeam(int $user,string $name): void {
        if(($this->config['product']??'pro')!=='supervision')throw new CatalogException('Plan no válido.',422);
        require_once __DIR__.'/supervision.php';
        $team=supervisor_require($this->db,$user);if(mb_strlen(trim($name))>100)throw new CatalogException('Escribí un nombre de hasta 100 caracteres.',422);$name=catalog_text($name,100);
        if($name===''||mb_strlen($name)>100)throw new CatalogException('Escribí un nombre de hasta 100 caracteres.',422);
        stock_query($this->db,'UPDATE supervisor_teams SET name=?,name_pending=0,revision=revision+1 WHERE id=? AND owner_id=?',[$name,$team['id'],$user]);
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
        $this->query('UPDATE pro_subscriptions SET provider_id=?,status=?,checkout_url=?,updated_at=? WHERE reference=?',[$remote['id'],$status,$checkout,time(),$row['reference']]);
    }
    public function checkout(int $user,string $cardToken='',bool $hosted=false): string {
        if($hosted&&empty($this->config['hosted_enabled']))throw new CatalogException('El pago en el sitio de Mercado Pago todavía no está habilitado para esta aplicación.',503);
        $pending=$this->row($user);$resuming=$pending&&$pending['status']==='pending'&&$pending['checkout_url']!=='';
        if(!$hosted&&!$resuming&&($this->config['plan']??'')!==''&&!preg_match('/^[a-zA-Z0-9_-]{16,256}$/D',$cardToken))throw new CatalogException('Completá la tarjeta de prueba para continuar.',422);
        $this->seller();
        // A persistent reference/idempotency key survives network timeouts and repeated clicks.
        $ref=bin2hex(random_bytes(24));
        $this->query("INSERT INTO pro_subscriptions(reference,user_id,amount_cents) VALUES(?,?,?) ON CONFLICT(user_id) DO NOTHING",[$ref,$user,$this->config['amount']]);
        $create=$this->transaction(function()use($user){
            $row=$this->lock($this->row($user)['reference']);
            if($row['status']==='beta_trial'&&$row['provider_id']===null&&$row['checkout_url']===''){$this->query("UPDATE pro_subscriptions SET status='new',amount_cents=? WHERE reference=?",[$this->config['amount'],$row['reference']]);$row['status']='new';$row['amount_cents']=$this->config['amount'];}
            if(in_array($row['status'],['cancelled','paused'],true))throw new CatalogException('La suscripción está cancelada o pausada. Revisala antes de contratar nuevamente.',409);
            if($row['status']==='authorized')throw new CatalogException('Ya tenés una suscripción autorizada.',409);
            if($row['checkout_url']!=='')return ['url'=>$row['checkout_url']];
            $first=$row['status']==='new';
            if($first)$this->query("UPDATE pro_subscriptions SET status='creating',updated_at=? WHERE reference=?",[time(),$row['reference']]);
            return ['row'=>$row,'first'=>$first];
        });
        if(isset($create['url']))return $create['url'];
        $row=$create['row'];
        if($create['first']){
            $remote=$this->request('POST','/preapproval',[
                ...(($this->config['plan']??'')!==''?['preapproval_plan_id'=>$this->config['plan'],...(!$hosted?['card_token_id'=>$cardToken]:[])]:[]),
                'reason'=>(($this->config['product']??'pro')==='supervision'?'RepoTools Supervisión':'RepoTools PRO').' · prueba','external_reference'=>$row['reference'],'payer_email'=>$this->config['buyer'],
                'auto_recurring'=>['frequency'=>1,'frequency_type'=>'months','transaction_amount'=>(int)$row['amount_cents']/100,'currency_id'=>'ARS'],
                'back_url'=>$this->config['url'].'/pro.php?product='.($this->config['product']??'pro'),'status'=>!$hosted&&($this->config['plan']??'')!==''?'authorized':'pending'
            ],$row['reference']);
        }else{
            // Do not repeat POST after an ambiguous timeout: recover by reference instead.
            $search=$this->request('GET','/preapproval/search?external_reference='.rawurlencode($row['reference']));
            $matches=array_values(array_filter($search['results']??[],fn($r)=>($r['external_reference']??'')===$row['reference']));
            if(count($matches)!==1)throw new CatalogException('Estamos verificando el intento anterior. Volvé a intentar más tarde.',409);
            $remote=$this->request('GET','/preapproval/'.rawurlencode((string)$matches[0]['id']));
        }
        $result=$this->transaction(function()use($row,$remote){$current=$this->lock($row['reference']);
            $remote=$this->request('GET','/preapproval/'.rawurlencode((string)$remote['id']));$this->apply($remote,$current);
            return ['status'=>$remote['status'],'url'=>$remote['init_point']??''];});
        // Commit the verified provider state even when there is no checkout to open.
        if($result['status']==='authorized')return '';
        if($result['status']==='cancelled')throw new CatalogException('Mercado Pago canceló el intento de suscripción. No se activó el plan.',409);
        if($result['status']==='paused')throw new CatalogException('Mercado Pago dejó la suscripción pausada. Revisá su estado antes de continuar.',409);
        if($result['url']==='')throw new CatalogException('Mercado Pago todavía no tiene un enlace para este intento. Comprobá su estado antes de volver a intentar.',503);
        return $result['url'];
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
        $row=$this->query('SELECT reference FROM pro_subscriptions WHERE provider_id=?',[$id])->fetch();
        if(!$row){$remote=$this->request('GET','/preapproval/'.rawurlencode($id));$row=$this->query('SELECT reference FROM pro_subscriptions WHERE reference=?',[(string)($remote['external_reference']??'')])->fetch();if(!$row)return;}
        $this->transaction(function()use($row,$id){$current=$this->lock($row['reference']);$this->apply($this->request('GET','/preapproval/'.rawurlencode($id)),$current);});
    }
    public function reconcile(int $user): void {
        $row=$this->row($user);
        if($row&&!$row['provider_id']&&$row['status']==='creating'){
            $search=$this->request('GET','/preapproval/search?external_reference='.rawurlencode($row['reference']));
            $matches=array_values(array_filter($search['results']??[],fn($remote)=>($remote['external_reference']??'')===$row['reference']));
            if(count($matches)!==1)return;
            $id=(string)($matches[0]['id']??'');if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D',$id))throw new CatalogException('Suscripción no válida.',409);
            $this->subscription($id);$row=$this->row($user);
            if($row['status']==='cancelled')return;
        }
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
        $row=$this->query('SELECT reference FROM pro_subscriptions WHERE provider_id=?',[(string)($invoice['preapproval_id']??'')])->fetch();
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
            $this->query('INSERT INTO pro_payments(provider_id,reference,status,period_end,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(provider_id) DO UPDATE SET status=excluded.status,period_end=excluded.period_end,updated_at=excluded.updated_at',[$paymentId,$current['reference'],$status,$end,time()]);
            $until=(int)$this->query("SELECT COALESCE(MAX(period_end),0) FROM pro_payments WHERE reference=? AND status='approved'",[$current['reference']])->fetchColumn();
            $this->query('UPDATE pro_subscriptions SET paid_until=?,updated_at=? WHERE reference=?',[$until,time(),$current['reference']]);
            if(($this->config['product']??'pro')==='supervision'&&$until>time())$this->query("INSERT INTO supervisor_teams(owner_id,name,billing_required,name_pending) VALUES(?,'Mi equipo',1,1) ON CONFLICT(owner_id) DO NOTHING",[$current['user_id']]);
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
