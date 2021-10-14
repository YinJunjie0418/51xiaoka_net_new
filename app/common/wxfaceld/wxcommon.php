<?php
namespace app\common\wxfaceld;

use think\facade\Config;

class wxcommon extends sign {

    private $APPID='';
    private $SECRETID='';
    private $config=[];

    private $access_token='';
    private $ticket='';

    const version='1.0.0';

    public function __construct()
    {
        $this->config=Config::load('setting/aly','aly');
        $this->APPID=$this->config['appid'];
        $this->SECRETID=$this->config['secretid'];
    }

    public function getaccess_token(){
        $parm=[
            'app_id'=>$this->APPID,//	请添加小助手微信 faceid001，进行线下对接获取	String	腾讯云线下对接决定	是
            'secret'=>$this->SECRETID,//	请添加小助手微信 faceid001，进行线下对接获取	String	腾讯云线下对接决定	是
            'grant_type'=>'client_credential',//（必须小写）	String	20	是
            'version'=>self::version
        ];
        try{
            $result=$this->toRequest('https://idasc.webank.com/api/oauth2/access_token',$parm,'get');
            $result=json_decode($result);
            if($result->code=='0'){
                $this->access_token=$result->access_token;
                return true;
            }else{
                return $this->show("调起认证失败");
            }
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return $this->show("调起认证失败");
        }

    }

    public function getTicket($order){
        $ok=$this->getaccess_token();
        if($ok===true){
            $parm=[
                'app_id'=>$this->APPID,
                'access_token'=>$this->access_token,
                'type'=>'SIGN',
                'version'=>self::version
            ];
           try{
                $result=$this->toRequest('https://idasc.webank.com/api/oauth2/api_ticket',$parm,'get');
                $result=json_decode($result);
                if($result->code=='0'){
                    $res=$result->tickets;
                    $this->ticket=$res[0]->value;
                    cache($order."atiekas",$this->ticket,200);
                    return true;
                }else{
                    trace("调起认证失败".$result->msg,'error');
                    return $this->show("调起认证失败");
                }
            }catch (\Exception $e){
               trace("调起认证失败".$e->getMessage(),'error');
               return $this->show("调起认证失败");
            }
        }else{
            return $ok;
        }
    }

    public function getName($name){
        return $this->$name;
    }



}
