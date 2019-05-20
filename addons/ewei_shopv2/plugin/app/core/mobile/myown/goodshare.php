<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Goodshare_EweiShopV2Page extends AppMobilePage{
    //分享商品
    public function share(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $goodid=(int)$_GPC["goodid"];
        //判断该商品是否在赏金任务中
        $good=pdo_get("ewei_shop_goods",array('id'=>$goodid));
        $merchid=$good["merchid"];
        //获取赏金任务列表
        $rewards=pdo_fetchall("select * from ".tablename('ewei_shop_merch_reward')." where merch_id=:merch_id and is_end=0",array(':merch_id'=>$merchid));
        if ($rewards){
            foreach ($rewards as $k=>$v){
                //判断是否是全部商品赏金
                if ($v["type"]==2){
                     //更新点击分享
                     pdo_update("ewei_shop_merch_reward",array('share'=>$v["share"]+1),array('id'=>$v["id"]));
                     //判断用户是否已分享
                     $share=pdo_get("ewei_shop_merch_rewardshare",array('openid'=>$openid,'good_id'=>$goodid,'reward_id'=>$v["id"]));
                     if ($share){
                         $data["openid"]=$openid;
                         $data["good_id"]=$goodid;
                         $data["reward_id"]=$v["id"];
                         $data["create_time"]=time();
                         pdo_insert("ewei_shop_merch_rewardshare",$data);
                         app_error(0,"success");
                     }else{
                        
                         //更新商户
                         $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
                         $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                         //判断商家佣金
                         if ($member&&($member["credit1"]>=$v["share_price"])){
                             
                         pdo_update("ewei_shop_merch_user",array('card'=>$merch["card"]-$v["share_price"]),array('id'=>$merchid));
                         if ($merch["member_id"]!=0){
                             $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                             m('member')->setCredit($member["openid"], 'credit1', -$v["share_price"], "分享支出");
                         }
                         pdo_update("ewei_shop_merch_reward",array('commission_count'=>$v["commission_count"]+$v["share_price"]),array('id'=>$v["id"]));
                         $shoplog["merch_id"]=$merchid;
                         $shoplog["openid"]=$openid;
                         $shoplog["type"]=1;
                         $shoplog["intro"]="分享支出";
                         $shoplog["money"]=$v["share_price"];
                         $shoplog["expend_type"]=1;
                         $shoplog["reward_id"]=$v["id"];
                         $shoplog["goods_id"]=$goodid;
                         $shoplog["create_time"]=time();
                         pdo_insert("ewei_shop_merch_rewardlog",$shoplog);
                         //用户佣金
                         m('member')->setCredit($openid, 'credit1', $v["share_price"],"分享商品佣金");
                         
                         app_error(0,"分享获取佣金成功");
                         }
                         
                         app_error(0,"商家佣金不足");
                     }
                }else{
                    //指定商品任务
                    $g=unserialize($v["goodid"]);
                    if (in_array($goodid, $g)){
                        
                        //更新点击分享
                        pdo_update("ewei_shop_merch_reward",array('share'=>$v["share"]+1),array('id'=>$v["id"]));
                        //判断用户是否已分享
                        $share=pdo_get("ewei_shop_merch_rewardshare",array('openid'=>$openid,'good_id'=>$goodid,'reward_id'=>$v["id"]));
                        if ($share){
                            $data["openid"]=$openid;
                            $data["good_id"]=$goodid;
                            $data["reward_id"]=$v["id"];
                            $data["create_time"]=time();
                            pdo_insert("ewei_shop_merch_rewardshare",$data);
                            app_error(0,"success");
                        }else{
                            
                            //更新商户
                            $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
                            $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                            //判断商家佣金
                            if ($member&&($member["credit1"]>=$v["share_price"])){
                                
                                pdo_update("ewei_shop_merch_user",array('card'=>$merch["card"]-$v["share_price"]),array('id'=>$merchid));
                                
                                if ($merch["member_id"]!=0){
                                    $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                                    m('member')->setCredit($member["openid"], 'credit1', -$v["share_price"], "分享支出");
                                }
                                
                                pdo_update("ewei_shop_merch_reward",array('commission_count'=>$v["commission_count"]+$v["share_price"]),array('id'=>$v["id"]));
                                $shoplog["merch_id"]=$merchid;
                                $shoplog["openid"]=$openid;
                                $shoplog["type"]=1;
                                $shoplog["intro"]="分享支出";
                                $shoplog["money"]=$v["share_price"];
                                $shoplog["expend_type"]=1;
                                $shoplog["reward_id"]=$v["id"];
                                $shoplog["goods_id"]=$goodid;
                                $shoplog["create_time"]=time();
                                pdo_insert("ewei_shop_merch_rewardlog",$shoplog);
                                
                                $data["openid"]=$openid;
                                $data["good_id"]=$goodid;
                                $data["reward_id"]=$v["id"];
                                $data["create_time"]=time();
                                pdo_insert("ewei_shop_merch_rewardshare",$data);
                                //用户佣金
                                m('member')->setCredit($openid, 'credit1', $v["share_price"],"分享商品佣金");
                                app_error(0,"分享获取佣金成功");
                            }
                            
                            app_error(0,"分享成功");
                        }
                        
                        
                        
                    }
                    
                }
            }
            
            app_error(0,"该商品无佣金");
        }else{
            app_error(0,"商家无赏金任务");
        }
        
    }
    
    //点击商品
    public function click(){
        global $_W;
        global $_GPC;
        $goodid=$_GPC["goodid"];
        $share_id=$_GPC["share_id"];
        $sharemember=pdo_get("ewei_shop_member",array('id'=>$share_id));
        $openid=$_GPC["openid"];
        //判断该商品是否在赏金任务中
        $good=pdo_get("ewei_shop_goods",array('id'=>$goodid));
        $merchid=$good["merchid"];
//         var_dump($merchid);
        //获取赏金任务列表
        $rewards=pdo_fetchall("select * from ".tablename('ewei_shop_merch_reward')." where merch_id=:merch_id and is_end=0",array(':merch_id'=>$merchid));
//         var_dump($rewards);
        //判断openid
        $m=pdo_get("ewei_shop_member",array("openid"=>$openid));
        if (empty($m)&&str_replace("sns_wa_", '', $openid)){
            app_error(1,"openid不正确");
        }
        if ($rewards){
            
            foreach ($rewards as $k=>$v){
                //判断是否是全部商品赏金
                if ($v["type"]==2){
                    //更新点击分享
                    pdo_update("ewei_shop_merch_reward",array('click'=>$v["click"]+1),array('id'=>$v["id"]));
                    //判断用户是否已点击
                    $share=pdo_get("ewei_shop_merch_rewardclick",array('openid'=>$openid,'good_id'=>$goodid,'reward_id'=>$v["id"]));
                    if ($share){
                        $data["openid"]=$openid;
                        $data["good_id"]=$goodid;
                        $data["reward_id"]=$v["id"];
                        $data["share_id"]=$share_id;
                        $data["create_time"]=time();
                        pdo_insert("ewei_shop_merch_rewardclick",$data);
                        app_error(0,"success");
                    }else{
                        
                        //更新商户
                        $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
                        $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                        //判断商家佣金
                        if ($member&&($member["credit1"]>=$v["click_price"])){
                            
                            pdo_update("ewei_shop_merch_user",array('card'=>$merch["card"]-$v["click_price"]),array('id'=>$merchid));
                            if ($merch["member_id"]!=0){
                                $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                                m('member')->setCredit($member["openid"], 'credit1', -$v["click_price"], "点击支出");
                            }
                            pdo_update("ewei_shop_merch_reward",array('commission_count'=>$v["commission_count"]+$v["click_price"]),array('id'=>$v["id"]));
                            $shoplog["merch_id"]=$merchid;
                            $shoplog["openid"]=$openid;
                            $shoplog["type"]=1;
                            $shoplog["intro"]="点击支出";
                            $shoplog["money"]=$v["click_price"];
                            $shoplog["expend_type"]=2;
                            $shoplog["reward_id"]=$v["id"];
                            $shoplog["goods_id"]=$goodid;
                            $shoplog["create_time"]=time();
                            pdo_insert("ewei_shop_merch_rewardlog",$shoplog);
                            //用户佣金
                            m('member')->setCredit($sharemember["openid"], 'credit1', $v["click_price"],"点击商品佣金");
                            app_error(0,"点击获取佣金成功");
                        }
                        
                        app_error(0,"商家佣金不足");
                    }
                }else{
                    //指定商品任务
                    $g=unserialize($v["goodid"]);
                    if (in_array($goodid, $g)){
                        
                        //更新点击分享
                        pdo_update("ewei_shop_merch_reward",array('click'=>$v["click"]+1),array('id'=>$v["id"]));
                        //判断用户是否已分享
                        $share=pdo_get("ewei_shop_merch_rewardclick",array('openid'=>$openid,'good_id'=>$goodid,'reward_id'=>$v["id"]));
                        if ($share){
                            $data["openid"]=$openid;
                            $data["good_id"]=$goodid;
                            $data["reward_id"]=$v["id"];
                            $data["create_time"]=time();
                            $data["share_id"]=$share_id;
                            pdo_insert("ewei_shop_merch_rewardclick",$data);
                            app_error(0,"success");
                        }else{
                            
                            //更新商户
                            $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
                            $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                            //判断商家佣金
                            if ($member&&($member["credit1"]>=$v["click_price"])){
                                
                                pdo_update("ewei_shop_merch_user",array('card'=>$merch["card"]-$v["click_price"]),array('id'=>$merchid));
                                
                                //商家资金减少
                                if ($merch["member_id"]!=0){
                                    $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
                                    m('member')->setCredit($member["openid"], 'credit1', -$v["click_price"], "点击支出");
                                }
                                
                                pdo_update("ewei_shop_merch_reward",array('commission_count'=>$v["commission_count"]+$v["click_price"]),array('id'=>$v["id"]));
                                $shoplog["merch_id"]=$merchid;
                                $shoplog["openid"]=$openid;
                                $shoplog["type"]=1;
                                $shoplog["intro"]="点击支出";
                                $shoplog["money"]=$v["click_price"];
                                $shoplog["expend_type"]=2;
                                $shoplog["reward_id"]=$v["id"];
                                $shoplog["goods_id"]=$goodid;
                                $shoplog["create_time"]=time();
                                pdo_insert("ewei_shop_merch_rewardlog",$shoplog);
                                
                                $data["openid"]=$openid;
                                $data["good_id"]=$goodid;
                                $data["reward_id"]=$v["id"];
                                $data["create_time"]=time();
                                $data["share_id"]=$share_id;
                                pdo_insert("ewei_shop_merch_rewardclick",$data);
                                //用户佣金
                                m('member')->setCredit($sharemember["openid"], 'credit1', $v["click_price"],"点击商品佣金");
                                app_error(0,"点击获取佣金成功");
                            }
                            
                            app_error(0,"分享成功");
                        }
                        
                        
                        
                    }
                    
                }
            }
            
            app_error(0,"该商品无佣金");
            
            
            
        }else{
            app_error(0,"该商家无赏金任务");
        }
        
    }
    
}