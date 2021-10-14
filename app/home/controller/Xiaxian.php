<?php
declare (strict_types = 1);

namespace app\home\controller;
use app\common\controller\IndexBase;

use think\Request;

class Xiaxian extends IndexBase
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return view();
    }

   
}
