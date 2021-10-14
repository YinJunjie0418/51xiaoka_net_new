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

Route::post("card_cancel","home/Sellcard/cancel");
Route::rule('/', 'home/Index/index');
Route::rule('getname', 'home/Index/getName');
Route::rule("cardtype",'home/Card/cardType');
Route::rule('gapidata','home/Card/gapiData');
Route::post('apimach','home/Card/mach');
Route::rule('apitocard','home/Card/tocard');
Route::get("loginerr",'home/Index/loginErr');
Route::get("zixun","home/Helpfaq/zixun");
Route::get('zhixing','home/Index/zhixing');

Route::rule('apiback','home/Landedat/callback');
Route::rule('qqlogin','home/Landedat/qqlogin');
Route::rule('qqtologin','home/Landedat/qqtologin');
Route::rule('wxlogin','home/Landedat/wxlogin');
Route::rule('gongzhonghao','home/Gongzhaohao/index');
Route::rule('oauth_callback','home/Gongzhaohao/oauthcallback');
Route::rule('wxtologin','home/Gongzhaohao/wxtologin');
Route::rule('weixinback','home/Wechat/index');

Route::rule('faceindex','home/Wxface/index');
Route::rule('facequery','home/Wxface/facequery');
Route::rule("qianback","home/wxface/backqian");

Route::rule('getregion','home/Bank/getregion');


Route::get('card', 'home/Card/index');
Route::get('xiaxian', 'home/Xiaxian/index');
Route::get('help_index', 'home/Helpfaq/index');
Route::get('help_danye/:id', 'home/Helpfaq/danye');
Route::get("business",'home/Helpfaq/business');
Route::get('help_help', 'home/Helpfaq/helpa');
Route::get('help_guide', 'home/Helpfaq/guide');
Route::get('help_aboutus', 'home/Helpfaq/aboutus');
Route::get('help_faq', 'home/Helpfaq/faq');
Route::get('help_agreement', 'home/Helpfaq/agreement');
Route::get('help_privacy', 'home/Helpfaq/privacy');
Route::get('help_adeclare', 'home/Helpfaq/adeclare');
Route::get('help_api', 'home/Helpfaq/api');
Route::get('help_protocol','home/Helpfaq/protocol');


Route::get("app",'home/Index/app');
Route::get("company",'home/Index/company');
Route::get("category",'home/Index/category');
Route::get("cooperation",'home/Index/cooperation');
Route::rule("feedback",'home/Index/feedback');
Route::rule('yaoqing','home/Index/accets');
Route::rule('feilv','home/Index/feilv');
Route::rule('assetslog','home/Member/assetslog');

Route::rule('cashindex','home/CashApi/index');
Route::rule('cashset','home/CashApi/setStatus');
Route::rule('cashconsign','home/CashApi/consign');
Route::rule('apistatistic896s',"home/CashApi/statistics");
Route::rule('apiexport',"home/CashApi/export");
Route::rule("apieditip","home/CashApi/geteditip");

Route::rule("login",'home/Login/login');
Route::rule('logout','home/Login/logout');
Route::rule('forgetpassword','home/Login/forgetpassword');
Route::rule('logregister','home/Login/register');
Route::rule('signlogin','home/Login/kuanglogin');
Route::rule("faceback",'home/Login/faceback');
Route::rule("errreg",'home/Login/errreg');

Route::rule("sendmsg",'home/Api/sendMsg');
Route::rule("sendemail",'home/Api/sendEmail');
Route::rule("cardback",'home/Api/callback');
Route::rule('checkFace','home/Api/checkFace');
Route::rule("ceshiback",'home/Api/ceshiback');


Route::get("user_index",'home/Member/index');
Route::get("user_pro458",'home/Member/profile');
Route::get("user_realname",'home/Member/realname');
Route::rule("user_password",'home/Member/password');
Route::rule("user_paymentcodepin","home/Member/paymentcodepin");
Route::get("user_photo","home/Member/photo");
Route::get("user_email","home/Member/email");
Route::post("checkpcode",'home/Member/checkPCode');
Route::post("checkecode",'home/Member/checkECode');
Route::post("checkpass",'home/Member/checkPass');
Route::rule("user_actqq","home/Member/addqq");
Route::rule("user_setcash","home/Member/setcash");
Route::rule("user_ali","home/Member/alipay");
Route::rule("user_wx","home/Member/weixin");
Route::get("memberlog","home/Member/memberlog");
Route::rule("userdelqq","home/Member/delqq");
Route::rule("yaoqinguser","home/Member/assets");
Route::rule("userdelwx","home/Member/delwx");
Route::rule("apiqian","home/Member/qian");
Route::rule("checkQian","home/Member/checkQian");

Route::get("userc","home/Sellcard/index");
Route::get("card_order","home/Sellcard/order");
Route::get("card_sell","home/Sellcard/selldetailinfo");
Route::get("card_statistics","home/Sellcard/statistics");
Route::get("card_export","home/Sellcard/export");
Route::get('reissued','home/Sellcard/reissued');


Route::post("account_upimg",'home/Account/uploadImage');
Route::rule("account_enterprise",'home/Account/enterprise');
Route::rule('weixinname','home/Account/index');


Route::rule("account_setphoto","home/Mobile/setphoto");
Route::rule("account_upphoto","home/Mobile/upphoto");

Route::rule("account_setemail","home/Email/setEmail");
Route::rule("account_upemail","home/Email/upEmail");

Route::rule("edit_tradepwd","home/Editpass/index");

Route::rule("bank_addbank","home/Bank/addbank");
Route::rule("bank_addali","home/Bank/addalipay");
Route::rule("bank_del","home/Bank/del");
Route::get("bank_addweixin","home/Bank/addweixin");
Route::post("bank_city","home/Bank/getcity");
Route::post("bank_code","home/Bank/getcode");



Route::rule("act_cash","home/Cash/index");
Route::rule("act_cashbank","home/Cash/bank");
Route::rule("act_cashwx","home/Cash/weixin");
Route::post("act_withdraw","home/Cash/withdraw");
Route::rule("act_cashrecords","home/Cash/cashrecords");

Route::rule("api_index","home/Apiface/apiindex");
Route::rule("api_miyao","home/Apiface/getmiyao");
Route::rule("api_editmiyao","home/Apiface/geteditmi");
Route::post("api_setStatus","home/Apiface/setStatus");
Route::get("api_consign","home/Apiface/consign");
Route::get("api_export","home/Apiface/export");
Route::get("api_statis","home/Apiface/statistics");
Route::post("card_xiafa","home/Apiface/xiafa");
Route::rule('card_info','home/Apiface/selldetailinfo');

Route::miss('home/Index/miss');
