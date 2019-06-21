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
            $num = array_sum($array);
            $rand = rand(1,$num);
            $add2 = [
                'openid'=>$openid,
                'type'=>$type,
                'module'=>'ewei_shopv2',
                'num'=>$money==0?0:-$money,
                'uniacid'=>$_W['uniacid'],
                'createtime'=>time(),
                'remark'=>'幸运转盘抽奖',
            ];
            if($data['type'] == 1){
                $add2['credittytpe'] = "credit1";
            }elseif ($data['type'] == 2){
                $add2['credittype'] = "credit3";
            }

            //如果不是免费抽奖  就减去对应的东西
            if($money != 0){
                $this->addcredit($openid,-$money,$type);
            }
            pdo_insert('mc_credits_record',$add2);
            pdo_insert('ewei_shop_member_credit_record',$add2);
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    preg_match('/\d+/',$key,$arr);
                    $add1 = [
                        'num'=>$arr[0],
                        'type'=>1,
                        'reward'=>"抽中".$key,
                        'createtime'=>time(),
                    ];
                    //array_merge($a,$b)  当$a,$b有共同的键  后者后覆盖前者
                    $add = array_merge($add2,$add1);
                    //如果$data['type']  == 1 就是卡路里   == 2 就是折扣宝
                    $this->addcredit($openid,$arr[0],$type);
                    pdo_insert('mc_credits_record',$add);
                    pdo_insert('ewei_shop_member_credit_record',$add);
                    return $key;
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
}