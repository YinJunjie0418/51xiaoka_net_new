<?php

namespace app\common\model;

use think\Model;

class AuthGroupAccess extends Model
{
    public function authGroup()
    {
        return $this->belongsTo(AuthGroup::class, 'group_id', 'id')->bind(['rules']);
    }
}