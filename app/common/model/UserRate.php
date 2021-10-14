<?php
namespace app\common\model;

use think\Model;

class UserRate extends Model
{
	protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
	        'update_time'=>'timestamp'
    ];
	 
    public static function addRate($uid=""){
        $list=CardChannel::select();
        $arr=[];
        foreach($list as $key=>$v){
            $arr[$key]['uid']=$uid;
            $arr[$key]['listid']=$v['listid'];
            $arr[$key]['mianzhi']=$v['mianzhi'];
            $arr[$key]['feilv']=$v['feilv'];
        }
        (new UserRate)->saveAll($arr);
	}

	public static function upRate($id=''){
        if($id){
            $list=CardChannel::where('listid','in',$id)->select();
        }else{
            $list=CardChannel::select();
        }
        foreach($list as $item=>$val){
            try{
                    $result = self::where(['listid' => $val['listid'], 'mianzhi' => $val['mianzhi']])->selectOrFail();
                    $arr = [];
                    foreach ($result as $k => $v) {
                        $arr[$k]['id'] = $v['id'];
                        $arr[$k]['feilv'] = $val['feilv'];
                    }
                    (new UserRate)->saveAll($arr);
            }catch (\Exception $e){
                $arr=[];
                $ulist=User::select();
                foreach($ulist as $key=>$value){
                    $arr[$key]['uid']=$value['id'];
                    $arr[$key]['listid']=$val['listid'];
                    $arr[$key]['type']=$val['type'];
                    $arr[$key]['mianzhi']=$val['mianzhi'];
                    $arr[$key]['feilv']=$val['feilv'];
                }
                (new UserRate)->saveAll($arr);
            }
        }

    }

    public function gengxin($id){
        $ulist=$this->alias("a")->join("user b",'a.uid=b.id and b.rategroup='.$id.' and b.type=0')->field('a.id,a.listid,a.mianzhi')->select();
        $map=[];
        foreach($ulist as $k=>$v){
            $map[$k]['id']=$v['id'];
            $res=UserShopList::where(['bid'=>$id,'listid'=>$v['listid'],'mianzhi'=>$v['mianzhi']])->find();
            $map[$k]['feilv']=$res['feilv'];
            $map[$k]['status']=$res['status'];
        }
        $this->saveAll($map);
    }
	
	
}