<?php
namespace app\common\model;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class CardList extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public static function onAfterDelete($data){
        UserShopList::where(['listid'=>$data['id']])->delete();
        UserRate::where(['listid'=>$data['id']])->delete();
        CardChannel::where(['listid'=>$data['id']])->delete();
        SellListRate::where(['bindid'=>$data['id']])->delete();
    }
	
    public static function onBeforeUpdate($data){
		if(isset($data['type'])){
			$type=self::where(['id'=>$data['id']])->value('type');
			if($type!=$data['type']){
				Order::update(['class'=>$data['type']],['class'=>$type]);
			}
		}
	}

	
	public function fenlei()
    {
        return $this->hasOne(CardModel::class, 'id', 'cid')->bind(['name'=>'title']);
    } 
	
	 public function models()
    {
        return $this->hasMany(CardChannel::class, 'listid', 'id');
    }    	
}