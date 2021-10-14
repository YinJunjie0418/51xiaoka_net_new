<?php
namespace app\api\lib;



class ActionQueue{

    public function cashBack($map){
        $ss=cache('logback')?cache('logback'):0;
        if($ss%4==0){
            \think\facade\Queue::push("app\home\job\Jobone@CashBack", $map,'CashBackaQueue');
        }elseif($ss%3==0){
            \think\facade\Queue::push("app\home\job\Jobone@CashBack", $map,'CashBackaQueueb');
        }elseif($ss%2==0){
            \think\facade\Queue::push("app\home\job\Jobone@CashBack", $map,'CashBackaQueuec');
        }else{
            \think\facade\Queue::push("app\home\job\Jobone@CashBack", $map,'CashBackaQueued');
        }
        cache('logback',$ss+1);
        if($ss>10000){
            cache('logback',0);
        }
        return true;
    }

    public function backorder($id){
        return \think\facade\Queue::push("app\home\job\Jobone@sendPost",['id'=>$id],'sendPostJobQueue');
    }


    public function cashSend($map){
        $ss=cache('casha')?cache('casha'):0;
        if($ss%4==0){
           $isok=\think\facade\Queue::push("app\home\job\Jobone@CashOrder", $map,'CashOrderQueue');
        }elseif($ss%3==0){
            $isok=\think\facade\Queue::push("app\home\job\Jobone@CashOrder", $map,'CashOrderQueueb');
        }elseif($ss%2==0){
            $isok=\think\facade\Queue::push("app\home\job\Jobone@CashOrder", $map,'CashOrderQueuec');
        }else{
            $isok=\think\facade\Queue::push("app\home\job\Jobone@CashOrder", $map,'CashOrderQueued');
        }
        cache('casha',$ss+1);
        if($ss>10000){
            cache('casha',0);
        }
        if($isok)return true;
        return false;
    }

    public function  cashorderback($data){
        //return \think\facade\Queue::push("app\home\job\Jobone@huidiao", $data,'huidiaoQueue');
        
        $ss=cache('cashas')?cache('cashas'):0;
        if($ss%4==0){
           $isok=\think\facade\Queue::push("app\home\job\Jobone@huidiao", $data,'huidiaoQueue');
        }elseif($ss%3==0){
            $isok=\think\facade\Queue::push("app\home\job\Jobone@huidiao", $data,'huidiaoQueueb');
        }elseif($ss%2==0){
            $isok=\think\facade\Queue::push("app\home\job\Jobone@huidiao", $data,'huidiaoQueuec');
        }else{
            $isok=\think\facade\Queue::push("app\home\job\Jobone@huidiao", $data,'huidiaoQueued');
        }
        cache('cashas',$ss+1);
        if($ss>10000){
            cache('cashas',0);
        }
        if($isok)return true;
        return false;
    }


}
