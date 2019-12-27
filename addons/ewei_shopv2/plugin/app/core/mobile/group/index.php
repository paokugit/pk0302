<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Index_EweiShopV2Page extends AppMobilePage
{
    //banner
    public function banner(){
        global $_W;
        global $_GPC;
        $adv=pdo_fetchall("select id,thumb from ".tablename("ewei_shop_groups_adv")." where enabled=1 order by displayorder desc");
        $adv=set_medias($adv, array( "thumb" ));
        apperror(0,"",$adv);
    }
    //商品列表
    public function goodslist(){
        global $_W;
        global $_GPC;
        $page=$_GPC["page"]?$_GPC["page"]:1;
        $price=$_GPC["price"];
        if ($price){
            $order=" groupsprice ".$price;
        }
        $sale=$_GPC["sale"];
        if ($sale){
            $order=" sales ".$sale;
        }
        if (empty($order)){
            $order=" id desc";
        }
        $pageindex=($page-1)*20;
        $condition=" stock>0 and status=1";
        $list=pdo_fetchall("select id,title,groupsprice,thumb,sales,groupnum,freight,merchid from ".tablename("ewei_shop_groups_goods")." where ".$condition." order by ".$order." limit ".$pageindex.",20");
       
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
            if ($v["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$v["merchid"]));
            $list[$k]["merchname"]=$merch["merchname"];
            }else{
            $list[$k]["merchname"]="跑库自营";
            }
        }
        if (empty($list)){
            $list=new ArrayObject();
        }
        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_goods")." where ".$condition);
        $res["list"]=$list;
        $res["total"]=$total;
        $res["pagesize"]=20;
        $res["pageindex"]=$page;
        $res["pagetotal"]=ceil($total/20);
        apperror(0,"",$res);
    }
    //商品详情
    public function good_detail(){
        global $_W;
        global $_GPC;
        $goods_id=$_GPC["goods_id"];
        if (empty($goods_id)){
            apperror(1,"","商品id不可为空");
        }
        $good=pdo_fetch("select id,description,ccate,title,freight,stock,thumb_url,price,groupsprice,single,singleprice,groupnum,content,more_spec,merchid,gid,quality,seven from ".tablename("ewei_shop_groups_goods")." where id=:goods_id and status=1 and deleted=0",array(":goods_id"=>$goods_id));
        if (empty($good)){
            apperror(1,"","商品不存在");
        }
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $openid=$_GPC["openid"];
        if ($openid){
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"","用户不存在");
        }
        }
        $good["services"]=array();
        if ($good["quality"]==1){
            $good["services"][]="正品保证";
            unset($good["quality"]);
        }
        if ($good["seven"]==1){
            $good["services"][]="七天无理由退换";
            unset($good["seven"]);
        }
        $thumb_url=iunserializer($good["thumb_url"]);
        $good["thumb_url"]=array();
        foreach ($thumb_url as $k=>$v){
            $good["thumb_url"][$k]=tomedia($v);
        }
        if ($type==1){
            $good["content"]=m("appnews")->img($good["content"]);
            foreach ($good["content"] as $k=>$v){
                $good["content"][$k]["image"]=tomedia($v);
            }
        }else{
            $good["content"]=m('common')->html_to_images($good['content']);
        }
        //获取商家
        if ($good["merchid"]!=0){
            $merch=pdo_get("ewei_shop_merch_user",array("id"=>$good["merchid"]));
            $good["merchname"]=$merch["merchname"];   
            $good["logo"]=tomedia($merch["logo"]);
        }else{
            $good["merchname"]="跑库自营"; 
            $good["logo"]="";
        }
        //获取总数量
        $good["goodtotal"]=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_goods")." where merchid=:merchid and status=1 and deleted=0 and total>0",array(":merchid"=>$good["merchid"]));
        $good["merch_good"]=pdo_fetchall("select id,title,thumb,marketprice from ".tablename("ewei_shop_goods")." where merchid=:merchid and status=1 and deleted=0 and total>0 order by id desc limit 3",array(":merchid"=>$good["merchid"]));
        $good["merch_good"]=set_medias($good["merch_good"], array( "thumb" ));
        if (empty($good["merch_good"])){
            $good["merch_good"]=array();
        }
        //获取相关的产品
        $good["relevant_good"]=pdo_fetchall("select id,title,thumb,marketprice from ".tablename("ewei_shop_goods")." where ccate=:ccate and status=1 and deleted=0 and total>0 order by sales desc limit 3",array(":ccate"=>$good["ccate"]));
        $good["relevant_good"]=set_medias($good["relevant_good"],array("thumb"));
        if (empty($good["relevant_good"])){
            $good["relevant_good"]=array();
        }
        //获取拼团信息
        $good["group"]=array();
        $good["group"]["count"]=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where goodid=:goodid and status>0 and endtime>:endtime",array(":goodid"=>$goods_id,":endtime"=>time())); 
        $good["group"]["list"]=m("appnews")->group_list($goods_id,0,2);
        
        //获取评价
        $comment=m("appnews")->group_comment($goods_id,0,1);
        $good["comment"]["count"]=$comment["total"];
        $good["comment"]["list"]=$comment["list"][0];
        $good["comment"]["label"]=$comment["label"];
        $good["comment"]["rate"]=$comment["rate"];
       
        apperror(0,"",$good);
    }
    //选择规格
    public function option(){
        global $_W;
        global $_GPC;
        $goods_id=$_GPC["goods_id"];
        if (empty($goods_id)){
            apperror(1,"","商品id不可为空");
        }
        $good=pdo_fetch("select * from ".tablename("ewei_shop_groups_goods")." where id=:goods_id and status=1 and deleted=0",array(":goods_id"=>$goods_id));
        if (empty($good)){
            apperror(1,"","商品不存在");
        }
        if ($good["more_spec"]==0){
            apperror(0,"该商品无规格属性");
        }
        $signal=$_GPC["single"];
        if ($signal==1&&$good["single"]!=1){
            apperror(1,"","该商品不可单购");
        }
        $res["goods"]["id"]=$good["id"];
        $res["goods"]["marketprice"]=$signal?$good["singleprice"]:$good["groupsprice"];
        $res["goods"]["thumb"]=tomedia($good["thumb"]);
        $res["goods"]["total"]=$good["stock"];
        $res["goods"]["maxprice"]=$signal?$good["singleprice"]:$good["groupsprice"];
        $res["goods"]["minprice"]=$signal?$good["singleprice"]:$good["groupsprice"];
        //获取规格
        $res["specs"]=pdo_fetchall("select id,title from ".tablename("ewei_shop_goods_spec")." where goodsid=:goodsid order by id asc",array(":goodsid"=>$good["gid"]));
        foreach ($res["specs"] as $k=>$v){
          
            $res["specs"][$k]["goodsid"]=$goods_id;
            //获取规格列表
            $res["specs"][$k]["items"]=pdo_fetchall("select id,specid,title,thumb from ".tablename("ewei_shop_goods_spec_item")." where specid=:specid order by id asc",array(":specid"=>$v["id"]));
            foreach ($res["specs"][$k]["items"] as $kk=>$vv){
                $res["specs"][$k]["items"][$kk]["thumb"]=tomedia($vv);
            }
        }
        $res["options"]=pdo_fetchall("select * from ".tablename("ewei_shop_groups_goods_option")." where groups_goods_id=:groups_goods_id order by id asc",array(":groups_goods_id"=>$goods_id));
        foreach ($res["options"] as $k=>$v){
            $res["options"][$k]["goodsid"]=$v["groups_goods_id"];
            if ($signal==1){
                $res["options"][$k]["marketprice"]=$v["single_price"];
                if ($v["single_price"]>$res["goods"]["maxprice"]){
                    $res["goods"]["maxprice"]=$v["single_price"];
                }
                if ($v["single_price"]<$res["goods"]["minprice"]){
                    $res["goods"]["minprice"]=$v["single_price"];
                }
            }else{
                $res["options"][$k]["marketprice"]=$v["price"];
                if ($v["price"]>$res["goods"]["maxprice"]){
                    $res["goods"]["maxprice"]=$v["price"];
                }
                if ($v["price"]<$res["goods"]["minprice"]){
                    $res["goods"]["minprice"]=$v["price"];
                }
            }
           //获取图片
           $option=pdo_get("ewei_shop_goods_option",array("id"=>$v["goods_option_id"]));
           $res["options"][$k]["thumb"]=tomedia($option["thumb"]);
        }
        
        
//         $option=pdo_fetchall("select * from ".tablename("ewei_shop_groups_goods_option")." where ")
        
        apperror(0,"",$res);
    }
    //拼团列表
    public function group_list(){
        global $_W;
        global $_GPC;
        $goods_id=$_GPC["goods_id"];
        if (empty($goods_id)){
            apperror(1,"","商品id不可为空");
        }
        $good=pdo_fetch("select * from ".tablename("ewei_shop_groups_goods")." where id=:goods_id and status=1 and deleted=0",array(":goods_id"=>$goods_id));
        if (empty($good)){
            apperror(1,"","商品不存在");
        }
        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where goodid=:goodid and status=1 and success=0 and heads=1 and is_team=1 and endtime>:endtime",array(":goodid"=>$goods_id,":endtime"=>time()));
        
//         $page=$_GPC["page"]?$_GPC["page"]:1;
//         $first=($page-1)*20;
        $list=m("appnews")->group_list($goods_id,0,$total);
        $res["list"]=$list;
//         $res["page"]=$page;    
//         $res["total"]=$total;
//         $res["pagesize"]=20;
//         $res["pagetotal"]=ceil($total/20);
        apperror(0,"",$res);
    }
    //评价列表
    public function comment_list(){
        global $_W;
        global $_GPC;
        $goods_id=$_GPC["goods_id"];
        if (empty($goods_id)){
            apperror(1,"","商品id不可为空");
        }
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        if ($openid){
             $member=m("appnews")->member($openid,$type);
        }else{
            $member=array();
        }
        
        $page=$_GPC["page"]?$_GPC["page"]:1;
        $first=($page-1)*20;
        $label=$_GPC["label"]?$_GPC["label"]:"";
        $res=m("appnews")->group_comment($goods_id,$first,20,$label,$member["id"]);
        $res["page"]=$page;
        apperror(0,"",$res);
    }
}