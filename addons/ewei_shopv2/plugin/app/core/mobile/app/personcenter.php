<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Personcenter_EweiShopV2Page extends AppMobilePage
{
    public function index(){
        global $_GPC;
        global $_W;
        $token=$_GPC["token"];
        $member_id=m('member')->getLoginToken($token);
        if ($member_id==0){
            app_error(1,"无此用户");
        }
        $member=pdo_fetch("select nickname,avatar,credit2,credit3,credit4,agentlevel,is_open,expire_time,mobile from ".tablename("ewei_shop_member")." where id=:id",array(":id"=>$member_id));
       
        //消息条数
        $member["news"]=0;
        //年卡判断 1开通
        if ($member["is_open"]==1&&$member["expire_time"]>time()){
            $member["is_openyear"]=1;
        }else{
            $member["is_openyear"]=0;
        }
        $resault["member"]=$member;
        //bannner广告
        $resault['banner'] = pdo_fetchall('select title,thumb from '.tablename('ewei_shop_adsense').' where uniacid="'.$_W['uniacid'].'" and type=2 order by sort desc');
        foreach ($resault['banner'] as $key=>$item){
            $resault['banner'][$key]['thumb'] = tomedia($item['thumb']);
        }
        //图标
        $icon=pdo_get("ewei_shop_small_set",array("id"=>3));
        $r=unserialize($icon["icon"]);
        foreach ($r["order"] as $k=>$v){
            if (!empty($v)){
                $resault["order"][$k]=tomedia($v);
            }
        }
        foreach ($r["server"] as $k=>$v){
            if (!empty($v)){
                $resault["server"][$k]=tomedia($v);
            }
        }
       app_error(0,$resault);
    }
    //粉丝
    public function fans(){
        global $_GPC;
        global $_W;
        $token=$_GPC["token"];
        $member_id=m('member')->getLoginToken($token);
        if ($member_id==0){
            app_error(1,"无此用户");
        }
        $member=pdo_get("ewei_shop_member",array("id"=>$member_id));
        if (empty($member)){
            app_error(1,"无此用户");
        }
        $resault["id"]=$member_id;
        if ($member["agentid"]){
           $agent=pdo_get("ewei_shop_member",array("id"=>$member["agentid"]));
           if ($agent){
               $resault["agentname"]=$agent["nickname"];
           }else{
               $resault["agentname"]="暂无";
           }
        }else{
            $resault["agentname"]="暂无";
        }
        //获取直推数据
        $count=pdo_fetch("select count(*) as count from ".tablename("ewei_shop_member")." where agentid=:agentid",array(":agentid"=>$member_id));
        $resault["recommend"]=$count["count"];
        $member_agentcount=pdo_get("ewei_shop_member_agentcount",array("openid"=>$member["openid"]));
        if ($member_agentcount){
           $resault["shopkeeperallcount"]=$member_agentcount["shopkeeperallcount"];
           $resault["agentallcount"]=$member_agentcount["agentallcount"];
        }else{
            $resault["shopkeeperallcount"]=0;
            $resault["agentallcount"]=0;
        }
        app_error(0,$resault);
    }
    //粉丝--列表
    public function fans_list(){
        global $_GPC;
        global $_W;
        $token=$_GPC["token"];
        $member_id=m('member')->getLoginToken($token);
        if ($member_id==0){
            app_error(1,"无此用户");
        }
//         $member_id=89;
        $member=pdo_get("ewei_shop_member",array("id"=>$member_id));
        if (empty($member)){
            app_error(1,"无此用户");
        }
        $page=$_GPC["page"];
        if (empty($page)){
            $page=1;
        }
        $first=($page-1)*15;
        $list=pdo_fetchall("select id,openid,nickname,agentlevel,avatar,createtime from ".tablename("ewei_shop_member")." where agentid=:agentid order by createtime desc limit ".$first." ,15",array(":agentid"=>$member_id));
        foreach ($list as $k=>$v){
            $list[$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
            $count=pdo_get("ewei_shop_member_agentcount",array("openid"=>$v["openid"]));
           if ($count){
            $list[$k]["agentallcount"]=$count["agentallcount"];
           }else{
               $list[$k]["agentallcount"]=0;
           }
        }
        app_error(0,$list);
    }
    
   
}