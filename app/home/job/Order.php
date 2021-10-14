<?php
namespace app\home\job;


use app\common\model\Order as Orders;
use app\common\model\Card;
use app\common\model\User;
use think\facade\Db;
use GuzzleHttp\Client;


class Order{
    protected $order;
    protected $card;
    protected $key;



    public function getOrder($orderid){
        $order=Orders::where(['id|orderno'=>$orderid])->find();
        if($order){
            $order['status']=$order->getData('state');
            $user=User::where(['id'=>$order['uid']])->find();
            $this->key=$user['apikey'];
            $this->card=new Card($user['apides']);
            $this->order=$order;
            return $this->send($user['shopid']);
        }else{
            return true;
        }
    }

    private function send($shopid){
        $map['customerId']=$shopid;
        $map['orderId']=$this->order['tmporder'];
        $map['systemOrderId']=$this->order['orderno'];
        $map['status']=$this->order['status'];
        $map['cardNumber']=$this->order['card_no'];
        $map['cardPassword']=$this->order['card_key'];
        $map['amount']=$this->order['money'];
        $map['successAmount']=$this->order['settle_amt'];
        $map['actualAmount']=$this->order['amount'];
        $map['successTime']=time();
        $map['message']=$this->order['remarks'];
        $map['extendParams']=$this->order['custom'];
        $map['realPrice']=$this->order['settle_amt'];
        $sign=md5Verify($map,$this->key);
        $map['sign']=$sign;;
        if(!empty($this->order['notify'])){
            $ok=$this->sendPostNotify($this->order['notify'],$map);
            (new Orders)->where(['id'=>$this->order['id']])->update(['tongzhi'=>$ok]);
            if($ok!='SUCCESS'){
                return false;
            }else{
                return true;
            }
        }else{
            (new Orders)->where(['id'=>$this->order['id']])->update(['tongzhi'=>'回掉路径为空']);
            return true;
        }
    }

    public function sendPostNotify($url,$data){
        try{
            $response=(new Client())->request("post",$url,['form_params'=>$data]);
            $msg=$response->getBody()->getContents();
            return empty($msg)?"未知返回":$msg;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }





}
