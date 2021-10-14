<?php
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Link extends Model
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
    
    public function category()
    {
        return $this->belongsTo(LinkModel::class, 'category', 'id')->bind(['category_name'=>'name']);
    }
}