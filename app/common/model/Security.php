<?php
namespace app\common\model;
use think\Model;

class Security extends Model
{
    public static function onBeforeUpdate($data){
		$datakey=self::where(['id'=>$data['id']])->value('datakey');
		if($data['datakey']!=$datakey){
			self::uporder("order",$datakey,$data['datakey']);
			self::uporder("apiorder",$datakey,$data['datakey']);
		}
	}
	public static function uporder($order="order",$oldkey,$newkey){
		if($order=="order"){$or=new Order;}else{$or=new Apiorder;}
		    $order=$or::select();
			$card=new Card($oldkey);
			$cardb=new Card($newkey);
			$map=[];
			foreach($order as $k=>$v){
				$v=$v->getData();
				$jie=$card->decrypt($v['card_no']);
				if($jie===false)continue;
				$jieb=$card->decrypt($v['card_key']);
				$rty=$cardb->encrypt($jie);
				$id=$v['id'];
				$map[$k]['card_no']=$cardb->encrypt($jie);
				$map[$k]['card_key']=$cardb->encrypt($jieb);
				$or->where(['id'=>$id])->update($map[$k]);
			}
	}
}