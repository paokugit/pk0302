<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Reading_EweiShopV2Page extends AppMobilePage{
    //列表
    public function index(){
        
        global $_GPC;
        global $_W;
        $page=$_GPC["page"];
        if (empty($page)){
            $page=1;
        }
        $first=($page-1)*10;
       
        $list=pdo_fetchall("select id,title,img from ".tablename("ewei_shop_member_reading")."order by create_time desc limit ".$first." ,10");
        foreach ($list as $k=>$v){
            $list[$k]["img"]=tomedia($v["img"]);
        }
        $l["list"]=$list;
        $count=pdo_count("ewei_shop_member_reading");
        $l["count"]=$count;
        app_error(0,$l);
      
    }
    //详情
    public function detail(){
        global $_GPC;
        global $_W;
        $readid=$_GPC["readid"];
        $detail=pdo_get("ewei_shop_member_reading",array("id"=>$readid));
        if (empty($detail)){
            app_error(1,"该文章已被删除");
        }
        //添加view
        pdo_update("ewei_shop_member_reading",array("view"=>$detail["view"]+1),array("id"=>$readid));
        $detail["img"]=tomedia($detail["img"]);
        if (!empty($detail["detail_img"])){
        $detail["detail_img"]=tomedia($detail["detail_img"]);
        }
        $detail["music"]=tomedia($detail["music"]);
        $detail["create_time"]=date("Y-m-d",$detail["create_time"]);
        app_error(0,$detail);
    }
    //评论列表
    public function comment(){
        global $_GPC;
        global $_W;
        $readid=$_GPC["readid"];
        $page=$_GPC["page"];
        if (empty($page)){
            $page=1;
        }
        $first=($page-1)*10;
        $openid=$_GPC["openid"];
        $list=pdo_fetchall("select id,comment,zan,openid from ".tablename("ewei_shop_member_readcomment")." where read_id=:read_id order by create_time desc limit ".$first.",10",array(":read_id"=>$readid));
        foreach ($list as $k=>$v){
            $list[$k]["reply"]=pdo_fetchall("select reply from ".tablename("ewei_shop_member_readreply")." where comment_id=:comment_id",array(":comment_id"=>$v["id"]));
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
            $list[$k]["avatar"]=$member["avatar"];
            //判断是否点赞
            $log=pdo_get("ewei_shop_member_readzan",array("openid"=>$openid,"comment_id"=>$v["id"]));
            if ($log){
                $list[$k]["myzan"]=1;
            }else{
                $list[$k]["myzan"]=0;
            }
        }
        $count=pdo_count("ewei_shop_member_readcomment",array("read_id"=>$readid));
        $l["list"]=$list;
        $l["count"]=$count;
        app_error(0,$l);
    }
    //评论
    public function com(){
        global $_GPC;
        global $_W;
        $readid=$_GPC["readid"];
        $detail=pdo_get("ewei_shop_member_reading",array("id"=>$readid));
        if (empty($detail)){
            app_error(1,"文章id不正确");
        }
        $data["comment"]=$_GPC["comment"];
        if (empty($data["comment"])){
            app_error(1,"评论内容不可为空");
        }
        $data["read_id"]=$readid;
        $data["openid"]=$_GPC["openid"];
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_member_readcomment",$data)){
            app_error(0,"评论成功");
        }else{
            app_error(1,"评论失败");
        }
    }
    //点赞
    public function support(){
        global $_GPC;
        global $_W;
        $comment_id=$_GPC["comment_id"];
        $openid=$_GPC["openid"];
        $log=pdo_get("ewei_shop_member_readzan",array("comment_id"=>$comment_id,"openid"=>$openid));
        if ($log){
            app_error(1,"不可重复点赞");
        }
        $comment=pdo_get("ewei_shop_member_readcomment",array("id"=>$comment_id));
        $data["comment_id"]=$comment_id;
        $data["openid"]=$openid;
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_member_readzan",$data)){
            pdo_update("ewei_shop_member_readcomment",array("zan"=>$comment["zan"]+1),array("id"=>$comment_id));
            
            app_error(0,"点赞成功");
        }else{
            app_error(1,"点赞失败");
        }
        
    }
    //取消点赞
    public function del_support(){
        global $_GPC;
        global $_W;
        $comment_id=$_GPC["comment_id"];
        $openid=$_GPC["openid"];
        $log=pdo_get("ewei_shop_member_readzan",array("comment_id"=>$comment_id,"openid"=>$openid));
        if (empty($log)){
            app_error(1,"未点赞");
        }
        $comment=pdo_get("ewei_shop_member_readcomment",array("id"=>$comment_id));
        if (pdo_delete("ewei_shop_member_readzan",array("comment_id"=>$comment_id,"openid"=>$openid))){
            pdo_update("ewei_shop_member_readcomment",array("zan"=>$comment["zan"]-1),array("id"=>$comment_id));
            app_error(0,"取消成功");
        }else{
            app_error(1,"取消失败");
        }
    }
    //评论--删除
    public function del_comment(){
        global $_GPC;
        global $_W;
        $comment_id=$_GPC["comment_id"];
        $openid=$_GPC["openid"];
        $comment=pdo_get("ewei_shop_member_readcomment",array("id"=>$comment_id));
        if (empty($comment)){
            app_error(1,"不存在该评论");
        }
        if ($comment["openid"]!=$openid){
            app_error(1,"您无权限删除该评论");
        }
        if (pdo_delete("ewei_shop_member_readcomment",array("id"=>$comment_id))){
            app_error(0,"删除成功");
        }else{
            app_error(1,"删除失败");
        }
    }
}