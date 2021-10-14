<?php
namespace app\merchants\validate;

use app\common\model\Payment;
use think\Validate;

class Recharge extends Validate
{
	
    protected $rule = [
        'bankid' => 'require|isName:bankid|token',
        'moneyoff' => 'require|float|gt:1',
        'rechpic' => 'require|length:20,100',
    ];

    protected $message = [
		'bankid.require' => ['code'=>'#anjian','msg'=>'非法参数2'],
		'bankid.isName' => ['code'=>'#anjian','msg'=>'参数非法'],
        'bankid.token' => ['code'=>'#anjian','msg'=>'表单检验失败请刷新'],
		
		'moneyoff.require' => ['code'=>'#moneyoff','msg'=>'请输入金额'],
		'moneyoff.float' => ['code'=>'#moneyoff','msg'=>'请输入正确的金额'],
		'moneyoff.gt' => ['code'=>'#moneyoff','msg'=>'加款金额需要大于1元'],

		'rechpic.require'=>['code'=>'#preview_0','msg'=>'请上传打款截图'],
		'rechpic.length'=>['code'=>'#preview_0','msg'=>'参数非法']
		
    ];
	

	
	protected function isName($value,$rule,$data){
	    $isok=Payment::where(['id'=>$value,'state'=>1])->find();
		if($isok){
		    if($isok['daymoney']-$isok['remamoney']>=$data['moneyoff'] || $isok['daymoney']==0){
		        return true;
            }else{
		        return ['code'=>'#moneyoff','msg'=>'超出限额'];
            }
		}else{
			return false;
		}
        
    }

	
}
