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
                //消息提醒
                $dd["keyword1"]=60;
                $dd["keyword2"]="推荐付费会员,达到".$jl."人";
                $dd["keyword3"]=date("Y-m-d H:i:s");
                $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                $this->notice($parent["openid"], $dd);
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
                
                //消息提醒
                $dd["keyword1"]=1000;
                $dd["keyword2"]="推荐店主".$jl."人";
                $dd["keyword3"]=date("Y-m-d H:i:s");
                $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                $this->notice($parent["openid"], $dd);
                
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
        
        //活动期间内--奖励
        $jl=pdo_get("ewei_shop_member_devotejl",array("id"=>3));
        $ddt=date("Y-m-d");
        if ($jl["start_date"]<=$ddt&&$jl["end_date"]>=$ddt){
            //添加记录奖励
            m('member')->setCredit($member["openid"], 'credit4', $jl["num"], "推荐新用户获取");
            
            //消息提醒
            $dd["keyword1"]=$jl["num"];
            $dd["keyword2"]="推荐新用户获取";
            $dd["keyword3"]=date("Y-m-d H:i:s");
            $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
            $this->notice($member["openid"], $dd);
            
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
        
        //消息提醒
        $dd["keyword1"]=20;
        $dd["keyword2"]="直推100人";
        $dd["keyword3"]=date("Y-m-d H:i:s");
        $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
        $this->notice($member["openid"], $dd);
        
        
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
                    
                    //消息提醒
                    $dd["keyword1"]=$dt["num"];
                    $dd["keyword2"]="最活动内推荐人数达到标准获取贡献值奖励";
                    $dd["keyword3"]=date("Y-m-d H:i:s");
                    $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                    $this->notice($member["openid"], $dd);
                    
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
            $remark="直推健康达人：".$member["nickname"];
            $credit=1;
        }elseif ($level==2){
            $remark="直推星选达人:".$member["nickname"];
            $credit=10;
        }elseif ($level==5){
            $remark="直推店主:".$member["nickname"];
            $credit=100;
        }
        if ($credit!=0){
            //添加记录奖励
            m('member')->setCredit($parent["openid"], 'credit4', $credit, $remark);
            //消息提醒
            $dd["keyword1"]=$credit;
            $dd["keyword2"]=$remark;
            $dd["keyword3"]=date("Y-m-d H:i:s");
            $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
            $this->notice($parent["openid"], $dd);
            
        }
        return true;
    }
    //直推折扣宝
    //openid
    public function rewardfour($openid,$num,$order=array()){
        global $_W;
        $member = m('member')->getMember($openid);
         $num=(int)$num;
        if (empty($member)||$member["agentid"]==0){
            return false;
        }
        //用户卡路里增加(折扣宝)
        m('member')->setCredit($openid, 'credit3', 15000*$num, "购买金主礼包");
        //消息提醒9960
        $dd["keyword1"]=15000*$num;
        $dd["keyword2"]="购买金主礼包";
        $dd["keyword3"]=date("Y-m-d H:i:s");
        $dd["keyword4"]="恭喜您获取折扣宝奖励，奖励已达到您的折扣宝账户，请注意查收";
        $this->notice($openid, $dd);
        //获取上级
        $sparent = $parent = m('member')->getMember($member["agentid"]);
        if(count($order)>0 && $order['share_id'] != $member['id']){
            $parent=m('member')->getMember($order["share_id"]);
        }
        pdo_insert('log',['log'=>json_encode($parent),'createtime'=>date('Y-m-d H:i:s',time())]);
        if (empty($parent)){
            return false;
        }
        
        //判断是否开启贡献值
        if (empty($parent["mobile"])||empty($parent["weixin"])){
            return false;
        }
	
        //如果是我和郝艳萍同学  就送贡献机
        //if($parent['openid'] == "sns_wa_owRAK44_gHTrMTJMVSxFy-jtNef8" || $parent['openid'] == "sns_wa_owRAK43dDy1s6i0_rbVfZUqgx854"){
            if ($parent["agentlevel"] == 2||$parent["agentlevel"]==5){
                for ($i = 0;$i<$num ;$i++){
                    pdo_insert('ewei_shop_devote_record',['openid'=>$parent['openid'],'uniacid'=>$_W['uniacid'],'status'=>1,'expire'=>strtotime('+30 days',strtotime(date('Y-m-d'))),'createtime'=>time()]);
                }
                //消息提醒
                $dd["keyword1"]="30天贡献机*".$num."台";
                $dd["keyword2"]="直推金主礼包";
                $dd["keyword3"]=date("Y-m-d H:i:s");
                $dd["keyword4"]="恭喜您获取贡献机奖励，奖励已达到折扣宝页面，请每天及时收取贡献值";
                pdo_insert('log',['log'=>json_encode($dd),'createtime'=>$dd['keyword3']]);
                $this->notice($parent["openid"], $dd);
            }
        //}else{
            //fanbeibei之前写的 推荐会员直接送3000  贡献值
//            if ($parent["agentlevel"]==2||$parent["agentlevel"]==5){
//                //添加记录奖励
//                m('member')->setCredit($parent["openid"], 'credit4', 3000*$num, "直推金主礼包");
//                //消息提醒
//                $dd["keyword1"]=3000*$num;
//                $dd["keyword2"]="直推金主礼包";
//                $dd["keyword3"]=date("Y-m-d H:i:s");
//                $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
//                $this->notice($parent["openid"], $dd);
//            }
//        }
	
        //获取上上级
        if ($sparent["agentid"]!=0){
            $pparent=m('member')->getMember($sparent["agentid"]);
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
                //消息提醒
                $dd["keyword1"]=300*$num;
                $dd["keyword2"]="团队提成";
                $dd["keyword3"]=date("Y-m-d H:i:s");
                $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                $this->notice($pparent["openid"], $dd);
            }

            //发送超级推荐人奖励
           $supperAgentId = $this->getSupperOwnerAgent($parent["agentid"]);
           if($supperAgentId){
                $supperAgent=m('member')->getMember($supperAgentId);
                //添加记录奖励
                m('member')->setCredit($supperAgent['openid'], 'credit4',300*$num, "团队提成");
                //消息提醒
                $sdd["keyword1"]=300*$num;
                $sdd["keyword2"]="团队提成";
                $sdd["keyword3"]=date("Y-m-d H:i:s");
                $sdd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                $this->notice($supperAgent["openid"], $sdd);
            }
        }
       
        return true;
    }

    /**
     * 获取推荐人的上级是否是超级金主
     */
    public function getSupperOwnerAgent($agentid){
        $mids = $this->getSupperMember();
        if($mids){
            if(in_array($agentid,$mids)){
                return $agentid;
            }else{
                $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(":id" => $agentid));
                if($memberInfo['agentid']>0 && in_array($memberInfo['agentid'],$mids)){
                    return $memberInfo['agentid'];
                }elseif($memberInfo['agentid']>0){
                    return $this->getSupperOwnerAgent($memberInfo['agentid']);
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }

    /**
     * 获取超级上级
     * @author lihanwen@paokucoin.com
     */
    public function getSupperMember(){
        $memberList=pdo_fetchall("select id from ".tablename("ewei_shop_member")." where supermaster=:supermaster",array(":supermaster"=>1));
        if($memberList){
            $ids = array();
            foreach ($memberList as $key=>$member){
                array_push($ids,$member['id']);
            }
            return $ids;
        }
        return false;
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
            //消息提醒
            $dd["keyword1"]=$jiangli["num"];
            $dd["keyword2"]="推荐新用户获取贡献值奖励";
            $dd["keyword3"]=date("Y-m-d H:i:s");
            $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
            $this->notice($member["openid"], $dd);
        }
        return true;
    }
    //订单商品奖励
    public function rewardorder($order_id){
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            return false;
        }
        $order_good=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$order_id));
        $my_price=0;
        $agent_price=0;
        foreach ($order_good as $k=>$v){
            $good=pdo_get("ewei_shop_goods",array("id"=>$v["goodsid"]));
            $my_price+=$good["my_devote"]*$v["total"];
            $agent_price+=$good["agent_devote"]*$v["total"];
        }
        //获取用户
        $member=m('member')->getMember($order["openid"]);
        //判断是否开启贡献值
        if ($member["mobile"]&&$member["weixin"]){
           //判断用户级别
           if ($member["agentlevel"]>=1){
               if ($my_price>0){
                   m('member')->setCredit($member["openid"], 'credit4',$my_price, "自购订单".$order["ordersn"]);
                   //消息提醒
                   $d["keyword1"]=$my_price;
                   $d["keyword2"]="自购订单获取贡献值";
                   $d["keyword3"]=date("Y-m-d H:i:s");
                   $d["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                   $this->notice($member["openid"], $d);
               }
           }
        }
        //上级用户
       // if ($member["agentid"]!=0){
        if ($order["share_id"]!=0 && $member['id'] != $order['share_id']){
          //  $agent=pdo_get("ewei_shop_member",array("id"=>$member["agentid"]));
            //$agent=m('member')->getMember($member["agentid"]);
            $agent=m('member')->getMember($order["share_id"]);
            if ($agent["mobile"]&&$agent["weixin"]){
                //判断用户级别
                if ($agent["agentlevel"]>=1){
                    if ($agent_price>0){
                        m('member')->setCredit($agent["openid"], 'credit4',$agent_price, $member["nickname"]."下单,订单编号：".$order["ordersn"]);
                        //消息提醒
                        $dd["keyword1"]=$agent_price;
                        //$dd["keyword2"]="您推荐的用户购买商品获取贡献值奖励";
                        $dd["keyword2"]="您分享的商品被购买获取贡献值奖励";
                        $dd["keyword3"]=date("Y-m-d H:i:s");
                        $dd["keyword4"]="恭喜您获取贡献值奖励，奖励已达到您的贡献值账户，请注意查收";
                        $this->notice($agent["openid"], $dd);
                    }
                }
            }
            
        }
        return true;
    }
    //消息提醒
    public function notice($openid,$data){
        $postdata=array(//变动金额
            'keyword1'=>array(
                'value'=>$data["keyword1"],
                'color' => '#ff510'
            ),//原因
            'keyword2'=>array(
                'value'=>$data["keyword2"],
                'color' => '#ff510'
            ),//时间
            'keyword3'=>array(
                'value'=>$data["keyword3"],
                'color' => '#ff510'
            ),//备注
            'keyword4'=>array(
                'value'=>$data["keyword4"],
                'color' => '#ff510'
            )
        );
        p("app")->mysendNotice($openid, $postdata, "", "nSJSBKVYwLYN_LcsUXyvTLVjseO46nQA8RqKsRnsiRs");
        return true;
    }
}