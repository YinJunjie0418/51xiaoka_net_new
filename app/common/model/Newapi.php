<?php
namespace app\common\model;
use app\common\library\MongData;
use think\facade\Log;
use think\Model;
use think\facade\Db;

class Newapi extends Model
{
    Protected $autoCheckFields = false;
	private $superNew;
	private $typea='';
	
	public function __construct($type) {
	   $this->typea=$type;
	   $class=CardOperator::where(['id'=>$type])->value('class');
	   $className="\api\\".$class;
	   if(class_exists($className)){
		   $this->superNew=$className;
	   }else{
		   $this->superNew='';
	   }
	   $this->domain=Db::name("sysconfig")->where(['name'=>'domain'])->value("value");
    }
	
	public function blindSearch($order){//查询功能
		if(method_exists($this->superNew,'blindSearch')){
			$this->superNew=new $this->superNew($order);
			$res=$this->superNew->blindSearch();
			if($res['code']==1 && isset($res['orderno'])){
				successful($res['orderno'],2,$res['amount'],$res['settle'],$res['mianzi'],$res['msg']);
			}elseif($res['code']==-1 && isset($res['orderno'])){
				successful($order['orderno'],3,0,0,0,$res['msg']);
			}
			return $res;
		}else{
			return ['code'=>-1,'msg'=>'接口文件错误'];
		}
	}
	
	public function notify($data){//回掉功能
		if(method_exists($this->superNew,'tonotify')){
			$this->superNew=new $this->superNew($data);
			$res=$this->superNew->tonotify('order');
			if(!isset($res['orderno']))return ['code'=>-1,'msg'=>"订单号缺少"];
			if($res['code']==1){//amount结算金额settle结算面值出现卡损和真实面值不一致mianzi真实面值 不管是不是出现卡损都不变
                 successful($res['orderno'],2,$res['amount'],$res['settle'],$res['mianzi'],$res['msg'],isset($res['mcode'])?$res['mcode']:'');
			}elseif($res['code']==-1){
				 successful($res['orderno'],3,0,0,0,$res['msg'],isset($res['mcode'])?$res['mcode']:'');
			}
            $id=Order::field('id,tmporder,money,xitmoney,amount')->where(['orderno'=>$res['orderno']])->find();
            if($id['tmporder']!=0){
                (new \app\home\job\Order())->getOrder($id);
            }
			return ['code'=>1,'msg'=>'success'];
		}else{
			return ['code'=>-1,'msg'=>'接口文件错误'];
		}
	}
	
	public function cashSearch($order){//查询功能
		if(method_exists($this->superNew,'blindSearch')){
			$this->superNew=new $this->superNew($order);
			$res=$this->superNew->blindSearch();
			return $res;
		}else{
			return ['code'=>-1,'msg'=>'接口文件错误'];
		}
	}

	public function cancelOrder($data){
        if(method_exists($this->superNew,'revokeData')){
            $this->superNew=new $this->superNew($data);
            $res=$this->superNew->revokeData();
            return $res;
        }else{
            return ['code'=>-1,'msg'=>'该接口没有取消功能'];
        }
    }
	
	
	public function sendData($data){//提交功能
        try {
            if(isset($data['batchno']) && cache($data['batchno'])){
                return true;
            }
            $data['card_no'] = Order::getCardno($data['card_no']);
            $data['card_key'] = Order::getCardno($data['card_key']);
            if ($this->typea == 0) {
                $xiao = $this->InventoryHedging($data);
                return true;
            }
            if (method_exists($this->superNew, 'sendData')) {
                $url = $this->domain . "/cardback/action/" . $this->typea . ".html";
                $ca = CardList::where(['type' => $data['class']])->find();
                if ($ca['is_auto']) {
                    $this->superNew = new $this->superNew($data);
                    $res = $this->superNew->sendData($url);
                } else {
                    $res = ['code' => 1, 'msg' => '手动处理'];
                }
                trace($res);
                Order::where(['orderno' => $data['orderno']])->update(['state' => 1]);
                if ($res['code'] == 1) {
                    successful($data['orderno'], 1, 0, 0, 0, $res['msg']);
                } else {
                    successful($data['orderno'], 3, 0, 0, 0, $res['msg']);
                }
                return $res;
            } else {
                if (isset($data['orderno'])) Order::where(['orderno' => $data['orderno']])->update(['state' => 1, 'remarks' => '接口文件错误']);
                return ['code' => -1, 'msg' => '接口文件错误'];
            }
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            echo $e->getMessage();
        }
	}
	
	public function InventoryHedging($data){
        $sellid=CardList::where(['type'=>$data['class']])->value('id');
        $bind=SellListRate::field('listid')->where(['bindid'=>$sellid])->select();
        $binds=[];
        foreach($bind as $item){
            $binds[]=$item['listid'];
        }
        $name=SellList::where([['id','in',$binds],['mianzhi','=',$data['money']]])->value('geway');
        $mcash=Db::name('cashdata')->where(['cm'=>$name.$data['money']])->order('overtime asc')->find();
	    if($mcash){
	        Db::name('cashdata')->where(['id'=>$mcash['id']])->delete();
            Db::startTrans();
           try {
               $oka=Db::name('cash_order')->where(['id' => $mcash['csid']])->update(['state' => 1]);
               $okb=Db::name('order')->where(['orderno' => $data['orderno']])->update(['state' => 1]);
               $arr['orderno'] = $data['orderno'];
               $arr['cardno'] = $data['card_no'];
               $arr['cardkey'] = $data['card_key'];
               $arr['uuid'] = $data['uid'];
               $arr['toorder'] = $mcash['orderno'];
               $okc=\think\facade\Queue::push("app\home\job\Jobone@tiao", $arr, 'tiaoQueue');
               if($oka && $okb && $okc) {
                   $data['tmporder']=$mcash['orderno'];
                   $ok=(new MongData)->addOrderData($data,$name);
                   Log::info("匹配话单成功".json_encode($mcash));
                   Db::commit();
                   cache($data['orderno'],null);
                   return true;
               }else{
                   throw new \think\exception\HttpException(500, "匹配成功，事务失败");
               }
           }catch (\Exception $e) {
               $ok=(new MongData)->addOrderData($data,$name);
               if($ok){
                   Log::info("匹配话单成功,事务失败".json_encode($mcash));
               }else{
                   Db::name('order')->where(['orderno' => $data['orderno']])->update(['state' =>9]);
               }
               Log::info("匹配话单事务失败".json_encode($mcash)."错误信息：".$e->getMessage());
               Db::rollback();
               return false;
           }
        }else{
	        if(isset($data['tmporder']))unset($data['tmporder']);
            $ok=(new MongData)->addOrderData($data,$name);
            if($ok){
                //Log::info("匹配话单失败保存数据".json_encode($mcash));
                return true;
            }else{
                Db::name('order')->where(['orderno' => $data['orderno']])->update(['state' =>9]);
                return true;
            }
        }
    }


}