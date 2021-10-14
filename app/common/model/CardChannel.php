<?php
namespace app\common\model;

use think\Model;


class CardChannel extends Model
{
    protected $defaultSoftDelete = 0;
    protected $type = [
	        'update_time'=>'timestamp'
    ];

	public static function onUpdate($data){
	    try{
            if(CardList::find($data['id'])){
                if(count($data['content'])==0)return "没有提交数据";
                $arr=[];
                foreach($data['content'] as $item=>$value){
                    if($res=self::where(['listid'=>$data['id'],'mianzhi'=>$value['mianzhi']])->find()){
                        $arr[$item]['id']=$res['id'];
                        $arr[$item]['type']=$value['type'];
                        $arr[$item]['feilv']=$value['feilv'];
                    }else{
                        $arr[$item]['listid']=$data['id'];
                        $arr[$item]['type']=$value['type'];
                        $arr[$item]['mianzhi']=$value['mianzhi'];
                        $arr[$item]['feilv']=$value['feilv'];
                    }
                }
                (new CardChannel)->saveAll($arr);
                if(isset($data['geng']))UserRate::upRate($data['id']);
                return true;
            }else{
                return "id错误";
            }
        }catch (\think\Exception $e){
	        return $e->getMessage();
        }
	}

    public static function onAfterDelete($data){
        UserShopList::where(['listid'=>$data['listid'],'mianzhi'=>$data['mianzhi']])->delete();
        UserRate::where(['listid'=>$data['listid'],'mianzhi'=>$data['mianzhi']])->delete();
    }


    public static function onAfterInsert($data){
        $userlist=User::select();
        foreach($userlist as $item=>$val){
            $map[$item]['uid']=$val['id'];
            $map[$item]['listid']=$data['listid'];
            $map[$item]['mianzhi']=$data['mianzhi'];
            $map[$item]['feilv']=$data['feilv'];
        }
        (new UserRate)->saveAll($map);

        $rategrouplist=UserShopFeilv::select();
        foreach($rategrouplist as $k=>$v){
            $map[$k]['bid']=$v['id'];
            $map[$k]['listid']=$data['listid'];
            $map[$k]['mianzhi']=$data['mianzhi'];
            $map[$k]['feilv']=$data['feilv'];
        }
        (new UserShopList)->saveAll($map);
    }
	
	 public function protle()
    {
        return $this->hasOne(CardOperator::class, 'id', 'cid')->bind(['name']);
    }  
	
	
   
}