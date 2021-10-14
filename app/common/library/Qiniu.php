<?php
namespace app\common\library;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;


class Qiniu{
    
    public function initialize()
    {
        parent::initialize();
        $params = \think\facade\Config::load('setting/qiniu','qiniu');
        $this->ak = $params['ak'];
        $this->sk = $params['sk'];
        $this->bucket = $params['bucket'];
        $this->qiniu_path = $params['domain'];
    }

    public function token()
    {
        $params = \think\facade\Config::load('setting/qiniu','qiniu');
        $auth = new Auth($params['ak'], $params['sk']);
        // 生成上传Token
        $token = $auth->uploadToken($params['bucket']);

        return $token;
    }

    /**
     * @param $filePath  要上传文件的本地路径
     * @param $key 上传到七牛后保存的文件名
     * @return mixed
     * @throws \Exception
     */
    public function upload($filePath, $key)
    {
        $token = $this->token();

        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            ajaxError($err);
        } else {
            return $ret['key'];
        }
    }

    public function delete($key)
    {
        $params = \think\facade\Config::load('setting/qiniu','qiniu');
        $auth = new Auth($params['ak'], $params['sk']);
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $err = $bucketManager->delete($params['bucket'], $key);
    }
}