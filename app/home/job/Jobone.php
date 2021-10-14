<?php
namespace app\home\job;

use app\common\library\MongData;
use app\common\model\CashOrder;
use app\common\model\CashOrder as CASH;
use app\common\model\Emaillog;
use app\common\model\Neworder;
use app\common\model\User;
use think\facade\Cache;
use think\facade\Console;
use think\queue\Job;
use app\common\model\UserLog;
use app\common\model\Newapi;
use app\api\lib\Recharge;

class Jobone{
    public function sendCard(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        $api=new Newapi(isset($data['type'])?$data['type']:0);
        $ok=$api->sendData($data);
        $job->delete();
    }

    public function sendCach(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        $USER=new User();
        $USER->startTrans();
        try{
            $bres=(new User)->field('money,xin')->where(['id'=>$data['uid']])->lock(true)->find();
            if($bres){
                $occupymoney=$this->occupymoney($data['uid']);
                $umoney=$bres['money']+$bres['xin']-$occupymoney;
                trace($occupymoney."??".$umoney."??".$data['price']);
                if($umoney<$data['price']){
                    (new CashOrder)::where(['orderno'=>$data['orderno']])->update(['state'=>3,'remarks'=>'余额不足,请查看占用金额']);
                    $USER->commit();
                    $job->delete();
                    return true;
                }else{
                    (new MongData)->setCache($data['uid'],$data['price']);
                    $USER->commit();
                }
            }else{
                trace("查找用户失败1：".$data['orderno'],'log');
                $USER->rollback();
                $job->delete();
                return false;
            }
        }catch (\Exception $e){
            $USER->rollback();
            trace($e->getMessage(),'error');
            $job->delete();
            return false;
        }
        $data['state'] = 1;
        $data['itemId']=$data['type'];
        $data['sysorder']=$data['orderno'];
        $data['checkItemFacePrice']=$data['money'];
        $data['customerId']=$data['uid'];
        $dai = new \app\api\lib\Cashorder($data);
        $ok = $dai->firestCash();
        if($ok===true){
            (new CashOrder)::where(['orderno'=>$data['orderno']])->update(['state'=>1]);
        }
        trace($ok);
        $job->delete();
    }

    private function occupymoney($uid){
        $isok=Cache::has($uid."money");
        if($isok){
            return Cache::get($uid."money");
        }else{
            $money=CASH::where('state','in','0,1,4,6')->where('uid',$uid)->sum('price');
            Cache::set($uid."money",$money);
            return $money;
        }
    }

    public function sendPost(Job $job, $data){//回掉发送队列
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        echo "回掉订单12".$data['id'];
        $api=new Order();
        $ok=$api->getOrder($data['id']);
        if($ok || $job->attempts()>4){
            $job->delete();
        }else{
            $num=$job->attempts();
            $num=$num<=0?1:$num;
            $job->release($num*10);//任务失败3秒后重试
        }
    }

    public function tixian(Job $job, $data){//提现任务
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        echo "执行任务".$data['orderid'];
        $with=new Withdrawals();
        $ok=$with->getOrder(isset($data['orderid'])?$data['orderid']:0);
        $job->delete();
    }

    public function getAdress(Job $job, $data){//登陆ip获取任务
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        echo "执行任务".$data['id'];
        $ok=$this->ProcessIp(isset($data['id'])?$data['id']:0,$data);
        if($ok){
            $job->delete();
        }else{
            $job->release(3);//任务失败3秒后重试
        }
    }

    public function ProcessIp($id,$data=[]){
        $res=UserLog::where(['id'=>$id])->where('area','null')->find();
        if($res){
            $res=$res->toArray();
            $adress=UserLog::where(['ip'=>$res['ip']])->whereNotNull('area')->value('area');
            if(empty($adress)){
                $adress=sendToAddress($res['ip']);
            }
            UserLog::where(['id'=>$id])->update(['area'=>$adress]);
        }
        if($id=="batchno"){
            Console::Call('restbatchno');
        }
        if($id=="addprocode"){
            Console::call('addbank', [$data['procode']]);
        }
        if($id=='cancelOrder'){
            $api=new Newapi(isset($data['type'])?$data['type']:0);
            $ok=$api->cancelOrder($data);
        }
        return true;
    }
    
    public function pCashOrder(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        try{
            (new Recharge)->psendData($data);
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
        }
        $job->delete();
    }

    public function addCashOrder(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        $ok=(new CASH)->addOrder($data);
        if($ok){
            $job->delete();
        }else{
            $job->release(3);//任务失败3秒后重试
        }
    }

    public function CashOrder(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        try{
            (new Recharge)->sendData($data);
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
        }
        $job->delete();
    }

    public function tiao(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        (new Recharge)->Reissued($data['toorder'],$data);
        $job->delete();
    }

    public function editCashOrder(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        $ok=(new Recharge)->Reissued($data['tmporder']);
        $job->delete();

    }

    public function sendEmail(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        if($data['email']){
            $ok=sendEmail($data['email'],0,$data['msg'],'','all',$data['title']);
            if($ok['code']==1){
                Emaillog::where(['id'=>$data['logid']])->update(['sendok'=>['inc',1],'sum'=>['inc',1]]);
            }else{
                $err=Emaillog::where(['id'=>$data['logid']])->find();
                $str=$err['errmsg']?$err['errmsg']:[];
                array_push($str,['id'=>$data['id'],'msg'=>$ok['err']]);
                $err->errmsg=$str;
                $err->sum=$err['sum']+1;
                $err->save();
            }
        }else{
            $err=Emaillog::where(['id'=>$data['logid']])->find();
            $str=is_array($err['errmsg'])?$err['errmsg']:[];
            array_push($str,['id'=>$data['id'],'msg'=>'邮箱为空']);
            $err->errmsg=$str;
            $err->sum=$err['sum']+1;
            $err->save();
        }
        $job->delete();
    }

    public function backque(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        try{
            $api=new Newapi($data['action']);
            $code=$api->notify($data);
            if(isset($code['code'])) $job->delete();
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        $job->delete();
    }

    public function CashBack(Job $job, $data){
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        try{
            if($data['type']==1){
                $price=(new CASH)->successOrder($data['orderno'],$data['data']);
                echo $price;
                if(false!==$price){
                    $iscash=User::where(['id'=>$data['uid']])->value('iscash');
                    if($iscash){
                        successful($data['cardorder'],2,$price,$data['money'],$data['money'],'实际金额大于申明金额，实际金额['.$data['moneyb'] .']');
                    }else{
                        successful($data['cardorder'],9,$price,$data['moneyb'],$data['money'],'实际金额大于申明金额，实际金额['.$data['moneyb'].']');
                    }
                }
            }else{
                if((new CASH)->successOrder($data['orderno'],$data['data'])){
                   successful($data['cardorder'],2,$data['money'],$data['money'],$data['money'],'销卡成功');
                }
            }
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        $job->delete();
    }

     //独立回掉进程//
    public function huidiao(Job $job, $data){//回掉发送队列
        if(empty($data) || !is_array($data)){
            $job->delete();
        }
        $ok=(new CASH)->sendPostNotify($data['url'],$data['data'],$data['key']);
        trace($ok);
        if($ok=="success" || $ok=="SUCCESS" || $job->attempts()>4){
            (new CASH)->where(['orderno'=>$data['data']['orderno']])->update(['notifymsg'=>$ok]);
            $job->delete();
        }else{
            $num=$job->attempts();
            $num=$num<=0?1:$num;
            $job->release($num*10);//任务失败3秒后重试
        }

    }

}
