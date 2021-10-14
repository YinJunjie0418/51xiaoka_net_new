<?php
namespace app\common\model;

use think\Model;
use think\facade\Session;
use think\model\concern\SoftDelete;

class Article extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $json = ['jdata'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;
    protected $type = [

    ];
    protected function base($query)
    {
        //$query->where('userid', session('user_auth.user_id'));
    }
    
    public function setPhotoAttr($value)
    {
        return serialize($value);
    }

    public function getPhotoAttr($value)
    {
        return unserialize($value);
    }

    public function setContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
    
    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
    
    public function setJdataAttr($jdata)
    {
        unset($jdata['id'],$jdata['userid'],$jdata['cid'],$jdata['title'],$jdata['image'],$jdata['author'],$jdata['summary'],$jdata['photo'],$jdata['content'],$jdata['keywords'],$jdata['description'],$jdata['template']);
        return $jdata;
    }
    public function category()
    {
        return $this->belongsTo('category', 'cid', 'id')->bind(['category_name'=>'name','category_url'=>'url']);
    }
    static function update_url($id) {
        $col = self::where(['id'=>$id])->find();
        //获取规则
        $url = '/home/help_danye/'.$col['id'].'.html';
        self::where(['id'=>$id])->update(['url'=>$url]);
        return ;
    }
    
}