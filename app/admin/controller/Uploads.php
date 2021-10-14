<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\weback\ContractApi;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Session;
use think\facade\Env;
use think\facade\Filesystem;
use think\facade\Config;

use app\common\model\Uploads as UploadsModel;

class Uploads extends AdminBase
{
    protected $noAuth = [ 'uploadImage', 'uploadFile', 'uploadVideo', 'editor'];
    
    public function index()
    {
        return $this->fetch('index');
    }
    public function index_json($limit='15')
    {
        $list = UploadsModel::order('id desc')->paginate($limit);
        $this->result($list);
    }
	
	    //上传文件
   public function upFile(){
		try {
			$file = request()->file();
			foreach($file as $k=>$v){
			    	validate([$k=>'fileSize:1000240|fileExt:php,pem,pdf,pfx,crt|hex:1'])->check($file);
			}
			$files = request()->file('file');
			$conid="";
			if($files->extension()=="php"){
				$name=str_replace(".php","",$files->getOriginalName());
				$savename = Filesystem::disk('extend')->putFile('api',$files,$name);
            }elseif(in_array($files->extension(),["pem",'pfx','crt'])){
			   $savename = Filesystem::disk('config')->putFile('ecrt',$files);
			}else{
                $name=str_replace(".pdf","",$files->getOriginalName());
                $savename = Filesystem::disk('config')->putFile('pdf',$files,$name);
                $conid=(new ContractApi)->getContractId($savename);
                if($conid['code']==1){
                    $conid=$conid['msg'];
                }else{
                    return json(['code' => -1,'msg'=>$conid['msg']]);
                }
            }
			if($savename){//上传成功
				$data=array(
					'code' => 1,
					'url' => '/'.$savename,
					'msg'=>'上传成功',
                    'conid'=>$conid
				);
			}else{
				$data=array(
					'code' => -1,
					'msg'=>'保存失败'
				);
			}
			return json($data);
		 } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }   
    }

    //上传图片
    public function uploadImage()
    {
        try {
            $files = request()->file();
			foreach($files as $k=>$v){
			   validate([$k=>'fileSize:1000240|fileExt:jpg,png,gif|hex:1'])->check($files);
			}
            $file = request()->file('file');
            //处理图片
            UploadsModel::UploadValidate($file);
            $params = Config::load('setting/qiniu','qiniu');
            //判断上传位置
            if($params['type']=='1'){//七牛
                $key = date('Y/md/His_').substr(microtime(), 2, 6).'_'.mt_rand(0,999).'.'.$file->getOriginalExtension();
                $qiniu = new \app\common\library\Qiniu();
                $url = $params['domain'].$qiniu->upload($file->getRealPath(), $key);
                UploadsModel::CreateInfoAdmin('qiniu',$params['domain'], $key, $file->getSize(), $file->getOriginalMime());
                insert_admin_log('上传了图片');
                return ['code' => 1, 'url' => $url,'msg'=>'上传成功','data'=>['src'=>$url]];
            }else{//默认本地
                $savename = Filesystem::disk('public')->putFile('uploads',$file);
                $url ="/".$savename;
                UploadsModel::CreateInfoAdmin('local','', $savename, $file->getSize(), $file->getOriginalMime());
                insert_admin_log('上传了图片');
                return ['code' => 1, 'url' => $url,'msg'=>'上传成功','data'=>['src'=>$url]];
            }
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' =>$e->getMessage(),'data'=>[]];

        }
    }
	
    //上传图片
    public function uploadImagea()
    {
        try {
            $files = request()->file();
			foreach($files as $k=>$v){
			   validate([$k=>'fileSize:1000240|fileExt:jpg,png|hex:1'])->check($files);
			}
            $file = request()->file('file');
            //处理图片
            UploadsModel::UploadValidate($file);
            $params = Config::load('setting/qiniu','qiniu');
            //判断上传位置
            if($params['type']=='1'){//七牛
                $key = date('Y/md/His_').substr(microtime(), 2, 6).'_'.mt_rand(0,999).'.'.$file->getOriginalExtension();
                $qiniu = new \app\common\library\Qiniu();
                $url = $params['domain'].$qiniu->upload($file->getRealPath(), $key);
                UploadsModel::CreateInfoAdmin('qiniu',$params['domain'], $key, $file->getSize(), $file->getOriginalMime());
                insert_admin_log('上传了图片');
                return ['code' => 0, 'url' => $url,'msg'=>'上传成功','data'=>['src'=>$url]];
            }else{//默认本地
                $savename = Filesystem::disk('public')->putFile('uploads',$file);
                $url ="/".$savename;
                UploadsModel::CreateInfoAdmin('local','', $savename, $file->getSize(), $file->getOriginalMime());
                insert_admin_log('上传了图片');
                return ['code' => 0, 'url' => $url,'msg'=>'上传成功','data'=>['src'=>$url]];
            }
        } catch (\Exception $e) {
            return ['code' => 1, 'msg' =>$e->getMessage(),'data'=>[]];
        }
    }
    //删除图片
    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $files = UploadsModel::where('id',$param['id'])->find();
            //判断存储位置
            if($files->getData('storage')=='qiniu'){
                $qiniuconfig = Config::load('setting/qiniu','qiniu');
                $key = $files['file_name'];
                $qiniu = new \app\common\library\Qiniu();
                $qiniu->delete($key);
                UploadsModel::destroy($param['id'],true);
                insert_admin_log('删除了图片');
                $this->success('删除成功');
            }elseif($files->getData('storage')=='local'){
                $name=str_replace("\\","/",$files['file_name']);
				$result=true;
				if(file_exists($name))$result = unlink($name);
                if($result){
                    $delete = UploadsModel::destroy($param['id'],true);
                    insert_admin_log('删除了图片');
                    $this->success('删除成功');
                }else{
                    $delete = UploadsModel::destroy($param['id'],true);
                    UploadsModel::where('id',$param['id'])->update(['status'=>'0']);
                    $this->success('删除失败');
                }
            }
            
        }
    }
	//批量删除图片
    public function delall()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
			$ok=0;
			$sumc=count($param['ids']);
			foreach($param['ids'] as $v){
				$files = UploadsModel::where('id',$v)->find();
				//判断存储位置
				if($files->getData('storage')=='qiniu'){
					$qiniuconfig = Config::load('setting/qiniu','qiniu');
					$key = $files['file_name'];
					$qiniu = new \app\common\library\Qiniu();
					$qiniu->delete($key);
					UploadsModel::destroy($param['id'],true);
					$ok++;
				}elseif($files->getData('storage')=='local'){
					$name=str_replace("\\","/",$files['file_name']);
                    $result=true;
				    if(file_exists($name))$result = unlink($name);
					if($result){
						$delete = UploadsModel::destroy($v,true);
						$ok++;
					}else{
						$delete = UploadsModel::destroy($v,true);
						UploadsModel::where('id',$v)->update(['status'=>'0']);
					}
				}
            }
			return json(['code'=>1,'msg'=>"共删除{$sumc}张图片，成功删除{$ok}张图片"]);
		}
    }
	
	
}
