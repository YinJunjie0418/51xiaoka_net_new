<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\Category as ca;

class Category extends Validate
{
    protected $rule = [
        'name' => 'require',
        'model' => 'require',
        'pid'=>'require|isPid:pid'
    ];

    protected $message = [
        'name.require' => '分类名称不能为空',
        'model.require' => '模型不能为空',
        'pid.require'=>'pid参数错误'
    ];

    protected function isPID($value,$rule,$data=[]){
        if($value==0)return true;
        $pid= Ca::where(['id' => $value])->value('pid');
        if($pid>0) {
            return "不能添加3级分类";
        }else{
            return true;
        }
    }


}
