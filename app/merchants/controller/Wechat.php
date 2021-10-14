<?php
declare (strict_types = 1);

namespace app\merchants\controller;
use app\common\controller\IndexBase;
use app\common\model\Wechat as wx;
use app\common\model\User;
use app\common\model\Userbank;
use app\common\model\UserAuth;
use GuzzleHttp\Client;
use think\facade\Config;
class Wechat extends IndexBase
{
    private $wxapp;
    
    public function  __construct()
    {
        $this->wxapp = wx::weixin();
    }

    public function index()
    {
        $app = $this->wxapp;
        $app->server->push(function ($message) { 
            switch ($message['MsgType']) { 
                case 'event':
                    $returnInfo = $this->eventHandler($message); 
                    return $returnInfo; 
                    break; 
                case 'text': 
                    return '收到文字消息'; break; 
                case 'image': 
                    return '收到图片消息'; break; 
                case 'voice': 
                    return '收到语音消息'; break; 
                case 'video': 
                    return '收到视频消息'; break; 
                case 'location': 
                    return '收到坐标消息'; break; 
                case 'link': 
                    return '收到链接消息'; break; 
                default: 
                    $this->checkSignature();exit(); 
					break; 
            } 
        }); 
        $response = $app->server->serve();
        $response->send();
    }
    
    public function checkSignature()
		{
		    $da=input();
		    $weixin = Config::load('setting/wxpay','wxpay');
			$signature = $da["signature"];
			$timestamp = $da["timestamp"];
			$nonce = $da["nonce"];
			$token = $weixin['token'];
			$tmpArr = array($token, $timestamp, $nonce);
			sort($tmpArr, SORT_STRING);
			$tmpStr = implode( $tmpArr );
			$tmpStr = sha1( $tmpStr );
			if( $tmpStr == $signature ){
				echo $da['echostr'];
			}else{
				return false;
			}
		}
    //获取信息
    private function eventHandler($messageEvent) { 
        switch ($messageEvent['Event']) { 
            case 'subscribe': 
                //logt($messageEvent);
                //二维码事件
                if(strpos($messageEvent['EventKey'],'qrscene_') !==false){
                    $msg=explode("_",$messageEvent['EventKey']);
                    if(count($msg)==2){
                         $msg=$this->qrscene($messageEvent['FromUserName'],$msg[1]);
                    }elseif(count($msg)==3){
                        return $this->bindwei($msg[1],$messageEvent['FromUserName']);
                    }
                }else{
					$msg=$this->subscribe($messageEvent['FromUserName']);
				}
				return $msg;
                break;
            case 'unsubscribe': 
                $this->unsubscribe($messageEvent['FromUserName']);
                return '欢迎订阅'; 
                break;
        } 
    }
    //获取OPENID等
    private function subscribe($openid) { 
        $app = $this->wxapp;
        //检查用户
        $admin = User::where(['wxopenid'=>$openid])->find();
        if(!$admin){
    		$user = $app->user->get($openid);
    		$map['shopid'] = User::order('id desc')->value('shopid')+1;
    		$map['username']=$user['nickname'];
    		$map['password']=generate_password(4);
    		$map['wxopenid']=$openid;
    		(new User)->save($map);
        }
        return "欢迎关注我们的微信公众账号.";
    }
    
    private function bindwei($id,$openid){
        if(User::where(['wxopenid'=>$openid])->find()){
            return '该微信已经绑定其他账户，请取消绑定后再重新绑定';
        }
        $admin = User::find($id);
        if($admin){
            $admin->unionid=$this->getUnionId($openid);
            $admin->wxopenid=$openid;
            $admin->save();
            return '欢迎关注我们的微信公众账号,微信绑定成功，请不要取消关注以免接不到通知';
        }else{
            return '微信绑定失败，请取消关注后重试';
        }
    }
    
    
    private function getUnionId($openid){
        $app=$this->wxapp;
        $accessToken = $app->access_token; //
        $token = $accessToken->getToken(true);
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token['access_token']}&openid=".$openid;
        $data=['access_token'=>$token['access_token'],'openid'=>$openid];
        $response = (new Client())->request('get',$url,['form_params' => $data]);
        $result=$response->getBody()->getContents();
        $result=json_decode($result,true);
        if(isset($result['unionid'])){
            return $result['unionid'];
        }else{
            return "";
        }
    }
    
     //取消关注事件
    private function unsubscribe($openid)
    { 
        $user=User::where(['wxopenid'=>$openid])->find();
        $user->wxopenid="";
        $user->unionid="";
        $user->wxopenidg="";
        $user->save();
	    Userbank::where(['accounts'=>$openid])->delete();
        return ;
    }
    
    //二维码绑定
    private function qrscene($openid,$message)
    {
       $user=User::find($message);
	   $real=UserAuth::where(['uid'=>$user['id']])->find();
	   if($user && $real){
		   $app = $this->wxapp;
		   $user = $app->user->get($openid);
		   $map['uid']=$message;
		   $map['bankname']=$user['nickname'];
		   $map['accounts']=$openid;
		   $map['user']=$real['clas']==1?$real['name']:$real['company_name'];
		   $map['bankid']=-2;
		   $map['create_time']=time();
		   Userbank::create($map);
		   return '欢迎关注我们的微信公众账号,提现微信绑定成功';
	   }
	   elseif(!$real){
		   return '该账户没有实名认证,请实名认证后再添加提现微信';
	   }
        
    }
    
    public function bindweixin(){
		try{
		    $id=session('user_auth.user_id');
			$app = $this->wxapp;
			$res=$app->qrcode->temporary($id."_bind", 6 * 24 * 3600);
			$url = $app->qrcode->url($res['ticket']);
			return $url;
		}catch (\Exception $e){
			return false;
		}
		
	}
    
}
