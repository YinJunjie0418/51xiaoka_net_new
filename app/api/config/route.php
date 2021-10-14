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

Route::post('tocard', 'api/Index/index');

Route::post('blindSearch', 'api/Index/blindSearch');

Route::rule('callback', 'api/Index/callback');

Route::rule("cardback",'api/Index/webback');

Route::rule('queryBalance','api/UnicomMy/queryBalance');

Route::rule('queryBizOrder','api/UnicomMy/queryBizOrder');

Route::rule('queryBuy','api/UnicomMy/queryBuy');

Route::miss('api/Index/miss');
