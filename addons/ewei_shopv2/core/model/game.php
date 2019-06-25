<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Game_EweiShopV2Model{
    /**
     * @param $data
     * @param $type
     * @param $openid
     * @param $money
     * @return int|string
     */
      public function prize($data,$type,$openid,$money)
      {
            $array = [];
            foreach (iunserializer($data['sets']) as $key=>$val){
                $array[$val['reward'.($key+1)]] = $val['rate'.($key+1)] *100;
            }
            asort($array);
            //计算总额
            $num = array_sum($array);
            $rand = rand(1,$num);
            //扣除抽奖的钱的日志
            $this->addlog($openid,$type,-$money,$data['game_type'],'幸运转盘抽奖');
            //如果不是免费抽奖  就减去对应的东西
            if($money != 0){
                $this->addcredit($openid,-$money,$data['game_type']);
            }
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    preg_match('/\d+/',$key,$arr);
                    //添加中奖日志
                    $this->addlog($openid,1,$arr[0],$data['game_type'],"抽中".$key);
                    //如果$data['type']  == 1 就是卡路里   == 2 就是折扣宝
                    $this->addcredit($openid,$arr[0],$data['game_type']);
                    return $arr[0];
                    break;
                }else{
                    $rand -= $value;
                }
            }
      }

    /**
     * 更新用户的卡路里或者折扣宝
     * @param $openid
     * @param $money
     * @param $type
     */
      public function addcredit($openid,$money,$type){
          $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
          if($type == 1){
              $credit = $member['credit1'] + $money;
              pdo_update('ewei_shop_member',['credit1'=>$credit],['openid'=>$openid]);
          }elseif ($type == 2){
              $credit = $member['credit3'] + $money;
              pdo_update('ewei_shop_member',['credit3'=>$credit],['openid'=>$openid]);
          }
      }

    /**
     * 添加日志
     * @param $openid
     * @param $type
     * @param $money
     * @param $datatype
     * @param $remark
     */
      public function addlog($openid,$type,$money,$datatype,$remark)
      {
          global $_W;
          $add = [
              'openid'=>$openid,
              'type'=>$type,
              'module'=>'ewei_shopv2',
              'num'=>$money==0?0:$money,
              'uniacid'=>$_W['uniacid'],
              'createtime'=>time(),
              'remark'=>$remark,
              'credittytpe'=>"credit1"
          ];
	  //原本的设想是  卡路里转盘  用卡路里抽奖 奖励卡路里  折扣宝抽奖  用折扣宝抽奖  奖励折扣宝
//          if($datatype == 1){
//              $add['credittytpe'] = "credit1";
//          }elseif ($datatype == 2){
//              $add['credittype'] = "credit3";
//          }
          pdo_insert('mc_credits_record',$add);
          pdo_insert('ewei_shop_member_credit_record',$add);
      }
}