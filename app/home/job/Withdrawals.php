<?php
namespace app\home\job;

use app\common\model\Sysconfig;
use think\facade\Config;
use app\common\model\Withdraw;
use app\common\alipay\Cashwith;
use think\facade\Db;

class Withdrawals{
	protected $config = [];
	protected $order;
	protected $wxpz;
	
	public function __construct()
    {
		$this->config=Config::load('setting/cash','cash');
	}
	
	public function getOrder($orderid){
	    try {
            $order = Withdraw::with('preone')->where(['order' => $orderid, 'status' => 0])->find();
            $user = Db::name("user")->where(['id' => $order['uid']])->find();
            if ($user['money'] + getsale($user['yuti'], $user['id']) < 0) {
                (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1, 'content' => '[余额异常]']);
            } else {
                if ($order) {
                    switch ($order['bankid']) {
                        case -1:
                            if ($this->config['alitype'] == 1) {
                                $ok = (new Cashwith)->withdr($order['order'], $order['accounts'], $order['money'] - $order['price'], "ASSR", $order['user']);
                                if ($ok['code'] == 1) {
                                    (new Withdraw)->where(['id' => $order['id']])->update(['status' => 2,'update_time'=>time(), 'content' => '[商户结算][' . $ok['msg'] . ']']);
                                } else {
                                    (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1,'update_time'=>time(), 'content' => '[转账失败][' . $ok['msg'] . ']']);
                                }
                            } else {
                                (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1,'update_time'=>time(), 'content' => '[自动通道关闭]']);
                                return true;
                            }
                            break;
                        case -2:
                            if ($this->config['wxtype'] == 1) {
                                $this->wxpz = Config::load('setting/wxpay', 'wxpay');
                                return $this->chargg($order, $order['price']);
                            } else {
                                (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1,'update_time'=>time(), 'content' => '[自动通道关闭]']);
                                return true;
                            }
                            break;
                        default:
                            if (!isset($this->config['banktype'])) {
                                (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1, 'update_time'=>time(),'content' => '[自动通道未配置]']);
                            } else {
                                if ($this->config['banktype'] == 1) {
                                    $className = "\api\\" . $this->config['txclass'];
                                    if (class_exists($className)) {
                                        $pp=Sysconfig::where(['name'=>'domain'])->value("value");
                                        $idty = Db::name('card_operator')->where(['class' => $this->config['txclass']])->value('id');
                                        $url = $pp. "/cardback/action/" . $idty . ".html";
                                        $data = ['uid' => $order['uid'], 'order' => $order['order'], 'money' => $order['money'] - $order['price'], 'accounts' => $order['accounts'], 'user' => $order['user'], 'cid' => $order['cid']];
                                        $BANK = new $className($data);
                                        $res = $BANK->sendData($url);
                                        if (isset($res['code'])) (new Withdraw)->where(['id' => $order['id']])->update(['status' => $res['code'], 'update_time'=>time(),'content' => "[{$res['msg']}]"]);
                                    } else {
                                        (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1,'update_time'=>time(), 'content' => '[通道文件错误]']);
                                    }
                                } else {
                                    (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1, 'update_time'=>time(),'content' => '[自动通道关闭]']);
                                    return true;
                                }
                            }
                    }
                } else {
                    return true;
                }
            }
        }catch (\Exception $e) {
            (new Withdraw)->where(['id' => $order['id']])->update(['status' => 1,'update_time'=>time(), 'content' => $e->getMessage()]);
            trace($e->getMessage(),'error');
        }
	}
	
	private function chargg($uu,$price){
		try{
			$post_data = array (
				'mch_appid' => $this->wxpz['appid'], //pid
				'mchid' => $this->wxpz['mch_id'], //商户号
				'nonce_str'=>generate_password(9), 
				'partner_trade_no'=>$uu['order'], 
				'openid' =>$uu['account'], 
				'check_name'=>'NO_CHECK',
				'amount'=>($uu['money']-$price)*100,
				'desc'=>"MYCC GO PAY",
				'spbill_create_ip'=>isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:"127.0.0.1"
			);
			$sign = md5Verify($post_data,"","&key={$this->wxpz['key']}");
			$post_data['sign'] = strtoupper($sign);
			$url ='https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
			$xml=arraytoxml($post_data);
			$res=vpost($url,$xml,true,$this->wxpz);
			$ret=xmltoarray($res);
			if($ret['return_code']=="SUCCESS"){
				if($ret['result_code']=="SUCCESS"){
					(new Withdraw)->where(['id'=>$uu['id']])->update(['status'=>2,'content'=>'[商户结算][微信打款]']);
				}else{
					$msg=$this->chaxun($uu['order']);
					if($msg===true){
						(new Withdraw)->where(['id'=>$uu['id']])->update(['status'=>2,'content'=>'[商户结算][微信打款]']);
					}else{
						(new Withdraw)->where(['id'=>$uu['id']])->update(['status'=>1,'content'=>'[转账失败]['.$msg.']']);
					}
				}
				return true;
			}else{
				(new Withdraw)->where(['id'=>$uu['id']])->update(['status'=>1,'content'=>'[转账失败]['.$ret['return_msg'].']']);
			}
		}catch (\Exception $e) {
			(new Withdraw)->where(['id'=>$uu['id']])->update(['status'=>1,'content'=>'[转账失败]['.$e->getMessage().']']);
		}
		return true;
	}
	
	private function chaxun($order){
		$url="https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo";
		$arr['nonce_str']=generate_password(9);
		$arr['partner_trade_no']=$order;
		$arr['mch_id']=$this->wxpz['appid'];
		$arr['appid']=$this->wxpz['mch_id'];
		$sign = md5Verify($arr,"","&key={$this->wxpz['key']}");
		$arr['sign'] = strtoupper($sign);
		$xml=arraytoxml($arr);
		$res=vpost($url,$xml,true,$arr);
		$ret=xmltoarray($res);
		if($ret['return_code']=="SUCCESS"){
			if($ret['result_code']=="SUCCESS"){
				if($ret['status']=="SUCCESS"){
					return true;
				}else{
					return $ret['reason'];
				}
			}else{
				return $ret['err_code'];
			}
		}else{
			return $ret['return_msg'];
		}
	}
    
   
}
