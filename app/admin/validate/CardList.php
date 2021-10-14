<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\CardList as cd;

class CardList extends Validate
{
    protected $rule = [
        'title' => 'require',
		'type' => 'require|unique:cardList',
		'iconurl'=>'require',
		'phoneRecycleIcon'=>'require'
    ];

    protected $message = [
        'title.require' => '分类名称不能为空',
        'type.require' => '通道标识不能为空',
		'type.unique' => '通道标识已经存在',
		'iconurl.require'=>'点卡图标不能为空',
		'phoneRecycleIcon.require'=>'点卡图标不能为空'
    ];
	
}
