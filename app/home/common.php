<?php
use think\facade\Db;
/*
*批量订单状态
*
*/
function batchno($arr=""){
	$arr=explode(",",$arr);
	$str='<span class="text-primary">等待受理</span>';
    if(in_array(1,$arr) || in_array(4,$arr)){
        $str='<span class="text-danger">处理中</span>';
    }elseif(in_array(3,$arr) || in_array(7,$arr) || in_array(8,$arr)){
        $str='<span class="text-red">处理失败</span>';
    }elseif(in_array(2,$arr)){
        $str='<span class="text-success">处理成功</span>';
    }
	return $str;
}

function getArr($str){
    if($str instanceof think\response\Json){
        $str = json_decode($str->getContent(),true);

        return [$str['code'],$str['content']];
    }
    $data=str_replace("\r\n",',',$str);
	$data=str_replace("\n",',',$data);
	$data=str_replace("\r",',',$data);
	$xstr=explode(",",$data);
	return $xstr;
}


/*
*批量订单说明
*
*/
function batchRemaks($str=""){
	$arr=explode(',',$str);
	$arr=array_unique($arr);
	$arr=array_merge($arr);
	$tostr="";
	for($i=0;$i<count($arr);$i++){
		if( $arr[$i]=="" || $arr[$i]=='成功')continue;
		$tostr.="[{$arr[$i]}]";
	}
	return $tostr;
}