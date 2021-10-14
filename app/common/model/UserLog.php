<?php
namespace app\common\model;

use think\Model;
use think\Request;
use think\model\concern\SoftDelete;

class UserLog extends Model
{
	use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
       
    ];
    public function getTypeAttr($value)
    {
		$arr=['','登陆','退出','登陆错误','其他操作'];
		return isset($arr[$value])?$arr[$value]:"";
	}
    //获取部门
    public function shop()
    {
        return $this->belongsTo(User::class,'uid','id')->bind(['shop_name'=>'mobile','shopid'=>'shopid']);
    }
    
    
    
}