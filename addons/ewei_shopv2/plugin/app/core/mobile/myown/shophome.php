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
        $merch=pdo_get("ewei_shop_merch_user",array("id"=>$merch_id));
        if (empty($merch)){
            app_error(-1,"不存在该商户");
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
        $merch["shopvideo_img"]=tomedia($merch["shopvideo_img"]);
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
        $fllow=pdo_get("ewei_shop_merch_follow",array("merch_id"=>$merch_id,"openid"=>$openid));
        if (empty($fllow)){
            $merch["follow"]=0;
        }else{
            $merch["follow"]=1;
        }
        app_error(0,$merch);
    }
    //店铺关注/取消
    public function follow(){
        global $_GPC;
        global $_W;
        $merch_id=$_GPC["merch_id"];
        if (empty($merch_id)){
            app_error(-1,"商户id未传");
        }
        $openid=$_GPC["openid"];
        $follow=$_GPC["follow"];//1表示关注 0取消关注
        if ($follow==1){
            $merch_follow=pdo_get("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id));
            if ($merch_follow){
                app_error(-1,"不可重复关注");
            }
            $data["openid"]=$openid;
            $data["merch_id"]=$merch_id;
            $data["create_time"]=time();
            if (pdo_insert("ewei_shop_merch_follow",$data)){
                app_error(0,"关注成功");
            }else{
                app_error(-1,"关注失败");
            }
            
        }else{
            $merch_follow=pdo_get("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id));
            if (empty($merch_follow)){
                app_error(-1,"该用户未关注该商户");
            }
            if (pdo_delete("ewei_shop_merch_follow",array("openid"=>$openid,"merch_id"=>$merch_id))){
                app_error(0,"取消关注成功");
            }else{
                app_error(-1,"取消关注失败");
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
}
 
  