<?php
namespace app\common\model;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class CardModel extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [

    ];
    protected function _initialize()
    {
        parent::_initialize();
        
    }
    protected function base($query)
    {
        //不显示回收站
        if(request()->controller() != 'Recovery'){
            $query->where('delete_time','0');
        }
    }
    //模型列表
    public static function lists()
    {
        $list = self::where(['status'=>1])->select();
        return $list;
    }
	
	public function comments()
    {
        return $this->hasMany('CardList','cid','id');
    }
}