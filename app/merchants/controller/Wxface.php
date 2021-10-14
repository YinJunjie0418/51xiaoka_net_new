<?php
namespace app\merchants\controller;

use app\common\controller\IndexBase;
use app\common\model\Order;
use app\common\weback\ContractApi;
use think\facade\View;
use app\common\wxfaceld\pcticket;
use app\common\model\UserAuth;

class Wxface extends IndexBase
{
    public function index(){
        $id=input('id');
        $ress=['code'=>-1,'msg'=>'err'];
        if($id){
            if(cache($id)){
                $str=cache($id);
                $str=Order::getCardno($str);
                $data=json_decode($str,true);
                if(isset($data['username']) && isset($data['idcard']) && isset($data['uid']) && isset($data['imga']) && isset($data['imgb'])){
                    $http="http://";
                    if(is_https())$http="https://";
                    $order=generate_password(16);
                    cache($order.'bbs',$data,1200);
                    $ress=(new pcticket())->pcLoginUrl($data['username'],$data['idcard'],$order,$http.$_SERVER['HTTP_HOST'].url('merchants/Wxface/facequery'),$data['imga'],$data['imgb']);
                }else{
                    $ress=['code'=>-1,'msg'=>"参数缺少，请重新获取二维码"];
                }
            }else{
                $ress=['code'=>-1,'msg'=>"二维码过期，请重新获取"];
            }
        }

        if($ress['code']==1){
            $this->redirect($ress['msg']);
        }else{
            View::assign('title','信息提示');
            View::assign('msg',$ress['msg']);
            return view();
        }
    }

    public function facequery(){
        $da=input();
        $ok=false;
        if(isset($da['orderNo'])){
            $ok=(new pcticket())->pcBack($da);
            if($ok){
                $data=cache($da['orderNo'].'bbs');
                $map['uid']=$data['uid'];
                $map['name']=$data['username'];
                $map['retype']=1;
                $map['clas']=1;
                $map['hastype']=2;
                $map['idcard']=$data['idcard'];
                $map['orderno']=$da['orderNo'];
                $map['back_img']=$data['imgb'];
                $map['positive_img']=$data['imga'];
                $isu=UserAuth::where(['uid'=>$data['uid']])->find();
                if($isu){
                    UserAuth::update($map,['id'=>$isu['id']]);
                }else{
                    UserAuth::create($map);
                }
            }
        }
        View::assign("ok",$ok?1:0);
        View::assign('title',"认证返回");
        return view('login/wap/faceback');
    }



}