<?php
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class SellList extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $type = [
        'update_time'=>'timestamp',
        'apiid'=>'array'
    ];

    public static function onAfterInsert($data){
         $userlist=User::select();
		 $map=[];
         foreach($userlist as $item=>$val){
             $map[$item]['uid']=$val['id'];
             $map[$item]['sellid']=$data['id'];
             $map[$item]['rate']=$data['rate'];
         }
         (new SellUser)->saveAll($map);

         $rategrouplist=UserFeilvModel::select();
		 $mapa=[];
         foreach($rategrouplist as $k=>$v){
             $mapa[$k]['bid']=$v['id'];
             $mapa[$k]['sellid']=$data['id'];
             $mapa[$k]['rate']=$data['rate'];
         }
        (new UserFeilvList)->saveAll($mapa);
     }

     public static function onAfterDelete($data){
        SellUser::where(['sellid'=>$data['id']])->delete();
        UserFeilvList::where(['sellid'=>$data['id']])->delete();
     }

    public static function onBeforeUpdate($data){//更新前事件
        $olddata=self::find($data['id']);
        if($olddata['geway']!=$data['geway'] && $data['geway']!=''){
            Neworder::where(['fields'=>$olddata['geway'].$olddata['mianzhi']])->update(['fields'=>$data['geway'].$olddata['mianzhi']]);
        }
    }


    public function prolie(){
         return $this->belongsTo('sellListRate', 'id','listid')->bind(['bindid']);
     }
     
      public function operType(){
        return $this->hasOne(CardOperator::class,'id','operid')->bind(['name','class','apistate'=>'status']);
    }



}
