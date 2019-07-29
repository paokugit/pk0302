<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

//fanbeibei
class Novice_EweiShopV2Page extends AppMobilePage{
    //获取列表
    public function novice_list(){
        global $_GPC;
        global $_W;
        $page=$_GPC["page"];
        $first=($page-1)*10;
        $list=pdo_fetchall("select id,title,photo,time,type,video from ".tablename("ewei_shop_notive")."order by sort desc limit ".$first." ,10");
        foreach ($list as $k=>$v){
            $list[$k]["photo"]=tomedia($v["photo"]);
            $list[$k]["video"]=tomedia($v["video"]);
        }
        app_error(0,$list);
    }
    //文章详情
    public function novice_detail(){
        global $_GPC;
        global $_W;
        $id=$_GPC["id"];
        $detail=pdo_get("ewei_shop_notive",array("id"=>$id));
        $detail["photo"]=tomedia($detail["photo"]);
        $detail["video"]=tomedia($detail["video"]);
        $detail["createtime"]=date("Y-m-d H:i:s",$detail["createtime"]);
        app_error(0,$detail);
    }
    
    //帮助指南‘
    public function help(){
        global $_GPC;
        global $_W;
        $page=$_GPC["page"];
        $first=($page-1)*16;
        $list=pdo_fetchall("select id,title from ".tablename("ewei_shop_notive_article")."order by sort desc limit ".$first." ,16");
       
        app_error(0,$list);
    }
    //帮助指南--详情
    public function help_detail(){
        global $_GPC;
        global $_W;
        $id=$_GPC["id"];
        $detail=pdo_get("ewei_shop_notive_article",array("id"=>$id));
        $detail["createtime"]=date("Y-m-d H:i:s");
        app_error(0,$detail);
    }
    
    
    //问题反馈
    public function question(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        $data["openid"]=$openid;
        $data["content"]=$_GPC["content"];
        $data["mobile"]=$_GPC["mobile"];
        $data["time"]=$_GPC["time"];
        $data["create_time"]=time();
        $img=$_GPC["img"];
        if ($img){
        $data["img"]=serialize($img);
        }
        if (pdo_insert("ewei_shop_notive_question",$data)){
            app_error(0,"成功");
        }else{
            app_error(1,"失败");
        }
    }
}
