<?php
namespace app\common\model;

use think\Model;


class SellUser extends Model{


    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $type = [
        'update_time'=>'timestamp'
    ];

    public function gengxin($id){
        $ulist=$this->alias("a")->join("user b",'a.uid=b.id and b.rategroup='.$id.' and b.type=1')->field('a.id,a.sellid')->select();
        $map=[];
        foreach($ulist as $k=>$v){
            $map[$k]['id']=$v['id'];
            $map[$k]['rate']=UserFeilvList::where(['bid'=>$id,'sellid'=>$v['sellid']])->value('rate');
            $map[$k]['status']=UserFeilvList::where(['bid'=>$id,'sellid'=>$v['sellid']])->value('status');
        }
        $this->saveAll($map);
    }

    public static function addRate($uid=""){
        $list=SellList::select();
        $arr=[];
        foreach($list as $key=>$v){
            $arr[$key]['uid']=$uid;
            $arr[$key]['sellid']=$v['id'];
            $arr[$key]['rate']=$v['rate'];
        }
        (new SellUser)->saveAll($arr);
    }

    public function prolie(){
        return $this->belongsTo('SellList', 'sellid','id')->bind(['title','mianzhi','geway']);
    }

}

