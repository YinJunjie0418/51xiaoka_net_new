<?php
namespace app\api\validate;

use app\api\model\ApiBase;
use app\common\model\SellList;
use app\common\model\CashOrder;
use app\common\model\SellUser;
use app\common\model\User;
use app\home\controller\Api;
use think\Validate;

class Unicommy extends Validate{

    protected $regex = ['url' => '/^http(s)?:\\/\\/.+/'];

    protected $rule = [
        'orderno' => 'require|length:4,40|isApi:orderno|isIp:orderno',
        'itemId'=>'require|length:1,10',//产品编码
        'checkItemFacePrice'=>'require|number|isMoneyIden:checkItemFacePrice',//bigint	商品面值(单位元用于验证上传面值是否跟系统itemId的面值一致)
        'number'=>'require',//	是	String	充值账号
        'notify_url'=>'require|regex:url|length:5,100',//回掉地址
        'overtime'=>'require|number',//可等待时间 单位秒 0即时
        'amt'=>'require',//话费、流量只能传1或者不传 游戏充值实际购买游戏币为 购买数量乘以兑换比例， 比如兑换比例为10的商品，购买数量填写1，实际购买游戏币为 10)
        'ext12'=>'require',//备用参数1 ,中石油-传手机号,中石化-传手机号 游戏充值 区 视频卡直充-传IP
        'ext22'=>'require',//备用参数2 ,中石油-身份证号 游戏充值 服
        'ext32'=>'require'//备用参数3 ,中石油-身份证姓名 '  游戏充值 终端IP（Q币充值必传）

    ];

    protected $message = [
        'number.mobile'=>'手机号错误',
        'orderno.isIp'=>'IP白名单错误'
    ];

    public function sceneBizorder()
    {
        return $this->only(['orderno'])
            ->remove('orderno',['unique','isApi']);
    }

    public function sceneBill()
    {
        return $this->only(['orderno','itemId','checkItemFacePrice','number','amt','overtime','notify_url'])
            ->append('number',['mobile'])
            ->append('amt',['isAmt:amt']);
    }

    public function sceneFuel()
    {
        return $this->only(['orderno','itemId','checkItemFacePrice','number','amt','overtime','notify_url']);

    }

    public function sceneVideo()
    {
        return $this->only(['orderno','itemId','checkItemFacePrice','number','amt','notify_url','overtime','ext1']);
    }

    public function sceneGame()
    {
        return $this->only(['orderno','itemId','checkItemFacePrice','number','amt','notify_url','overtime','ext1','ext2','ext3']);
    }

    protected function isApi($value,$rule,$data){
        $apilb=ApiBase::getUser('cashapi');
        if(!$apilb)return "该接口暂未开通";
        if(CashOrder::where(['tmporder'=>$value])->find())return "订单号不能重复";
        return true;
    }

    protected function isIP($value,$rule,$data){
        $apiip=ApiBase::getUser('ip');
        if(!$apiip)return "IP白名单未设置";
        $ipArr=explode(",",$apiip);
        if(!in_array($data['ip'],$ipArr) && $data['ip']!="127.0.0.1")return "拒绝服务[{$data['ip']}]";
        return true;
    }

    protected function isAmt($value,$rule,$data){
        if($value!=1)return "Amt购买数量只能填1";
        return true;
    }


    protected function isMoneyIden($value,$rule,$data){
        $geway=ApiBase::$sellList;
        if($geway['status']!=1){
            return "通道关闭";
        }
        $uid=ApiBase::getUser('id');
        $sellid= ApiBase::$sellList['id'];
        $status=SellUser::where(['uid'=>$uid,'sellid'=>$sellid])->value('status');
        if($status!=1){
            return "用户通道关闭";
        }
        if($geway && $geway['mianzhi']==$value){
            return true;
        }else{
            return "面值与产品不符";
        }
    }



}
