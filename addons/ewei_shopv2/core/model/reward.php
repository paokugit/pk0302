<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Reward_EweiShopV2Model
{

    /**
     * 会员购买成功后给推荐人分佣金
     * @param $openid 购买人的opendid
     */
	public function addReward($openid){
        global $_W;
        $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
        if(!$memberInfo) return false;
        $res = $this->getReward($memberInfo['agentid'],$memberInfo['agentlevel'],$memberInfo['openid']);
        return $res;
    }

    /**
     * @param $agentid  推荐人
     * @param $memberlevel  被推荐人的等级
     * @param $memberid 被推荐人
     */
    public function getReward($agentid,$memberlevel,$memberopenid){
        global $_W;
        $agentInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $agentid ));
        if(!$agentInfo) return false;
        $rewardMoney = $this->getRewardMoney($agentInfo['agentlevel'],$memberlevel);// 奖励金额
        $shopOwner = $this->getShopOwnerAgent($agentid);//获取是否有上级店长
        m('memberlog')->rewardMember($agentInfo['openid'],$rewardMoney,$memberopenid);//直推奖
        if($shopOwner){//有店长
            $ownerMoney = $this->shopOwnerMoney($memberlevel);
            return m('memberlog')->rewardShowOwnerMember($shopOwner,$ownerMoney,$memberopenid);
        }
    }



    /**
     * 根据等级获取奖励金额
     * @param $agentlevel
     */
    public function getRewardMoney($agentlevel,$memberlevel){
        if($memberlevel==0) return 0;
	    if($agentlevel==0) return 0;
	    if($agentlevel==1) return 2;
	    if($agentlevel==2) return 20;
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
            if($memberlevel==5) return 196;
            //if($memberlevel==4) return 70;
            if($memberlevel==3) return 70;
            if($memberlevel==2) return 20;
            if($memberlevel==1) return 2;
            return 0;
        }
	    return 0;
    }

    public function shopOwnerMoney($memberlevel){
        switch ($memberlevel){
            case 0:
                return 0;break;
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
     * @param $openid  被推荐人
     * @param $agentid 推荐人
     */
    public function getShopOwnerAgent($agentid){
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

}
?>