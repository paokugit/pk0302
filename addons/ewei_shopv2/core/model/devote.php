<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fbb 贡献值
class Devote_EweiShopV2Model{
    //直推付费会员达30 奖励
     //$openid 购买会员openid
    public function rewardone($openid){
        $member = m('member')->getMember($openid);
       
        if (empty($member)||$member["agentid"]==0){
            return false;
        }
        //获取上级
        $parent=m('member')->getMember($member["agentid"]);
        if ($parent["agentlevel"]==0){
            return false;
        }
        //获取推荐付费会员的总数
        $sum=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where agentlevel>0 and agentid=:agentid",array(":agentid"=>$member["agentid"]));
        if ($sum["count"]<30){
            return false;
        }
        $count=floor($sum/30);
        //查询是否已奖励
        $log=pdo_fetch("select * from ".tablename("ewei_shop_member_credit_record")." where openid=:openid and credittype=:credittype and remark like :remark",array(":openid"=>$parent["openid"],":credittype"=>"credit4",":remark"=>"推荐付费会员,达到".$count."人"));
        if (empty($log)){
        $jl=$count*30;
        //奖励
        m('member')->setCredit($parent["openid"], 'credit4', $jl, "推荐付费会员,达到".$count."人");
        }
        return true;
    }
    //直推粉丝达到100个
    //parent_id 推荐人id
    public function rewardtwo($parent_id){
        $member = m('member')->getMember($parent_id);
        if (empty($member)||$member["agentlevel"]==0){
            return false;
        }
        //获取直推会员的总数
        $sum=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where  agentid=:agentid",array(":agentid"=>$parent_id));
        if ($sum["count"]<100){
            return false;
        }
        //查询是否奖励过
        $log=pdo_get("erwei_shop_member_credit_record",array("openid"=>$member["openid"],"credittype"=>"credit4","remark"=>"直推100人完成"));
        if ($log){
            return false;
        }
        //添加记录奖励
        m('member')->setCredit($member["openid"], 'credit4', 20, "直推100人完成");
        return true;
    }
    //直推付费会员
    //openid购买会员openid level推荐会员级别
    public function rewardthree($openid,$level){
        $member = m('member')->getMember($openid);
        
        if (empty($member)||$member["agentid"]==0){
            return false;
        }
        //获取上级
        $parent=m('member')->getMember($member["agentid"]);
        if ($parent["agentlevel"]==0){
            return false;
        }
        $credit=0;
        if ($level==1){
            $remark="直推健康达人";
            $credit=1;
        }elseif ($level==2){
            $remark="直推星选达人";
            $credit=10;
        }elseif ($level==5){
            $remark="直推店长";
            $credit=100;
        }
        if ($credit!=0){
            //添加记录奖励
            m('member')->setCredit($parent["openid"], 'credit4', $credit, $remark);
        }
        return true;
    }
    //直推折扣宝
    //openid
    public function rewardfour($openid){
        $member = m('member')->getMember($openid);
        
        if (empty($member)||$member["agentid"]==0){
            return false;
        }
        //获取上级
        $parent=m('member')->getMember($member["agentid"]);
        if ($parent["agentlevel"]==2||$parent["agentlevel"]==5){
            //添加记录奖励
            m('member')->setCredit($parent["openid"], 'credit4', 3000, "直推金主礼包");
            m('member')->setCredit($parent["openid"], 'credit1', 30000, "直推金主礼包");
        }
        //获取上上级
        if ($parent["agentid"]!=0){
            $pparent=m('member')->getMember($parent["agentid"]);
            if ($pparent["agentlevel"]==2||$pparent["agentlevel"]==5){
                //添加记录奖励
                m('member')->setCredit($pparent["openid"], 'credit4',500, "团队提成");
              
            }
        }
       
        return true;
    }
}