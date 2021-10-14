<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;

class Ad extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    
    protected $type = [

    ];
    protected function base($query)
    {
        //$query->where('userid', session('user_auth.user_id'));
    }
    
    public function category()
    {
        return $this->belongsTo(AdModel::class, 'category', 'id')->bind(['category_name'=>'name']);
    }
}