<?php
declare (strict_types = 1);

namespace app\home\controller;

use app\common\model\BankPrcptcd;
use think\facade\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\Banks;
use app\common\model\Userbank;
use app\common\model\Wechat;

class Bank extends UserBase
{
    public function initialize()
     {
        parent::initialize();
		if($this->user['userReal']['retype']!=1 && $this->user['userReal']['retype']!=2){
			$data['confirm']['name']= "请先实名认证！"; 
			$data['confirm']['width']=400;
            $data['confirm']['prompt']= "warning";
			$data['confirm']['time']=2;
			$data['confirm']['url']='/user_realname.html';
			$data['content']='操作成功...';
			echo json_encode($data);
			exit;
		}
	 }
    public function addbank(){
		if ($this->request->isPost()){
			$data=input();
		    if(isset($data['id']) && !empty($data['id'])){
				try{
					$this->validate($data, 'bank.upbank');
					if($this->user['userReal']['retype']==2){
                        $this->validate($data, 'bank.qiye');
                    }
				}catch (\Exception $e){
					$str=$e->getMessage();
					$res=getArr($str);
					return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
				}
				$ban=Banks::where(['id'=>$data['tobank']])->find();
				$arr=['accounts'=>$data['accounts'],
                    'bankname'=>$data['bankname'],
                    'bankid'=>$data['tobank'],
                    'bankaddress'=>$data['bankaddress']
                    ];
				Userbank::where(['id'=>$data['id'],'uid'=>$this->user['id']])->update($arr);
			}else{
				try{
					$this->validate($data, 'bank.addbank');
                    if($this->user['userReal']['retype']==2){
                        $this->validate($data, 'bank.qiye');
                    }
				}catch (\Exception $e){
					$str=$e->getMessage();
					$res=getArr($str);
					return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
				}
				$ban=Banks::where(['id'=>$data['tobank']])->find();
				$map['uid']=session('user_auth.user_id');
				$map['bankname']=$data['bankname'];
				$map['accounts']=$data['accounts'];
				$map['user']=$this->user['userReal']['clas']==1?$this->user['userReal']['name']:$this->user['userReal']['company_name'];
				$map['bankid']=$ban['id'];
                $map['bankaddress']=$data['bankaddress'];
				$map['create_time']=time();
				Userbank::create($map);
			}
			return json(['confirm'=>['name'=> "操作成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'操作成功...']);
		}
        $lists=openJson('citylist');
        View::assign('citylist',$lists);
		View::assign("res",Userbank::where(['id'=>input('id')])->find());
		View::assign('banks',Banks::where(['state'=>1])->select());
		if(request()->isMobile()){
			return view('bank/wap/addbank');
		}else{
		return view();
		}
	}


    public function getcity(){
        $lists=openJson('citylist');
        return json($lists);
    }
    public function getcode(){
        if($this->request->isAjax()){
            $da=input();
            $res=BankPrcptcd::field('bankname,prcptcd')->where(['bank_code'=>$da['bankcode'],'city_code'=>isset($da['cityid'])?$da['cityid']:''])->select();
            $this->result($res);
        }
    }
	
	public function addalipay(){
		if ($this->request->isPost()){
		    $data=input();
			if(isset($data['id']) && !empty($data['id'])){
				try{
					$this->validate($data, 'bank.upali');
				}catch (\Exception $e){
					$str=$e->getMessage();
					$res=getArr($str);
					return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
				}
				Userbank::where(['id'=>$data['id'],'uid'=>$this->user['id']])->update(['accounts'=>$data['accounts']]);
			}else{
				try{
					$this->validate($data, 'bank.addali');
				}catch (\Exception $e){
					$str=$e->getMessage();
					$res=getArr($str);
					return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
				}
				$map['uid']=session('user_auth.user_id');
				$map['bankname']="支付宝";
				$map['accounts']=$data['accounts'];
				$map['user']=$this->user['userReal']['clas']==1?$this->user['userReal']['name']:$this->user['userReal']['company_name'];
				$map['bankid']=-1;
				$map['create_time']=time();
				Userbank::create($map);
			}
			return json(['confirm'=>['name'=> "操作成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'操作成功...']);
		}
		
		View::assign("res",Userbank::where(['id'=>input('id')])->find());
		if(request()->isMobile()){
			return view('bank/wap/addalipay');
		}else{
		  return view();
		}
	}
	
	 public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $banks=(new Userbank)->where(['id'=>$param['id'],'uid'=>session('user_auth.user_id')])->find();
            $banks->delete();
            return json(['confirm'=>['name'=> "删除成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'删除提现方式成功...']);
        }
		return view();
    }
	
	public function addweixin(){
		$da=input('id');
		try{
			$app = Wechat::weixin();
			$res=$app->qrcode->temporary($this->user['id'], 6 * 24 * 3600);
			$url = $app->qrcode->url($res['ticket']);
		}catch (\Exception $e){
			return "获取二维码失败";
		}
		View::assign('url',$url);
		if(request()->isMobile()){
			return view('bank/wap/addweixin',["id"=>$da]);
		}else{
		  return view();
		}
	}
	
	
}
