<?php
session_start();
include 'config.php';
include 'CardDes.php';

$data=$_POST;
$type=$_GET['type'];
$data['customerId']= $_SESSION['pid'];
$data['timestamp']=time();
$url=$_SESSION['url'];

switch($type){
	case 1:
		$card=new Card(base64_encode($_SESSION['aeskey']));

		$data['cardNumber']=$card->encrypt(isset($data['cardNumber'])?$data['cardNumber']:'');

		$data['cardPassword']=$card->encrypt(isset($data['cardPassword'])?$data['cardPassword']:'');
		
		$data['sign']=md5Verify($data,$_SESSION['md5key']);

		$result=vpost($url.URL,$data);

		echo $result;
	break;
	case 'blindSearch':
	    $data['sign']=md5Verify($data,$_SESSION['md5key']);

		$result=vpost($url.SearchUrl,$data);

		echo $result;
	break;
	case 'yuer':
	    $data['sign']=md5Verify($data,$_SESSION['md5key']);

		$result=vpost($url.SearchMoney,$data);

		echo $result;
	break;
	case 'cha':
	    $data['sign']=md5Verify($data,$_SESSION['md5key']);

		$result=vpost($url.BizOrder,$data);

		echo $result;
	break;
    case "config":
        if(empty($data['shopid']) || empty($data['aeskey']) || empty($data['md5'])){
            echo "请填写完整参数";
            exit;
        }

        $_SESSION["pid"]=$data['shopid'];
        $_SESSION["aeskey"]=$data['aeskey'];
        $_SESSION["md5key"]=$data['md5'];
		$_SESSION["url"]=$data['url'];
        echo "设置成功";
        break;
	default:
	    if($data['decimal']=="")unset($data['decimal']);
	     $data['sign']=md5Verify($data,$_SESSION['md5key']);

		$result=vpost($url.buy,$data);

		echo $result;
	 
}
