<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}

class Merch_EweiShopV2Model{
    //商家卡路里记录
    //type=0表示充值 1表示卡路里消耗
      public function set_cardlog($data,$type=0){
          if ($type==0){
              $log=pdo_get("ewei_shop_merch_purchaselog",array("order_sn"=>$data));
              $merch=pdo_get("ewei_shop_merch_user",array('id'=>$log["merch_id"]));
              if ($merch["member_id"]!=0){
              $member=pdo_get("ewei_shop_member",array('id'=>$merch["member_id"]));
              }
              $card=$merch["card"]+$log["purchase"]+$log["give"];
              if (pdo_update("ewei_shop_merch_user",array('card'=>$card),array('id'=>$log["merch_id"]))){
                  
                  pdo_update("ewei_shop_merch_purchaselog",array('status'=>1),array('order_sn'=>$data));
                  //商家记录变更
                  $dat["merch_id"]=$log["merch_id"];
                  $dat["intro"]="充值金额";
                  $dat["money"]=$log["purchase"];
                  $dat["create_time"]=time();
                  pdo_insert("ewei_shop_merch_rewardlog",$dat);
                  if ($merch["member_id"]!=0){
                  //更新小程序用户
                  m('member')->setCredit($member["openid"], 'credit1', $log["purchase"], "充值");
                  }
                  
                  if ($log["give"]!=0){
                      $dat["merch_id"]=$log["merch_id"];
                      $dat["intro"]="充值赠送金额";
                      $dat["money"]=$log["give"];
                      $dat["create_time"]=time();
                      pdo_insert("ewei_shop_merch_rewardlog",$dat);
                      if ($merch["member_id"]!=0){
                      //更新小程序用户
                      m('member')->setCredit($member["openid"], 'credit1', $log["give"], "充值赠送金额");
                      }
                  }
                  
                  return true;
              }else{
                
                  return false;
                  
              }
              
          }else{
              //商家支出
          }
          
      }
    //判断商品是否含有
    public function good($data,$good_id){
        foreach ($data as $k=>$v){
            if (in_array($good_id, $v)){
                return true;
            }
        }
        return false;
    }
    
    //订单
    public function order($order_id){
        $order=pdo_get("ewei_shop_order",array('id'=>$order_id));
        $merchid=$order["merchid"];
        $merch_user=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
        $share_member=pdo_get("ewei_shop_member",array('id'=>$order["share_id"]));
        if ($merch_user["reward_type"]==1){
        //指定商品
        $reward=pdo_fetchall("select * from ".tablename("ewei_shop_merch_reward")." where merch_id=:merchid and is_end=0 and type=1",array(':merchid'=>$merchid));
        if ($reward){
        
        $r=array();
        foreach ($r as $k=>$v){
            $r[$k]["reward_id"]=$v["id"];
            $r[$k]["goodsid"]=unserialize($v["goodid"]);
        }
        $good=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(':orderid'=>$order_id));
        $share_price=0;
        foreach ($good as $k=>$v){
            $good_detail=pdo_get("ewei_shop_goods",array('id'=>$v["goodsid"]));
            //判断赏金任务中是否含有
            $reward_id=$this->order_good($r, $v["goodsid"]);
            if ($reward_id){
                $good_reward=pdo_get("ewei_shop_merch_reward",$reward_id);
                //判断商品实际支付金额
                $reality_price=$this->good_price($order_id, $v["goodsid"]);
                if ($reality_price!=0){
                    //商品分享佣金
                    $good_shareprice=$reality_price*$good_reward["commission"]/100;
                    $share_price=$share_price+$good_shareprice;
                     //更新订单商品
                     pdo_update("ewei_shop_order_goods",array('share_price'=>$good_shareprice),array('id'=>$v["id"]));
                     //更新用户
                     pdo_update("ewei_shop_member",array('frozen_credit2'=>$good_shareprice+$share_member["frozen_credit2"]),array('id'=>$order["share_id"]));
                     //用户记录
                     $member_log["uid"]=$order["share_id"];
                     $member_log["orderid"]=$order_id;
                     $member_log["share_price"]=$good_shareprice;
                     $member_log["good_id"]=$v["goodsid"];
                     $member_log["mall_openid"]=$order["openid"];
                     $member_log["create_time"]=time();
                     pdo_insert("ewei_shop_member_credit2",$member_log);
                }
                
            }
            
        }
        //更新订单
        pdo_update("ewei_shop_order",array('share_price'=>$share_price),array('id'=>$order_id));
        $this->notice($share_price, $share_member["openid"]);
        return true;
        }else{
            
            return true;
        }
        
        }else{
          //全部商品  
            $reward=pdo_fetch("select * from ".tablename("ewei_shop_merch_reward")." where merch_id=:merchid and is_end=0 and type=2",array(':merchid'=>$merchid));
            if ($reward){
                $good=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(':orderid'=>$order_id));
                $share_price=0;
                foreach ($good as $k=>$v){
                    $good_detail=pdo_get("ewei_shop_goods",array('id'=>$v["goodsid"]));
                    
                        //判断商品实际支付金额
                        $reality_price=$this->good_price($order_id, $v["goodsid"]);
                        if ($reality_price!=0){
                            //商品分享佣金
                            $good_shareprice=$reality_price*$reward["commission"]/100;
                            $share_price=$share_price+$good_shareprice;
                            //更新订单商品
                            pdo_update("ewei_shop_order_goods",array('share_price'=>$good_shareprice),array('id'=>$v["id"]));
                            //更新用户
                            pdo_update("ewei_shop_member",array('frozen_credit2'=>$good_shareprice+$share_member["frozen_credit2"]),array('id'=>$order["share_id"]));
                            //用户记录
                            $member_log["uid"]=$order["share_id"];
                            $member_log["orderid"]=$order_id;
                            $member_log["share_price"]=$good_shareprice;
                            $member_log["good_id"]=$v["goodsid"];
                            $member_log["mall_openid"]=$order["openid"];
                            $member_log["create_time"]=time();
                            pdo_insert("ewei_shop_member_credit2",$member_log);
                        }
                      
                }
                //更新订单
                pdo_update("ewei_shop_order",array('share_price'=>$share_price),array('id'=>$order_id));
                $this->notice($share_price, $share_member["openid"]);
                return true;
                
            }else{
                return true;
            }
        }
    }
    //判断赏金任务中是否有该商品
    public function order_good($reward,$goodid){
        foreach ($reward as $k=>$v){
            if (in_array($goodid, $v["goodsid"])){
                return $v["reward_id"];
            }
        }
        return false;
    }
    //判断商品实际支付金额
    //order_id订单id good_id商品id
    public function good_price($order_id,$good_id){
        $order=pdo_get("ewei_shop_order",array('id'=>$order_id));
        $good=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(':orderid'=>$order_id));
        if ($order["deductprice"]==0){
            $g=pdo_get("ewei_shop_order_goods",array('orderid'=>$order_id,'goodsid'=>$good_id));
            return $g["price"];
        }else{
            $g=pdo_get("ewei_shop_order_goods",array('orderid'=>$order_id,'goodsid'=>$good_id));
            $deduct=0;
            foreach ($good as $k=>$v){
                $gg=pdo_get("ewei_shop_goods",array('id'=>$v["goodsid"]));
                $deduct=$deduct+$gg["deduct"];
            }
            $g1=pdo_get("ewei_shop_goods",array('id'=>$good_id));
            return $g["price"]-$g1["deduct"]/$deduct*$order["deductprice"];
            
        }
    }
    //消息
    public function notice($money,$openid){
        $openid=str_replace("sns_wa_", '', $openid);
        $postdata=array(
            'keyword1'=>array(
                'value'=>$money,
                'color' => '#ff510'
            ),
            'keyword2'=>array(
                'value'=>"获取赏金任务的分享订单佣金",
                'color' => '#ff510'
            ),
            'keyword3'=>array(
                'value'=>date("Y-m-d",time()),
                'color' => '#ff510'
            ),
            'keyword4'=>array(
                'value'=>"您分享的商品链接被人购买",
                'color' => '#ff510'
            )
            
        );
        p("app")->mysendNotice($openid, $postdata, "", "nSJSBKVYwLYN_LcsUXyvTLVjseO46nQA8RqKsRnsiRs");
        return true;
    }
    //获取商家最大赏金
    public function reward_money($merchid,$reward_type){
        $money=0;
//         var_dump($merchid);
//         var_dump($reward_type);die;
        if ($reward_type==1){
            //指定商品
            $list=pdo_fetchall("select * from ".tablename("ewei_shop_merch_reward")." where merch_id=:merch_id and is_end=0",array(':merch_id'=>$merchid));
//             var_dump($list);die;
            foreach ($list as $k=>$v){
                $goodsid=unserialize($v["goodid"]);
                //获取数组长度
                $length=count($goodsid);
                $money=$money+($v["share_price"]+$v["click_price"])*$length;
                
                //获取订单佣金
                foreach ($goodsid as $kk=>$vv){
                    //获取商品
                    $good=pdo_get("ewei_shop_goods",array('id'=>$vv));
                    $money=$money+$v["commission"]*$good["maxprice"]/100;
                }
            }
            return $money;
        }else{
            //全部商品
            $list=pdo_get("ewei_shop_merch_reward",array('merch_id'=>$merchid,'is_end'=>0));
            $goods=pdo_fetchall("select * from ".tablename("ewei_shop_goods")."where merchid=:merchid",array(':merchid'=>$merchid));
            foreach ($goods as $k=>$v){
                $money=$money+($list["share_price"]+$list["click_price"]);
                $money=$money+($list["commission"]*$v["maxprice"]/100);

            }
        }
        return $money;
    }

    /**
     * 获取最近的店铺
     * @return mixed
     */
    public function get_near_merch($is_from=0)
    {
        global $_GPC;
        global $_W;

        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        $citysel = false;
        $citys = array();
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $data = array();
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;
            $lat = floatval($_GPC['lat']);
            $lng = floatval($_GPC['lng']);
            $sorttype = intval($_GPC['sorttype']);
            $range = intval($_GPC['range']);

            $data = array_merge($data, array('status' => 1, 'field' => 'id,uniacid,merchname,salecate,logo,groupid,cateid,address,tel,lng,lat'));
            if (!(empty($sorttype))) {
                $data['orderby'] = array('id' => 'desc');
            }
            $merchuser = $merch_plugin->getMerch($data);
            // print_r($data);print_r($merchuser);pdo_debug();exit;
            $data = array();
            $data = array_merge($data, array('status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc')));
            $category = $merch_plugin->getCategory($data);
            if (!(empty($merchuser))) {
                $cate_list = array();
                if (!(empty($category))) {
                    foreach ($category as $k => $v) {
                        $cate_list[$v['id']] = $v;
                    }
                }

                if ($pindex == 1) {
                    $member = m('member')->getMember($_W['openid']);
                    if (!empty($member['agentid'])) {
                        $agent = m('member')->getMember($member['agentid']);
                        $isstore = pdo_getall('ewei_shop_merch_user', array('payopenid' => $agent['openid']));
                    }
                }
                if (!empty($isstore)) {
                    $merchuser = array_merge($isstore, $merchuser);
                }
                foreach ($merchuser as $k => $v) {
                    if (($lat != 0) && ($lng != 0) && !(empty($v['lat'])) && !(empty($v['lng']))) {
                        $distance = m('util')->GetDistance($lat, $lng, $v['lat'], $v['lng'], 2);
                        if ((0 < $range) && ($range < $distance)) {
                            unset($merchuser[$k]);
                            continue;
                        }
                        $merchuser[$k]['distance'] = $distance;
                        if ($distance < 1) $disname = ($distance * 100) . 'm';
                        else $disname = ($distance) . 'km';
                        $merchuser[$k]['disname'] = $disname;
                    } elseif ($range) {
                        unset($merchuser[$k]);
                        continue;
                    } else {
                        $merchuser[$k]['distance'] = 100000;
                        $merchuser[$k]['disname'] = '';
                    }
                    $merchuser[$k]['catename'] = $cate_list[$v['cateid']]['catename'];
                    $merchuser[$k]['logo'] = tomedia($v['logo']);

                    if($is_from>0){
                        $goodsNum = pdo_count("ewei_shop_goods", "deleted =0 and status=1  and merchid = " . $v['id']);
                        if($goodsNum<3){
                            unset($merchuser[$k]);
                        }
                    }
                }
            }
            if ($sorttype == 0 && !empty($merchuser)) {
                $merchuser = m('util')->multi_array_sort($merchuser, 'distance');
            }
            return $merchuser[0];
        }
    }
}