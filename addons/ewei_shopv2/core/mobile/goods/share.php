<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Share_EweiShopV2Page extends MobilePage
{
    public function index(){
       global $_W;
       global $_GPC;
       
       if (empty($_W["openid"])){
           mc_oauth_account_userinfo();
       }
           var_dump("22");
           $openid=$_W["openid"];
      //获取上级openid
      $share_openid=$_GPC["share_openid"];
      
       var_dump($openid);
      
    }
    //活动页面商品
    public function good(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $good_id=$_GPC["good_id"];
        $good=pdo_fetch("select id,title,marketprice,total,thumb_url,commission1_pay,commission2_pay,viewcount,forwardcount,description from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$good_id));
        $openid=$_GPC["openid"];
        
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
        $good["thumb_url"]=iunserializer($good["thumb_url"]);
        //获取音乐
        $music=pdo_get("ewei_shop_music",array("id"=>$good["other"]["music"]));
        $good["other"]["music"]=$music["music"];
        //获取红包记录
        $resalut=pdo_fetchall("select openid,sum(money) as m from ".tablename("ewei_shop_goods_redlog")." where goodid=:goodid and status=1 group by openid order by m desc",array(":goodid"=>$good_id));
        $my=array();
        foreach ($resalut as $k=>$v){
            $mc_fans=pdo_get("mc_mapping_fans",array("openid"=>$v["openid"]));
            $mc_member=pdo_get("mc_members",array("uid"=>$mc_fans["uid"]));
            $resalut[$k]["nickname"]=$mc_member["nickname"];
            $resalut[$k]["avatar"]=$mc_member["avatar"];
            if ($v["openid"]==$openid){
                $my["money"]=$v["m"];
                $my["sort"]=$k+1;
            }
        }
        $good["red"]["log"]=$resalut;
        if (empty($my)){
            $my["money"]=0;
            $my["sort"]=0;
        }
        $good["red"]["myred"]=$my;
        //获取订单记录
        $sql="select o.openid,o.price,o.createtime from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and o.status=1 ORDER BY o.createtime DESC ";
        $good["order"]=pdo_fetch("select count(*) as count from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and o.status=1 ORDER BY o.createtime DESC ",array(":goodid"=>$good_id));
        $good["order"]["log"]=pdo_fetchall($sql,array(":goodid"=>$good_id));
        foreach ($good["order"]["log"] as $k=>$v){
             $mc_fans=pdo_get("mc_mapping_fans",array("openid"=>$v["openid"]));
             $mc_member=pdo_get("mc_members",array("uid"=>$mc_fans["uid"]));
             $good["order"]["log"][$k]["nickname"]=$mc_member["nickname"];
             $good["order"]["log"][$k]["avatar"]=$mc_member["avatar"];
             $good["order"]["log"][$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
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
        $openid=$_W["openid"];
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
        $data["share_openid1"]=$_GPC["share_openid1"];
        $data["share_openid2"]=$_GPC["share_openid2"];
        
        if ($good["other"]["pro_type"]==1){
            //物流
            $data["price"]=$good["marketprice"]+$good["other"]["express_price"];
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
        $list["paytime"]=date("Y-m-d H:i:s",$order["paytime"]);
        $list["price"]=$order["price"];
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
        if (empty($order)){
            show_json(0,"订单编号不存在");
        }else{
            if (pdo_update("ewei_shop_order",array("status"=>1),array("ordersn"=>$ordersn))){
                //更新红包
                pdo_update("ewei_shop_goods_redlog",array("status"=>1),array("order_sn"=>$ordersn));
               
                show_json(1,"更新成功");
            }else{
                
                show_json(0,"更新失败");
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
        }
        
        if (empty($openid)){
            $openid=$_GPC["openid"];
        }
        $order_sn=$_GPC["order_sn"];
        $log=pdo_get("ewei_shop_order",array('ordersn'=>$order_sn));
        
        $params["openid"]=$openid;
        $params["fee"] =$log["price"];
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
        
       var_dump($wechat);die;
        include $this->template();
    }
    
    //分享信息
    public function share_url()
    {
        global $_W;
        global $_GPC;
        $url = trim($_GPC['url']);
        $account_api = WeAccount::create($_W['acid']);
        $jssdkconfig = $account_api->getJssdkConfig($url);
        show_json(1, $jssdkconfig);
    }
   //测试
   public function cs(){
       
       $sql="select o.openid,o.price,o.createtime from " . tablename("ewei_shop_order") . " o"  . " left join " . tablename("ewei_shop_order_goods") . " m on m.orderid=o.id where m.goodsid=:goodid and o.status=1 ORDER BY o.createtime DESC ";
       var_dump(pdo_fetchall($sql,array("goodid"=>451)));
   }
}