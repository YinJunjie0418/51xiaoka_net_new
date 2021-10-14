<?php
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Category extends Model
{
    protected function _initialize()
    {
        parent::_initialize();
        
    }
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [

    ];

    public function models()
    {
        return $this->belongsTo(CategoryModel::class, 'model', 'model')->bind(['model_name'=>'name']);
    }
    public function page()
    {
        return $this->belongsTo(Page::class, 'id', 'id')->bind(['title','view','datetime'=>'create_time']);
    }
    //URL
    static function update_url($id) {
        $url = "/home/help_danye/{$id}.html";
        self::where(['id'=>$id])->update(['url'=>$url]);
        return;
    }
    //获取栏目列表
    static function list_to_level($model) {
        $list = list_to_level(self::where(['model'=>$model])->order('sort_order desc,id desc')->select());
        return $list;
    }
    //获取列表页模板
    static function list_template() {
        $filepath = app()->getRootPath().'app\home"."/view\index' . DIRECTORY_SEPARATOR;
        $list = str_replace($filepath . DIRECTORY_SEPARATOR, '', glob($filepath . DIRECTORY_SEPARATOR . 'list*'));
        return $list;
    }
    //获取详情页模板
    static function show_template() {
        $filepath = app()->getRootPath()."app/home/view/index" . DIRECTORY_SEPARATOR;
        $list = str_replace($filepath, '', glob($filepath .  'show*'));
        return $list;
    }
    //获取栏目列表
    public static function get_category_list($cid) {
        if($cid == '0'){
            $data['model'] = 'prodcuts';
        }else{
            //查询数据
            $data = self::where('id',$cid)->find();
        }
        //查询列表
        $list = self::where('model',$data['model'])->order('sort_order desc,id desc')->select();

        return $list;
    }
    
}