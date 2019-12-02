<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Superior_EweiShopV2Page extends AppMobilePage
{
    //获取banner
    public function banner(){
        global $_GPC;
        global $_W;
        //获取bannenr
        $list["banner"]=pdo_fetchall("select * from ".tablename("ewei_shop_jdbanner")." order by sort desc");
        //获取分类
        $list["cate"]=pdo_fetchall("select * from ".tablename("ewei_shop_jdgoods_cate")." order by sort desc");
        apperror(0,"",$list);
    }
    //列表
    public function goodlist(){
        global $_GPC;
        global $_W;
        $cate_id=$_GPC["cate_id"];
        $condition=" and onsale=1 and isdelete=0 and saleState=:saleState";
        $param[":saleState"]=1;
        //分类
        if ($cate_id){
            $condition.=" and cateid=:cateid";
            $param[":cateid"]=$cate_id;
        }
        //排序
        $price=$_GPC["price"];
        if ($price){
            $order=" order by ptprice ".$price;
        }
        //销量
        $sale=$_GPC["sale"];
        if ($sale){
            $order=" order by sale ".$sale;
        }
        if (empty($order)){
            $order=" order by id desc";
        }
        $page=$_GPC["page"]?$_GPC["page"]:1;
        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_jdgoods")." where 1  ".$condition,$param);
        $first=($page-1)*20;
        $list=pdo_fetchall("select id,imagePath,name,ptprice from ".tablename("ewei_shop_jdgoods")." where 1  ".$condition.$order." limit ".$first.",20",$param);
        $url=m("jdgoods")->homeaddr();
        foreach ($list as $k=>$v){
            $list[$k]["imagePath"]=$url.$v["imagePath"];
        }
        //获取总页数
        $pagetotal=ceil($total/20);
        $l["list"]=$list;
        $l["page"]=$page;
        $l["total"]=$total;
        $l["pagesize"]=20;
        $l["pagetotal"]=$pagetotal;
        apperror(0,"",$l);
    }
    //获取商品详情
    public function detail(){
        global $_GPC;
        global $_W;
        $id=$_GPC["id"];
        if (empty($id)){
            apperror(1,"商品id不可为空");
        }
        //获取商品
        $detail=pdo_fetch("select id,saleUnit,weight,imagePath,productArea,wareQD,param,sku,brandName,upc,appintroduce,jdprice,ptprice,price,isdelete,onsale from ".tablename("ewei_shop_jdgoods")." where id=:id ",array(":id"=>$id));
        if (empty($detail)){
            apperror(1,"商品id不正确");
        }
        //判断商品销售状况
        if ($detail["isdelete"]==1||$detail["onsale"]==0){
            apperror(1,"该商品已下架");
        }
        $sku=$detail["sku"];
        $res=m("jdgoods")->onsale($sku);
        if (!$res){
            apperror(1,"该商品暂时无法查询上架情况");
        }
        if ($res[0]["state"]==0){
            apperror(1,"该商品三方已下架");
        }
        //获取三方价格
        $price=m("jdgoods")->batch_price($sku);
        $price=$price["result"][0];
        //更新供货价
        $p["price"]=$price["price"];
        $p["jdprice"]=$price["jdprice"];
        pdo_update("ewei_shop_jdgoods",$p,array("id"=>$id));
//         var_dump($price);var_dump($detail["ptprice"]);die;
        if ($price["price"]>$detail["ptprice"]){
            $detail["ptprice"]=$price["jdPrice"];
            $detail["price"]=$price["price"];
            $detail["jdprice"]=$price["jdPrice"];
            //更新
            $d["ptprice"]=$price["jdPrice"];
            $d["price"]=$price["price"];
            $d["jdprice"]=$price["jdPrice"];
            pdo_update("ewei_shop_jdgoods",$d,array("id"=>$id));
        }
        //获取图片集
        $detail["img"]=array();
        $img=m("jdgoods")->img($sku);
//          var_dump($img[$detail["sku"]]);die;
        $url=m("jdgoods")->homeaddr();
        $imgurl=m("jdgoods")->imgaddr();
        if (!$img){
            $detail["img"][0]=$url.$detail["imagePath"];
        }else{
           $img= $img[$detail["sku"]];
           foreach ($img as $k=>$v){
               $detail["img"][$k]=$imgurl.$v["path"];
           }
        }
        $detail["imagePath"]=$url.$detail["imagePath"];
        apperror(0,"",$detail);
    }
    //获取收货地址
    public function address(){
        global $_GPC;
        global $_W;
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $id=$_GPC["id"];
        if (($type==1||$type==2)&&empty($id)){
            apperror(1,"上级id未传入");
        }
        if ($type==0){
        $province=m("jdgoods")->address();
        }elseif ($type==1){
        $province=m("jdgoods")->city($id);
        }elseif ($type==2){
        $province=m("jdgoods")->area($id);
        }
        $i=0;
        $list["province"]=array();
        foreach ($province as $k=>$v){
            $list["province"][$i]["name"]=$k;
            $list["province"][$i]["id"]=$v;
            //获取市
//             $list["province"][$i]["city"]=array();
//             $city=m("jdgoods")->city($v);
//             $ii=0;
//             foreach ($city as $c=>$cv){
//                 $iii=0;
//                 $list["province"][$i]["city"][$ii]["city_name"]=$c;
//                 $list["province"][$i]["city"][$ii]["city_id"]=$cv;
//                 //获取区
//                 $list["province"][$i]["city"][$ii]["area"]=array();
//                 $area=m("jdgoods")->area($cv);
//                 foreach ($area as $a=>$av){
//                     $list["province"][$i]["city"][$ii]["area"][$iii]["area_name"]=$a;
//                     $list["province"][$i]["city"][$ii]["area"][$iii]["area_id"]=$av;
//                     $iii+=1;
//                 }
//                 $ii+=1;
//             }

            $i+=1;
        }
       apperror(0,"",$list);
    }
    //添加收货地址
    public function add_address(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        $data["uniacid"]=1;
        $data["user_id"]=$member["id"];
        $data["openid"]=$member["openid"];
        $data["realname"]=$_GPC["realname"];
        $data["mobile"]=$_GPC["mobile"];
        $data["deleted"]=1;
        $data["province"]=$_GPC["province"];
        $data["city"]=$_GPC["city"];
        $data["area"]=$_GPC["area"];
        $data["address"]=$_GPC["address"];
        $data["province_id"]=$_GPC["province_id"];
        $data["city_id"]=$_GPC["city_id"];
        $data["area_id"]=$_GPC["area_id"];
        $data["jdtype"]=1;
        pdo_insert("ewei_shop_member_address",$data);
        $res["address_id"]=pdo_insertid();
        $res["province_id"]=$_GPC["province_id"];
        $res["city_id"]=$_GPC["city_id"];
        $res["area_id"]=$_GPC["area_id"];
        apperror(0,"",$res);
    }
    //获取运费
    public function freight(){
        global $_GPC;
        global $_W;
        $data["province"]=$_GPC["province"];
        $data["city"]=$_GPC["city"];
        $data["county"]=$_GPC["county"];
        $data["town"]=0;
        $sku=$_GPC["sku"];
        $num=$_GPC["num"];
        $d[0]["skuId"]=$sku;
        $d[0]["num"]=$num;
        $data["sku"]=json_encode($d);
        $feight=m("jdgoods")->freight($data);
        
        if (!$feight["success"]){
            apperror(1,$feight["resultMessage"]);
        }
        $feight=$feight["result"];
        $l["freight"]=$feight["freight"];
        apperror(0,"",$l);
    }
    //下单
    public function order(){
        global $_GPC;
        global $_W;
        $address_id=$_GPC["address_id"];
        $address=pdo_get("ewei_shop_member_address",array("id"=>$address_id));
//         var_dump($address);die;
        if (empty($address)){
            apperror(1,"收货地址id不存在");
        }
        //用户
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        //获取商品
        $goods_id=$_GPC["goods_id"];
        if (empty($goods_id)){
            apperror(1,"商品id未传");
        }
        $good=pdo_get("ewei_shop_jdgoods",array("id"=>$goods_id));
        if ($good["isdelete"]==1||$good["onsale"]==0){
            apperror(1,"该商品已下架");
        }
        //获取三方价格
        $price=m("jdgoods")->batch_price($good["sku"]);
//         var_dump($price);
        if ($price["success"]){
        $price=$price["result"][0];
        //数量
        $total=$_GPC["total"]?$_GPC["total"]:1;
        //更新供货价
        $p["price"]=$price["price"];
        $p["jdprice"]=$price["jdprice"];
        pdo_update("ewei_shop_jdgoods",$p,array("id"=>$goods_id));
        $supply_price=$price["price"];
        }else{
        $supply_price=$good["price"];
        }
        //获取运费
        $data["province"]=$address["province_id"];
        $data["city"]=$address["city_id"];
        $data["county"]=$address["area_id"];
        $data["town"]=0;
       
        $num=$total;
        $d[0]["skuId"]=$good["sku"];
        $d[0]["num"]=$num;
        $data["sku"]=json_encode($d);
        $feight=m("jdgoods")->freight($data);
//         var_dump($feight);
        if ($feight["success"]){
        $feight=$feight["result"];
        $feight=$feight["freight"];
        }else{
            apperror(1,$feight["resultMessage"]);
        }
        
        //生成本平台订单编号
        $ordersn="JDYP".date("YmdHis") . random(6, true);
        
        //优品京东订单
        $jd["name"]=$address["realname"];
        $jd["province"]=$address["province_id"];
        $jd["city"]=$address["city_id"];
        $jd["county"]=$address["area_id"];
        $jd["town"]=0;
        $jd["address"]=$address["address"];
        $jd["mobile"]=$address["mobile"];
        $jd["thirdOrder"]=$ordersn;
        $sku[0]["skuId"]=$good["sku"];
        $sku[0]["num"]=$total;
        $sku[0]["bNeedAnnex"]=true;
        $sku[0]["bNeedGift"]=true;
        $jd["sku"]=json_encode($sku);
        $orderPriceSnap[0]["skuId"]=$good["sku"];
        $orderPriceSnap[0]["price"]=$supply_price;
        $jd["orderPriceSnap"]=json_encode($orderPriceSnap);
        $jd["remark"]=$_GPC["remark"];
      
        $jdres=m("jdgoods")->order($jd);
//         var_dump($jdres);die;
        
        if (!$jdres["success"]){
            apperror(1,$jdres["resultMessage"]);
        }
         $order["jdOrderId"]=$jdres["result"]["jdOrderId"];
//         var_dump($order["jdOrderId"]);
         $order["ordersn"]=$ordersn;
         $order["uniacid"]=1;
         $order["openid"]=$member["openid"];
         $order["user_id"]=$member["id"];
         $order["price"]=$good["ptprice"]*$total+$feight;
         $order["goodsprice"]=$good["ptprice"]*$total;
         $order["remark"]=$_GPC["remark"];
         $order["addressid"]=$address_id;
         $order["dispatchprice"]=$feight;
         $order["createtime"]=time();
         $order["oldprice"]=$good["ptprice"]*$total+$feight;
         $order["olddispatchprice"]=$feight;
         $order["jdtype"]=1;
         $order["jdprice"]=$jdres["result"]["orderPrice"];
         pdo_insert("ewei_shop_order",$order);
         $order_id=pdo_insertid();
         //添加订单商品
         $order_good["uniacid"]=1;
         $order_good["orderid"]=$order_id;
         $order_good["goodsid"]=$goods_id;
         $order_good["price"]=$good["ptprice"];
         $order_good["total"]=$total;
         $order_good["createtime"]=time();
         $order_good["realprice"]=$good["ptprice"]*$total+$feight;
         $order_good["openid"]=$member["openid"];
         $order_good["dispatchprice"]=$feight;
         pdo_insert("ewei_shop_order_goods",$order_good);
         $res["orderid"]=$order_id;
        
         apperror(0,"",$res);
    }
    //取消未支付订单
    public function cancel_order(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")." where id=:order_id and deleted=0 and userdeleted=0",array(":order_id"=>$order_id));
        if (empty($order)){
            apperror(1,"该订单不存在");
        }
        if ($order["status"]==-1){
            apperror(1,"不可重复取消");
        }
        //京东取消
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单不可调用此接口");
        }
//         var_dump($order["jdOrderId"]);
        $jd["jdOrderId"]=$order["jdOrderId"];
        $res=m("jdgoods")->cancel($jd);
        if ($res){
            $d["status"]=-1;
            $d["canceltime"]=time();
            pdo_update("ewei_shop_order",$d,array("id"=>$order_id));
            apperror(0,"取消成功");
        }else{
            apperror(1,"取消失败");
        }
    }
   //支付
   public function pay(){
       global $_GPC;
       global $_W;
       $order_id=$_GPC["orderid"];
       $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
       if (empty($order)){
           apperror(1,"订单id不正确");
       }
       if ($order["status"]==-1){
           apperror(1,"该订单已被取消");
       }
       $payinfo = array( "openid" =>$order["openid"], "title" =>"京东订单", "tid" =>$order["ordersn"], "fee" => $order["price"]);
       $res["wx"] = $this->wxpay($payinfo, 98);
        if ($res["wx"]["errno"]==-2){
            apperror(1,"暂不可支付");
        };
       $res["orderid"]=$order_id;
       apperror(0,"",$res);
   }
    /**
     * 小程序微信支付
     * @param $params
     * @param int $type
     * @return array
     */
    public function wxpay($params, $type = 0)
    {
        global $_W;
        $data = m('common')->getSysset('app');
        $openid = ((empty($params['openid']) ? $_W['openid'] : $params['openid']));
        if (isset($openid) && strexists($openid, 'sns_wa_')) {
            $openid = str_replace('sns_wa_', '', $openid);
        }
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        $package = array();
        $package['appid'] = $data['appid'];
        $package['mch_id'] = $sec['wxapp']['mchid'];
        $package['nonce_str'] = random(32);
        $package['body'] = $params['title'];
        $package['device_info'] = 'ewei_shopv2';
        $package['attach'] = $_W['uniacid'] . ':' . $type;
        $package['out_trade_no'] = $params['tid'];
        $package['total_fee'] = $params['fee'] * 100;
        $package['spbill_create_ip'] = CLIENT_IP;
        if (!(empty($params['goods_tag']))) {
            $package['goods_tag'] = $params['goods_tag'];
        }
        $package['notify_url'] = $_W['siteroot'] . 'addons/ewei_shopv2/payment/wechat/notify.php';
        $package['trade_type'] = 'JSAPI';
        $package['openid'] = $openid;
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= $key . '=' . $v . '&';
        }
        $string1 .= 'key=' . $sec['wxapp']['apikey'];
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
        if (is_error($response)) {
            return error(-1, $response['message']);
        }
        $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL') {
            return error(-2, strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL') {
            return error(-3, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        $prepayid = $xml->prepay_id;
        $wOpt['appId'] = $data['appid'];
        $wOpt['timeStamp'] = TIMESTAMP . '';
        $wOpt['nonceStr'] = random(32);
        $wOpt['package'] = 'prepay_id=' . $prepayid;
        $wOpt['signType'] = 'MD5';
        ksort($wOpt, SORT_STRING);
        $string = '';
        foreach ($wOpt as $key => $v) {
            $string .= $key . '=' . $v . '&';
        }
        $string .= 'key=' . $sec['wxapp']['apikey'];
        $wOpt['paySign'] = strtoupper(md5($string));
        unset($wOpt['appId']);
        return $wOpt;
    }
    //确认收货
    public function finish(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if ($order["status"]==-1){
            apperror(1,"该订单已被取消");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        $d["jdOrderId"]=$order["jdOrderId"];
        $res=m("jdgoods")->confirmReceived($d);
        if ($res["success"]){
            $o["status"]=3;
            pdo_update("ewei_shop_order",$o,array("id"=>$order_id));
            apperror(0,"成功");
        }else{
           apperror(1,"失败");
        }
    }
    //订单详情
    public function orderdetail(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select id,ordersn,price,status,addressid,dispatchprice,createtime,paytime,jdOrderId from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        $order["createtime"]=date("Y-m-d H:i:s",$order["createtime"]);
        
        //获取京东详情
        $jddata["jdOrderId"]=$order["jdOrderId"];
        $jddata["queryExts"]="orderType,jdOrderState";
        $res=m("jdgoods")->selectJdOrder($jddata);
        //获取状态
        $order["jdOrderState"]=$res["result"]["jdOrderState"];
        $order["jdstatus_msg"]=m("jdgoods")->status($res["result"]["jdOrderState"]);
        //获取商品
        $good=pdo_fetch("select id,goodsid,price,total from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$order_id));
        $order["goods"]=$good;
        $url=m("jdgoods")->homeaddr();
      
        $g=pdo_get("ewei_shop_jdgoods",array("id"=>$good["goodsid"]));
         $order["goods"]["imagePath"]=$url.$g["imagePath"];
      
        //获取收货地址
        $address=pdo_fetch("select realname,mobile,province,city,area,address from ".tablename("ewei_shop_member_address")." where id=:id",array(":id"=>$order["addressid"]));
        $order["address"]=$address;
        apperror(0,"",$order);  
        
    }
    //查询物流
    public function orderTrack(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select id,ordersn,price,status,addressid,dispatchprice,createtime,paytime,jdOrderId from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        $data["jdOrderId"]=$order["jdOrderId"];
        $res=m("jdgoods")->orderTrack($data);
        if ($res["success"]){
            $r["orderTrack"]=$res["result"]["orderTrack"];
        }else{
            apperror(1,$res["resultMessage"]);
        }
        apperror(0,"",$r);
    }
    //申请售后展示
    public function after_view(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        //获取商品
        $order_good=pdo_get("ewei_shop_order_goods",array("orderid"=>$order_id));
        $list=pdo_fetch("select id,sku,imagePath,name from ".tablename("ewei_shop_jdgoods")." where id=:goodsid",array(":goodsid"=>$order_good["goodsid"]));
        $url=m("jdgoods")->homeaddr();
        $list["imagePath"]=$url.$list["imagePath"];
        $list["orderid"]=$order_id;
        $list["total"]=$order_good["total"];
        $list["price"]=$order_good["price"];
        //获取提供的售后
        $jd["jdOrderId"]=$order["jdOrderId"];
        $jd["skuId"]=$list["sku"];
        $res=m("jdgoods")->getCustomerExpectComp($jd);
        $list["service"]=array();
        $list["service"]["resultMessage"]=$res["resultMessage"];
        $list["service"]["result"]=$res["result"];
        apperror(0,"",$list);
        
    }
    //售后--展示
    public function after_return(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        //获取商品
        $order_good=pdo_get("ewei_shop_order_goods",array("orderid"=>$order_id));
        $list=pdo_fetch("select id,sku,imagePath,name from ".tablename("ewei_shop_jdgoods")." where id=:goodsid",array(":goodsid"=>$order_good["goodsid"]));
        $url=m("jdgoods")->homeaddr();
        $list["imagePath"]=$url.$list["imagePath"];
        $list["orderid"]=$order_id;
        $list["total"]=$order_good["total"];
        $list["price"]=$order_good["price"];
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $list["address"]=array();
        $list["address"]["province"]=$address["province"];
        $list["address"]["city"]=$address["city"];
        $list["address"]["area"]=$address["area"];
        $list["address"]["address"]=$address["address"];
        $list["address"]["realname"]=$address["realname"];
        $list["address"]["mobile"]=$address["mobile"];
        //获取返回方式
        $jd["jdOrderId"]=$order["jdOrderId"];
        $jd["skuId"]=$list["sku"];
        $res=m("jdgoods")->getWareReturnJdComp($jd);
        $list["service"]=array();
        $list["service"]["resultMessage"]=$res["resultMessage"];
        $list["service"]["result"]=$res["result"];
        apperror(0,"",$list);
    }                       
    //售后--提交
    public function after_submit(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        //获取商品
        $order_good=pdo_get("ewei_shop_order_goods",array("orderid"=>$order_id));
        $good=pdo_fetch("select id,sku,imagePath,name from ".tablename("ewei_shop_jdgoods")." where id=:goodsid",array(":goodsid"=>$order_good["goodsid"]));
        $jd["jdOrderId"]=$order["jdOrderId"];
        $jd["customerExpect"]=$_GPC["customerExpect"];//售后类型 退货(10)、换货(20)、 维修(30)
        $jd["questionDesc"]=$_GPC["questionDesc"];
        $jd["isNeedDetectionReport"]="true";
        $jd["isHasPackage"]="false";
        $jd["packageDesc"]=0;
        //图片
        $img=$_GPC["img"];
        $jd["questionPic"]="";
        foreach ($img as $k=>$v){
            if (empty($jd["questionPic"])){
                $jd["questionPic"]=tomedia($v);
            }else{
                $jd["questionPic"]=$jd["questionPic"].",".$v;
            }
        }
        //取件方式
        $pickwareType=$_GPC["pickwareType"];//4上门取件 40客户发货
        if (empty($pickwareType)){
            apperror(1,"取件方式不可为空");
        }
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        if ($pickwareType==40){
            //客户发货：默认收货地址
            $pick["pickwareProvince"]=$address["province_id"];
            $pick["pickwareCity"]=$address["city_id"];
            $pick["pickwareCounty"]=$address["area_id"];
            $pick["pickwareVillage"]=0;
            $pick["pickwareAddress"]=$address["address"];
        }else{
            //取件地址
            $pick["pickwareProvince"]=$_GPC["pick_provinceid"]?$_GPC["pick_provinceid"]:$address["province_id"];
            $pick["pickwareCity"]=$_GPC["pick_cityid"]?$_GPC["pick_cityid"]:$address["city_id"];
            $pick["pickwareCounty"]=$_GPC["pick_areaid"]?$_GPC["pick_areaid"]:$address["area_id"];
            $pick["pickwareVillage"]=0;
            $pick["pickwareAddress"]=$_GPC["pick_address"]?$_GPC["pick_address"]:$address["address"];
        }
        $pick["pickwareType"]=$pickwareType;
        $jd["asPickwareDto"]=json_encode($pick);
        //获取客户信息
        if ($pickwareType==4){
            //上门取件
            $customer["customerContactName"]=$address["realname"];
            $customer["customerTel"]=$address["mobile"];
            $customer["customerMobilePhone"]=$address["mobile"];
            $customer["customerEmail"]="";
            $customer["customerPostcode"]="";
        }else{
            //客户自己寄件
            $customer["customerContactName"]=$_GPC["customer_realname"]?$_GPC["customer_realname"]:$address["realname"];
            $customer["customerTel"]=$address["customer_mobile"]?$_GPC["customer_mobile"]:$address["mobile"];
            $customer["customerMobilePhone"]=$address["customer_mobile"]?$_GPC["customer_mobile"]:$address["mobile"];
            $customer["customerEmail"]="";
            $customer["customerPostcode"]="";
        }
        $jd["asCustomerDto"]=json_encode($customer);
        //返件信息
        if ($_GPC["customerExpect"]==20||$_GPC["customerExpect"]==30){
            //换货||维修
            $return["returnwareType"]=10;
            $return["returnwareProvince"]=$_GPC["returnProvince"]?$_GPC["returnProvince"]:$address["province_id"];
            $return["returnwareCity"]=$_GPC["returnCity"]?$_GPC["returnCity"]:$address["city_id"];
            $return["returnwareCounty"]=$_GPC["returnCounty"]?$_GPC["returnCounty"]:$address["area_id"];
            $return["returnwareVillage"]=0;
            $return["returnwareAddress"]=$_GPC["returnAddress"]?$_GPC["returnAddress"]:$address["address"];
            $jd["asReturnwareDto"]=json_encode($return);
        }else{
            $return["returnwareType"]=10;
            $return["returnwareProvince"]=$address["province_id"];
            $return["returnwareCity"]=$address["city_id"];
            $return["returnwareCounty"]=$address["area_id"];
            $return["returnwareVillage"]=0;
            $return["returnwareAddress"]=$address["address"];
            $jd["asReturnwareDto"]=json_encode($return);
        }
        $g["skuId"]=$good["sku"];
        //申请售后数量
        $total=$_GPC["total"]?$_GPC["total"]:1;
        if ($total>$order_good["total"]){
            apperror(1,"申请数量不可大于订单商品数量");
        }
         $g["skuNum"]=$total;
         $jd["asDetailDto"]=json_encode($g);
//          var_dump($jd);
         $res=m("jdgoods")->createAfsApply($jd);
         if (!$res["success"]){
             apperror(1,$res["resultMessage"]);
         }
         //提交成功的情况下
         $sc["jdOrderId"]=$order["jdOrderId"];
         $sc["pageSize"]=10;
         $sc["pageIndex"]=1;
         $scres=m("jdgoods")->getServiceListPage($sc);
         $serv=$scres["result"]["serviceInfoList"][0];
         //订单更新
         $orderdata["jdafsServiceId"]=$serv["afsServiceId"];
         $orderdata["jdcancel"]=$serv["cancel"];
         $orderdata["refundstate"]=2;
         $orderdata["jdcustomerExpect"]=$jd["customerExpect"];
         pdo_update("ewei_shop_order",$orderdata,array("id"=>$order_id));
         $r["orderid"]=$order_id;
         $r["jdafsServiceId"]=$orderdata["jdafsServiceId"];
         apperror(0,"",$r);
    }
    //客户发运信息
    public function updateSendSku(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        if ($order["jdcustomerExpect"]==0){
            apperror(1,"该订单未申请售后");
        }
        $jdafsServiceId=$order["jdafsServiceId"];
        if (empty($order["jdafsServiceId"])){
            //提交成功的情况下
            $sc["jdOrderId"]=$order["jdOrderId"];
            $sc["pageSize"]=10;
            $sc["pageIndex"]=1;
            $scres=m("jdgoods")->getServiceListPage($sc);
            $serv=$scres["result"]["serviceInfoList"][0];
            //订单更新
            $orderdata["jdafsServiceId"]=$serv["afsServiceId"];
            pdo_update("ewei_shop_order",$orderdata,array("id"=>$order_id));
            $jdafsServiceId=$serv["afsServiceId"];
        }
        $jd["afsServiceId"]=$jdafsServiceId;
        $jd["freightMoney"]=$_GPC["freightmoney"]?$_GPC["freightmoney"]:0;//运费
        $jd["expressCompany"]=$_GPC["expressCompany"];//快递
        $jd["deliverDate"]=date("Y-m-d H:i:s");
        $jd["expressCode"]=$_GPC["expressCode"];
        if (empty($jd["expressCompany"])||empty($jd["expressCode"])){
            apperror(1,"快递公司和单号不可为空");
        }
        $res=m("jdgoods")->updateSendSku($jd);
        if ($res["success"]){
            $d["jdcustomer"]=1;
            pdo_update("ewei_shop_order",$d,array("id"=>$order_id));
            apperror(0,"提交成功");
        }else{
            apperror(1,$res["resultMessage"]);
        }
    }
    //取消售后申请
    public function auditCancel(){
        global $_GPC;
        global $_W;
        $order_id=$_GPC["orderid"];
        $order=pdo_fetch("select * from ".tablename("ewei_shop_order")."where id=:id",array(":id"=>$order_id));
        if (empty($order)){
            apperror(1,"订单id不正确");
        }
        if (empty($order["jdOrderId"])){
            apperror(1,"该订单不是京东订单");
        }
        if ($order["status"]!=1){
            apperror(1,"该订单不是支付的订单");
        }
        if ($order["jdcustomerExpect"]==0){
            apperror(1,"该订单未申请售后");
        }
        $jdafsServiceId=$order["jdafsServiceId"];
        if (empty($order["jdafsServiceId"])){
            //提交成功的情况下
            $sc["jdOrderId"]=$order["jdOrderId"];
            $sc["pageSize"]=10;
            $sc["pageIndex"]=1;
            $scres=m("jdgoods")->getServiceListPage($sc);
            $serv=$scres["result"]["serviceInfoList"][0];
            //订单更新
            $orderdata["jdafsServiceId"]=$serv["afsServiceId"];
            pdo_update("ewei_shop_order",$orderdata,array("id"=>$order_id));
            $jdafsServiceId=$serv["afsServiceId"];
        } 
//         var_dump($jdafsServiceId);die;
        $jd["serviceId"]=$jdafsServiceId;
        $jd["approveNotes"]=$_GPC["reason"];
        if (empty($_GPC["reason"])){
            apperror(1,"取消原因不可为空");
        }
        $res=m("jdgoods")->auditCancel($jd);
        if ($res["success"]){
            //成功
            $orderdata["jdafsServiceId"]="";
            $orderdata["jdcancel"]=0;
            $orderdata["refundstate"]=0;
            $orderdata["jdcustomerExpect"]=0;
            pdo_update("ewei_shop_order",$orderdata,array("id"=>$order_id));
            apperror(0,"取消成功");
        }else{
            apperror(1,$res["resultMessage"]);
        }
    }
    
    
    public function comfim(){
        $data["jdOrderId"]="107216381949";
        $res=m("jdgoods")->confirmOrder($data);
        var_dump($res);
    }
}