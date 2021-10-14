<?php
namespace app\common\wxfaceld;

use app\common\model\UserAuth;


class pcticket extends wxcommon{
    private $faceid='';
    private $uid='';


    public function index($order)
    {
        parent::__construct();
        $this->uid=generate_password(16);
        return $this->getTicket($order);
        
    }

    public function geth5faceid($name,$cardno,$orderno){
        $parm=[
            'webankAppId'=>$this->getName('APPID'),//	请添加小助手微信 faceid001，进行线下对接获取	String	腾讯云线下对接决定	是
            'orderNo'=>$orderno,//	订单号，由合作方上送，每次唯一，不能超过32位	String	不能超过32位	是
            'name'=>$name,//	姓名	String	-	是
            'idNo'=>$cardno,//	证件号码	String	-	是
            'userId'=>$this->uid,//	用户 ID ，用户的唯一标识（不能带有特殊字符）	String	-	是
            'version'=>self::version,
            'ticket'=>cache($orderno."atiekas")
        ];
        try{
            $result=$this->toRequest('https://idasc.webank.com/api/server/h5/geth5faceid',$parm);
            logt($result);
            $result=json_decode($result);
            if($result->code=='0'){
                $this->faceid=$result->result->h5faceId;
                return true;
            }else{
                return $this->show($result->msg);
            }
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return $this->show("调起认证失败");
        }
    }

    public function getNoce(){
        $parm=[
            'app_id'=>$this->getName('APPID'),//	请添加小助手微信 faceid001，进行线下对接获取	String	腾讯云线下对接决定	是
            'access_token'=>$this->getName('access_token'),//	请根据 Access Token 获取 指引进行获取	String	腾讯云线下对接决定	是
            'type'=>'NONCE',//	ticket 类型，默认值：NONCE（必须大写）	String	20	是
            'version'=>self::version,//	版本号	String	20	是
            'user_id'=>$this->uid
        ];
        try{
            $request=$this->toRequest('https://idasc.webank.com/api/oauth2/api_ticket',$parm,'get');
            $res=json_decode($request);
            if($res->code=='0'){
                $ress=$res->tickets;
                return ['code'=>1,'msg'=>$ress[0]->value];
            }else{
                return $this->show($res->msg);
            }
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return $this->show("调起认证失败");
        }

    }

    public function pcLoginUrl($name,$cardno,$orderno,$backurl=''){
        $index=$this->index($orderno);
        if($index!==true){
            return $index;
        }
        $ok=$this->geth5faceid($name,$cardno,$orderno);
        $noce=$this->getNoce();
        if($noce['code']!=1)return $noce;
        if($ok===true && $noce['code']==1){
            $http="http://";
            if(is_https())$http="https://";
            $parm=[
                'webankAppId'=>$this->getName('APPID'),
                'userId'=>$this->uid,
                'nonce'=>generate_password(16),
                'version'=>self::version,
                'h5faceId'=>$this->faceid,
                'orderNo'=>$orderno
            ];
            $parm['ticket']=$noce['msg'];
            $parm['sign']=$this->signTC3($parm);
            if(request()->isMobile()){
                $map['uid']=session('user_auth.user_id');
                $map['name']=$name;
                $map['retype']=1;
                $map['clas']=1;
                $map['hastype']=2;
                $map['idcard']=$cardno;
                $map['orderno']=$orderno;
                cache($orderno,$map,200);
                $parm['from']='browser';
                $parm['url']=empty($backurl)?$http.$_SERVER['HTTP_HOST'].url('home/Login/faceback'):$backurl;
                unset($parm['ticket']);
                $url="https://ida.webank.com/api/web/login?".http_build_query($parm);
            }else{
                $parm['url']=empty($backurl)?$http.$_SERVER['HTTP_HOST'].url('home/Login/faceback'):$backurl;
                unset($parm['ticket']);
                $url="https://ida.webank.com/api/pc/login?".http_build_query($parm);
            }
            return ['code'=>1,'msg'=>$url];
        }else{
            return $ok;
        }
    }



    public function getGign($data){
        parent::__construct();
        try{
            $arr=[
                'app_id'=>$this->getName('APPID'),
                'nonce'=>generate_password(16),
                'version'=>self::version,
                'order_no'=>$data
            ];
            $arr['ticket']=cache($data['orderno']."atiekas");
            $arr['sign']=$this->signTC3($arr);
            $arr['get_file']=2;
            $result=$this->toRequest("https://idasc.webank.com/api/server/sync",$arr,'get');
            $res=json_decode($result);
            if($res->code==0){
                $map['uid']=session('user_auth.user_id');
                $map['name']=$res->name;
                $map['retype']=1;
                $map['clas']=1;
                $map['hastype']=2;
                $map['idcard']=$res->idNo;
                $map['orderno']=$data['orderno'];
                $map['positive_img']=$res->photo;
                UserAuth::create($map);
                return true;
            }
            return false;
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return false;
        }
    }

    public function replaceBack($da){
        parent::__construct();
        $map['orderNo']=$da['orderNo'];
        $map['code']=$da['code'];
        $map['ticket']=cache($da['orderNo']."atiekas");
        $map['AppID']=$this->getName('APPID');
        $sign=$this->signTC3($map);
        cache($da['orderNo']."atiekas",null);
        if(strtoupper($sign)==$da['newSignature']){
            $mapp=cache($da['orderNo']);
            if($mapp){
                cache($da['orderNo'],null);
                $isu=UserAuth::where(['uid'=>$mapp['uid']])->find();
                if($isu){
                    UserAuth::update($mapp,['id'=>$isu['id']]);
                }else{
                    UserAuth::create($mapp);
                }
                return true;
            }
        }
        return false;
    }

    public function pcBack($da){
        parent::__construct();
        $map['orderNo']=$da['orderNo'];
        $map['code']=$da['code'];
        $map['ticket']=cache($da['orderNo']."atiekas");
        $map['AppID']=$this->getName('APPID');
        $sign=$this->signTC3($map);
        cache(session('user_auth.user_id').'as',null);
        if(strtoupper($sign)==$da['newSignature'] && $da['code']==0){
            return true;
        }
        return false;
    }
}
