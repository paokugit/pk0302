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
            foreach ($data as $key=>$val){
                $array[$val['reward'.($key+1)]] = $val['rate'.($key+1)] *100;
            }
            asort($array);
            $num = array_sum($array);
            $rand = rand(1,$num);
            $add = [
                'openid'=>$openid,
                'money'=>$money,
                'type'=>$type,
                ''=>'',
            ];
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    pdo_insert('mc_credits_record',$add);
                    return $key;
                    break;
                }else{
                    $rand -= $value;
                }
            }

      }
}