<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Share_EweiShopV2Page extends MobilePage
{
    public function index(){
       global $_W;
       global $_GPC;
       
       $openid=$_W["openid"];
       if (empty($_W["openid"])){
          $resault= mc_oauth_account_userinfo();
            $openid=$resault["openid"];
        //    $openid="oQmU56Lf1GeIkpqsLStPq5Qktm9I";
       }

       
      //获取上级openid
      $share_openid1=$_GPC["share_openid1"];
      if ($share_openid1==$openid){
          $share_openid1="";
      }
      //获取二级分享者
      $share_openid2=$_GPC["share_openid2"];
      if ($share_openid2==$openid){
          $share_openid2="";
      }
      //添加查看记录
      $good_id=$_GPC["good_id"];
      
//       $good_id=451;
      
      
      $good=pdo_get("ewei_shop_goods",array("id"=>$good_id));
      if (pdo_update("ewei_shop_goods",array("viewcount"=>$good["viewcount"]+1),array("id"=>$good_id))){
          //添加记录
          $log["openid"]=$openid;
          $log["good_id"]=$good_id;
          $log["type"]=1;
          $log["create_time"]=time();
          pdo_insert("ewei_shop_goods_redview",$log);
      }
      
      //获取商品是否截止
      $other=pdo_get("ewei_shop_goods_bribe_expert",array("goods_id"=>$good_id));
      if ($other["end_time"]<=time()){
          header('location: ' . mobileUrl("goods/share/end")."&good_id=".$good_id);
      }
     
       //分享url
       if (empty($share_openid1)){
       $url="https://www.paokucoin.com/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile&r=goods.share.index&good_id=".$good_id."&share_openid1=".$openid;
       }elseif ($share_openid1==$openid){
           if ($share_openid2){
           $url="https://www.paokucoin.com/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile&r=goods.share.index&good_id=".$good_id."&share_openid1=".$openid."&share_openid2=".$share_openid2;
           }else{
               $url="https://www.paokucoin.com/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile&r=goods.share.index&good_id=".$good_id."&share_openid1=".$openid;
           }
        }else{
           $url="https://www.paokucoin.com/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile&r=goods.share.index&good_id=".$good_id."&share_openid1=".$openid."&share_openid2=".$share_openid1;
           
       }
       include $this->template();
    }
    //活动页面商品
    public function good(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $good_id=$_GPC["good_id"];
        $good=pdo_fetch("select id,merchid,title,marketprice,total,thumb_url,commission1_pay,commission2_pay,viewcount,forwardcount,description from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$good_id));
        $openid=$_GPC["openid"];
        //获取商家信息
        $merch=pdo_get("ewei_shop_merch_user",array("id"=>$good["merchid"]));
        $good["merchname"]=$merch["merchname"];
        if (empty($good)){
            show_json(0,"商品不存在");
        }
        if ($good["total"]<=0){
            show_json(0,"已无库存");
        }
        //获取商品其他信息
        $good["other"]=pdo_get("ewei_shop_goods_bribe_expert",array("goods_id"=>$good_id));
//         if ($good["other"]["end_time"]<time()){
//             show_json(0,"该活动结束");
//         }
        $good["thumb_url"]=set_medias(iunserializer($good["thumb_url"]));
        //获取音乐
        $music=pdo_get("ewei_shop_music",array("id"=>$good["other"]["music"]));
        $good["other"]["music"]=tomedia($music["music"]);
//         $good["other"]["end_time"]=date("Y-m-d H:i:s",$good["other"]["end_time"]);
        //获取红包记录
        $resalut=pdo_fetchall("select openid,sum(money) as m from ".tablename("ewei_shop_goods_redlog")." where goodid=:goodid and status=2 group by openid order by m desc",array(":goodid"=>$good_id));
        $my=array();
        $i=0;
        foreach ($resalut as $k=>$v){
            $mc_fans=pdo_get("mc_mapping_fans",array("openid"=>$v["openid"]));
            $mc_member=pdo_get("mc_members",array("uid"=>$mc_fans["uid"]));
            $resalut[$k]["nickname"]=$mc_member["nickname"];
            $resalut[$k]["avatar"]=$mc_member["avatar"];
            if ($v["openid"]==$openid){
                $my["money"]=$v["m"];
                $my["sort"]=$k+1;
            }
            $i=$k+1;
        }
        $good["red"]["log"]=$resalut;
        $good["red"]["count"]=$i;
        if (empty($my)){
            $my["money"]=0;
            $my["sort"]=0;
        }
        $good["red"]["myred"]=$my;
        //获取订单记录
        $sql="select o.openid,o.price,o.commission1_pay,o.commission2_pay,o.createtime,o.dispatchtype,o.isvirtual,o.carrier,o.addressid from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and (o.status=1 or o.status=2 or o.status=3) ORDER BY o.createtime DESC ";
        $good["order"]=pdo_fetch("select count(*) as count from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and (o.status=1 or o.status=2 or o.status=3) ORDER BY o.createtime DESC ",array(":goodid"=>$good_id));
        $good["order"]["log"]=pdo_fetchall($sql,array(":goodid"=>$good_id));
        foreach ($good["order"]["log"] as $k=>$v){
             $mc_fans=pdo_get("mc_mapping_fans",array("openid"=>$v["openid"]));
             $mc_member=pdo_get("mc_members",array("uid"=>$mc_fans["uid"]));
             $good["order"]["log"][$k]["nickname"]=$mc_member["nickname"];
             $good["order"]["log"][$k]["avatar"]=$mc_member["avatar"];
             $good["order"]["log"][$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
             $good["order"]["log"][$k]["price"]=$v["price"]+$v["commission1_pay"]+$v["commission2_pay"];
             //获取电话号码
             if ($v["dispatchtype"]==1||$v["isvirtual"]==1){
                 $carrier=iunserializer($v["carrier"]);
                 $good["order"]["log"][$k]["mobile"]=substr($carrier["carrier_mobile"],0,3)."****".substr($carrier["carrier_mobile"],7,4);;
             }else{
                 $addr=pdo_get("ewei_shop_member_address",array("id"=>$v["addressid"]));
                 $good["order"]["log"][$k]["mobile"]=substr($addr["mobile"],0,3)."****".substr($addr["mobile"],7,4);;
             }
         }
        show_json(1,$good);
    }
    //立即抢购
    public function order(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $good_id=$_GPC["good_id"];
        $good=pdo_fetch("select * from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$good_id));
//         var_dump($good);die;
        if (empty($good)){
            show_json(0,"商品不存在");
        }
        if ($good["total"]<=0){
            show_json(0,"已无库存");
        }
        //获取商品其他信息
        $good["other"]=pdo_get("ewei_shop_goods_bribe_expert",array("goods_id"=>$good_id));
        if ($good["other"]["end_time"]<time()){
            show_json(0,"该活动结束");
        }
//         $openid=$_W["openid"];
        if (empty($openid)){
            $openid=$_GPC["openid"];
        }
        $data["uniacid"]=1;
        $data["openid"]=$openid;
        $data["ordersn"]="RD".date("Ymdhis").rand(100000,999999);
        $data["goodsprice"]=$good["marketprice"];
        $data["order_type"]=1;
        $data["createtime"]=time();
        $data["merchid"]=$good["merchid"];
        
        //获取分享的人
        if ($_GPC["share_openid1"]==$openid){
            $data["share_openid1"]=$_GPC["share_openid2"];
            $data["share_openid2"]="";
        }else{
        $data["share_openid1"]=$_GPC["share_openid1"];
        $data["share_openid2"]=$_GPC["share_openid2"];
        }
        
        if ($good["other"]["pro_type"]==1){
            //物流
            $data["price"]=$good["marketprice"]+$good["other"]["express_price"];
            if (!empty($data["share_openid1"])){
                $data["price"]=$data["price"]-$good["commission1_pay"];
                $data["commission1_pay"]=$good["commission1_pay"];
            }
            if (!empty($data["share_openid2"])){
                $data["price"]=$data["price"]-$good["commission2_pay"];
                $data["commission2_pay"]=$good["commission2_pay"];
            }
            $data["dispatchprice"]=$good["other"]["express_price"];
            $data["dispatchtype"]=0;
            //收货地址
            $addr["uniacid"]=$_W["uniacid"];
            $addr["openid"]=$openid;
            $addr["realname"]=$_GPC["realname"];
            $addr["mobile"]=$_GPC["mobile"];
            $addr["province"]=$_GPC["province"];
            $addr["city"]=$_GPC["city"];
            $addr["area"]=$_GPC["area"];
            $addr["address"]=$_GPC["address"];
            $res=pdo_insert("ewei_shop_member_address",$addr);
            if (!empty($res)){
            $data["addressid"]=pdo_insertid();
            }else{
               show_json(0,"填写收货人信息");
            }
            
        }elseif ($good["other"]["pro_type"]==2){
            //自取产品
            $data["price"]=$good["marketprice"];
            //红包减额
            if (!empty($_GPC["share_openid1"])){
                $data["price"]=$data["price"]-$good["commission1_pay"];
                $data["commission1_pay"]=$good["commission1_pay"];
            }
            if (!empty($_GPC["share_openid2"])){
                $data["price"]=$data["price"]-$good["commission2_pay"];
                $data["commission2_pay"]=$good["commission2_pay"];
            }
            
            $data["dispatchtype"]=1;
            //获取自取用户信息
            $carrier["carrier_realname"]=$_GPC["realname"];
            $carrier["carrier_mobile"]=$_GPC["mobile"];
            if (empty($carrier["carrier_mobile"])||empty($carrier["carrier_realname"])){
                show_json(0,"完善购买人信息");
            }
            $data["carrier"]=iserializer($carrier);
        }else{
            //虚拟产品
            $data["price"]=$good["marketprice"];
            if (!empty($_GPC["share_openid1"])){
                $data["price"]=$data["price"]-$good["commission1_pay"];
                $data["commission1_pay"]=$good["commission1_pay"];
            }
            if (!empty($_GPC["share_openid2"])){
                $data["price"]=$data["price"]-$good["commission2_pay"];
                $data["commission2_pay"]=$good["commission2_pay"];
            }
            $data["isvirtual"]=1;
            //获取自取用户信息
            $carrier["carrier_realname"]=$_GPC["realname"];
            $carrier["carrier_mobile"]=$_GPC["mobile"];
            if (empty($carrier["carrier_mobile"])||empty($carrier["carrier_realname"])){
                show_json(0,"完善购买人信息");
            }
            $data["carrier"]=iserializer($carrier);
        }
        $order=pdo_insert("ewei_shop_order",$data);
        if (!empty($order)){
            $order_id=pdo_insertid();
            $g["orderid"]=$order_id;
            $g["uniacid"]=$_W["uniacid"];
            $g["goodsid"]=$good_id;
            $g["price"]=$good["marketprice"];
            $g["total"]=1;
            $g["createtime"]=time();
            pdo_insert("ewei_shop_order_goods",$g);
            $order_sn["ordersn"]=$data["ordersn"];
            //生成红包记录
//             var_dump($data);
            if ($good["commission1_pay"]!=0&&$data["share_openid1"]){
                
                $red1["openid"]=$data["share_openid1"];
                $red1["goodid"]=$good_id;
                $red1["order_sn"]=$data["ordersn"];
                $red1["money"]=$good["commission1_pay"];
                $red1["level"]=1;
                $red1["status"]=0;
                $red1["create_time"]=time();
                pdo_insert("ewei_shop_goods_redlog",$red1);
            }
            if ($good["commission2_pay"]!=0&&$data["share_openid2"]){
                $red2["openid"]=$data["share_openid2"];
                $red2["goodid"]=$good_id;
                $red2["order_sn"]=$data["ordersn"];
                $red2["money"]=$good["commission2_pay"];
                $red2["level"]=2;
                $red2["status"]=0;
                $red2["create_time"]=time();
                pdo_insert("ewei_shop_goods_redlog",$red2);
            }
            show_json(1,$order_sn);
        }else{
            show_json(0,"生成订单失败");
        }
    }
    //购买凭证
    public function vouchar(){
        header('Access-Control-Allow-Origin:*');
        global $_w;
        global $_GPC;
        $ordersn=$_GPC["ordersn"];
        $order=pdo_get("ewei_shop_order",array("ordersn"=>$ordersn));
        if (empty($order)){
            show_json(0,"不存在该订单");
        }
        if ($order["status"]==0){
            show_json(0,"该订单未支付");
        }
        $order_good=pdo_get("ewei_shop_order_goods",array("orderid"=>$order["id"]));
        $good=pdo_get("ewei_shop_goods",array("id"=>$order_good["goodsid"]));
        $list["title"]=$good["title"];
        $list["paytime"]=date("Y-m-d H:i:s",$order["createtime"]);
        $list["price"]=$order["price"]+$order["commission1_pay"]+$order["commission2_pay"];
        if ($order["addressid"]!=0){
            $addr=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
            $list["realname"]=$addr["realname"];
            $list["mobile"]=$addr["mobile"];
        }else{
            $carrier=iunserializer($order["carrier"]);
            $list["realname"]=$carrier["carrier_realname"];
            $list["mobile"]=$carrier["carrier_mobile"];
            
        }
        //获取商家信息
        $shop=pdo_get("ewei_shop_merch_user",array("id"=>$good["merchid"]));
        $list["shop_name"]=$shop["merchname"];
        $list["shop_mobile"]=$shop["mobile"];
        $list["shop_address"]=$shop["address"];
        show_json(1,$list);
    }
    
    //订单支付成功
    public function order_wxback(){
        global $_GPC;
        global $_W;
        $ordersn=$_GPC["order_sn"];
        $order=pdo_get("ewei_shop_order",array("ordersn"=>$ordersn));
       
               pdo_update("ewei_shop_order",array("status"=>1,'paytype'=>21),array("ordersn"=>$ordersn));
                //更新红包
                pdo_update("ewei_shop_goods_redlog",array("status"=>1),array("order_sn"=>$ordersn));

                //上级红包发放
                $this->addMoney($ordersn);

                
              include $this->template();
        
    }
    /**
     * 消息推送
     */
    public function msg(){
        global $_GPC;
        global $_W;
        $ordersn=$_GPC["order_sn"];
//         var_dump($ordersn);
        $order=pdo_get("ewei_shop_order",array("ordersn"=>$ordersn));
//         var_dump($order);
        $order_goods=pdo_get("ewei_shop_order_goods",array("orderid"=>$order["id"]));
        $good=pdo_get("ewei_shop_goods",array("id"=>$order_goods["goodsid"]));
        $order_price=$order["price"]+$order["commission1_pay"]+$order["commission2_pay"];
        
        $res=array();
        //消息发送
        //获取购买者信息
        if ($order["openid"]){
            $member=pdo_get("mc_mapping_fans",array("openid"=>$order["openid"]));
            
            $postdata["first"]=array("value"=>"您的订单已支付成功","color"=>"#173177");
            $postdata["keyword1"]=array("value"=>$member["nickname"],"color"=>"#173177");
            $postdata["keyword2"]=array("value"=>$order["ordersn"],"color"=>"#173177");
            $postdata["keyword3"]=array("value"=>$order_price."元","color"=>"#173177");
            $postdata["keyword4"]=array("value"=>$good["title"],"color"=>"#173177");
            $r=m("message")->sendTplNotice($order["openid"],"rPnwJBoYeGcLumJ7iIymhepzgO9dH4pB2YyGBRUITxc",$postdata);
         //   var_dump($r);
            $res["user"]="success";
        }
        //获取商家信息
        if ($order["merchid"]){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$order["merchid"]));
            if ($merch["openid"]){
                $postdata["first"]=array("value"=>"您的商品已被购买","color"=>"#173177");
                $postdata["keyword1"]=array("value"=>$merch["merchname"],"color"=>"#173177");
                $postdata["keyword2"]=array("value"=>$order_price."元","color"=>"#173177");
                $postdata["keyword3"]=array("value"=>"0元","color"=>"#173177");
                $postdata["keyword4"]=array("value"=>date("Y-m-d H:i:s"),"color"=>"#173177");
                $postdata["keyword5"]=array("value"=>$order["ordersn"],"color"=>"#173177");
                $postdata["remark"]=array("value"=>"您的商品已被购买，请及时处理","color"=>"#173177");
               $rs= m("message")->sendTplNotice($merch["openid"],"Bs28K29IdrVDfmF8w9iNEY0IqkrNL8GxIESVov_YMVc",$postdata);
            //   var_dump($rs);
               $res["merch"]="success";
            }
            
        }
        echo json_encode($res);    
    }
    
    /**
     * 给上级发放红包奖励
     * @param $order_sn
     */
    public function addMoney($order_sn)
    {
        //查找订单状态
        $query = pdo_fetchall('select * from '.tablename('ewei_shop_goods_redlog').' where order_sn="'.$order_sn.'"');
        foreach ($query as $item){
            if($item['status'] == 0){
                pdo_insert('log',['log'=>"订单号为".$item['order_sn']."红包等级为".$item['level']."的红包记录支付状态未支付",'createtime'=>date("Y-m-d H:i:s",time())]);
                continue;
            }
            if($item['status'] == 2){
                pdo_insert('log',['log'=>"订单号为".$item['order_sn']."红包等级为".$item['level']."的红包记录发放状态已发放",'createtime'=>date("Y-m-d H:i:s",time())]);
                continue;
            }
            $salt = pdo_getcolumn("mc_mapping_fans",["openid"=>$item['openid']],'salt');
            //因为这两个红包记录的订单是一样的   所以 加上 这个人公众号粉丝表的salt  因为这个是随机生成
            $ordersn = $item['order_sn'].$salt;
            $params = [
                'desc'=>'订单提成奖励',
                'order_sn'=>$ordersn,
                'fee'=>$item['money'],
                'openid'=>$item['openid'],
            ];
            //请求微信发送支付
            $res = m('user')->get_transfers($params,1);
            if($res['return_code'] == "SUCCESS" && $res['result_code'] == "SUCCESS"){
                pdo_update('ewei_shop_goods_redlog',['status'=>2],['order_sn'=>$order_sn,'level'=>$item['level']]);
                pdo_insert('log',['log'=>"订单号为".$item['order_sn']."红包等级为".$item['level']."的红包奖励发放成功",'createtime'=>date("Y-m-d H:i:s",time())]);
            }else{
                pdo_insert('log',['log'=>"订单号为".$item['order_sn']."红包等级为".$item['level']."的红包奖励发放失败,错误代码".$res['err_code']."错误代码描述".$res['err_code_des'],'createtime'=>date("Y-m-d H:i:s",time())]);
            }
        }
    }

    //充值--微信支付
    public function order_wx(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        
        $openid=$_W["openid"];
        if (empty($openid)){
           $result = mc_oauth_userinfo();
           $openid=$result["openid"];
           
          //  $openid="oQmU56Lf1GeIkpqsLStPq5Qktm9I";
        }
        
        $order_sn=$_GPC["order_sn"];
        $log=pdo_get("ewei_shop_order",array('ordersn'=>$order_sn));
        
        $params["openid"]=$openid;
        $params["fee"] =$log["price"]+$log["commission1_pay"]+$log["commission2_pay"];
        $params["title"]="购买活动产品";
        $params["tid"]=$order_sn;
        load()->model("payment");
        $setting = uni_setting($_W["uniacid"], array( "payment" ));
        if( is_array($setting["payment"]) )
        {
            $options = $setting["payment"]["wechat"];
            $options["appid"] = $_W["account"]["key"];
            $options["secret"] = $_W["account"]["secret"];
        }
        $options["mch_id"]=$options["mchid"];
       
        $wechat = m("common")->fwechat_child_build($params, $options, 0);
        
   //    var_dump($wechat);die;
        include $this->template();
    }
    
    //分享信息
    public function share_url()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
         $url = trim($_GPC['url']);
        $account_api = WeAccount::create($_W['acid']);
        $jssdkconfig = $account_api->getJssdkConfig($url);
        show_json(1, $jssdkconfig);
    }
    //分享成功回调
    public function share_back(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $data["openid"]=$_GPC["openid"];
        $data["good_id"]=$_GPC["good_id"];
        $data["type"]=2;
        $data["create_time"]=time();
        $good=pdo_get("ewei_shop_goods",array("id"=>$_GPC["good_id"]));
        if (pdo_update("ewei_shop_goods",array("forwardcount"=>$good["forwardcount"]+1),array("id"=>$_GPC["good_id"]))){
            pdo_insert("ewei_shop_goods_redview",$data);
            show_json(1,"提交成功");
        }else{
            show_json(0,"提交失败");
        }
    }
   //分享订单
   public function share_order(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $goodid=$_GPC["good_id"];
       if (empty($openid)||empty($goodid)){
           show_json(0,"用户openid|商品id不可为空");
       }
       $count=pdo_fetch("select sum(money) as count from ".tablename("ewei_shop_goods_redlog")." where openid=:openid and goodid=:goodid and status=1",array(":openid"=>$openid,":goodid"=>$goodid));
       $onecount=pdo_fetch("select sum(money) as count from ".tablename("ewei_shop_goods_redlog")." where openid=:openid and goodid=:goodid and status=1 and level=1",array(":openid"=>$openid,":goodid"=>$goodid));
       $twocount=pdo_fetch("select sum(money) as count from ".tablename("ewei_shop_goods_redlog")." where openid=:openid and goodid=:goodid and status=1 and level=2",array(":openid"=>$openid,":goodid"=>$goodid));
       if ($count["count"]){
       $list["count"]=$count["count"];
       }else{
        $list["count"]=0;
       }
       if ($onecount["count"]){
           
           $list["onecount"]=$onecount["count"];
       }else{
           $list["onecount"]=0;
       }
       if ($twocount["count"]){
           $list["twocount"]=$twocount["count"];
       }else{
           $list["twocount"]=0;
       }
       //获取总数
       $c=pdo_fetch("select count(*) as count from ".tablename("ewei_shop_goods_redlog")." where openid=:openid and goodid=:goodid and status=1",array(":openid"=>$openid,":goodid"=>$goodid));
       if ($c["count"]){
           $list["logcount"]=$c["count"];
       }else{
           $list["logcount"]=0;
       }
       //获取分享列表
       $l=pdo_fetchall("select order_sn,money,create_time from ".tablename("ewei_shop_goods_redlog")." where openid=:openid and goodid=:goodid and status=1 order by create_time desc",array(":openid"=>$openid,":goodid"=>$goodid));
       foreach ($l as $k=>$v){
           //获取购买人信息
           $order=pdo_get("ewei_shop_order",array("ordersn"=>$v["order_sn"]));
           
           $member=pdo_get("mc_mapping_fans",array("openid"=>$order["openid"]));
           $message=pdo_get("mc_members",array("uid"=>$member["uid"]));
           $l[$k]["nickname"]=$message["nickname"];
           $l[$k]["avatar"]=$message["avatar"];
           $l[$k]["create_time"]=date("Y-m-d H:i:s",$v["create_time"]);
           //获取电话号码
           if ($order["dispatchtype"]==1||$order["isvirtual"]==1){
               $carrier=iunserializer($order["carrier"]);
//                $l[$k]["mobile"]=$carrier["carrier_mobile"];
               $l[$k]["mobile"]=substr($carrier["carrier_mobile"],0,3)."****".substr($carrier["carrier_mobile"],7,4);
           
           }else{
               $addr=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
//                $l[$k]["mobile"]=$addr["mobile"];
               $l[$k]["mobile"]=substr($addr["mobile"],0,3)."****".substr($addr["mobile"],7,4);
               
           }
       }
       $list["log"]=$l;
       show_json(1,$list);
   }
   //结束页面
   public function end(){
       global $_W;
       global $_GPC;
       
       $openid=$_W["openid"];
       if (empty($_W["openid"])){
                     $resault= mc_oauth_account_userinfo();
                      $openid=$resault["openid"];
          // $openid="oQmU56Lf1GeIkpqsLStPq5Qktm9I";
       }
       
       
       $good_id=$_GPC["good_id"];
       include $this->template();
   }
   //分享订单页面
   public function shareorder(){
       global $_W;
       global $_GPC;
       $good_id=$_GPC["good_id"];
       $openid=$_W["openid"];
       if (empty($_W["openid"])){
                 $resault= mc_oauth_account_userinfo();
                 $openid=$resault["openid"];
        //   $openid="oQmU56Lf1GeIkpqsLStPq5Qktm9I";
       }
       include $this->template();
   }
   //测试
   public function cs(){
    
//        var_dump(mobileUrl("goods/share/order_wx")."&id=11");
//        var_dump(mobileUrl("goods/share/order_wx",array("ordersn"=>11)));
//        $sql="select o.openid,o.price,o.createtime from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and o.status=1 ORDER BY o.createtime DESC ";
//        var_dump(pdo_fetchall($sql,array("goodid"=>451)));
       
//        var_dump(m("common")->getAccount());
//        var_dump(m("common")->getSysset("app"));
       
//        $postdata["first"]=array("value"=>"跑库订单提醒","color"=>"#173177");
//         $postdata["keyword1"]=array("value"=>"星月","color"=>"#173177");
//        var_dump(m("message")->sendTplNotice("oQmU56Lf1GeIkpqsLStPq5Qktm9I","rPnwJBoYeGcLumJ7iIymhepzgO9dH4pB2YyGBRUITxc",$postdata));

       
       $merch_user=pdo_fetch("select * from ".tablename("ewei_shop_merch_user")." where id=:id",array(':id'=>3));
   
       if (!empty($merch_user["wxopenid"])){
           //获取商品信息
           $goods=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(':orderid'=>3724));
           $goos_name="";
           foreach ($goods as $g){
               $good=pdo_fetch("select * from ".tablename("ewei_shop_goods")." where id=:good_id",array(':good_id'=>$g["goodsid"]));
               if (empty($goos_name)){
                   $goods_name=$good["title"];
               }else{
                   $goods_name=$goods_name.",".$good["title"];
               }
           }
           $postdata=array(
               'keyword1'=>array(
                   'value'=>$merch_user["merchname"],
                   'color' => '#ff510'
               ),
               'keyword2'=>array(
                   'value'=>$order["ordersn"],
                   'color' => '#ff510'
               ),
               'keyword3'=>array(
                   'value'=>$goods_name,
                   'color' => '#ff510'
               ),
               'keyword4'=>array(
                   'value'=>$order["goodsprice"],
                   'color' => '#ff510'
               ),
               'keyword5'=>array(
                   'value'=>$order["price"],
                   'color' => '#ff510'
               ),
               'keyword6'=>array(
                   'value'=>"用户已下单，请登录商家后台及时对订单处理",
                   'color' => '#ff510'
               )
               
           );
           $r=p("app")->mysendNotice($merch_user["wxopenid"], $postdata, "", "si0GH6bbqNTByQrSRhxRl06CKUSKz473JrbdHwBSbts");
           var_dump($r);
       }else{
           var_dump("11");
       }
       
   }

    /**
     * 我的凭证列表页面
     */
    public function prooflist()
    {
        include $this->template('goods/share/listvoucher');
    }
    /**
     * 我的凭证列表接口
     */
    public function proof_list()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        $openid = $_W['openid'];
        if($openid == ""){
            $resault= mc_oauth_account_userinfo();
            $openid=$resault["openid"];
        }
        $fields = 'price,createtime,id,commission1_pay,commission2_pay,addressid,carrier';
        $list = pdo_fetchall('select '.$fields.' from '.tablename('ewei_shop_order').' where openid = :openid and uniacid="'.$_W['uniacid'].'" and status > 1',[':openid'=>$openid]);
        foreach ($list as $key=>$item){
            $goods_id = pdo_getcolumn('ewei_shop_order_goods',['orderid'=>$item['id']],'goodsid');
            $goods = pdo_get('ewei_shop_goods',['id'=>$goods_id]);
            if($goods['isred'] !=1){
                unset($list[$key]);
            }
            if ($item["addressid"]!=0){
                $addr = pdo_get("ewei_shop_member_address",array("id"=>$item["addressid"]));
                $list[$key]["realname"] = $addr["realname"];
                $list[$key]["mobile"] = $addr["mobile"];
            }else{
                $carrier = iunserializer($item["carrier"]);
                $list[$key]["realname"] = $carrier["carrier_realname"];
                $list[$key]["mobile"] = $carrier["carrier_mobile"];
            }
            $list[$key]['price'] = $item["price"]+$item["commission1_pay"]+$item["commission2_pay"];
            $list[$key]["title"] = $goods["title"];
            $list[$key]["createtime"] = date("Y-m-d H:i:s",$item["createtime"]);
            //获取商家信息
            $shop = pdo_get("ewei_shop_merch_user",array("id"=>$goods["merchid"]));
            $list[$key]["shop_name"] = $shop["merchname"];
            $list[$key]["shop_mobile"] = $shop["mobile"];
            $list[$key]["shop_address"] = $shop["address"];
        }
        if(count($list) == 0){
            show_json(0,'暂无信息');
        }else{
            show_json(1,['list'=>$list]);
        }
    }
}