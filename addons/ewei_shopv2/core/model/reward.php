<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Reward_EweiShopV2Model
{
    /**
     * 会员购买成功后给推荐人分佣金
     * @param $openid   购买人的opendid
     * @return bool
     */
	public function addReward($openid){
        global $_W;
        $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $openid ));
        if(!$memberInfo) return false;
        $res = $this->getReward($memberInfo['agentid'],$memberInfo['agentlevel'],$memberInfo['openid']);
        return $res;
    }

    /**
     * @param $agentid  推荐人
     * @param $memberlevel  被推荐人的等级
     * @param $memberopenid  被推荐人
     * @return bool
     */
    public function getReward($agentid,$memberlevel,$memberopenid){
        global $_W;
        $agentInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(  ":id" => $agentid ));
        if(!$agentInfo) return false;
        $rewardMoney = $this->getRewardMoney($agentInfo['agentlevel'],$memberlevel);// 奖励金额
        $shopOwner = $this->getShopOwnerAgent($agentid);//获取是否有上级店长
        if($rewardMoney>0){
            m('memberlog')->rewardMember($agentInfo['openid'],$rewardMoney,$memberopenid);//直推奖
        }
        if($shopOwner && $memberlevel<5 && $shopOwner!=$agentInfo['openid']){//有店长
            $ownerMoney = $this->shopOwnerMoney($memberlevel);
            if($ownerMoney>0) {
                m('memberlog')->rewardShowOwnerMember($shopOwner, $ownerMoney, $memberopenid);
            }
        }
        return true;

    }

    /**
     * 根据等级获取奖励金额
     * 店主和星选达人 推荐健康达人改为5元   健康达人推荐健康达人改为3元
     * @param $agentlevel
     * @param $memberlevel
     * @return int
     */
    public function getRewardMoney($agentlevel,$memberlevel){
        if($memberlevel==0) return 0;
	    if($agentlevel==0) return 0;
	    if($agentlevel==1) return 3;
	    if($agentlevel==2){
            if($memberlevel==1) return 5;
            if($memberlevel==5) return 100;
            if($memberlevel==2) return 40;
        }
	    if($agentlevel==3){
	        if($memberlevel==2) return 20;
	        if($memberlevel==1) return 2;
	        return 70;
        }
	    /*if($agentlevel==4){
	        if($memberlevel==3) return 70;
            if($memberlevel==2) return 20;
            if($memberlevel==1) return 2;
            return 280;
	    }*/
	    if($agentlevel==5){
            if($memberlevel==5) return 200;
            //if($memberlevel==4) return 70;
            //if($memberlevel==3) return 70;
            if($memberlevel==2) return 40;
            if($memberlevel==1) return 5;
            return 0;
        }
	    return 0;
    }

    /**
     * @param $memberlevel
     * @return int
     */
    public function shopOwnerMoney($memberlevel){
        switch ($memberlevel){
            case 0:
                return 0;
            case 1:
                return 1;
            case 2:
                return 10;
            case 3:
                return 35;
           // case 4:
           //     return 98;
            case 5:
                return 98;
        }
    }

    /**
     * 获取推荐人的上级店长
     * @param $agentid  推荐人
     * @return bool
     */
    public function getShopOwnerAgent2($agentid){
        while ($agentid>0){
            $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(":id" => $agentid));
            $agentid = $memberInfo['agentid'];
            if($agentid>0){
                $agentInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(":id" => $agentid));
                    if($agentInfo['agentlevel']==5){//店长
                        return $agentInfo['openid']; break;
                    }
            }
        }
        return false;
    }


    /**
     * 获取推荐人的上级店长
     */
    public function getShopOwnerAgent($agentid,$openid=''){
        $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(":id" => $agentid));
        if($memberInfo['agentlevel']==5){
            $openid = $memberInfo['openid'];
        }elseif ($memberInfo['agentlevel']>0){
            $openid = $this->getShopOwnerAgent($memberInfo['agentid'],$openid);
        }else{
            return false;
        }
        return $openid;
    }

     //判断是否是赏金
     public function good($goods_id){
         $goods = pdo_fetch("select * from " . tablename("ewei_shop_goods") . " where id=:id limit 1", array( ":id" => $goods_id ));
         //判断是否在赏金任务内
         $merchid=$goods["merchid"];
         if ($merchid==0){
             $status=0;
            
         }else{
             $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
             if ($merch["reward_type"]==0){
                 $status=0;
                
             }else{
                 if ($merch["reward_type"]==1){
                     //指定商品
                     //获取商家赏金
                     $reward=pdo_fetchall('select * from'.tablename('ewei_shop_merch_reward').'where is_end=0 and type=1 and merch_id=:merchid',array(':merchid'=>$merchid));
                     
                     $g=array();
                     if (!empty($reward)){
                         foreach ($reward as $k=>$v){
                             $g[$k]["reward_id"]=$v["id"];
                             $g[$k]["goodsid"]=unserialize($v["goodid"]);
                         }
                     }
                     if (!empty($g)){
                         $reward_id=m("merch")->order_good($g,$goods_id);
                         if ($reward_id){
                             $r=pdo_get("ewei_shop_merch_reward",array('id'=>$reward_id));
                             $status=1;
                             
                         }else{
                             $status=0;
                            
                         }
                         
                     }else{
                         $status=0;
                     }
                 }else{
                     //全部商品
                     $reward=pdo_get("ewei_shop_merch_reward",array("merch_id"=>$merchid,"is_end"=>0,"type"=>2));
                     if ($reward){
                         $status=1;
                         
                     }else{
                         $status=0;
                         
                     }
                 }
             }
         }
         return $status;
     }
}
?>