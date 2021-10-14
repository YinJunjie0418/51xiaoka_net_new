<?php
namespace app\api\lib;

use app\common\model\SellList;
use app\common\model\CashOrder as CASH;

class Cashorder {
    protected $data;
    protected $api;
    protected $newCalss;
    protected $state=0;

    public function __construct($data) {
        $this->data=$data;
        $result=SellList::with('operType')->where(['geway'=>$data['itemId']])->find();
        $this->state=$result['apistate'];
        $this->data['daitype']=$result['type'];
        $className="\api\\".$result['class'];
        if(class_exists($className)){
            $this->api=$className;
        }
    }

    public function firestCash(){
        try{
            if($this->isNew()){
                $res=$this->newCalss->sendData(1);
                switch($res['code']){
                    case 1:
                        return true;
                        break;
                    case 3:
                        return $res;
                        break;
                    default:
                        $tdata['orderno']=$this->data['sysorder'];
                        $tdata['state']=3;
                        $tdata['msg']=$res['msg'];
                        (new CASH)->updateOrder($tdata);
                        return $res;
                }
            }else{
                return false;
            }
        }catch (\Exception $e){
             $tdata['orderno']=$this->data['sysorder'];
             $tdata['state']=3;
             $tdata['msg']=$e->getMessage();
             (new CASH)->updateOrder($tdata);
             return false;
        }
    }

    public function isNew(){
        $newCalss=new \ReflectionClass($this->api);
        if($newCalss->isInstantiable() && $this->state){
            $this->newCalss=new $this->api($this->data);
            return true;
        }else{
             $tdata['orderno']=$this->data['sysorder'];
             $tdata['state']=3;
             $tdata['msg']=$this->state?'接口实例化失败':"接口关闭";
             (new CASH)->updateOrder($tdata);
            return false;
        }
    }

}
