<?php
namespace app\api\validate;

use app\api\model\ApiBase;
use think\Validate;

class ApiPubCheck extends Validate{
    private $key;
    protected $rule = [
        'customerId' => 'require|number',
        'timestamp'=>'require|length:10',
        'sign'=>'require|length:32'
        ];
    protected $message = [

    ];

    public function scenePubcheck()
    {
        return $this->only(['customerId','timestamp','sign']);

    }


}
