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
        $good=pdo_fetch("select id,ccate,title,freight,thumb_url,price,groupsprice,single,singleprice,groupnum,content,more_spec,merchid,gid from ".tablename("ewei_shop_groups_goods")." where id=:goods_id and status=1 and deleted=0",array(":goods_id"=>$goods_id));
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
        $thumb_url=iunserializer($good["thumb_url"]);
        foreach ($thumb_url as $k=>$v){
            $good["thumb_url"][$k]=tomedia($v);
        }
        if ($type==1){
            $good["content"]=m("appnews")->img($good["content"]);
            foreach ($good["content"] as $k=>$v){
                $good["content"][$k]=tomedia($v);
            }
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
        $good["group"]["count"]=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where goodid=:goodid and status>0",array(":goodid"=>$goods_id)); 
        $good["group"]["list"]=m("appnews")->group_list($goods_id,0,2);
        
        //获取评价
        $comment=m("appnews")->group_comment($goods_id,0,1);
        $good["comment"]["count"]=$comment["total"];
        $good["comment"]["list"]=$comment["list"][0];
        
        
        apperror(0,"",$good);
    }
}