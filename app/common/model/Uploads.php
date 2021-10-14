<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;
use think\facade\Config;

class Uploads extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    
    protected function base($query)
    {

    }
    public function getTypeAttr($value)
    {
        $status = [0=>'本地',1=>'七牛'];
        return $status[$value];
    }
    public function getFilesizeAttr($value)
    {
        return format_bytes($value);
    }
    public function getUrlAttr($value, $data)
    {
        if ($data['storage'] === 'local') {
            return '/'.$data['file_name'];
        }
        return $data['file_url'] . '/' . $data['file_name'];
    }
    //处理图片
    static public function UploadValidate($file) {
        $upload_image = Config::load('admin/upload_setting');
        if ($upload_image['is_thumb'] == 1 || $upload_image['is_water'] == 1 || $upload_image['is_text'] == 1) {
			
            $object_image = \think\Image::open($file->getPathName());
            // 图片压缩
            if ($upload_image['is_thumb'] == 1) { 
                $object_image->thumb($upload_image['max_width'], $upload_image['max_height']);
            }
            $object_image->save($file->getPathName());
        }
        return $file;
    }
    //后台保存记录前端
    static public function CreateInfoAdmin($storage,$domain,$filename,$filesize,$mine) {
        self::create([
            'shop_id' => '0',
            'storage' => $storage,
            'user_id'  => session('admin_auth.admin_id'),
            'file_url' => $domain,
            'file_name' => $filename,
            'file_size' => $filesize,
            'mine' => $mine
        ]);
        return;
    }
    //用户保存记录前端
    static public function CreateInfo($storage,$domain,$filename,$filesize,$mine) {
        self::create([
            'shop_id' => session('user_auth.shop_id'),
            'storage' => $storage,
            'user_id'  => UserId(),
            'file_url' => $domain,
            'file_name' => $filename,
            'file_size' => $filesize,
            'mine' => $mine
        ]);
        return;
    }
    
    
}