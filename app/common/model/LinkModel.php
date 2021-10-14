<?php
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class LinkModel extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [

    ];
    protected function base($query)
    {
        //$query->where('userid', session('user_auth.user_id'));
    }
    //模型列表
    public static function lists()
    {
        $list = self::select();
        return $list;
    }
}