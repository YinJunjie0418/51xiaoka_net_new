<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\api\lib\Recharge;
use app\api\model\ApiBase;
use app\common\model\Order;
use app\common\model\User;
use app\common\model\CardOperator;
use think\facade\Cache;
use app\common\model\Newapi;

class Index extends ApiBase
{

    public function index()
    {
        try{
            validate(\app\api\validate\Apicheck::class)->scene("index")->check(self::$param);
        }catch (\Exception $e){
            return self::show(-1,$e->getMessage());
        }
        $ok=$this->payOrder();
		if($ok['code']==1){
            Cache::inc('apiorder');
            \think\facade\Queue::push("app\home\job\Jobone@sendCard", $ok['data'],'apiJobQueue');
            return self::show(1,'接收成功');
		}else{
            return self::show(999,'系统繁忙');
        }
    }


	public function blindSearch(){
        try{
            $data=self::$param;
            validate(\app\api\validate\Apicheck::class)->scene("blindSearch")->check($data);
        }catch (\Exception $e){
            return self::show(-1,$e->getMessage());
        }
            $uid=User::where(['shopid'=>self::$param['customerId']])->value('id');
			$res=Order::where(['tmporder'=>$data['orderId'],'uid'=>$uid])->find();
			if($res){
				    $map['customerId']=self::$param['customerId'];
					$map['orderId']=$res['tmporder'];
					$map['systemOrderId']=$res['orderno'];
					$map['status']=$res->getData('state');
				switch($res->getData('state')){
					case 0:
					  $code=1;
					  $map['message']='等待验证';
					break;
					case 1:
					  $code=1;
					  $map['message']='正在处理';
					break;
					case 2:
					  $code=1;
					  $map['message']='处理成功';
					  $map['amount']=$res['money'];
					  $map['successAmount']=$res['settle_amt'];
					  $map['actualAmount']=$res['amount'];
					  $map['successTime']=date("Y/m/d H:i:s");
					  $map['message']=$res['remarks'];
					  $map['extendParams']=$res['custom'];
					  $map['realPrice']=$res['settle_amt'];
					break;
					case 3:
					  $code=1;
					  $map['message']=$res['remarks'];
					break;
                    default:
                        $code=1;
                        $map['status']=1;
                        $map['message']='正在处理';
				}
                return self::show($code,'查询成功',$map);
			}else{
                return self::show(-1,'订单不存在');
			}

	}
	
	public function miss(){
		echo "fail";
	}


	public function webback(){
		$da=input();
		trace($da,'log');
		if(isset($da['action'])){
		    $api=new Newapi($da['action']);
            $code=$api->notify($da);
		    $class=CardOperator::where(['id'=>$da['action']])->value('class');
		    switch($class){
                case 'LianBank':
                    return json(['ret_code'=>"0000","ret_msg"=>"交易成功"]);
                    break;
                case 'SpeedCard':
                    echo "Y";
                    break;
                
                default:
                    echo "success";
            }
			exit;
		}else{
			exit('fail');
		}
	}

	
	 public function callback(){
	     $da=input();
	     $str="success";
	     trace($da,'log');
	     try{
            if(isset($da['action'])){
                $res=CardOperator::find($da['action']);
                if($res){
                    $className="\api\\".$res['class'];
                    if(class_exists($className)){
                        $newClass=new $className($da);
                        $result=$newClass->tonotify('1');
                        if(isset($da['voucher'])){
                            $result['voucher']=$da['voucher'];
                        }elseif(isset($da['thirdOrderNo'])){
                            $result['voucher']=$da['thirdOrderNo'];
                        }elseif(isset($da['ticketNo'])){
                            $result['voucher']=$da['ticketNo'];
                        }elseif(isset($da['remark1'])){
                            $result['voucher']=$da['remark1'];
                        }else{
                            $result['voucher']="";
                        }

                        $result['ext']=isset($da['memo'])?$da['memo']:'';
                        $msg=(new Recharge)->handleCash($result);
                        $str=$msg?"success":"fail";
                    }else{
                        return self::show(-1,'接口文件错误');
                    }
                }
            }
            switch($da['action']){
                case 20:
                    return json(["code"=>"success","message"=>"成功"]);
                    break;
                case 26:
                    echo "ok";
                    break;
                default:
                    echo $str;
            }
            exit;
	     }catch (\Exception $e){
	        trace("callback:".$e->getMessage(),'error');
            echo "fail";
        }
    }
}
