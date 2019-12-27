<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Team_EweiShopV2Page extends AppMobilePage
{
    //下单
    public function order(){
        global $_W;
        global $_GPC;
       
        $goods_id=$_GPC["goods_id"];
        $good=pdo_fetch("select * from ".tablename("ewei_shop_groups_goods")." where id=:id",array(":id"=>$goods_id));
        if (empty($good)){
            apperror(1,"商品id不正确");
        }
        if ($good["status"]==0){
            apperror(1,"该商品已下架");
        }
        $option_id=$_GPC["option_id"];
        if ($good["more_spec"]==1&&empty($option_id)){
            apperror(1,"该商品是多规格商品");
        }
        $total=$_GPC["total"]?$_GPC["total"]:1;
        if ($good["more_spec"]==0&&$good["stock"]<$total){
            apperror(1,"库存数量不足");
        }
        if ($option_id){
            $option=pdo_get("ewei_shop_groups_goods_option",array("id"=>$option_id,"groups_goods_id"=>$goods_id));
            if (empty($option)){
                apperror(1,"规格不存在");
            }
            if ($option["stock"]<$total){
                apperror(1,"该规格库存数量不足");
            }
        }   
        $single=$_GPC["single"]?$_GPC["single"]:0;
        if ($single==1&&$good["single"]==0){
            apperror(1,"该商品不支持单独购买");
        }
        $team_id=$_GPC["team_id"]?$_GPC["team_id"]:0;
        $openid=$_GPC["openid"];
        if (empty($openid)){
            apperror(1,"用户信息不可为空");
        }
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"不存在用户");
        }
        
        if ($team_id!=0){
            $order=pdo_get("ewei_shop_groups_order",array("id"=>$team_id,"heads"=>1));
            if (empty($order)){
                apperror(1,"团队id不正确");
            }
            if ($order["success"]==1){
                apperror(1,"该团队已组团成功，请选择其他团队");
            }
            if ($order["endtime"]<time()){
                apperror(1,"该组团已截止");
            }
            $mo=pdo_fetch("select * from ".tablename("ewei_shop_groups_order")." where (id=:id or teamid=:id) and is_team=1 and status=1 and user_id=:user_id",array(":id"=>$team_id,":user_id"=>$member["id"]));
            if ($mo){
                apperror(1,"不可重复参与同意团队");
            }
        }
        $data["uniacid"]=$_W["uniacid"];
        $data["openid"]=$member["openid"];
        $data["user_id"]=$member["id"];
        $data["orderno"]=m("common")->createNO("groups_order", "orderno", "PT");
        if (empty($_GPC["addressid"])){
            apperror(1,"收货地址id未传");
        }
        $data["addressid"]=$_GPC["addressid"];
        if ($single==1){
            //单购
            if ($option_id){
                $price=$option["single_price"];
            }else{
                $price=$good["singleprice"];
            }
            $data["price"]=$price*$total;
            $data["groupnum"]=$total;
        }else{
            if ($option_id){
                $price=$option["price"];
            }else{
                $price=$good["groupsprice"];
            }
            $data["price"]=$price;
            $data["is_team"]=1;
            if ($team_id){
            $data["teamid"]=$team_id;
            }else{
            $data["heads"]=1;
//             $data["starttime"]=time();
//             $time=date('Y-m-d H:i:s', strtotime('+'.$good["endtime"].'hour'));
//             $data["endtime"]=strtotime($time);
            }
            $data["groupnum"]=$good["groupnum"];
        }
        $data["freight"]=$good["freight"];
        $data["addressid"]=$_GPC["addressid"];
        $data["goodid"]=$goods_id;
        $data["more_spec"]=$good["more_spec"];
        $data["createtime"]=time();
        $data["remark"]=$_GPC["remark"];
        $data["goods_price"]=$price;
        $data["goods_option_id"]=$option_id;
        $data["specs"]=$option["title"];
        $data["merchid"]=$good["merchid"];
        pdo_insert("ewei_shop_groups_order",$data);
        $order_id=pdo_insertid();
        if ($single==0&&$team_id==0){
            pdo_update("ewei_shop_groups_order",array("teamid"=>$order_id),array("id"=>$order_id));
            
        }
        $order_good["uniacid"]=$_W["uniacid"];
        $order_good["goods_id"]=$good["gid"];
        $order_good["groups_goods_id"]=$goods_id;
        $order_good["groups_goods_option_id"]=$option_id;
        $order_good["groups_order_id"]=$order_id;
        $order_good["price"]=$price;
        $order_good["option_name"]=$option["title"];
        $order_good["create_time"]=time();
        $order_good["total"]=$total;
        pdo_insert("ewei_shop_groups_order_goods",$order_good);
        $res["order_id"]=$order_id;
        $res["price"]=$data["price"];
        $res["RVC"]=$member["RVC"];
        $res["credit2"]=$member["credit2"];
        apperror(0,"",$res);
    }
    //团队详情
    public function team_detail(){
        global $_W;
        global $_GPC;
        $team_id=$_GPC["team_id"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_groups_order")." where id=:id and heads=1 and status=1",array(":id"=>$team_id));
        if (empty($order)){
            apperror(1,"团队id不正确");
        }
        $list["team_id"]=$team_id;
        $list["groupnum"]=$order["groupnum"];
        $list["endtime"]=$order["endtime"];
        $list["success"]=$order["success"];
        $list["goods"]=pdo_fetch("select id,title,price,groupsprice,thumb from ".tablename("ewei_shop_groups_goods")." where id=:id",array(":id"=>$order["goodid"]));
        $list["goods"]["thumb"]=tomedia($list["goods"]["thumb"]);
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        $list["partake"]=0;
        //获取团队
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where (id=:id or teamid=:id) and is_team=1 and status=1 order by id asc",array(":id"=>$team_id));
        $list["number"]=$order["groupnum"]-$count;
        $order_member=pdo_fetchall("select * from ".tablename("ewei_shop_groups_order")." where (id=:id or teamid=:id) and is_team=1 and status=1 order by id asc",array(":id"=>$team_id));
        $list["team"]=array();
        foreach ($order_member as $k=>$v){
            if ($v["id"]==$team_id){
                $list["team"][$k]["head"]=1;
            }else{
                $list["team"][$k]["head"]=0;
            }
           $m=pdo_get("ewei_shop_member",array("id"=>$v["user_id"]));
           $list["team"][$k]["avatar"]=$m["avatar"];
           if ($v["user_id"]==$member["id"]){
               $list["partake"]=1;
           }
        }
        
        apperror(0,"",$list);
    }
    //余额 rvc支付
    public function money(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if ($order["status"]>=1){
            apperror(1,"该订单不可重复支付");
        }
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        if ($member["id"]!=$order["user_id"]){
            apperror(1,"无权限访问该订单");
        }
        if ($order["success"]==-1||$order["status"]==-1){
            apperror(1,"该活动已失效");
        }
        $credittype=$_GPC["credittype"];
        if ($member[$credittype]<$order["price"]){
            apperror(1,"账户余额不足");
        }
       
           m("member")->setCredit($member["id"],$credittype,0-$order["price"]-$order["freight"],"拼团订单：".$order["orderno"]."购买商品消费",5);
           $log=pdo_get("ewei_shop_groups_paylog",array("tid"=>$order["orderno"]));
           if ($log){
            
              $d["type"]=$credittype;
              pdo_update("ewei_shop_groups_paylog",$d,array("id"=>$log["id"]));
           }else{
           $log = array( "type"=>$credittype,"uniacid" => $_W["uniacid"], "openid" => $member["openid"],"user_id"=>$member["id"],"module" => "groups", "tid" => $order["orderno"], "credit" => $order["credit"], "creditmoney" => $order["creditmoney"], "fee" => $order["price"]+ $order["freight"] );
            pdo_insert("ewei_shop_groups_paylog",$log);
           }
            $r=p("groups")->payResult($order["orderno"],$credittype,$type);
            if ($r){
            apperror(0,"支付成功");
            }else{
            apperror(1,"支付失败");
            }
    }
    //小程序
    public function small_program(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if ($order["status"]>=1){
            apperror(1,"该订单不可重复支付");
        }
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        if ($member["id"]!=$order["user_id"]){
            apperror(1,"无权限访问该订单");
        }
        if ($order["success"]==-1||$order["status"]==-1){
            apperror(1,"该活动已失效");
        }
        $log=pdo_get("ewei_shop_groups_paylog",array("tid"=>$order["orderno"]));
        if (empty($log)){
        $log = array( "uniacid" =>$_W["uniacid"], "openid" => $member["openid"],"user_id"=>$member["id"],"module" => "groups", "tid" => $order["orderno"], "credit" => $order["credit"], "creditmoney" => $order["creditmoney"], "fee" => $order["price"] - $order["creditmoney"] + $order["freight"], "status" => 0 );
        pdo_insert("ewei_shop_groups_paylog", $log);
        }
        $payinfo = array( "openid" => $_W["openid_wa"], "title" => "拼团订单", "tid" => $order["orderno"], "fee" => $order["price"] - $order["creditmoney"] + $order["freight"] );
        $res = $this->model->wxpay($payinfo, 19);
        if( !is_error($res) )
        {
            $wechat = array( "success" => true, "payinfo" => $res );
            if( !empty($res["package"]) && strexists($res["package"], "prepay_id=") )
            {
                $prepay_id = str_replace("prepay_id=", "", $res["package"]);
                pdo_update("ewei_shop_groups_order", array( "wxapp_prepay_id" => $prepay_id ), array( "id" => $order_id, "uniacid" => $_W["uniacid"] ));
            }
        }
        
        $l["pay"]=$res;
        apperror(0,"",$l);
    }
    //确认订单
    public function comfirm_order(){
        global $_W;
        global $_GPC;
        $goods_id=$_GPC["goods_id"];
        $good=pdo_fetch("select id,title,groupsprice,singleprice,thumb,more_spec,merchid,freight,seven from ".tablename("ewei_shop_groups_goods")." where id=:id",array(":id"=>$goods_id));
        $good["thumb"]=tomedia($good["thumb"]);
        if (empty($good)){
            apperror(1,"商品id不正确");
        }
        if ($good["merchid"]){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$good["merchid"]));
            $good["merchname"]=$merch["merchname"];
        }else{
            $good["merchname"]="跑库自营";
        }
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"openid不正确");
        }
        $optionid=$_GPC["optionid"];
        if ($good["more_spec"]==1&&empty($optionid)){
            apperror(1,"商品未选择规格");
        }
        if ($optionid){
            $option=pdo_get("ewei_shop_groups_goods_option",array("groups_goods_id"=>$goods_id,"id"=>$optionid));
            if (empty($option)){
                apperror(1,"规格不正确");
            }
            $good["option"]["single_price"]=$option["single_price"];
            $good["option"]["price"]=$option["price"];
            $good["option"]["title"]=$option["title"];
            $op=pdo_get("ewei_shop_goods_option",array("id"=>$option["goods_option_id"]));
            $good["option"]["thumb"]=tomedia($op["thumb"]);
        }else{
            $good["option"]=array();
        }
        if (empty($member["openid"])){
            $member["openid"]=0;
        }
        //获取地址
        $address=pdo_fetch("select id,realname,mobile,province,city,area,address from ".tablename("ewei_shop_member_address")." where (user_id=:user_id or openid=:openid) and deleted=0 order by isdefault desc limit 1",array(":user_id"=>$member["id"],":openid"=>$member["openid"]));
        if ($address){
            $good["address"]=$address;
        }else{
            $good["address"]=array();
        }
        apperror(0,"",$good);
    }
}