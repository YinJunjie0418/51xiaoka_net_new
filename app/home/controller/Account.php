<?php
declare (strict_types = 1);

namespace app\home\controller;

use app\common\wxfaceld\pcticket;
use Endroid\QrCode\QrCode;
use think\facade\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\UserAuth;
use app\common\model\Uploads as UploadsModel;
use think\facade\Config;
use think\facade\Filesystem;
use app\common\model\Order;

class Account extends UserBase
{
    public function initialize()
    {
        parent::initialize();
        $action=Request::action();
        if(in_array($this->user['userReal']['retype'],[1,2]) && $action!='uploadImage' && $action!='enterprise'){
            $this->redirect(url('home/Member/realname'));
        }
        if($this->user['userReal']['retype']==3)$this->redirect(url('home/Member/realname'));
    }


    public function index(){
        if($this->user['userReal']['clas']==2)$this->redirect(url('home/Account/enterprise'));
        if($this->request->isAjax()){
            if(cookie('isana')){
                return json(['code'=>-1,'msg'=>"请不要频繁操作"]);
            }else{
                cookie('isana',1,1);
            }
            $data=input();
            try{
                $this->validate($data, 'account.checkapi');
            }catch (\Exception $e){
                $str=$e->getMessage();
                $res=getArr($str);
                return json(["code"=>-1,"msg"=>$res[1],'token'=>token()]);
            }

            if(request()->isMobile()){
                $order=generate_password(16);
                $ress=(new pcticket())->pcLoginUrl($data['username'],$data['idcard'],$order);
                if($ress['code']!=1){
                    return json(["code"=>-1,"msg"=>$ress['msg'],'token'=>token()]);
                }
                $dataUri=$ress['msg'];
            }else{
                $http="http://";
                if(is_https())$http="https://";
                $udata=['uid'=>session('user_auth.user_id'),'username'=>$data['username'],'idcard'=>$data['idcard'],'time'=>time()];
                $enstr=Order::enCardno(json_encode($udata));
                cache((string)session('user_auth.user_id'),$enstr,600);
                $url=$http.$_SERVER['HTTP_HOST'].url('home/wxface/index',['id'=>session('user_auth.user_id')]);
                $qrCode= new QrCode((string)$url);
                $dataUri = $qrCode->writeDataUri();
            }
            return json(['code'=>1,'msg'=>$dataUri]);
        }
        View::assign("da",UserAuth::where(['uid'=>session('user_auth.user_id')])->find());
        if(request()->isMobile()){
            return view('account/wap/index',['title'=>"实名认证"]);
        }else{
            return view();
        }
    }

    //上传图片
    public function uploadImage()
    {
        try{
            $files = request()->file();
            foreach($files as $k=>$v){
                validate([$k=>'fileSize:1000240|fileExt:jpg,png'])->check($files);
            }
            $file = request()->file('image');
            //处理图片
            UploadsModel::UploadValidate($file);
            $params = Config::load('setting/qiniu','qiniu');
            //判断上传位置
            if($params['type']=='1'){//七牛
                $key = 'shop_'.UserId().'/'.date('Y/md/His_').substr(microtime(), 2, 6).'_'.mt_rand(0,999).'.'.$file->getOriginalExtension();
                $qiniu = new \app\common\library\Qiniu();
                $url = $params['domain'].$qiniu->upload($file->getRealPath(), $key);
                UploadsModel::CreateInfo('qiniu',$params['domain'], $key, $file->getSize(), $file->getOriginalMime());
                $result['code'] = 0;
                $result['info'] = '图片上传成功!';
                $result['imgurl']=  $url;
                return json($result);
            }else{//默认本地
                $savename = Filesystem::disk('public')->putFile('uploads/shop_'.UserId(),$file);
                $url = '/'.$savename;
                UploadsModel::CreateInfo('local','', $savename, $file->getSize(), $file->getOriginalMime());
                $result['code'] = 0;
                $result['info'] = '图片上传成功!';
                $result['imgurl']=  $url;
                return json($result);
            }
        }catch (\Exception $e) {
            trace($e->getMessage());
            $upid=input('upid');
           return json(['tip'=>"#preview_{$upid}", 'content'=>$e->getMessage()]);
           // return json(['code' => 0, 'info' => $e->getMessage()]);
        }
    }

    public function enterprise(){
        if($this->user['userReal']['retype']!=1 && $this->user['userReal']['retype']!=4){
            $this->redirect(url('home/Member/index'));
        }
        if ($this->request->isPost()){
            $data=input();
            try{
                $this->validate($data, 'account.checkqiye');
            }catch (\Exception $e){
                $str=$e->getMessage();
                $res=getArr($str);
                return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }

            $find=UserAuth::where(['uid'=>session('user_auth.user_id')])->update(['company_name'=>$data['company_name'],'canada'=>$data['canada'],'xuke_img'=>$data['xuke_img'],'canada_img'=>$data['canada_img'],'retype'=>3,'clas'=>2,'hastype'=>1]);
            if($find){
                return json(['confirm'=>['name'=> "提交认证成功！", 'width'=>400, 'prompt'=> "warning",'time'=>1,'url'=>'/user_realname.html'],'content'=>'等待认证审核...']);

            }else{
                return json(["tip"=>"#anjian","content"=>"参数错误",'token'=>token()]);
            }
        }
        View::assign("da",UserAuth::where(['uid'=>session('user_auth.user_id')])->find());
        if(request()->isMobile()){
            return view('account/wap/enterprise',['title'=>"企业认证"]);
        }else{
            return view();
        }
    }


}
