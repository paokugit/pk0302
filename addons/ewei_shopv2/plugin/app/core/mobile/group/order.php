<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Order_EweiShopV2Page extends AppMobilePage
{
    //订单列表
    public function index(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        $page=$_GPC["page"]?$_GPC["page"]:1;
        $first=($page-1)*20;
        $status=$_GPC["status"];//0待付款 1代发货 2待收货 4待成团 5待评价 6退换货 7全部
        $condition=" and deleted=0 and uniacid=:uniacid and user_id=:user_id";
        $param=array(":uniacid"=>$_W["uniacid"],":user_id"=>$member["id"]);
        if ($status!=""&&$status!=7){
            $status=intval($status);
            switch ($status){
                case 4: $condition.=" and status=1 and is_team=1 and success=0";
                break;
                case 5: $condition.=" and status=3 and iscomment=0";
                break;
                case 6: $condition.=" and refundstate!=0 and status!=3";
                break;
                case 1: $condition.=" and ((status=1 and is_team=0) or (status=1 and is_team=1 and success=1))";
                break;
                default: $condition.=" and status=".$status;
            }
        }
        $list=pdo_fetchall("select id,price,freight,status,goodid,is_team,endtime,specs,refundid,refundstate,heads,teamid,merchid,groupnum,iscomment,success from ".tablename("ewei_shop_groups_order")." where 1 ".$condition." order by createtime desc limit ".$first.",20",$param);
        foreach ($list as $k=>$v){
            $list[$k]["price"]=$v["price"]+$v["freight"];
            if ($v["refundid"]!=0){
                $refund=pdo_get("ewei_shop_groups_order_refund",array("id"=>$v["refundid"]));
                $list[$k]["refundstatus"]=$refund["refundstatus"];
                $list[$k]["rtype"]=$refund["rtype"];
            }else{
                $list[$k]["refundstatus"]="";
                $list[$k]["rtype"]="";
            }
            //获取商家
            if ($v["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$v["merchid"]));
            $list[$k]["merchname"]=$merch["merchname"];
            $list[$k]["mobile"]=$merch["mobile"];
            }else{
                $list[$k]["merchname"]="跑库自营";  
                $list[$k]["mobile"]="";
            }
            //获取商品
            $list[$k]["goods"]=pdo_fetchall("select og.id,g.id as goods_id,og.total,og.option_name,g.title,g.thumb,g.groupsprice,g.singleprice,g.seven from ".tablename("ewei_shop_groups_order_goods")." og left join ".tablename("ewei_shop_groups_goods")." g on og.groups_goods_id=g.id where og.groups_order_id=:order_id",array(":order_id"=>$v["id"]));
            foreach ($list[$k]["goods"] as $kk=>$vv){
                $list[$k]["goods"][$kk]["thumb"]=tomedia($vv["thumb"]);
            }
            //获取团队还差的人数
            if ($v["is_team"]==1){
                    $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where is_team=1 and teamid=:teamid and status=1",array(":teamid"=>$v["teamid"]));
                    $list[$k]["num"]=$v["groupnum"]-$count;
            }else{
                $list[$k]["num"]=0;
            }
        }
        $res["list"]=$list;
        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where 1 ".$condition,$param);
        $res["total"]=$total;
        $res["pagesize"]=20;
        $res["pagetotal"]=ceil($total/20);
        $res["page"]=$page;
        apperror(0,"",$res);
    }
    //取消订单
    public function cancel(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        $cancel=$_GPC["cancel"];
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }
        if ($order["user_id"]!=$order["user_id"]){
            apperror(1,"你无权限操作该订单");
        }
        if ($cancel==1&&$order["status"]!=0){
            apperror(1,"该订单暂不可取消");
        }
        if ($cancel==2&&$order["status"]!=-1&&$order["status"]!=3){
            apperror(1,"订单不可删除");
        }
        if ($cancel==1){
            $data["status"]=-1;
            $data["canceltime"]=time();
        }else{
            $data["deleted"]=1;
        }
        if (pdo_update("ewei_shop_groups_order",$data,array("id"=>$order_id))){
            apperror(0,"成功");
        }else{
            apperror(1,"失败");
        }
    }
    //确认收货
    public function finish(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单不存在");
        }
        if ($order["user_id"]!=$order["user_id"]){
            apperror(1,"你无权限操作该订单");
        }
        if ($order["status"]!=2){
            apperror(1,"该订单暂不可确认收货");
        }
        $data["status"]=3;
        $data["finishtime"]=time();
        if (pdo_update("ewei_shop_groups_order",$data,array("id"=>$order_id))){
            apperror(0,"成功");
        }else{
            apperror(1,"失败");
        }
    }
    //订单详情
    public function detail(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        
        $order=pdo_fetch("select id,orderno,express,expresssn,price,freight,status,goodid,is_team,endtime,specs,refundid,refundstate,heads,teamid,merchid,groupnum,iscomment,success,addressid,paytime,pay_type,createtime,sendtime  from ".tablename("ewei_shop_groups_order")." where id=:id",array(":id"=>$order_id));
        $order["createtime"]=date("Y-m-d H:i:s",$order["createtime"]);
        if ($order["paytime"]!=0){
        $order["paytime"]=date("Y-m-d H:i:s",$order["paytime"]);
        }else{
            $order["paytime"]="";
            $order["pay_type"]="";
        }
        if ($order["refundid"]!=0){
            $refund=pdo_get("ewei_shop_groups_order_refund",array("id"=>$order["refundid"]));
            $order["refundstatus"]=$refund["refundstatus"];
            $order["rtype"]=$refund["rtype"];
        }else{
            $order["refundstatus"]="";
            $order["rtype"]="";
        }
        if ($order["sendtime"]){
            $order["sendtime"]=date("Y-m-d H:i:s",$order["sendtime"]);
        } else{
            $order["sendtime"]="";
        }
        
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $order["address"]["realname"]=$address["realname"];
        $order["address"]["mobile"]=$address["mobile"];
        $order["address"]["province"]=$address["province"];
        $order["address"]["city"]=$address["city"];
        $order["address"]["area"]=$address["area"];
        $order["address"]["address"]=$address["address"];
        //获取商家
        if ($order["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$order["merchid"]));
            $order["merchname"]=$merch["merchname"];
        }else{
            $order["merchname"]="跑库自营";
        }
        $order["goodsprice"]=$order["price"];
        $order["price"]=$order["price"]+$order["freight"];
        //获取商品
        //获取商品
        $order["goods"]=pdo_fetchall("select og.id,g.id as goods_id,og.total,og.option_name,g.title,g.thumb,g.groupsprice,g.singleprice,g.seven from ".tablename("ewei_shop_groups_order_goods")." og left join ".tablename("ewei_shop_groups_goods")." g on og.groups_goods_id=g.id where og.groups_order_id=:order_id",array(":order_id"=>$order_id));
        foreach ($order["goods"] as $kk=>$vv){
            $order["goods"][$kk]["thumb"]=tomedia($vv["thumb"]);
        }
        //获取物流信息
        
        if ($order["status"]>=2){
            $expresslist = m("util")->getExpressList($order["express"], $order["expresssn"]);
            if ($expresslist){
                $order["logistics"]["time"]=$expresslist[0]["time"];
                $order["logistics"]["step"]=$expresslist[0]["step"];
            }
        }
        if (empty($order["logistics"])){
            $order["logistics"]=new ArrayObject();
        }
        
       
        apperror(0,"",$order);
    }
   //物流信息
    public function logistics(){
       global $_W;
       global $_GPC;
       $order_id=$_GPC["order_id"];
       $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
       if (empty($order)){
           apperror(1,"订单不存在");
       }
       $expresslist = m("util")->getExpressList($order["express"], $order["expresssn"]);
       $status = "";
       if( !empty($expresslist) )
       {
           if( strexists($expresslist[0]["step"], "签收") )
           {
               $status = "已签收";
           }
           else
           {
               if( count($expresslist) <= 2 )
               {
                   $status = "备货中";
               }
               else
               {
                   $status = "配送中";
               }
           }
       }
       $list["com"]=$order["expresscom"];
       $list["sn"]=$order["expresssn"];
       $list["status"]=$status;
       //商品
       $goods=pdo_fetch("select og.id,g.id as goods_id,og.total,og.option_name,g.title,g.thumb,g.groupsprice,g.singleprice,g.seven from ".tablename("ewei_shop_groups_order_goods")." og left join ".tablename("ewei_shop_groups_goods")." g on og.groups_goods_id=g.id where og.groups_order_id=:order_id",array(":order_id"=>$order_id));
       $list["count"]=$goods["total"];
       $list["thumb"]=tomedia($goods["thumb"]);
       $list["expresslist"]=$expresslist;
       apperror(0,"",$list);
   }
   //评价--展示
   public function comment_view(){
       global $_W;
       global $_GPC;
       $order_id=$_GPC["order_id"];
       $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
       if (empty($order)){
           apperror(1,"订单不存在");
       }
       if ($order["status"]!=3){
           apperror(1,"该订单不可评价");
       }
       if ($order["iscomment"]>=2){
           apperror(1,"该订单已被评价");
       }
       $list["order"]["id"]=$order["id"];
       $list["order"]["status"]=$order["status"];
       $list["order"]["iscomment"]=$order["iscomment"];
       $list["order"]["merchid"]=$order["merchid"];
       if ($order["merchid"]!=0){
       $merch=pdo_get("ewei_shop_merch_user",array("id"=>$order["merchid"]));
       $list["order"]["merchname"]=$merch["merchname"];
       }else{
       $list["order"]["merchname"]="跑库自营";
       }
       //获取商品
       $list["goods"]=pdo_fetchall("select g.id as goods_id,og.total,og.option_name,g.title,g.thumb,g.ccate from ".tablename("ewei_shop_groups_order_goods")." og left join ".tablename("ewei_shop_groups_goods")." g on og.groups_goods_id=g.id where og.groups_order_id=:order_id",array(":order_id"=>$order_id));
       foreach ($list["goods"] as $k=>$v){
       $list["goods"][$k]["thumb"]=tomedia($v["thumb"]);
       if ($v["ccate"]!=0){
           $cate=pdo_get("ewei_shop_category",array("id"=>$v["ccate"]));
           if ($cate["label"]){
//                $list["goods"][$k]["label"]=explode(",", $cate["label"]);
               $label=explode(",", $cate["label"]);
               foreach ($label as $kk=>$v){
                   $list["goods"][$k]["label"][$kk]["name"]=$v;
               }
           }else{
               $list["goods"][$k]["label"]=array();
           }
       }else{
           $list["goods"][$k]["label"]=array();
       }
       
       }
       apperror(0,"",$list);
   }
   //评价
   public function comment_submit(){
       
       global $_W;
       global $_GPC;
       $openid = $_GPC['openid'];
       $uniacid = $_W['uniacid'];
       $orderid = intval($_GPC['orderid']);
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       $order = pdo_fetch('select id,status,iscomment from ' . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid and  user_id=:user_id limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ":user_id"=>$member["id"]));
       
       if (empty($order)) {
               apperror(1,"不存在该订单");
       }
       
       
       $comments = $_GPC['comments'];
       
       
       if (is_string($comments)) {
           $comments_string = htmlspecialchars_decode(str_replace('\\', '', $comments));
           $comments = @json_decode($comments_string, true);
       }
       
       if (!is_array($comments)) {
               apperror(1,"数据出错,请重试!");
       }
       
       $trade = m('common')->getSysset('trade');
       
       if (!empty($trade['commentchecked'])) {
           $checked = 0;
       }
       else {
           $checked = 1;
       }
       //        apperror(1,"",$comments);die;
       foreach ($comments as $c) {
           $old_c = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order_comment') . ' where type=1 and uniacid=:uniacid and group_orderid=:orderid and group_goodsid=:goodsid limit 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $c['goodsid'], ':orderid' => $orderid));
           $good=pdo_get("ewei_shop_groups_goods",array("id"=>$c["goodsid"]));
           if (empty($old_c)) {
              
               $comment = array('type'=>1,'uniacid' => $uniacid,'goodsid'=>$good["gid"],'group_orderid' => $orderid, 'group_goodsid' => $c['goodsid'], 'level' => $c['level'], 'content' => trim($c['content']), 'images' => is_array($c['images']) ? iserializer($c['images']) : iserializer(array()), 'openid' => $member["openid"], 'user_id'=>$member["id"],'nickname' => $member['nickname'], 'headimgurl' => $member['avatar'], 'createtime' => time(), 'checked' => $checked,'label'=>trim($c["label"]),'deliverry_service'=>intval($_GPC["deliverry_service"]),'service_attitude'=>intval($_GPC["service_attitude"]),"anonymous"=>$c["anonymous"]);
              
               pdo_insert('ewei_shop_order_comment', $comment);
           }
           else {
               $comment = array('append_content' => trim($c['content']), 'append_images' => is_array($c['images']) ? iserializer($c['images']) : iserializer(array()), 'replychecked' => $checked);
               pdo_update('ewei_shop_order_comment', $comment, array('uniacid' => $_W['uniacid'], 'group_goodsid' => $c['goodsid'], 'group_orderid' => $orderid,'type'=>1));
           }
       }
       
       if ($order['iscomment'] <= 0) {
           $d['iscomment'] = 1;
       }
       else {
           $d['iscomment'] = 2;
       }
       
       pdo_update('ewei_shop_groups_order', $d, array('id' => $orderid, 'uniacid' => $uniacid));
       apperror(0,"成功");
   }
   //申请退换--展示
   public function refund_mes(){
       global $_W;
       global $_GPC;
       $order_id = intval($_GPC['order_id']);
       $order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where id=:id limit 1', array(':id' => $order_id));
       if (empty($order)) {
           apperror(1,"不存在该订单");
       }
       $list["order_id"]=$order["id"];
       if ($order["status"]==1){
       $list["price"]=$order["price"]+$order["freight"];
       }else{
           $list["price"]=$order["price"];
       }
       $list["freight"]=$order["freight"];
       $list["is_team"]=$order["is_team"];
       //获取商品
       //获取商品
       $list["goods"]=pdo_fetchall("select g.id as goods_id,og.total,og.option_name,g.title,g.thumb,g.more_spec from ".tablename("ewei_shop_groups_order_goods")." og left join ".tablename("ewei_shop_groups_goods")." g on og.groups_goods_id=g.id where og.groups_order_id=:order_id",array(":order_id"=>$order_id));
       foreach ($list["goods"] as $k=>$v){
           $list["goods"][$k]["thumb"]=tomedia($v["thumb"]);
       }
       //获取地址
       $list["address"]=pdo_fetch("select realname,mobile,province,city,area,address from ".tablename("ewei_shop_member_address")." where id=:id",array(":id"=>$order["addressid"]));
       apperror(0,"",$list);
   }
   //申请退款||退货退款
   public function refund_submit(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $order_id = intval($_GPC['order_id']);
       $order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where id=:id limit 1', array(':id' => $order_id));
       if (empty($order)) {
           apperror(1,"不存在该订单");
       }
       if ($order["user_id"]!=$member["id"]){
           apperror(1,"该用户无权限访问此订单");
       }
       if ($order["status"]==0){
           apperror(1,"该订单暂不可申请售后");
       }
       $rtype=$_GPC["rtype"];
       $refund["rtype"]=$rtype;
       $refund["reason"]=$_GPC["reason"];
       $refund["refundno"] = m("common")->createNO("groups_order_refund", "refundno", "PR");
       $refund["orderid"]=$order_id;
       $refund["openid"]=$member["openid"];
       $refund["user_id"]=$member["id"];
       $refund["applycredit"] = $order["credit"];
       $refund["applytime"] = time();
       $refund["content"]=$_GPC["content"];
       if ($order["status"]==1){
           $refund["applyprice"]=$order["price"]+$order["freight"];
       }else{
           $refund["applyprice"]=$order["price"];
       }
       $refund["uniacid"]=$_W["uniacid"];
       $refund["refundaddressid"]=$order["addressid"];
       if ($order["refundstate"]==0){
           pdo_insert("ewei_shop_groups_order_refund",$refund);
           $refundid = pdo_insertid();
           pdo_update("ewei_shop_groups_order", array( "refundid" => $refundid, "refundstate" => 1 ), array( "id" => $order_id, "uniacid" => $_W["uniacid"] ));
       }else{
           
           pdo_update("ewei_shop_groups_order", array( "refundstate" => 1 ), array( "id" => $order_id, "uniacid" => $_W["uniacid"] ));
           pdo_update("ewei_shop_groups_order_refund", $refund, array( "id" => $order["refundid"], "uniacid" => $_W["uniacid"] ));
       }
       $res["refundid"]=$refundid?$refundid:$order["refundid"];
       apperror(0,"",$res);
   }
   //售后申请--取消
   public function cancel_refund(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $refund_id=$_GPC["refund_id"];
       $refund=pdo_get("ewei_shop_groups_order_refund",array("id"=>$refund_id));
       if (empty($refund)){
           apperror(1,"refund_id不正确");
       }
       if ($member["id"]!=$refund["user_id"]){
           apperror(1,"您无权限操作改信息");
       }
       $data["refundstatus"]=-2;
       $data["refundtime"]=time();
       if (pdo_update("ewei_shop_groups_order_refund",$data,array("id"=>$refund_id))){
           pdo_update("ewei_shop_groups_order",array("refundstate"=>0),array("id"=>$refund["orderid"]));
           apperror(0,"取消成功");
       }else{
           apperror(1,"取消失败");
       }
   }
   //换货--提交
   public function exchange_submit(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $order_id=$_GPC["order_id"];
       $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
       if (empty($order)){
           apperror(1,"订单id不正确");
       }
       if ($order["status"]!=2){
           apperror(1,"该订单暂不可申请换货");
       }
       if ($member["id"]!=$order["user_id"]){
           apperror(1,"无权限访问该订单");
       }
       $goods_option_id=$_GPC["optionid"];
       if ($order["more_spec"]==1&&empty($goods_option_id)){
           apperror(1,"请选择更换的规格");
       }
       $option=pdo_get("ewei_shop_groups_goods_option",array("id"=>$goods_option_id,"groups_goods_id"=>$order["goodid"]));
       if (empty($option)){
           apperror(1,"规格id不正确");
       }
       $ordergood=pdo_get("ewei_shop_groups_order_goods",array("groups_order_id"=>$order_id));
       $total=$_GPC["total"]?$_GPC["total"]:1;
       if ($ordergood["total"]<$total){
           apperror(1,"更换数量不可大于购买数量");
       }
       $rtype=2;
       $refund["rtype"]=$rtype;
       $refund["reason"]=$_GPC["reason"];
       $refund["content"]=$_GPC["content"];
       $refund["refundno"] = m("common")->createNO("groups_order_refund", "refundno", "PR");
       $refund["orderid"]=$order_id;
       $refund["openid"]=$member["openid"];
       $refund["user_id"]=$member["id"];
       $refund["applycredit"] = $order["credit"];
       $refund["applytime"] = time();
       $refund["applyprice"]=$order["price"];
       $refund["uniacid"]=$_W["uniacid"];
       $refund["refundaddressid"]=$order["addressid"];
       $refund["total"]=$total;
       $refund["goods_option_id"]=$goods_option_id;
       $refund["option"]=$option["title"];
       if ($order["refundstate"]==0){
           pdo_insert("ewei_shop_groups_order_refund",$refund);
           $refundid = pdo_insertid();
           pdo_update("ewei_shop_groups_order", array( "refundid" => $refundid, "refundstate" => 2 ), array( "id" => $order_id, "uniacid" => $_W["uniacid"] ));
       }else{
           pdo_update("ewei_shop_groups_order", array( "refundstate" => 2 ), array( "id" => $order_id, "uniacid" => $_W["uniacid"] ));
           pdo_update("ewei_shop_groups_order_refund", $refund, array( "id" => $order["refundid"], "uniacid" => $_W["uniacid"] ));
       }
       $res["refundid"]=$refundid?$refundid:$order["refundid"];
       apperror(0,"",$res);
   }
   //客户寄物品--快递
   public function submit_express(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $refund_id=$_GPC["refund_id"];
       $refund=pdo_get("ewei_shop_groups_order_refund",array("id"=>$refund_id));
       if (empty($refund)){
           apperror(1,"售后id不正确");
       }
       $data["expresscom"]=$_GPC["expresscom"];
       $data["express"]=$_GPC["express"];
       $data["expresssn"]=$_GPC["expresssn"];
       $data["sendtime"]=time();
       $data["refundstatus"]=4;
       if (pdo_update("ewei_shop_groups_order_refund",$data,array("id"=>$refund_id))){
           apperror(0,"成功");
       }else{
           apperror(1,"失败");
       }
   }
   //售后进度
   public function sale_progress(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $refund_id=$_GPC["refund_id"];
       $refund=pdo_get("ewei_shop_groups_order_refund",array("id"=>$refund_id));
       if (empty($refund)){
           apperror(1,"售后id不正确");
       }
       $order=pdo_get("ewei_shop_groups_order",array("id"=>$refund["orderid"]));
       $list["ordersn"]=$order["orderno"];
       
       $list["id"]=$refund["id"];
       $list["status"]=$refund["refundstatus"];
       $list["rtype"]=$refund["rtype"];
       $list["createtime"]=date("Y-m-d H:i:s",$refund["applytime"]);
       $list["price"]=$refund["applyprice"];
       if ($refund["refundstatus"]!=-1){
           $list["operatetime"]=$refund["operatetime"]?date("Y-m-d H:i:d",$refund["operatetime"]):"";
       }else{
           $list["operatetime"]=$refund["endtime"]?date("Y-m-d H:i:d",$refund["endtime"]):"";
       }
       $list["expresssn"]=$refund["expresssn"];
       $list["expresscom"]=$refund["expresscom"];
       $list["sendtime"]=$refund["sendtime"]?date("Y-m-d H:i:s",$refund["sendtime"]):"";
       $list["reason"]=$refund["reason"];
       if ($refund["rtype"]==0||$refund["rtype"]==1){
           $list["time"]=$refund["refundtime"]?date("Y-m-d H:i:s",$refund["refundtime"]):"";
       }else{
           $list["time"]=$refund["returntime"]?date("Y-m-d H:i:s",$refund["returntime"]):"";
       }
       apperror(0,"",$list);
   }
   //个人中心--拼团
   public function my(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       
       $order=pdo_fetchall("select * from ".tablename("ewei_shop_groups_order")." where user_id=:user_id and endtime>:endtime and is_team=1 and success=0 and status=1  order by  endtime asc limit 5",array(":endtime"=>time(),":user_id"=>$member["id"]));
      
       $list=array();
       foreach ($order as $k=>$v){
           
           $list[$k]["teamid"]=$v["id"];
           $list[$k]["endtime"]=$v["endtime"];
         
           //获取总数量
           $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where is_team=1 and status=1 and teamid=:teamid",array(":teamid"=>$v["id"]));
           $list[$k]["count"]=$count;
           $list[$k]["groupnum"]=$v["groupnum"];
           $list[$k]["number"]=$v["groupnum"]-$count;
           //获取头像
           $team=pdo_fetchall("select openid,user_id from ".tablename("ewei_shop_groups_order")." where  is_team=1 and status=1 and teamid=:teamid",array(":teamid"=>$v["id"]));
           $list[$k]["avatar"]=array();
           $list[$k]["userid"]=array();
           foreach ($team as $kk=>$vv){
               if ($vv["user_id"]){
                   $team_member=pdo_get("ewei_shop_member",array("id"=>$vv["user_id"]));
               }else{
                   $team_member=pdo_get("ewei_shop_member",array("openid"=>$vv["openid"]));
               }
               
               $list[$k]["avatar"][$kk]=$team_member["avatar"];
               $list[$k]["userid"][$kk]=$team_member["id"];
           }
           $good=pdo_get("ewei_shop_groups_goods",array("id"=>$v["goodid"]));
           $list[$k]["thumb"]=tomedia($good["thumb"]);
       }
       $res["list"]=$list;
       apperror(0,"",$list);
   }
}