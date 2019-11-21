<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

//fbb 商家主页

class Shophome_EweiShopV2Page extends AppMobilePage{
    
    //商家主页
    public function index(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        //获取经纬度
        $lng=floatval($_GPC["lng"]);
        $lat=floatval($_GPC["lat"]);
        $openid=$_GPC["openid"];
        //获取商家信息
        $merch=pdo_fetch("select id,merchname,mobile,wxsignal,salecate,logo,address,lat,lng,shopimg,shopvideo,status from ".tablename("ewei_shop_merch_user")." where id=:id",array(":id"=>$merch_id));
		
        if (empty($merch)){
            app_error(-1,"不存在该商户");
        }
		if ($merch["status"]==2){
            app_error(1,"该商户已下架");
        }
		$type=$_GPC["type"]?$_GPC["type"]:0;
		 
		$member=m("appnews")->member($openid,$type);
        if (!$member){
            app_error(1,"用户不存在");
        }
		
        $merch["logo"]=tomedia($merch["logo"]);
        if (!empty($merch["shopimg"])){
        $merch["shopimg"]=unserialize($merch["shopimg"]);
        foreach ($merch["shopimg"] as $k=>$v){
            $merch["shopimg"][$k]=tomedia($v);
        }
        }
        //获取视频
        if (!empty($merch["shopvideo"])){
        $merch["shopvideo"]=tomedia($merch["shopvideo"]);
        //$merch["shopvideo_img"]=tomedia($merch["shopvideo_img"]);
        }
        //获取距离、
        if (($lat != 0) && ($lng != 0) && !(empty($merch["lat"])) && !(empty($merch["lng"]))) {
            $distance = m('util')->GetDistance($lat, $lng, $merch["lat"], $merch["lng"], 2);
            if ($distance < 1) {
                $distance=$distance*100;
                $disname = $distance."m";
            } else $disname = $distance. "km";
            $merch['distance'] = $disname;
        } else {
            $merch['distance'] ="";
        }
        //获取关注情况
         $follow=pdo_fetch("select * from ".tablename("ewei_shop_merch_follow")." where (openid=:openid or user_id=:user_id) and merch_id=:merchid",array(":openid"=>$member["openid"],":user_id"=>$member["id"],"merchid"=>$merch_id));
        if (empty($fllow)){
            $merch["follow"]=0;
        }else{
            $merch["follow"]=1;
        }
		//获取总共有多少人关注
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_merch_follow")." where merch_id=:merchid",array(":merchid"=>$merch_id));
        $merch["followcount"]=$count;
        app_error(0,$merch);
    }
    //店铺关注/取消
    public function follow(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            apperror(-1,"商户id未传");
            
        }
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        //修改
        $member=m("member")->getMember($openid);
        $follow=$_GPC["follow"];//1表示关注 0取消关注
        if ($follow==1){
//             $merch_follow=pdo_get("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id));
            $merch_follow=pdo_fetch("select * from ".tablename("ewei_shop_merch_follow")." where (openid=:openid or user_id=:user_id) and merch_id=:merch_id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":merch_id"=>$merch_id)); 
            if ($merch_follow){
               
                apperror(-1,"不可重复关注");
                
                
            }
            $data["openid"]=$member["openid"];
            $data["user_id"]=$member["id"];
            $data["merch_id"]=$merch_id;
            $data["create_time"]=time();
            if (pdo_insert("ewei_shop_merch_follow",$data)){
               
                apperror(0,"关注成功");
                
            }else{
              
                apperror(-1,"关注失败");
                
            }
            
        }else{
//             $merch_follow=pdo_get("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id));
            $merch_follow=pdo_fetch("select * from ".tablename("ewei_shop_merch_follow")." where (openid=:openid or user_id=:user_id) and merch_id=:merch_id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":merch_id"=>$merch_id)); 
            if (empty($merch_follow)){
               
                apperror(-1,"该用户未关注该商户");
                
            }
//             if (pdo_delete("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id))){
            if (pdo_query("delete from ".tablename("ewei_shop_merch_follow")." where (openid=:openid or user_id=:user_id) and merch_id=:merch_id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":merch_id"=>$merch_id))){
               
                apperror(0,"取消关注成功");
            
            }else{
               
                apperror(-1,"取消关注失败");
            
            }
        }
    }
    //店铺图片
    public function shopimg(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        $page=$_GPC["page"];
        if (empty($page)){
            $page=1;
        }
        
        //获取商家信息
        $merch=pdo_get("ewei_shop_merch_user",array("id"=>$merch_id));
        if (empty($merch)){
            app_error(-1,"不存在该商户");
        }
        $img=unserialize($merch["shopimg"]);
        $image=array();
        $i=0;
        $j=($page-1)*6;
        foreach ($img as $k=>$v){
            if ($k>=$j&&$i<6){
                $image[$i]=tomedia($v);
                $i=$i+1;
            }
        }
        app_error(0,$image);
    }
	//店铺简介
    public function desc(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        //获取商家信息
        $merch=pdo_fetch("select * from ".tablename("ewei_shop_merch_user")." where id=:id",array(":id"=>$merch_id));
        
        if (empty($merch)){
            app_error(1,"不存在该商户");
        }
        if ($merch["status"]==2){
            app_error(1,"该商户已下架");
        }
        $m["id"]=$merch["id"];
        $m["merchname"]=$merch["merchname"];
        $m["desc"]=$merch["desc"];
        $m["logo"]=tomedia($merch["logo"]);
        
        app_error(0,$m);
    }
    //店铺公告 --优惠券
    public function shopcoupon(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        //获取商家信息
        $merch=pdo_fetch("select * from ".tablename("ewei_shop_merch_user")." where id=:id",array(":id"=>$merch_id));
        
        if (empty($merch)){
            app_error(1,"不存在该商户");
        }
        if ($merch["status"]==2){
            app_error(1,"该商户已下架");
        }
        //获取公告
        $notice=pdo_fetch("select title from ".tablename("ewei_shop_merch_notice")." where merchid=:merchid",array(":merchid"=>$merch_id));
        $list["notice"]=$notice["title"];
        //获取优惠券
        $coupon=pdo_fetchall("select id,enough,deduct from ".tablename("ewei_shop_coupon")." where merchid=:merchid and status=1 and total>0 and ((timelimit=1 and timestart<=:time and timeend>=:time) or timelimit=0) order by createtime desc limit 2",array(":merchid"=>$merch_id,":time"=>time()));
        $list["coupon"]=$coupon;
        app_error(0,$list);
    }
    //优惠券--详情
    public function coupon_detail(){
        global $_GPC;
        global $_W;
        $id=$_GPC["id"];
        $detail=pdo_fetch("select id,enough,deduct,total,timelimit,timestart,timeend from ".tablename("ewei_shop_coupon")." where id=:id",array(":id"=>$id));
        if (empty($detail)){
            app_error(1,"不存在该优惠券");
        }
        if (!empty($detail["timestart"])){
            $detail["timestart"]=date("Y-m-d",$detail["timestart"]);
            $detail["timeend"]=date("Y-m-d",$detail["timeend"]);
        }
        //判断是否已领取
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            app_error(1,"用户不存在");
        }
        $l=pdo_fetch("select * from ".tablename("ewei_shop_coupon_data")." where couponid=:couponid and (openid=:openid or user_id=:user_id) and used=0",array(":couponid"=>$id,":openid"=>$member["openid"],":user_id"=>$member["id"]));
        if ($l){
            $detail["log"]=1;
        }else{
            $detail["log"]=0;
        }
        app_error(0,$detail);
    }
    //优惠券--领取
    public function coupon_receive(){
        global $_GPC;
        global $_W;
        $id=$_GPC["id"];
        $detail=pdo_get("ewei_shop_coupon",array("id"=>$id));
        if (empty($detail)){
            apperror(1,"优惠券id不正确");
        }
        if ($detail["total"]!=-1&&$detail["total"]==0){
            apperror(1,"该优惠券已被领取完");
        }
        $openid=$_GPC["openid"];
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"用户不存在");
        }
        //判断用户是否可以多次领取
        $l=pdo_fetch("select * from ".tablename("ewei_shop_coupon_data")." where couponid=:couponid and (openid=:openid or user_id=:user_id) and used=0",array(":couponid"=>$id,":openid"=>$member["openid"],":user_id"=>$member["id"]));
        if ($l){
            apperror(1,"不可多次领取");
        }
        $d["uniacid"]=1;
        $d["openid"]=$member["openid"];
        $d["gettype"]=1;
        $d["gettime"]=time();
        $d["couponid"]=$id;
        $d["merchid"]=$detail["merchid"];
        $d["user_id"]=$member["id"];
        if (pdo_insert("ewei_shop_coupon_data",$d)){
            apperror(0,"领取成功");
        }else{
            apperror(1,"领取失败");
        }
    }
    //推荐
    public function recommend(){
        global $_GPC;
        global $_W;
        $page=$_GPC["page"]?$_GPC["page"]:1;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        //获取商家信息
        $merch=pdo_fetch("select id,status from ".tablename("ewei_shop_merch_user")." where id=:id",array(":id"=>$merch_id));
        
        if (empty($merch)){
            app_error(1,"不存在该商户");
        }
        if ($merch["status"]==2){
            app_error(1,"该商户已下架");
        }
        $first=($page-1)*20;
        $condition=" and status=1 and deleted=0 and merchid=:merchid";
       
        if ($_GPC["recommend"]==1){
            $condition=$condition." and isrecommand=1 ";
        }else{
            if ($_GPC["price"]==1){
                //价格有高到底
                $order=" order by marketprice desc";
            }elseif ($_GPC["price"]==2){
                $order=" order by marketprice asc";
            }elseif ($_GPC["sale"]==1){
                $order=" order by sales desc";
            }elseif ($_GPC["sale"]==2){
                $order=" order by sales asc";
            }
        }
        if (empty($order)){
            $order=" order by createtime desc ";
        }
        $list=pdo_fetchall("select id,title,marketprice,thumb,total,sales,ishot,istime from ".tablename("ewei_shop_goods")." where 1  ".$condition.$order."  limit ".$first.",20",array(":merchid"=>$merch_id));
        
        $list=set_medias($list, array( "thumb" ));
        app_error(0,$list);
    }
    //上新
    public function upgood(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        //获取商家信息
        $merch=pdo_fetch("select id,status from ".tablename("ewei_shop_merch_user")." where id=:id",array(":id"=>$merch_id));
        if (empty($merch)){
            app_error(1,"不存在该商户");
        }
        if ($merch["status"]==2){
            app_error(1,"该商户已下架");
        }
        $list=pdo_fetchall("select FROM_UNIXTIME(createtime,'%Y-%m-%d') days from ".tablename("ewei_shop_goods")." where status=1 and deleted=0  and merchid=:merchid GROUP BY days order by createtime desc",array(":merchid"=>$merch_id));
        foreach ($list as $k=>$v){
            $start_time=strtotime($v["days"]);
            $end_time=strtotime(date('Y-m-d',strtotime('+1 day',strtotime($v["days"]))));
            //获取时间内的商品
            $goods=pdo_fetchall("select id,title,thumb,marketprice,total from ".tablename("ewei_shop_goods")." where status=1 and deleted=0 and merchid=:merchid and createtime>:start_time and createtime<=:endtime order by createtime desc",array(":merchid"=>$merch_id,":start_time"=>$start_time,":endtime"=>$end_time));
            $goods=set_medias($goods, array( "thumb" ));
            $list[$k]["goods"]=$goods;
        }
        app_error(0,$list);
    }
}
 
  