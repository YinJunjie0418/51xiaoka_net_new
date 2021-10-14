<?php
namespace app\common\model;
use think\facade\Db;
use think\Model;

class CategoryModelField extends Model
{
    //模型列表
    public static function get_list($model)
    {
        $list = self::where(['model'=>$model])->where('status',1)->order('sort_order asc,id desc')->select();
        return $list;
    }
    //新增默认字段
    public static function CreateDefaultField($model)
    {
        $data = [
            ['model' => $model, 'title' => '简介','name'=>'summary','type'=>'textarea','value'=>'','options'=>'','sort_order'=>'100','status'=>'1','is_sys'=>'1'],
            ['model' => $model, 'title' => '相册','name'=>'photo','type'=>'photo','value'=>'','options'=>'','sort_order'=>'100','status'=>'1','is_sys'=>'1'],
            ['model' => $model, 'title' => '关键字','name'=>'keywords','type'=>'input','value'=>'','options'=>'','sort_order'=>'100','status'=>'1','is_sys'=>'1'],
            ['model' => $model, 'title' => '描述','name'=>'description','type'=>'textarea','value'=>'','options'=>'','sort_order'=>'100','status'=>'1','is_sys'=>'1'],
        ];
        self::insertAll($data);
        return ;
    }
    //获取图片
    public function getOptionsdAttr($value)
    {
        if(!$value){
            $list = array();
        }else{
            $list = explode(',', $value);
        }
        return $list;
    }
    //判断是否系统字段
    static function IsSysField($value)
    {
        $result = in_array($value,['id','user_id','cid','title','image','author','summary','photo','content','view','is_top','is_hot','status','sort_order','keywords','description','template','url','jdata','create_time','update_time','delete_time']);
        return $result;
    }
    
}