<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;


Route::rule('/', 'merchants/Index/index');
Route::rule('getname', 'merchants/Index/getName');
Route::get("loginerr",'merchants/Index/loginErr');


Route::rule('apiback','merchants/Landedat/callback');
Route::rule('qqlogin','merchants/Landedat/qqlogin');
Route::rule('qqtologin','merchants/Landedat/qqtologin');
Route::rule('wxlogin','merchants/Landedat/wxlogin');
Route::rule('gongzhonghao','merchants/Gongzhaohao/index');
Route::rule('oauth_callback','merchants/Gongzhaohao/oauthcallback');
Route::rule('wxtologin','merchants/Gongzhaohao/wxtologin');
Route::rule('weixinback','merchants/Wechat/index');

Route::rule('faceindex','merchants/Wxface/index');
Route::rule('facequery','merchants/Wxface/facequery');
Route::rule("qianback","merchants/wxface/backqian");


Route::rule('assetslog','merchants/Member/assetslog');

Route::rule('cashindex','merchants/CashApi/index');
Route::rule('cashset','merchants/CashApi/setStatus');
Route::rule('cashconsign','merchants/CashApi/consign');
Route::rule('apistatistic896s',"merchants/CashApi/statistics");
Route::rule('apiexport',"merchants/CashApi/export");
Route::rule("apieditip","merchants/CashApi/geteditip");
Route::rule('sellpay','merchants/CashApi/sellpay');
Route::rule('sellmianzhi','merchants/CashApi/sellmianzhi');
Route::rule('loadmoney','home/CashApi/loadmoney');

Route::post('topay','merchants/Card/topay');

Route::rule("login",'merchants/Login/login');
Route::rule('logout','merchants/Login/logout');
Route::rule('forgetpassword','merchants/Login/forgetpassword');
Route::rule('logregister','merchants/Login/register');
Route::rule('signlogin','merchants/Login/kuanglogin');
Route::rule("faceback",'merchants/Login/faceback');
Route::rule("errreg",'merchants/Login/errreg');

Route::rule("sendmsg",'merchants/Api/sendMsg');
Route::rule("sendemail",'merchants/Api/sendEmail');
Route::rule("cardback",'merchants/Api/callback');
Route::rule('checkFace','merchants/Api/checkFace');


Route::get("user_index",'merchants/Member/index');
Route::get("user_pro458",'merchants/Member/profile');
Route::get("user_realname",'merchants/Member/realname');
Route::rule("user_password",'merchants/Member/password');
Route::rule("user_paymentcodepin","merchants/Member/paymentcodepin");
Route::get("user_photo","merchants/Member/photo");
Route::get("user_email","merchants/Member/email");
Route::post("checkpcode",'merchants/Member/checkPCode');
Route::post("checkecode",'merchants/Member/checkECode');
Route::post("checkpass",'merchants/Member/checkPass');
Route::rule("user_actqq","merchants/Member/addqq");
Route::rule("user_setcash","merchants/Member/setcash");
Route::rule("user_ali","merchants/Member/alipay");
Route::rule("user_wx","merchants/Member/weixin");
Route::get("memberlog","merchants/Member/memberlog");
Route::rule("userdelqq","merchants/Member/delqq");
Route::rule("yaoqinguser","merchants/Member/assets");
Route::rule("userdelwx","merchants/Member/delwx");
Route::rule("apiqian","merchants/Member/qian");
Route::rule("checkQian","merchants/Member/checkQian");
Route::rule("lookpic","merchants/Cash/lookpic");

Route::get('help_index', 'merchants/Helpfaq/index');
Route::get('help_danye/:id', 'merchants/Helpfaq/danye');
Route::get("business",'merchants/Helpfaq/business');
Route::get('help_help', 'merchants/Helpfaq/helpa');
Route::get('help_guide', 'merchants/Helpfaq/guide');
Route::get('help_aboutus', 'merchants/Helpfaq/aboutus');
Route::get('help_faq', 'merchants/Helpfaq/faq');
Route::get('help_agreement', 'merchants/Helpfaq/agreement');
Route::get('help_privacy', 'merchants/Helpfaq/privacy');
Route::get('help_adeclare', 'merchants/Helpfaq/adeclare');
Route::get('help_api', 'merchants/Helpfaq/api');
Route::get('help_protocol','merchants/Helpfaq/protocol');
Route::get("zixun","merchants/Helpfaq/zixun");




Route::rule("account_realname",'merchants/Account/actrealname');
Route::post("account_upimg",'merchants/Account/uploadImage');
Route::rule("account_enterprise",'merchants/Account/enterprise');
Route::rule('weixinname','merchants/Account/index');

Route::rule("account_setphoto","merchants/Mobile/setphoto");
Route::rule("account_upphoto","merchants/Mobile/upphoto");

Route::rule("account_setemail","merchants/Email/setEmail");
Route::rule("account_upemail","merchants/Email/upEmail");

Route::rule("edit_tradepwd","merchants/Editpass/index");



Route::rule("act_cash","merchants/Cash/index");
Route::rule("act_cashbank","merchants/Cash/bank");
Route::rule("act_cashwx","merchants/Cash/weixin");
Route::post("act_withdraw","merchants/Cash/withdraw");
Route::rule("act_cashrecords","merchants/Cash/cashrecords");

Route::rule("api_index","merchants/Apiface/apiindex");
Route::rule("api_miyao","merchants/Apiface/getmiyao");
Route::rule("api_editmiyao","merchants/Apiface/geteditmi");
Route::post("api_setStatus","merchants/Apiface/setStatus");
Route::get("api_consign","merchants/Apiface/consign");
Route::get("api_export","merchants/Apiface/export");
Route::get("api_statis","merchants/Apiface/statistics");
Route::post("card_xiafa","merchants/Apiface/xiafa");
Route::rule('card_info','merchants/Apiface/selldetailinfo');

Route::miss('merchants/Index/miss');
