<?php

namespace app\common\alipay;


class Cashwith{

    public function withdr($orderid,$userid='',$money='',$remarks='',$user=''){
        try{
            $pei=\think\facade\Config::load('setting/alipay','alipay');
            $appCertPath = root_path('config').$pei['appCertPublicKey'];
            $alipayCertPath = root_path('config').$pei['alipayCertPublicKey'];
            $rootCertPath = root_path('config').$pei['alipayRootCert'];
            $aop = new AopCertClient();
            $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
            $aop->appId = $pei['appid'];
            $aop->rsaPrivateKey = $pei['alimach'];
            $aop->format = "json";
            $aop->charset= "utf-8";
            $aop->signType= "RSA2";
            //调用getPublicKey从支付宝公钥证书中提取公钥
            $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
            //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
            $aop->isCheckAlipayPublicCert = true;
            //调用getCertSN获取证书序列号
            $aop->appCertSN = $aop->getCertSN($appCertPath);
            //调用getRootCertSN获取支付宝根证书序列号
            $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);
            $request = new AlipayFundTransUniTransferRequest();
            $out_biz_no = $orderid;    //订单号
            $request->setBizContent("{" .
                "\"out_biz_no\":\"".$out_biz_no."\"," .
                "\"product_code\":\"TRANS_ACCOUNT_NO_PWD\"," .
                "\"trans_amount\":\"".$money."\"," .
                "\"biz_scene\":\"DIRECT_TRANSFER\"," .
                "\"order_title\":\"".$pei['bei']."\"," .
                "\"remark\":\"".$pei['remarks']."\"," .
                "\"payee_info\":{" .
                "\"identity\":\"".$userid."\"," .
                "\"identity_type\":\"ALIPAY_LOGON_ID\"," .
                "\"name\":\"".$user."\"" .
                "    }" .
                "  }");
            $result = $aop->execute($request);
            $result = json_decode(json_encode($result->alipay_fund_trans_uni_transfer_response),true);
            if($result['code']== 10000 && $result['msg']=='Success'){
                $data['code'] = 1;
                $data['msg'] = '提现成功';
                $data['out_biz_no'] = $out_biz_no;
                return  $data;
            }else{
                $data['code'] = 0;
                $data['msg'] = $result['sub_msg'];
                return $data;
            }
        }catch (\Exception $e) {
            $data['code'] = 0;
            $data['msg'] = $e->getMessage();
            return $data;
        }
    }

	
	public function getMoney(){
            $pei=\think\facade\Config::load('setting/alipay','alipay');
            $appCertPath = root_path('config').$pei['appCertPublicKey'];
            $alipayCertPath = root_path('config').$pei['alipayCertPublicKey'];
            $rootCertPath = root_path('config').$pei['alipayRootCert'];
            $aop = new AopCertClient();
            $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
            $aop->appId = $pei['appid'];
            $aop->rsaPrivateKey = $pei['alimach'];
            $aop->format = "xml";
            $aop->charset= "utf-8";
            $aop->signType= "RSA2";
            //调用getPublicKey从支付宝公钥证书中提取公钥
            $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
            //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
            $aop->isCheckAlipayPublicCert = true;
            //调用getCertSN获取证书序列号
            $aop->appCertSN = $aop->getCertSN($appCertPath);
            //调用getRootCertSN获取支付宝根证书序列号
            $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);
            $request = new AlipayFundAccountQueryRequest ();
            if(!isset($pei['userid']))return "请填写支付宝Userid";
            $request->setBizContent("{" .
    		"\"alipay_user_id\":\"".$pei['userid']."\"," .
    		"\"account_type\":\"ACCTRANS_ACCOUNT\"" .
    		"  }");
           	$result = $aop->execute ($request);
    		trace($result,'log');
    		$result = json_decode(json_encode($result),true);
    		if($result['msg']=="Success" && $result['code'] == 10000){
    			return $result['available_amount'];
    		} else{
    			return $result['msg'];
    		}
        
	}
	
	
	

}