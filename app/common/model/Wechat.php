<?php
namespace app\common\model;
use think\Model;
use EasyWeChat\Factory;
use think\facade\Config;

class Wechat
{
    
    //小程序API
    public static function wxapp()
    {
        $wxapp = Config::load('setting/wxpay','wxpay');
        $appid = $wxapp['appid'];
        $secret = $wxapp['secretID'];

        $config = [
            'app_id' => $appid,
            'secret' => $secret,
            
            'log' => [
                'level' => 'debug',
                'file' => \think\facade\Env::get('runtime_path').'wechat.log',
            ],
        ];
        $app = Factory::miniProgram($config);
        return $app;
    }
    //公众号API
    public static function weixin()
    {
        $weixin = Config::load('setting/wxpay','wxpay');
        $config = [
            'app_id'  => $weixin['appid'],
            'secret'  => $weixin['secretID'],
            'token'   => $weixin['token'],          // Token
            'aes_key' => $weixin['aeskey'], 
            'oauth' => [
                  'scopes'   => ['snsapi_userinfo'],
                  'callback' =>  "https://www.365shouka.com".url('home/Gongzhaohao/oauthcallback'),
              ],
            'log' => [
                'level' => 'alert',
                'file' => \think\facade\Env::get('runtime_path').'wechat.log',
            ],
        ];
			$app = Factory::officialAccount($config);
			return $app;
    }
    
    public function wxCash($openid,$str,$money){
        $app = self::weixin();
        $res=$app->template_message->send([
            'touser' => $openid,
            'template_id' => 'u_1kXCKmn6pZ7wSWagGjQvUsFGW_DAYM2zXDQezhw-g',
            'url' => 'https://easywechat.org',
             "topcolor"=>"#FF0000",
			   "data"=>["first"=>["value"=>$str,"color"=>"#173177"],
			            "timet"=>["value"=>date("Y/m/d H:i:s"),"color"=>"#173177"],
						"money"=>["value"=>$money,"color"=>"#173177"],
						"remark"=>["value"=>"如该操作不是你本人操作，请修改密码后重新绑定","color"=>"#173177"]
						]
        ]);
       return $res;
    }


    //获取ACCESSTOKEN
    public static function access_token($true = false)
    {
        $app = self::weixin();
        $accessToken = $app->access_token;
        $token = $accessToken->getToken($true);
        return $token['access_token'];
    }

    
    
    
}
