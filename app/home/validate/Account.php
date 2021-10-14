<?php
namespace app\home\validate;

use app\common\model\User;
use think\Validate;
use app\common\model\UserAuth;


class Account extends Validate
{
	protected $regex = ['un' => '/^[\x80-\xff]{6,30}$/','unn' => '/^[\x80-\xff]{8,80}$/','card'=>'/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/','qiye'=>'/^[\dA-Z]{18}$/'];
	
    protected $rule = [
        'username' => 'require|regex:un|token',
        'idcard' => 'require|regex:card|isuser:idcard',
		'idjust'=>'require',
		'idback'=>'require',
		'license'=>'require',
		'company_name'=>'require|regex:unn|token',
		'canada'=>'require|regex:qiye',
		'canada_img'=>'require'

    ];

    protected $message = [
        'username.require' => ['code'=>'#username','msg'=>'请输入姓名'],
		'username.regex' => ['code'=>'#username','msg'=>'请输入正确姓名'],
		'username.token' => ['code'=>'#anjian','msg'=>'数据过期请重新提交'],
		'idcard.require' => ['code'=>'#idcard','msg'=>'请输入身份证号码'],
        'idcard.regex' => ['code'=>'#idcard','msg'=>'请输入正确的身份证号码'],
        'idcard.isuser' => ['code'=>'#idcard','msg'=>'该身份证已经实名请更换证件'],
		'idjust.require'=>['code'=>'#preview_0','msg'=>'请上传身份证正面'],
		'idback.require'=>['code'=>'#preview_1','msg'=>'请上传身份证背面'],
		'license.require'=>['code'=>'#preview_2','msg'=>'请上传手持身份证'],
		'company_name.require'=>['code'=>'#company_name','msg'=>'请填写企业名称'],
		'company_name.regex'=>['code'=>'#company_name','msg'=>'企业名称错误'],
		'company_name.token'=>['code'=>'#company_name','msg'=>'据过期请重新提交'],
		'canada.require'=>['code'=>'#canada','msg'=>'请填写统一社会信用代码'],
		'canada.regex'=>['code'=>'#canada','msg'=>'统一社会信用代码错误'],
		'canada_img.require'=>['code'=>'#zhizhao','msg'=>'请上传营业执照']
    ];
	
	 public function sceneCheckapi()
    {
        return $this->only(['username','idcard']);
			
	}
	
	 public function sceneCheckqiye()
    {
    	return $this->only(['company_name','canada','canada_img']);
			
	}
	
	 public function sceneCheckpic()
    {
    	return $this->only(['username','idcard','idjust','idback','license']);
			
	}

    protected function isuser($value){
	     $uid=UserAuth::where(['idcard'=>$value])->order("id desc")->value('uid');
        if($uid){
            if(User::find($uid))return false;
            return true;
        }else{
            return true;
        }
    }
	
	
	
}
