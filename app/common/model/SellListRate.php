<?php
namespace app\common\model;

use think\Model;

class SellListRate extends Model{
    public function bindTitle(){
        return $this->hasOne(CardList::class, 'id','bindid')->bind(['cardtitle'=>'title']);
    }
}
