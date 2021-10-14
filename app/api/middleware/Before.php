<?php
namespace app\api\middleware;

use app\api\model\ApiBase;

class Before
{

    public function handle($request, \Closure $next)
    {
        $accetp = $request->header('Accept');

        if(strstr($accetp,"application/json")===false){
            $request->isSync=false;
        }else{
            $request->isSync=true;
        }
        $response= $next($request);
        return $response;
    }

}