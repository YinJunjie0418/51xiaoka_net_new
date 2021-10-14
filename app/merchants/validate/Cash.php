<?php
namespace app\merchants\validate;

use think\Validate;
use app\common\model\User;
use think\facade\Config;
use app\common\model\Userbank;

class Cash extends Validate
{
	
    protected $rule = [
	    'type'=>'require|status:type|token',
        'bank_id' => 'require|isName:bank_id',
        'moneyoff' => 'require|float|gt:1|betw:moneyoff|isuser:moneyoff',
        'codeno' => 'require|ispass:paypass',
    ];

    protected $message = [
        'type.require' => ['code'=>'#anjian','msg'=>'非法参数1'],
		'type.status' => ['code'=>'#anjian','msg'=>'当前加款通道状态异常'],
		'type.token' => ['code'=>'#anjian','msg'=>'超时操作请刷新'],
		
		'bank_id.require' => ['code'=>'#anjian','msg'=>'请选择加款银行'],
		'bank_id.isName' => ['code'=>'#username','msg'=>'参数非法'],
		
		'moneyoff.require' => ['code'=>'#paypass','msg'=>'请输入密码'],
		'moneyoff.float' => ['code'=>'#moneyoff','msg'=>'请输入正确的金额'],
		'moneyoff.gt' => ['code'=>'#moneyoff','msg'=>'提现金额需要大于1元'],

		
    ];
	
	protected function ispass($value){
        $mobile=User::where(['id'=>session('user_auth.user_id')])->value('mobile');
        $code=session("?cash".$mobile);
        if($code) {
            $res = session("cash" . $mobile);
            if ($res['time'] < time()) {
                return json(['code' => "#codeno", 'content' => "验证码过期", 'token' => token()]);
            }
            if ($res['code'] == $value) {
                return true;
            }
        }else{
            return false;
        }
        
    }
	
	protected function isName($value){
		if(Userbank::where(['id'=>$value,'uid'=>session('user_auth.user_id')])->find()){
			return true;
		}else{
			return false;
		}
        
    }
	
	protected function betw($value,$rule,$data){
		$res=Config::load('setting/cash','cash');
		$min=0;$max=0;
		switch($data['type']){
			case 'bank':
			 $min=$res['bank_min'];
			 $max=$res['bank_max'];
			break;
			case 'alipay':
			  $min=$res['ali_min'];
			  $max=$res['ali_max'];
			break;
			case 'weixin':
			  $min=$res['wx_min'];
			  $max=$res['wx_max'];
			break;
		}
		if($value<$min){
			return ['code'=>'#moneyoff','msg'=>"提现金额不能小于{$min},单笔最少提现{$min}"];
		}elseif($value>$max){
		    	return ['code'=>'#moneyoff','msg'=>"提现金额不能大于{$max},单笔最多提现{$max}"];
		  }else{
			return true;
		}
        
    }
	
	protected function isuser($value){
	    $userModel=new User();
	    $userModel->startTrans();
	    try{
            $user=User::where(['id'=>session('user_auth.user_id')])->lock(true)->find();
            if($user){
                if($value>$user['money']){
                    $userModel->commit();
                    return ['code'=>'#moneyoff','msg'=>'余额不足'];
                }else{
                    $userModel->commit();
                    return true;
                }
            }else{
                $userModel->commit();
                return false;
            }
        }catch (\Exception $e){
	        $userModel->rollback();
	        return false;
        }
    }
	
	protected function status($value){
		$res=Config::load('setting/cash','cash');
		$op=0;
		switch($value){
			case 'bank':
			 $op=$res['banktype'];
			break;
			case 'alipay':
			  $op=$res['alitype'];
			break;
			case 'wxtype':
			  $op=$res['wxtype'];
			break;
		}
		if($op){
			return true;
		}else{
			return false;
		}
        
    }
	
	
	
}
