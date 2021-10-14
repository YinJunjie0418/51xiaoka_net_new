<?php

namespace app\common\model;

use think\Model;

class Admin extends Model
{
    protected $autoWriteTimestamp = true;

    public function setPasswordAttr($value)
    {
        return md6($value);
    }

    public function authGroupAccess()
    {
        return $this->belongsTo(AuthGroupAccess::class, 'id', 'uid')->bind(['group_id']);
    }

    public function authGroup()
    {
        return $this->belongsTo(AuthGroup::class, 'group_id', 'id')->bind(['bname'=>'name']);
    }
    public function getLastLoginTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}