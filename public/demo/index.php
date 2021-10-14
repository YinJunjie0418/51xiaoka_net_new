<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/style.css">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<title>卡类支付接口</title>
</head>
<body>
<br>
<?php
    session_start();
    function order($str=''){
        return $str.time().substr(microtime(),2,6).rand(0,9);
    }
?>
<style>
    input{
        width: 300px;
    }
    input[type=button]{
        width: 120px;
    }
</style>
<h2>设置参数</h2>
<form name="config" id="config" action="pay.php?type=config" method="POST">
    <table>
        <tr>
            <td>
                <font color=red>*</font>商户ID
            </td>

            <td>
                <input type="text" name="shopid" value="<?php echo isset($_SESSION['pid'])?$_SESSION['pid']:""?>" maxlength="20"> &nbsp;
            </td>
        </tr>
        <tr>
            <td>
                <font color=red>*</font>3DesKey
            </td>

            <td>
                <input type="text" name="aeskey"  value="<?php echo isset($_SESSION['aeskey'])?$_SESSION['aeskey']:""?>" maxlength="20">
            </td>
        </tr>
        <tr>
            <td>
                <font color=red>*</font>ApiKey
            </td>

            <td>
                <input type="text" name="md5" value="<?php echo isset($_SESSION['md5key'])?$_SESSION['md5key']:""?>"  > &nbsp;
            </td>
        </tr>
		<tr>
            <td>
                <font color=red>*</font>提交地址
            </td>

            <td>
                <input type="text" name="url" value="<?php echo isset($_SESSION['url'])?$_SESSION['url']:""?>"  > &nbsp;
            </td>
        </tr>

    </table>
    <input type='button' value='设置参数' onClick='setconfig()'>
</form >
<h2>寄售业务（寄售API）</h2>
<form name="createOrder" action="pay.php?type=1" method="POST">
	<table>

		<tr>
			<td>
				<font color=red>*</font>订单号
			</td>

			<td>
                    <input type="text" name="orderId" value="<?php echo order("C");?>" maxlength="20"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>点卡面值
			</td>

			<td>
                    <input type="text" name="amount" value="20" maxlength="20"> &nbsp;(不可以是小数)
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>点卡类型
			</td>

			<td>
                    <input type="text" name="productCode" value="12" > &nbsp;
            </td>
		</tr>
		
		<tr>
			<td>
				<font color=red>*</font>点卡卡号
			</td>

			<td>
                    <input type="text" name="cardNumber" value="12345888" > &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>点卡密码
			</td>

			<td>
                    <input type="text" name="cardPassword" value="dsfsdfsdfs" > &nbsp;
            </td>
		</tr>
		
		
		<tr>
			<td>
				<font color=red>*</font>异步通知地址
			</td>

			<td>
                    <input type="text" name="notify_url" value="http://localhost/pay/index.php" maxlength="100"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>自定义参数
			</td>

			<td>
                    <input type="text" name="custom" value="" maxlength="30"> 
            </td>
		</tr>
		
		
	
	</table>
	<input type='button' value='提交订单' onClick='document.createOrder.submit()'>
</form >
<h2 >查询业务（寄售API）</h2>
<div>
<form name="Order" action="pay.php?type=blindSearch" method="POST">

        <tr>
			<td>
				<font color=red>*</font>订单号
			</td>

			<td>
                    <input type="text" name="orderId" value="<?php echo order("C");?>" maxlength="20"> &nbsp;
            </td>
		</tr>
		<input type='button' value='提交订单' onClick='document.Order.submit()'>
</form>
</div>
<h2 >查询余额（充值API）</h2>
<div>
<form name="yuer" action="pay.php?type=yuer" method="POST">
   
		<input type='button' value='查询余额' onClick='document.yuer.submit()'>
</form>
</div>

<h2 >查询订单（充值API）</h2>
<div>
<form name="cha" action="pay.php?type=cha" method="POST">
   
        <tr>
			<td>
				<font color=red>*</font>订单号
			</td>

			<td>
                    <input type="text" name="orderno" value="" maxlength="20"> &nbsp;
            </td>
		</tr>
		<input type='button' value='查询订单' onClick='document.cha.submit()'>
</form>
</div>
<h2>充值业务（寄售API）</h2>
<form name="pay" action="pay.php?type=pay" method="POST">
	<table>
       

		<tr>
			<td>
				<font color=red>*</font>订单号
			</td>

			<td>
                    <input type="text" name="orderno" value="<?php echo order("C");?>" maxlength="20"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>充值金额
			</td>

			<td>
                    <input type="text" name="checkItemFacePrice" value="100" maxlength="20"> &nbsp;(不可以是小数)
            </td>
		</tr>
			<tr>
			<td>
				<font color=red>*</font>金额校验
			</td>

			<td>
                    <input type="text" name="decimal" value="100" maxlength="20"> &nbsp;(不传不校验)
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>充值产品
			</td>

			<td>
                    <input type="text" name="itemId" value="00lt100" > &nbsp;
            </td>
		</tr>
		
		<tr>
			<td>
				<font color=red>*</font>充值号码
			</td>

			<td>
                    <input type="text" name="number" value="13340770596" > &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>充值数量
			</td>

			<td>
                    <input type="text" name="amt" value="1" > &nbsp;
            </td>
		</tr>
		
		
		<tr>
			<td>
				<font color=red>*</font>异步通知地址
			</td>

			<td>
                    <input type="text" name="notify_url" value="http://z.368ys.cn/ceshiback.html" maxlength="100"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>等待时间
			</td>

			<td>
                    <input type="text" name="overtime" value="0" maxlength="30"> 
            </td>
		</tr>
		
		<tr>
			<td>
				<font color=red>*</font>自定义参数
			</td>

			<td>
                    <input type="text" name="ext1" value="" maxlength="30"> 
            </td>
		</tr>
		
		
	
	</table>
	<input type='button' value='提交订单' onClick='document.pay.submit()'>
</form >
<script>
    function setconfig(){
        var form=$("form[name=config]");
        $.post("pay.php?type=config",form.serialize(),function (e) {
            alert(e);
        })
    }

</script>
</body>
</html>