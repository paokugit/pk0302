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
        if (empty($parent)){
            return  false;
        }
        if ($parent["agentlevel"]==0){
            return false;
        }
        //判断是否开启贡献值
        if (empty($parent["mobile"])||empty($parent["weixin"])){
            return false;
        }
        //获取推荐付费会员的总数
        $sum=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where agentlevel>0 and agentid=:agentid",array(":agentid"=>$member["agentid"]));
        if ($sum["count"]>30){
            $count=floor($sum["count"]/30);
            $jl=$count*30;
            //查询是否已奖励
            $log=pdo_fetch("select * from ".tablename("ewei_shop_member_credit_record")." where openid=:openid and credittype=:credittype and remark like :remark",array(":openid"=>$parent["openid"],":credittype"=>"credit4",":remark"=>"推荐付费会员,达到".$jl."人"));
            if (empty($log)){
                
                //奖励
                m('member')->setCredit($parent["openid"], 'credit4', 60, "推荐付费会员,达到".$jl."人");
            }
            
        }
        
        //直推店主
        $shop=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where agentlevel=5 and agentid=:agentid",array(":agentid"=>$member["agentid"]));
        if ($shop["count"]>10){
            $count=floor($shop["count"]/10);
            //查询是否已奖励
            $jl=$count*10;
            $log=pdo_fetch("select * from ".tablename("ewei_shop_member_credit_record")." where openid=:openid and credittype=:credittype and remark like :remark",array(":openid"=>$parent["openid"],":credittype"=>"credit4",":remark"=>"推荐店主".$jl."人"));
           
            if (empty($log)){
                //奖励
                m('member')->setCredit($parent["openid"], 'credit4', 1000, "推荐店主".$jl."人");
            }
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
        //判断是否开启贡献值
        if (empty($member["mobile"])||empty($member["weixin"])){
            return false;
        }
        
        //获取直推会员的总数
        $sum=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where  agentid=:agentid",array(":agentid"=>$parent_id));
//         if ($sum["count"]<100){
//             return false;
//         }
        if ($sum["count"]>=100){
        //查询是否奖励过
        $log=pdo_get("erwei_shop_member_credit_record",array("openid"=>$member["openid"],"credittype"=>"credit4","remark"=>"直推100人完成"));
        if (empty($log)){
        //添加记录奖励
        m('member')->setCredit($member["openid"], 'credit4', 20, "直推100人完成");
        }
        }
        
       
        //添加直推10人奖励
        $jiangli=pdo_get("ewei_shop_member_devotejl",array("id"=>1));
        $dt=date("Y-m-d");
        $start_date=strtotime($jiangli["start_date"]);
        $end_date=strtotime($jiangli["end_date"]);
        $sum=pdo_fetch("select count(*) from ".tablename("ewei_shop_member")." where  agentid=:agentid and createtime>=:starttime and createtime<=:endtime",array(":agentid"=>$parent_id,":starttime"=>$start_date,":endtime"=>$end_date));
        if ($sum["count"]>=$jiangli["count"]){
            if ($member["agentlevel"]>=$jiangli["level"]&&$jiangli["start_date"]<=$dt&&$jiangli["end_date"]>=$dt){
                //查询是否奖励过
                $log=pdo_get("erwei_shop_member_credit_record",array("openid"=>$member["openid"],"credittype"=>"credit4","remark"=>"直推活动：".$jiangli["start_date"]."-".$jiangli["end_date"]."内推荐".$jiangli["count"]."人"));
                if (empty($log)){
                    //添加记录奖励
                    m('member')->setCredit($member["openid"], 'credit4', $dt["num"], "直推活动：".$jiangli["start_date"]."-".$jiangli["end_date"]."内推荐".$jiangli["count"]."人");
                }
            }
        }
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
        if (empty($parent)){
            return false;
        }
        if ($parent["agentlevel"]==0){
            return false;
        }
        //判断是否开启贡献值
        if (empty($parent["mobile"])||empty($parent["weixin"])){
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
            $remark="直推店主";
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
    public function rewardfour($openid,$num){
        $member = m('member')->getMember($openid);
         $num=(int)$num;
        if (empty($member)||$member["agentid"]==0){
            return false;
        }
        //用户卡路里增加
        m('member')->setCredit($openid, 'credit3', 30000*$num, "购买金主礼包");
        //获取上级
        $parent=m('member')->getMember($member["agentid"]);
        if (empty($parent)){
            return false;
        }
        
        //判断是否开启贡献值
        if (empty($parent["mobile"])||empty($parent["weixin"])){
            return false;
        }
        
        if ($parent["agentlevel"]==2||$parent["agentlevel"]==5){
            //添加记录奖励
            m('member')->setCredit($parent["openid"], 'credit4', 3000*$num, "直推金主礼包");
            
        }
        //获取上上级
        if ($parent["agentid"]!=0){
            $pparent=m('member')->getMember($parent["agentid"]);
            if (empty($pparent)){
                return  false;
            }
            //判断是否开启贡献值
            if (empty($pparent["mobile"])||empty($pparent["weixin"])){
                return false;
            }
            
            if ($pparent["agentlevel"]==2||$pparent["agentlevel"]==5){
                //添加记录奖励
                m('member')->setCredit($pparent["openid"], 'credit4',300*$num, "团队提成");
              
            }
        }
       
        return true;
    }
    
    //新用户助力
    public function rewardfive($parent_id){
        $member = m('member')->getMember($parent_id);
        $jiangli=pdo_get("ewei_shop_member_devotejl",array("id"=>2));
        if ($member["agentlevel"]<$jiangli["level"]){
            return false;
        }
        $dt=date("Y-m-d");
        if ($jiangli["start_date"]<=$dt&&$jiangli["end_date"]>=$dt){
            m('member')->setCredit($member["openid"], 'credit4',$jiangli["num"], "新用户助力奖励");
            
        }
        return true;
    }
}