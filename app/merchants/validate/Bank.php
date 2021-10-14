<?php
namespace app\merchants\validate;

use think\Validate;

use app\common\model\Banks;


class Bank extends Validate
{
	protected $regex = ['alipay' => '/^[A-Za-z0-9@._~\w-]{5,50}$/','bank' => '/^[0-9]{12,20}$/'];
	
    protected $rule = [
        'accounts' => 'require|regex:alipay|unique:userbank|token',
        'verifyacc' => 'require|confirm:accounts',
		'tobank'=>'require|isok:tobank',
		'id'=>'require'
        
    ];

    protected $message = [
        'accounts.require' => ['code'=>'#accounts','msg'=>'请输入账号'],
		'accounts.unique' => ['code'=>'#accounts','msg'=>'该账号已被绑定，请更换账号'],
		'accounts.regex' => ['code'=>'#accounts','msg'=>'支付宝账户格式错误'],
		'accounts.token' => ['code'=>'#anjian','msg'=>'超时操作请刷新'],
		'verifyacc.require' => ['code'=>'#verifyacc','msg'=>'请再次输入账号'],
		'verifyacc.confirm'=>['code'=>'#verifyacc','msg'=>'两次输入不一致'],
		'id.require' => ['code'=>'#anjian','msg'=>'非法参数'],
		'tobank.require'=>['code'=>'#tobank','msg'=>'非法参数']
    ];
	
	public function sceneAddali(){
		return $this->only(['accounts','verifyacc']);
	}
	
	public function sceneUpali(){
		return $this->only(['accounts','id']);
	}
	
	public function sceneAddbank(){
		return $this->only(['accounts','verifyacc','tobank'])
		       ->remove('account', 'regex')
		       ->append('account', 'regex:bank');
	}
	public function sceneUpbank(){
		return $this->only(['accounts','id','tobank'])
		       ->remove('accounts', 'regex')
		       ->append('accounts', 'regex:bank');
	}
	
	protected function isok($value){
		$user=Banks::where(['id'=>$value,'state'=>1])->findOrEmpty();
        if(!$user->isEmpty()){
            return true;
        }else{
            return false;
        }
    }
}
