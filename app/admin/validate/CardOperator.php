<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\CardOperator as Co;

class CardOperator extends Validate
{
    protected $rule = [
        'ApiName' => 'require',
        'ApiCalss' => 'require|isRepeatOn:ApiCalss',
		'ApiQq' => 'require',
		'ApiParameter' => 'require',
		'name'=>'require',
		'class'=>'require|isRepeatOn:class',
    ];

    protected $message = [
        'ApiName.require' => '配置参数错误：接口名称不能为空',
        'ApiCalss.require' => '配置参数错误：接口类名不能为空',
		'ApiQq.require' => '配置参数错误：QQ不能为空',
		'ApiParameter.require' => '配置参数错误：接口配置参数不能为空',
		'name.require'=>'接口名称不能为空',
		'class.require'=>'类名不能为空',
    ];
	
	 //定义验证场景
    protected $scene = [
        'edit' => ['name','class'],	
		'add'=>['ApiName','ApiCalss','ApiQq','ApiUrl','ApiParameter'],
    ];
	
	protected function isRepeatOn($value,$rule,$data=[]){
		if(isset($data['id'])){
			$ok=Co::where(['class'=>$value])->where('id','<>',$data['id'])->findOrEmpty();
		}else{
		   $ok=Co::where(['class'=>$value])->findOrEmpty();
		}
		if(!$ok->isEmpty()){
			return "配置参数错误：类名已经存在";
		}else{
			return true;
		}
	}
}
