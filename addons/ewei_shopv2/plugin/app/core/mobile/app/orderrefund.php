<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Orderrefund_EweiShopV2Page extends AppMobilePage
{
    //选择商品
    public function goods(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                apperror(1,"无权限访问此订单");
            }
        }
        //选择商品
        if ($order["status"]==2){
            //发货状态
        $goods=pdo_fetchall("select og.id,og.goodsid,og.price,og.optionname,og.optionid,g.title,og.total,g.thumb from ".tablename("ewei_shop_order_goods")." og "."left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid where og.orderid=:orderid and og.status=0 and og.rstate=0 and g.status!=2 and g.cannotrefund=0",array(":orderid"=>$order_id));
        
        }else{
            $goods=pdo_fetchall("select og.id,og.goodsid,og.price,og.optionname,og.optionid,g.title,og.total,g.thumb from ".tablename("ewei_shop_order_goods")." og "."left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid where og.orderid=:orderid and og.status=0 and og.rstate=0 and g.status!=2",array(":orderid"=>$order_id));
            
        }
        $goods = set_medias($goods, array( "thumb" ));
        foreach ($goods as $k=>$v){
            $option=pdo_get("ewei_shop_goods_option",array("id"=>$v["optionid"]));
//             var_dump($option);
            $spec_item=pdo_get("ewei_shop_goods_spec_item",array("id"=>$option["specs"]));
//             var_dump($spec_item);
            $spec=pdo_get("ewei_shop_goods_spec_item",array("id"=>$spec_item["specid"]));
//             var_dump($spec);
            $goods[$k]["spec"]=$spec["title"];
        }
        $res["order_id"]=$order_id;
        $res["status"]=$order["status"];
        $res["goods"]=$goods;
        apperror(0,"",$res);
    }
    //退款申请
    public function refund_mes(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                apperror(1,"无权限访问此订单");
            }
        }
        $goods_id=$_GPC["order_goodsid"];
        $res["order_id"]=$order_id;
        
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $res["mobile"]=$address["mobile"];
        $res["realname"]=$address["realname"];
        //获取商品
        if (empty($goods_id)){
        $goods = pdo_fetchall("select og.id,og.goodsid,og.price,og.deductprice,og.dispatchprice,og.discount_price,og.couponprice,g.title,g.status,g.thumb,og.optionname as optiontitle from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:order_id and og.status=0 and og.rstate=0 and g.cannotrefund=0", array("order_id"=>$order_id));
        }else{
        $goods = pdo_fetchall("select og.id,og.goodsid,og.price,og.deductprice,og.dispatchprice,og.discount_price,og.couponprice,g.title,g.status,g.thumb,og.optionname as optiontitle from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:order_id and og.id in (:order_goodsid)  and og.status=0 and og.rstate=0 and g.status!=2 and g.cannotrefund=0", array("order_id"=>$order_id,":order_goodsid"=>$goods_id));
        }
        $goods = set_medias($goods, array( "thumb" ));
        
        if ($order["status"]==1){
            //待发货状态
            if (empty($goods_id)){
                //订单退回
                $res["price"]=$order["price"];
                $res["dispatchprice"]=$order["dispatchprice"];
            }else{
                //商品单独退回
                $res["price"]=0;
                $res["dispatchprice"]=0;
                foreach ($goods as $k=>$v){
                 
                   $res["price"]+=$v["price"]-$v["deductprice"]-$v["discount_price"]-$v["couponprice"]+$v["dispatchprice"];
                   $res["dispatchprice"]+=$v["dispatchprice"];
                  
                }
            }
        }else{
            //已发货状态
            if (empty($goods_id)){
            $res["price"]=$order["price"]-$order["dispatchprice"];
            $res["dispatchprice"]=0;
            }else{
                //商品单独退回
                $res["price"]=0;
                $res["dispatchprice"]=0;
                foreach ($goods as $k=>$v){
                   
                    $res["price"]+=$v["price"]-$v["deductprice"]-$v["discount_price"]-$v["couponprice"];
                 
                }
            }
        }
        
        $res["goods"]=$goods;
        apperror(0,"",$res);
    }
    //退款||退货退款
    public function refund_submit(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        //订单商品id
        $order_goodsid=$_GPC["order_goodsid"];
        if (empty($order)){
            apperror(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                apperror(1,"无权限访问此订单");
            }
        }
        if ($order['status'] == '-1') {
            apperror(1, '订单已经处理完毕');
        }
        if (empty($order_id)||empty($order_goodsid)){
            apperror(1,"参数不对");
        }
        
       
        $ordergoodsid=implode(",", $order_goodsid);
        //获取订单所有非赠品商品数
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_order_goods")." og "."left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid "." where og.orderid=:orderid and g.status!=2",array(":orderid"=>$order_id));
//         var_dump($count);die;
        if ($order["refundstate"]!=0){
            apperror(1,"该订单处于售后中");
        }
       
        //判断订单商品中是否有已提交售后的
        $order_goods=pdo_fetchall("select og.*,g.cannotrefund from ".tablename("ewei_shop_order_goods")." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid"." where og.id in (:id)",array(":id"=>$ordergoodsid));
        $c=0;
        //所有商品id
        $goodid=array();
        foreach ($order_goods as $k=>$v){
            //判断商品的有效性
           if ($v["status"]==-1){
               apperror(1,"传入的订单商品id有误");
           }
           if ($v["rstate"]!=0){
               apperror(1,"提交的订单商品中含有申请售后中");
           }
           //已发货
           if ($order["status"]==2){
           if ($v["cannotrefund"]==1){
               apperror(1,"提交的商品含有不可退换商品");
           }
           }
           $c+=1;
           $goodid[$k]=$v["goodsid"];
        } 
        //商品id
        $goodsids=implode(",", $goodid);
//         var_dump($order_goodsid);
//         var_dump($order_goods);die;
        $rtype = intval($_GPC['rtype']);//0退款 1退款退货
        if ($count==$c){
            //整体订单提交退款申请
            $refund = array('uniacid' => $_W['uniacid'], 'merchid' => $order['merchid'], 'rtype' => $rtype, 'reason' => trim($_GPC['reason']), 'content' =>trim($_GPC['content']));
            
            $refund['createtime'] = time();
            $refund['orderid'] = $order_id;
            if ($order["status"]==1){
                $refund["applyprice"]=$order["price"];
                $refund['orderprice'] = $order['price'];
                $refund["price"]=$order["price"];
            }else{
                $refund["applyprice"]=$order["price"]-$order["dispatchprice"];
                $refund['orderprice'] = $order['price'];
                $refund["price"]=$order["price"]-$order["dispatchprice"];
            }
            
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            if (pdo_insert('ewei_shop_order_refund', $refund)){
                $refundid = pdo_insertid();
                pdo_update('ewei_shop_order', array('refundid' => $refundid, 'refundstate' => 1,'refundtime'=>time()), array('id' => $order_id));
                //更新订单商品
                pdo_query("update ".tablename("ewei_shop_order_goods")." set refundid=:refundid,rstate=1 where orderid=:order_id",array(":refundid"=>$refundid,":order_id"=>$order_id));
                apperror(0,"提交成功");
            }else {
                apperror(1,"提交失败");
            }
        }else{
            //商品单独申请售后
            //整体订单提交退款申请
            $refund = array('uniacid' => $_W['uniacid'], 'merchid' => $order['merchid'], 'rtype' => $rtype, 'reason' => trim($_GPC['reason']), 'content' =>trim($_GPC['content']));
            $refund['createtime'] = time();
            $refund['orderid'] = $order_id;
            $g=array();
            foreach ($order_goodsid as $k=>$v){
                $g[$k]["goods_id"]=$v;
            }
            $refund["goods_id"]=serialize($g);
            $goods_price=0;
            $dispatchprice=0;
            $deductprice=0;
            $discount_price=0;
            $couponprice=0;
            $deductenough=0;
            foreach ($order_goods as $k=>$v){
                $goods_price+=$v["price"];
                $dispatchprice+=$v["dispatchprice"];
                $discount_price+=$v["discount_price"];
                $deductprice+=$v["deductprice"];
                $couponprice+=$v["couponprice"];
                $deductenough+$v["deductenough"];
            }
            if ($order["status"]==1){
                $refund["applyprice"]=$goods_price-$discount_price-$deductprice-$couponprice+$dispatchprice-$deductenough;
                $refund['orderprice'] =$order["price"];
                $refund["price"]= $refund["applyprice"];
            }else{
                $refund["applyprice"]=$goods_price-$discount_price-$deductprice-$couponprice-$deductenough;
                $refund['orderprice'] = $order["price"];
                $refund["price"]= $refund["applyprice"];
            }
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            $refund["refundtype"]=3;
            if (pdo_insert('ewei_shop_order_refund', $refund)){
                $refundid = pdo_insertid();
                //更新订单商品
                pdo_query("update ".tablename("ewei_shop_order_goods")." set refundid=:refundid,rstate=1 where orderid=:orderid and (id in (:id)) or (good_giftid in (:goodsid))",array(":refundid"=>$refundid,"orderid"=>$order_id,":id"=>$ordergoodsid,":goodsid"=>$goodsids));
                apperror(0,"提交成功");
            }else {
                apperror(1,"提交失败");
            }
            
        }
       
    }
    //换货
    public function exchange_goods(){
        
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                apperror(1,"无权限访问此订单");
            }
        }
        $res["order_id"]=$order_id;
        $goods_id=$_GPC["goods_id"];
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $res["mobile"]=$address["mobile"];
        $res["realname"]=$address["realname"];
        $res["address"]=$address["province"].$address["city"].$address["area"].$address["address"];
        //获取商品
        $goods = pdo_fetch("select og.id,og.goodsid,og.total,g.title,g.thumb,og.optionname as optiontitle from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:order_id and og.id=:goods_id", array("order_id"=>$order_id,":goods_id"=>$goods_id));
        if (empty($goods)){
            apperror(1,"订单商品不存在");
        }
        $goods = set_medias($goods, array( "thumb" ));
        $res["goods"]=$goods;
        //获取规格
        $spec=pdo_fetchall("select * from ".tablename("ewei_shop_goods_spec")." order by id asc where goodsid=:goodsid",array(":goodsid"=>$goods["goodsid"]));
//          var_dump($spec);
        $res["spec"]=array();
        foreach ($spec as $k=>$v){
            $res["spec"][$k]["id"]=$v["id"];
            $res["spec"][$k]["title"]=$v["title"];
            $value=unserialize($v["content"]);
            $res["spec"][$k]["value"]=array();
            foreach ($value as $kk=>$vv){
                $spec_item=pdo_get("ewei_shop_goods_spec_item",array("id"=>$vv));
                $res["spec"][$k]["value"][$kk]["item_id"]=$vv;
                $res["spec"][$k]["value"][$kk]["item_name"]=$spec_item["title"];
            }
        }
        apperror(0,"",$res);
    }
    //换货--提交
    public function exchange_submit(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        //订单商品id
        $order_goodsid=$_GPC["order_goodsid"];
        if (empty($order_id)||empty($order_goodsid)){
            apperror(1,"参数传入不准确");
        }
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                apperror(1,"无权限访问此订单");
            }
        }
        if ($order["status"]!=2){
            apperror(1,"该订单未发货不可申请");
        } 
        if ($order["refundstate"]!=0){
            apperror(1,"该订单处于售后申请中");
        }
        $good=pdo_fetch("select og.id,og.goodsid,og.total,g.cannotrefund,og.rstate from ".tablename("ewei_shop_order_goods")." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid "."where og.orderid=:order_id and og.id=:id and og.status=0",array(":order_id"=>$order_id,":id"=>$order_goodsid));
        if (empty($good)){
            apperror(1,"参数不正确");
        }
        if ($good["cannotrefund"]==1){
            apperror(1,"该商品不可退换");
        }
        if ($good["rstate"]!=0){
            apperror(1,"该商品售后申请中");
        }
        //提交信息
        $total=$_GPC["total"];
        if (empty($total)){
            $total=1;
        }
        $spec_id=$_GPC["spec_id"];
        if ($total>$good["total"]){
            apperror(1,"最多可退换".$good["total"]."件");
        }
        //规格
        if (!empty($spec_id)){
            $count=count($spec_id);
            if ($count==1){
                $option=pdo_get("ewei_shop_goods_option",array("specs"=>$spec_id[0]));
            }else {
                $specs=implode("_", $spec_id);
                $option=pdo_get("ewei_shop_goods_option",array("specs"=>$specs));
            }
        }else{
            $option=array();
        }
        //判断订单商品数目
        $good_count=pdo_fetchcolumn("select count(og.id) from ".tablename("ewei_shop_order")." o left join ".tablename("ewei_shop_order_goods")." og on og.orderid=o.id "."where o.id=:id and og.gift!=1",array(":id"=>$order_id));
        $refund = array('uniacid' => $_W['uniacid'], 'merchid' => $order['merchid'], 'rtype' => 2, 'reason' => trim($_GPC['reason']), 'content' =>trim($_GPC['content']));
        $refund['createtime'] = time();
        $refund['orderid'] = $order_id;
        //获取商品信息
        $g[0]["goods_id"]=$good["id"];
        $g[0]["opentionid"]=$option["id"];
        $g[0]["opetionname"]=$option["title"];
        $g[0]["total"]=$total;
        $refund["goods_id"]=serialize($g);
      
           
            $refund["applyprice"]=$order["price"];
            $refund['orderprice'] = $order['price'];
            $refund["price"]=$order["price"]-$order["dispatchprice"];
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            if (pdo_insert('ewei_shop_order_refund', $refund)){
                $refundid = pdo_insertid();
                if ($good_count==1){
                pdo_update('ewei_shop_order', array('refundid' => $refundid, 'refundstate' => 2,'refundtime'=>time()), array('id' => $order_id));
                }
                //更新订单商品
                pdo_query("update ".tablename("ewei_shop_order_goods")." set refundid=:refundid,rstate=2 where id=:id or good_giftid=:goodsid",array(":refundid"=>$refundid,":id"=>$order_goodsid,":goodsid"=>$good["goodsid"]));
                apperror(0,"提交成功");
            }else {
                apperror(1,"提交失败");
            }
    }
    //售后申请--取消
    public function cancel(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        $refundid=$_GPC["refundid"];
        $refund=pdo_get("ewei_shop_order_refund",array("id"=>$refundid));
        if (empty($refundid)){
            apperror(1,"无此申请");
        }
        $order=pdo_get("ewei_shop_order",array("id"=>$refund["orderid"]));
        if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
            apperror(1,"无此权限操作");
        }
        $d["rstate"]=0;
        $d["refundid"]=0;
        if (pdo_update("ewei_shop_order_goods",$d,array("refundid"=>$refundid))){
            //更新订单
            $o["refundstate"]=0;
            $o["refundid"]=0;
            pdo_update("ewei_shop_order",$o,array("id"=>$refund["orderid"]));
            pdo_delete("ewei_shop_order_refund",array("id"=>$refundid));
            apperror(0,"取消成功");
        }else{
            apperror(1,"取消失败");
        }
    }
    //订单列表
    public function orderlist(){
        global $_W;
        global $_GPC;
        $uniacid = $_W["uniacid"];
        // 		$openid = $_W["openid"];
        $openid = $_GPC["openid"];
        if( empty($openid) )
        {
            apperror(1,"openid不可为空");
        }
        //修改
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member["openid"])){
            $member["openid"]=0;
            
        }
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 10;
        $show_status = $_GPC["status"];//待付款：0 待发货：1 待收货：2 已完成：3 退换货：4
        $condition = " and (openid=:openid or user_id=:user_id) and ismr=0 and deleted=0 and uniacid=:uniacid and jdtype=0";
        $params = array( ":uniacid" => $uniacid, ":openid" => $member["openid"],":user_id"=>$member["id"] );
       
        $condition .= " and merchshow=0 ";
        //hcc  加正则验证  验证ordersn订单号   不是以SC开头
        //$condition .= " and ordersn NOT REGEXP '^SC'";
        $condition .= " and type = 0";
        if( $show_status != "" )
        {
            $show_status = intval($show_status);
            switch( $show_status )
            {
                case 0: $condition .= " and status=0 and paytype!=3";
                break;
                case 2: $condition .= " and (status=2 or status=0 and paytype=3)";
                break;
                case 4: $condition .= " and refundstate>0";
                break;
                case 5: $condition .= " and userdeleted=1 ";
                break;
                default: $condition .= " and status=" . intval($show_status);
            }
            if( $show_status != 5 )
            {
                $condition .= " and userdeleted=0 ";
            }
        }
        else
        {
            $condition .= " and userdeleted=0 ";
        }
        $list = pdo_fetchall("select * from " . tablename("ewei_shop_order") . " where 1 " . $condition . " order by createtime desc LIMIT " . ($pindex - 1) * $psize . "," . $psize, $params);
        $total = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where 1 " . $condition, $params);
//         var_dump($total);
        $res["list"]=array();
        $res["total"]=$total;
        $res["page"]=$pindex;
        $res["pagesize"]=$psize;
        $res["pagetotal"]=ceil($total/$psize);
        foreach ($list as $k=>$v){
        $res["list"][$k]["id"]=$v["id"];
        $res["list"][$k]["status"]=$v["status"];
        //1表示虚拟商品
        $res["list"][$k]["isvirtual"]=$v["isvirtual"];
        //获取商家
        if ($v["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$v["merchid"]));
            $res["list"][$k]["merchname"]=$merch["merchname"];
        }else {
            $res["list"][$k]["merchname"]="跑库专享";
        }
        //获取售后
        $res["list"][$k]["refundstate"]=$v["refundstate"];
        $res["list"][$k]["refundid"]=$v["refundid"];
        //获取评价
        $res["list"][$k]["iscomment"]=$v["iscomment"];//评价状态 status 3,4 后允许评价 0 可评价 1 可追加评价 2 已评价
        //获取商品总数
        $res["list"][$k]["count"]=pdo_fetchcolumn("select sum(total) from ".tablename("ewei_shop_order_goods")." where orderid=:orderid and status!=-1", array(":orderid"=>$v["id"]));
        //获取商品
        $good=pdo_fetchall("select og.id,og.goodsid,og.price,og.total,og.optionname,og.refundid,og.rstate,g.thumb,g.title,g.status,g.cannotrefund from ".tablename("ewei_shop_order_goods")." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid "." where og.orderid=:orderid and og.status!=-1 and g.status!=2",array(":orderid"=>$v["id"]));
        $good=set_medias($good, array( "thumb" ));
        foreach ($good as $kk=>$vv){
            if ($vv["rstate"]!=0&&$vv["refundid"]){
            //获取售后状态
            $refund=pdo_get("ewei_shop_order_refund",array("id"=>$vv["refundid"]));
           
            $good[$kk]["refundstatus"]=$refund["status"];
            }else{
             $good[$kk]["refundstatus"]="";
            }
            
        }
        $res["list"][$k]["nogift"]=$good;
        //获取赠品商品
        $gift=pdo_fetchall("select og.id,og.goodsid,og.price,og.total,og.optionname,og.refundid,og.rstate,g.thumb,g.title,g.status,g.cannotrefund from ".tablename("ewei_shop_order_goods")." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid "." where og.orderid=:orderid and og.status!=-1 and g.status=2",array(":orderid"=>$v["id"]));
        $gift=set_medias($gift, array( "thumb" ));
        foreach ($gift as $kk=>$vv){
            if ($vv["rstate"]!=0&&$vv["refundid"]){
                //获取售后状态
                $refund=pdo_get("ewei_shop_order_refund",array("id"=>$vv["refundid"]));
                
                $gift[$kk]["refundstatus"]=$refund["status"];
            }else{
                $gift[$kk]["refundstatus"]="";
            }
            
        }
        $res["list"][$k]["gift"]=$gift;
        }
        apperror(0,"",$res);
    }
    //订单--详情
    public function detail(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        $openid = $_GPC["openid"];
        if( empty($openid) )
        {
            apperror(1,"openid未传");
        }
        //修改
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }
        if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
            apperror(1,"无权限访问");
        }
        $res=array("id"=>$order["id"],"status"=>$order["status"],"ordersn"=>$order["ordersn"],"price"=>$order["price"],"expresscom"=>$order["expresscom"],"dispatchprice"=>$order["dispatchprice"],"createtime"=>($order["createtime"]?date("Y-m-d H:i:s",$order["createtime"]):""),"finishtime"=>($order["finishtime"]?date("Y-m-d H:i:s",$order["finishtime"]):""),"paytime"=>($order["paytime"]?date("Y-m-d H:i:s",$order["paytime"]):""),"sendtime"=>($order["sendtime"]?date("Y-m-d H:i:s",$order["sendtime"]):""),"refundtime"=>($order["refundtime"]?date("Y-m-d H:i:s",$order["refundtime"]):""),"canceltime"=>($order["canceltime"]?date("Y-m-d H:i:s",$order["canceltime"]):""),"paytype"=>$order["paytype"],"couponprice"=>$order["couponprice"],"deductprice"=>$order["deductprice"],"discount_price"=>$order["discount_price"],"iscomment"=>$order["iscomment"],"refundstate"=>$order["refundstate"],"refundid"=>$order["refundid"]);
        //获取商家
        //获取商家
        if ($order["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$order["merchid"]));
            $res["merchname"]=$merch["merchname"];
        }else {
            $res["merchname"]="跑库专享";
        }
        //获取收货地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $res["realname"]=$address["realname"];
        $res["mobile"]=$address["mobile"];
        $res["address"]=$address["province"].$address["city"].$address["area"].$address["address"];
        //获取商品列表
        $good=pdo_fetchall("select og.id,og.goodsid,og.price,og.total,og.optionname,og.refundid,og.rstate,og.refundstatus,g.thumb,g.title,g.status,g.cannotrefund from ".tablename("ewei_shop_order_goods")." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid "." where og.orderid=:orderid and og.status!=-1",array(":orderid"=>$order_id));
        $good=set_medias($good, array( "thumb" ));
        foreach ($good as $kk=>$vv){
            if ($vv["rstate"]!=0&&$vv["refundid"]){
                //获取售后状态
                $refund=pdo_get("ewei_shop_order_refund",array("id"=>$vv["refundid"]));
                
                $good[$kk]["refundstatus"]=$refund["status"];
            }else{
                $good[$kk]["refundstatus"]="";
            }
            
        }
        $res["good"]=$good;
        apperror(0,"",$res);
    }
}