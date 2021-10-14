<?php
namespace app\api\validate;

use app\api\model\ApiBase;
use app\common\model\CardList;
use app\common\model\Order;
use app\common\model\Security;
use app\common\model\UserRate;
use think\Validate;



class Apicheck extends Validate
{
    private $cardno;
    private $cardkey;
    private $dekey;

    protected $regex = ['url' => '/^http(s)?:\\/\\/.+/'];

    protected $rule = [
        'customerId' => 'require|isUser:customerId|isApi:customerId',
        'sign'=>'require',
        'orderId' => 'require|length:5,40',
        'batchno' => 'require|length:5,40',
        'productCode'=>'require|length:1,8|isType:productCode',
        'amount'=>'require|length:1,4',
        'notify_url'=>'require|regex:url|length:5,100',
        'cardNumber'=>'require|length:5,100|CardnoDecrypt:cardNumber|cardno:cardNumber',
        'cardPassword'=>'require|length:5,100|CardDecrypt:cardPassword'


    ];

    protected $message = [

    ];

    public function sceneBlindSearch()
    {
        return $this->only(['customerId','orderId','sign']);

    }

    public function sceneIndex()
    {
        return $this->only(['customerId','orderId','batchno','amount','notify_url','cardNumber','cardPassword','productCode','sign']);

    }

    protected function isUser($value,$rule,$data){
        $apilb=ApiBase::getUser('apides');
        if(!$apilb)return "无此商户";
        $this->dekey=$apilb;
        return true;
    }

    protected function isApi($value,$rule,$data){
        $apilb=ApiBase::getUser('apilib');
        if(!$apilb)return "该接口暂未开通";
        return true;
    }

    protected function isType($value,$rule,$data){
        if($card=CardList::where(['type'=>$value])->find()){
            if($card['status']!=1) return '接口维护';
            $uid=ApiBase::getUser('id');
            $listid=CardList::where(['type'=>$data['productCode']])->value('id');
            $urate=UserRate::where(['uid'=>$uid,'listid'=>$listid,'mianzhi'=>$data['amount']])->find();
            if(!$urate)return "商品不存在";
				switch($urate['status']){
					case 2:
						return '该接口已被禁用';
						break;
					case 0:
						return '商户关闭接口';
						break;
					case 1:
						return true;
						break;
				}
        }else{
            return "卡类型不存在";
        }
    }


    protected function CheckSign($value,$rule,$data){
        $ok=md5Verify($data,ApiBase::getUser('apikey'),$data['sign']);
        if(!$ok)return "SIGN错误";
        return true;
    }

    protected function CardnoDecrypt($value,$rule,$data){
            $number=(new ApiBase)->isdecode($this->dekey,$value);
            if(!$number)return "卡号解密失败";
            $this->cardno=$number;
            return true;
    }
    protected function CardDecrypt($value,$rule,$data){
        $number=(new ApiBase)->isdecode($this->dekey,$value);
        if(!$number)return "卡密解密失败";
        $this->cardkey=$number;
        return true;
    }

    protected function cardno($value){
        $number=Order::enCardno($this->cardno);
        $ok=Order::where(['card_no'=>$number])->where('state','<>',3)->find();
        if($ok)return "此卡已在处理";
        return true;
    }



}
