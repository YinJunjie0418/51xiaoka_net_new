<?php
namespace app\common\model;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class CategoryModel extends Model
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
    public static function create_table($table,$name)
    {
        //创建数据表
        $sql = "sql
            CREATE TABLE `hq_web_$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cid` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类ID',
            `country` int(5) DEFAULT NULL COMMENT '国家/地区',
            `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
            `image` varchar(255) DEFAULT '' COMMENT '图片',
            `author` varchar(255) DEFAULT '' COMMENT '作者',
            `summary` text COMMENT '简介',
            `photo` text COMMENT '相册',
            `content` longtext COMMENT '内容',
            `view` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
            `is_top` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶',
            `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
            `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
            `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
            `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
            `description` varchar(255) DEFAULT '' COMMENT '描述',
            `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
            `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
            `url` varchar(200) DEFAULT NULL COMMENT 'URL',
            `template` varchar(255) DEFAULT '' COMMENT '模板',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT '$name'";	
        Db::execute($sql);
    }
    //相关字段
    public function fieldList()
    {
        return $this->hasMany(CategoryModelField::class, 'model', 'model')->where('status','1');
    }
    
}