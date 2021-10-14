<?php
namespace app\common\wxfaceld;


use GuzzleHttp\Client;

class sign {


    public function signTC3($str)
    {
        $str=$this->createLinkstring($str);
        return sha1($str);
    }



    public function show($msg){
        return ['code'=>-1,'msg'=>$msg];
    }

    public function toRequest($url,$data,$method='post'){
        if($method=="post"){
            $data['sign']=$this->signTC3($data);
            $resquest=(new client())->request($method,$url,
                ['headers'=>['Content-Type'=>'application/json']
                    ,'json' => $data]);
        }else{
            $url=$url."?".http_build_query($data);
            $resquest=(new client())->request($method,$url,
                ['form_params' => $data]);
        }
        return $resquest->getBody()->getContents();
    }
    public function paraFilter($para){
        $para_filter = array();
        foreach ($para as $key=>$val){
            if($key == "sign" || $key=="Sign" || $val === "" || $key=="s")continue;
            else
                $para_filter[] = $para[$key];
        }
        return $para_filter;
    }
    public function createLinkstring($prestr) {
        $arr_test=$this->paraFilter($prestr);
        $arr_test = array_values($arr_test);
        asort($arr_test);
        $arr_test =implode('',$arr_test);
        return $arr_test;
    }
}
