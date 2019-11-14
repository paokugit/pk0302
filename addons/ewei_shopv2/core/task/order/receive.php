<?php 
error_reporting(0);
require("../../../../../framework/bootstrap.inc.php");
require("../../../../../addons/ewei_shopv2/defines.php");
require("../../../../../addons/ewei_shopv2/core/inc/functions.php");
require("../../../../../addons/ewei_shopv2/core/inc/plugin_model.php");
global $_W;
global $_GPC;
ignore_user_abort();
set_time_limit(0);
$sets = pdo_fetchall("select uniacid from " . tablename("ewei_shop_sysset"));
foreach( $sets as $set ) 
{
    $_W["uniacid"] = $set["uniacid"];
    if( empty($_W["uniacid"]) ) 
    {
        continue;
    }

    $cityexpress_receive = 0;
    $cityexpress = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_city_express") . " WHERE uniacid=:uniacid AND merchid=:merchid", array( ":uniacid" => $_W["uniacid"], ":merchid" => 0 ));
    if( !empty($cityexpress["enabled"]) && !empty($cityexpress["receive_goods"]) ) 
    {
        $cityexpress_receive = (0 < intval($cityexpress["receive_goods"]) ? intval($cityexpress["receive_goods"]) : 0);
    }

    $trade = m("common")->getSysset("trade", $_W["uniacid"]);
    $days = intval($trade["receive"]);
    $p = p("commission");
    $pcoupon = com("coupon");
    $orders = pdo_fetchall("select id,couponid,openid,isparent,sendtime,price,merchid,isverify,addressid,isvirtualsend,`virtual`,dispatchtype,city_express_state from " . tablename("ewei_shop_order") . " where uniacid=" . $_W["uniacid"] . " and status=2", array(  ), "id");
    if( !empty($orders) ) 
    {
        foreach( $orders as $orderid => $order ) 
        {
            if( !empty($order["city_express_state"]) && !empty($cityexpress_receive) ) 
            {
                $days = $cityexpress_receive;
            }

            $result = goodsReceive($order, $days);
            if( !$result ) 
            {
                continue;
            }

            $time = time();
            pdo_query("update " . tablename("ewei_shop_order") . " set status=3,finishtime=:time where id=:orderid", array( ":time" => $time, ":orderid" => $orderid ));
            //折扣宝订单  花多少钱给多少钱
            $order_goods = pdo_fetchall('select id,orderid,goodsid from '.tablename('ewei_shop_order_goods').'where orderid = :orderid',[':orderid'=>$orderid]);
            $zhekoubao = 0;
            //如果该订单内  有折扣包商品  折扣金额为0  那么奖励该商品的价格数给用户的折扣宝
            foreach ($order_goods as $item){
                $good = pdo_get('ewei_shop_goods',['id'=>$item['goodsid']]);
                if($good['deduct_type'] == 2 && $good['deduct'] == 0 && $good['merchid'] == 0){
                    $zhekoubao = $zhekoubao + $good['marketprice'];
                }
            }
            if($zhekoubao > 0){
                m('member')->setCredit($order['openid'],'credit3',$zhekoubao,"购买折扣宝商品奖励折扣宝");
            }

            if( $order["isparent"] == 1 )
            {
                continue;
            }

            m("member")->upgradeLevel($order["openid"], $orderid);
            m("order")->setGiveBalance($orderid, 1);
            m("notice")->sendOrderMessage($orderid);
            m("order")->fullback($orderid);
            m("order")->setStocksAndCredits($orderid, 3);
            if( $pcoupon ) 
            {
                if( !empty($order["couponid"]) ) 
                {
                    $pcoupon->backConsumeCoupon($order["id"]);
                }

                $pcoupon->sendcouponsbytask($order["id"]);
            }

            if( $p ) 
            {
                $p->checkOrderFinish($orderid);
            }

            if( p("lottery") && $order["merchid"] == 0 ) 
            {
                $res = p("lottery")->getLottery($order["openid"], 1, array( "money" => $order["price"], "paytype" => 2 ));
                if( $res ) 
                {
                    p("lottery")->getLotteryList($order["openid"], array( "lottery_id" => $res ));
                }

            }

        }
    }

}
function goodsReceive($order, $sysday = 0)
{
    $days = array(  );
    if( checkFetchOrder($order) ) 
    {
        return false;
    }

    $isonlyverifygoods = m("order")->checkisonlyverifygoods($order["id"]);
    if( $isonlyverifygoods ) 
    {
        return false;
    }

    if( $order["merchid"] == 0 ) 
    {
        $goods = pdo_fetchall("select og.goodsid, g.autoreceive from " . tablename("ewei_shop_order_goods") . " og left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid where og.orderid=" . $order["id"]);
        foreach( $goods as $i => $g ) 
        {
            $days[] = $g["autoreceive"];
        }
        $day = max($days);
    }
    else
    {
        $day = 0;
    }

    if( $day < 0 ) 
    {
        return false;
    }

    if( $day == 0 ) 
    {
        if( $sysday <= 0 ) 
        {
            return false;
        }

        $day = $sysday;
    }

    $daytimes = 86400 * $day;
    if( $order["sendtime"] + $daytimes <= time() ) 
    {
        return true;
    }

    return false;
}

function checkFetchOrder($order)
{
    if( $order["isverify"] != 1 && empty($item["addressid"]) && empty($item["isvirtualsend"]) && empty($item["virtual"]) && $item["dispatchtype"] ) 
    {
        return true;
    }

    return false;
}


