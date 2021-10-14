<?php
namespace app\merchants\middleware;

use app\common\model\User;

class Before
{
    private $postfilter = "'|\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|\\s*script\\b|\\bEXEC\\b|eval|asp|php|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
    
    public function handle($request, \Closure $next)
    {
		$data=input();
		$ok=true;
		foreach($data as $key=>$value){
			$ok=$this->stopattack($key,$value,$this->postfilter);
			if(!$ok)break;
		}
        if(!$ok){
            logtErr(json_encode($data));
			if(session("?user_auth.user_id")){
				User::where(['id'=>session("user_auth.user_id")])->update(['status'=>0]);
				insert_user_log(2,'非法输入强制退出系统');
				session('user_auth', null);
				session('user_auth_sign', null);
			}
			
			if(request()->isPost()){
				return json(['confirm'=>['name'=> "非法操作！", 'width'=>400, 'prompt'=> "warning",'time'=>1,'url'=>'/'],'content'=>'非法操作！']);
			}
            return redirect('index/miss');
        }

       return $next($request);

    }

	
	/**
	* 参数检查并写日志
	*/
	public function stopattack($StrFiltKey, $StrFiltValue, $ArrFiltReq){
		if(is_array($StrFiltValue))$StrFiltValue = implode($StrFiltValue);
		if(preg_match("/".$ArrFiltReq."/is",$StrFiltValue) == 1){
			$str=request()->ip()." ".strftime("%Y-%m-%d %H:%M:%S")." ".$_SERVER["PHP_SELF"]." ".$_SERVER["REQUEST_METHOD"]." ".$StrFiltKey." ".$StrFiltValue;
			trace($str, 'error');
		   return false;
		}
		return true;
	}
}