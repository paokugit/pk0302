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
                $array[$key.'_'.$val['reward'.($key+1)]] = $val['rate'.($key+1)] *100;
            }
            asort($array);
            //计算总额
            $num = array_sum($array);
            $rand = rand(1,$num);
            //扣除抽奖的钱的日志  $type == 2免费  $type == 0 花钱
            $this->addlog($openid,$type,-$money,$data['game_type'],'幸运转盘抽奖');
            //抽奖  就减去对应的东西
            $this->addcredit($openid,$money,'sub',$data['game_type']);
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    $item = explode('_',$key);
                    preg_match('/\d+/',$item[1],$arr);
                    //添加中奖日志
                    $this->addlog($openid,1,$arr[0],$data['game_type'],"抽中".$item[1]);
                    //如果$data['type']  == 1 就是卡路里   == 2 就是折扣宝
                    $this->addcredit($openid,$arr[0],"add",$data['game_type']);
                    return ['location'=>$item[0],'num'=>$arr[0]];
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
     * @param $add
     * @param $game_type
     */
      public function addcredit($openid,$money,$add,$game_type){
          $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
          //如果是减得话  说明是抽奖  抽奖只能用卡路里  所以 减卡路里
          if($add == "sub"){
              $credit = $member['credit1'] - $money;
              pdo_update('ewei_shop_member',['credit1'=>$credit],['openid'=>$openid]);
          }elseif($add == "add"){
              //如果是加的话  就是中奖 中奖分卡路里 和 折扣宝
              if($game_type == 1){
                  $credit = $member["credit1"] + $money;
                  pdo_update('ewei_shop_member',['credit1'=>$credit],['openid'=>$openid]);
              }elseif ($game_type == 2){
                  $credit = $member["credit3"] + $money;
                  pdo_update('ewei_shop_member',['credit3'=>$credit],['openid'=>$openid]);
              }
          }
      }

    /**
     * 添加日志
     * @param $openid
     * @param $type  $type == 0普通的开支   1中奖   2免费抽奖
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
              'credittype'=>"credit1"
          ];
          //如果是中奖的话  给加中奖日志 也就是加如果转盘是折扣宝转盘 就加折扣宝  卡路里转盘 就加卡路里
          if($type == 1){
              if($datatype == 1){
                  $add['credittytpe'] = "credit1";
              }elseif ($datatype == 2){
                  $add['credittype'] = "credit3";
              }
          }
          pdo_insert('mc_credits_record',$add);
          pdo_insert('ewei_shop_member_credit_record',$add);
      }

    /**
     * @param $openid
     * @param $credittype
     * @param $money
     * @param $remark
     */
      public function addCreditLog($openid,$credittype,$money,$remark)
      {
          global $_W;
          $add = [
              'openid'=>$openid,
              'module'=>'ewei_shopv2',
              'num'=>$money==0?0:$money,
              'uniacid'=>$_W['uniacid'],
              'createtime'=>time(),
              'remark'=>$remark,
              'credittype'=>"credit".$credittype,
          ];
          pdo_insert('mc_credits_record',$add);
          pdo_insert('ewei_shop_member_credit_record',$add);
      }
}