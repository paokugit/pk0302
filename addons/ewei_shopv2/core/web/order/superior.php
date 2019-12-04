<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class Superior_EweiShopV2Page extends WebPage
{
    //列表
    public function index(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 20;
        $condition = "o.jdtype=1 and  o.uniacid = :uniacid and o.ismr=0 and o.deleted=0 and o.isparent=0 and o.istrade=0 and iscycelbuy=0";
        $uniacid = $_W["uniacid"];
        $paras = $paras1 = array( ":uniacid" => $uniacid );
        if( empty($starttime) || empty($endtime) )
        {
            $starttime = strtotime("-1 month");
            $endtime = time();
        }
        $priceCondition = "";
        $orderbuy = "o.createtime";
        $searchtime = trim($_GPC["searchtime"]);
        if( !empty($searchtime) && is_array($_GPC["time"]) && in_array($searchtime, array( "create", "pay", "send", "finish" )) )
        {
            $starttime = strtotime($_GPC["time"]["start"]);
            $endtime = strtotime($_GPC["time"]["end"]);
            $condition .= " AND o." . $searchtime . "time >= :starttime AND o." . $searchtime . "time <= :endtime ";
            $paras[":starttime"] = $starttime;
            $paras[":endtime"] = $endtime;
            $priceCondition .= " AND o." . $searchtime . "time >= " . $starttime . " AND o." . $searchtime . "time <= " . $endtime . " ";
            $timeCondition .= " AND o." . $searchtime . "time >= " . $starttime . " AND o." . $searchtime . "time <= " . $endtime . " ";
            $orderbuy = "o." . $searchtime . "time";
        }
        if( !empty($_GPC["searchfield"]) && !empty($_GPC["keyword"]) )
        {
            $searchfield = trim(strtolower($_GPC["searchfield"]));
            $_GPC["keyword"] = trim($_GPC["keyword"]);
            $paras[":keyword"] = htmlspecialchars_decode($_GPC["keyword"], ENT_QUOTES);
            $sqlcondition = "";
            if( $searchfield == "ordersn" )
            {
                $condition .= " AND locate(:keyword,o.ordersn)>0";
            }
            else
            {
               
                        if( $searchfield == "address" )
                        {
                            $condition .= " AND ( locate(:keyword,a.realname)>0 or locate(:keyword,a.mobile)>0 or locate(:keyword,o.carrier)>0 or locate(:keyword,o.address)>0)";
                            $priceCondition .= " AND (a.realname LIKE '" . $_GPC["keyword"] . "%' OR a.mobile LIKE '" . $_GPC["keyword"] . "%')";
                        }
                        else
                        {
                            if( $searchfield == "location" )
                            {
                                $condition .= " AND ( locate(:keyword,o.address)>0 or locate(:keyword,o.address_send)>0 )";
                                $priceCondition .= " AND (o.address LIKE '%" . $_GPC["keyword"] . "%' OR o.address_send LIKE '%" . $_GPC["keyword"] . "%' ) ";
                            }
                            else
                            {
                                     
                                                if( $searchfield == "goodstitle" )
                                                {
                                                    $sqlcondition = " inner join ( select DISTINCT(og.orderid) from " . tablename("ewei_shop_order_goods") . " og left join " . tablename("ewei_shop_jdgoods") . " g on g.id=og.goodsid where og.uniacid = '" . $uniacid . "' and (locate(:keyword,g.name)>0)) gs on gs.orderid=o.id";
                                                }
                                                else
                                                {
                                                             
                                                                    if($searchfield == "remark"){
                                                                        $condition .= " AND (locate(:keyword,remark)>0)";
                                                                    }
                                                      
                                                }
                                            
                                        
                               
                            }
                        }
                  
            }
        }
        $statuscondition = "";
        $status=$_GPC["status"];
        if( $status !== "" )
        {
            if( $status ==-1 )
            {
                $statuscondition = " AND o.status=-1 and o.refundtime=0"; 
            }
            elseif ($status==6){
                //未支付
                $statuscondition=" and o.status=0";
            }elseif ($status==5){
               //全部订单
                $statuscondition=" and o.deleted=0";
            }elseif ($status==1){
                //已付款
                $statuscondition=" and o.status=1";
            }elseif ($status==3){
                //已完成
                $statuscondition=" and o.status=3";
            }
            
        }
        if( $condition != " o.uniacid = :uniacid and o.ismr=0 and o.deleted=0 and o.isparent=0 and o.istrade=0 " || !empty($sqlcondition) )
        {
           
            $sql = "select o.* ,a.realname as arealname,a.mobile as amobile,a.province as aprovince ,a.city as acity , a.area as aarea, a.street as astreet,a.address as aaddress from " . tablename("ewei_shop_order") . " o" .  " left join " . tablename("ewei_shop_member_address") . " a on a.id=o.addressid "  . " " . $sqlcondition . " where " . $condition . " " . $statuscondition . " ORDER BY " . $orderbuy . " DESC  ";
            $sql .= "LIMIT " . ($pindex - 1) * $psize . "," . $psize;
            
            $list = pdo_fetchall($sql, $paras);
//             var_dump($list);
        }
        else
        {
            $status_condition = str_replace("o.", "", $statuscondition);
            $sql = "select * from " . tablename("ewei_shop_order") . " where uniacid = :uniacid and ismr=0 and deleted=0 and isparent=0 " . $status_condition . " GROUP BY id ORDER BY createtime DESC  ";
            
            $sql .= "LIMIT " . ($pindex - 1) * $psize . "," . $psize;
            $list = pdo_fetchall($sql, $paras);
           
            //获取地址
            foreach ($list as $k=>$v){
                $address=pdo_get("ewei_shop_member_address",array("id"=>$v["addressid"]));
                $list[$k]["arealname"]=$address["realname"];
                $list[$k]["amobile"]=$address["mobile"];
                $list[$k]["aprovince"]=$address["province"];
                $list[$k]["acity"]=$address["city"];
                $list[$k]["aarea"]=$address["area"];
            }
        }
        $paytype = array( array( "css" => "default", "name" => "未支付" ), array( "css" => "danger", "name" => "余额支付" ), 11 => array( "css" => "default", "name" => "后台付款" ), 2 => array( "css" => "danger", "name" => "在线支付" ), 21 => array( "css" => "success", "name" => "微信支付" ), 22 => array( "css" => "warning", "name" => "支付宝支付" ), 23 => array( "css" => "warning", "name" => "银联支付" ), 3 => array( "css" => "primary", "name" => "货到付款" ), 4 => array( "css" => "primary", "name" => "收银台现金收款" ) );
        $orderstatus = array( -1 => array( "css" => "default", "name" => "已关闭" ), 0 => array( "css" => "danger", "name" => "待付款" ), 1 => array( "css" => "info", "name" => "待发货" ), 2 => array( "css" => "warning", "name" => "待收货" ), 3 => array( "css" => "success", "name" => "已完成" ) );
//        var_dump($list);
        foreach ($list as $k=>$v){
            //获取用户
            if ($v["user_id"]){
                $member=pdo_get("ewei_shop_member",array("id"=>$v["user_id"]));
            }else{
                $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            }
            $list[$k]["nickname"]=$member["nickname"];
            //获取商品
            $order_goods=pdo_fetchall("select goodsid,total,price from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$v["id"]));
            $list[$k]["goods"]=$order_goods;
            //商品
            foreach ($list[$k]["goods"] as $kk=>$vv){
                $g=pdo_get("ewei_shop_jdgoods",array("id"=>$vv["goodsid"]));
                $list[$k]["goods"][$kk]["name"]=$g["name"];
                $url=m("jdgoods")->homeaddr();
                $list[$k]["goods"][$kk]["imagePath"]=$url.$g["imagePath"];
            }
            
        }
//         var_dump($list);
        include $this->template();
    }
    //物流信息
    public function express(){
        global $_W;
        global $_GPC;
        $orderid=$_GPC["id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$orderid));
        $data["jdOrderId"]=$order["jdOrderId"];
        $res=m("jdgoods")->orderTrack($data);
        $list=array();
        $l=$res["result"]["orderTrack"];
        foreach ($l as $k=>$v){
            $list[$k]["content"]=$v["content"];
            $list[$k]["msgTime"]=$v["msgTime"];
        }
        
        include $this->template();
    }
    //订单详情
    public function detail(){
        global $_W;
        global $_GPC;
        $orderid=$_GPC["id"];
        $item=pdo_get("ewei_shop_order",array("id"=>$orderid));
        //获取用户
        if ($item["user_id"]){
            $member=pdo_get("ewei_shop_member",array("id"=>$item["user_id"]));
        }else{
            $member=pdo_get("ewei_shop_member",array("openid"=>$item["openid"]));
        }
        //获取用户地址
        $user=pdo_get("ewei_shop_member_address",array("id"=>$item["addressid"]));
        //获取商品
        $order_goods=pdo_fetchall("select goodsid,price,total from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$orderid));
        $url=m("jdgoods")->homeaddr();
        foreach ($order_goods as $k=>$v){
            $good=pdo_get("ewei_shop_jdgoods",array("id"=>$v["goodsid"]));
            $order_goods[$k]["imagePath"]=$url.$good["imagePath"];
            $order_goods[$k]["name"]=$good["name"];
            $order_goods[$k]["upc"]=$good["upc"];
            $order_goods[$k]["sku"]=$good["sku"];
            
        }
        //获取京东订单描述
        //获取京东详情
        $jddata["jdOrderId"]=$item["jdOrderId"];
        $jddata["queryExts"]="orderType,jdOrderState";
        $res=m("jdgoods")->selectJdOrder($jddata);
        //获取状态
        
        $jdstatus_msg=m("jdgoods")->status($res["result"]["jdOrderState"]);
        
        include $this->template();
    }
    //维权详情
    public function refund(){
        
    }
    //取消订单
    public function cancel(){
        global $_W;
        global $_GPC;
       
        $orderid = intval($_GPC["id"]);
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")." where id=:order_id and deleted=0 and userdeleted=0",array(":order_id"=>$orderid));
        $jd["jdOrderId"]=$order["jdOrderId"];
        
        $res=m("jdgoods")->cancel($jd);
        if ($res){
            $d["status"]=-1;
            $d["canceltime"]=time();
            pdo_update("ewei_shop_order",$d,array("id"=>$orderid));
            plog("order.op.delete", "取消订单 ID: " . $orderid);
            show_json(1);
        }else{
            show_json(0, $res["resultMessage"]);
        }
       
    }
    //删除订单
    public function del(){
        global $_W;
        global $_GPC;
        
        $orderid = intval($_GPC["id"]);
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")." where id=:order_id and deleted=0 and userdeleted=0",array(":order_id"=>$orderid));
        if (empty($order)){
            show_json(0,"不存在该订单");
        }
        $d["deleted"]=-1;
        if (pdo_update("ewei_shop_order",$d,array("id"=>$orderid))){
           
            plog("order.op.delete", "删除订单 ID: " . $orderid);
            show_json(1);
        }else{
            show_json(0, "删除失败");
        }
        
    }
    
}