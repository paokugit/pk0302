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
        $member=pdo_fetch("select nickname,avatar,credit2,credit3,credit4,agentlevel,is_open,expire_time,mobile,qiandao from ".tablename("ewei_shop_member")." where id=:id",array(":id"=>$member_id));
       
        //消息条数
        $member["news"]=0;
        //签到
        if ($member["qiandao"]==date("Y-m-d",time())){
            $member["qiandao"]=1;
        }else{
            $member["qiandao"]=0;
        }
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
    //签到
    public function sign_in(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
        $member_id=m('member')->getLoginToken($openid);
        if ($member_id==0){
            app_error(1,"无此用户");
        }
        $openid=$member_id;
        }
        //         $member_id=89;
        $member=m("member")->getMember($openid);
        if (empty($member)){
            app_error(1,"无此用户");
        }
        //判断是否已签到
        if ($member["qiandao"]==date("Y-m-d",time())){
            app_error(1,"不可重复签到");
        }
        //昨天日期
        $yesterday=date("Y-m-d",strtotime("-1 day"));
        if ($member["qiandao"]==$yesterday){
            $data["sign_days"]=$member["sign_days"]+1;
        }else{
            $data["sign_days"]=1;
        }
        $data["qiandao"]=date("Y-m-d",time());
        $shopset = m("common")->getSysset("shop");
        if (pdo_update("ewei_shop_member",$data,array("id"=>$member["id"]))){
            //添加卡路里记录
            $d = array(
                'timestamp' => time(),
                'openid' => $member["openid"],
                'day' => date('Y-m-d'),
                'uniacid' => $_W['uniacid'],
                'step' => 1500,
                'type' => 2,
                'user_id'=>$member["id"]
            );
            pdo_insert('ewei_shop_member_getstep', $d);
            app_error(0,"签到成功");
        }else{
            app_error(1,"签到失败");
        }
    }
   //账户设置
   public function mes(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
       $member_id=m('member')->getLoginToken($openid);
       if ($member_id==0){
           app_error(1,"无此用户");
       }
       $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           app_error(1,"无此用户");
       }
       $d["nickname"]=$member["nickname"];
       $d["avatar"]=$member["avatar"];
       $d["mobile"]=$member["mobile"];
       $d["weixin"]=$member["weixin"];
       $d["gender"]=$member["gender"];//1男 2女
       //获取界别
       $level=pdo_get("ewei_shop_commission_level",array("id"=>$member["agentlevel"]));
       if ($level){
           $d["level"]=$level["levelname"];
       }else{
           $d["level"]="普通用户";
       }
       app_error(0,$d);
   }
   //设置--个人中心--性别
   public function mes_gender(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               app_error(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           app_error(1,"无此用户");
       }
       $data["gender"]=$_GPC["gender"];
       if (pdo_update("ewei_shop_member",$data,array("id"=>$member["id"]))){
           app_error(0,"设置成功");
       }else{
           app_error(1,"设置失败");
       }
   }
   //设置--消息推送
   public function setnews(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               app_error(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           app_error(1,"无此用户");
       }
       $set=unserialize($member["news"]);
       $d=array();
       if ($set["coupon"]){
           $d["coupon"]=$set["coupon"];
       }else{
           $d["coupon"]=0;
       }
       if ($set["logistic"]){
           $d["logistic"]=$set["logistic"];
       }else{
           $d["logistic"]=0;
       }
       if ($set["system"]){
           $d["system"]=$set["system"];
       }else{
           $d["system"]=0;
       }
       if ($set["dynamic"]){
           $d["dynamic"]=$set["dynamic"];
       }else{
           $d["dynamic"]=0;
       }
       app_error(0,$d);
   }
   //设置--消息推送--提交
   public function setnews_submit(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               app_error(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           app_error(1,"无此用户");
       }
       if ($member["news"]){
           $set=unserialize($member["news"]);
       }else{
           $set=array();
       }
       $mode=$_GPC["mode"];
       $open=$_GPC["open"];
       if ($set[$mode]==$open){
           app_error(1,"不可重复操作");
       }
       $set[$mode]=$open;
       $data["news"]=serialize($set);
       if (pdo_update("ewei_shop_member",$data,array("id"=>$member["id"]))){
           app_error(0,"成功");
       }else{
           app_error(1,"失败");
       }
   }
   //设置--关于跑库（隐私注册|软许）
   public  function about(){
       global $_GPC;
       global $_W;
       $id=$_GPC["id"];
       if (empty($id)){
           app_error(1,"id未传入");
       }
       $notice=pdo_get("ewei_shop_member_devote",array("id"=>$id));
       if (empty($notice)){
           app_error(1,"id不正确");
       }
       app_error(0,$notice);
   }
   //足迹
   public function footprint(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               app_error(1,"无此用户");
           }
           $openid=$member_id;
       }
       $member=m("member")->getMember($openid);
       if (empty($member)){
           app_error(1,"无此用户");
       }
       $pindex = max(1, intval($_GPC['page']));
       $psize = 10;
       $first=($pindex-1)*10;
       $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_history")." where (openid=:openid or user_id=:user_id) and deleted=0 order by createtime desc limit ".$first.",10",array(":openid"=>$member["openid"],":user_id"=>$member["id"]));
//        var_dump($list);
       $month=array();
       $monthi=0;
       $year=array();
       $yeari=0;
       $l=array();
       $i=0;
       $j=0;
       $jj=0;
       $jjj=0;
       $jjjj=0;
       $type=array();
       $typei=0;
       foreach($list as $k=>$v){
           $goods=pdo_get("ewei_shop_goods",array("id"=>$v["goodsid"]));
           //判断时间阶段
           $time=$this->judge_day($v["createtime"]);
           if ($time["type"]==0){
               //今天
               if (in_array($time["type"], $type)){
                   $l[$i]["dt"][$j]["id"]=$v["id"];
                   $l[$i]["dt"][$j]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$j]["title"]=$goods["title"];
                   $l[$i]["dt"][$j]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$j]["marketprice"]=$goods["marketprice"];
                   $j+=1;
                   
               }else{
                  $l[$i]["type"]=0;
                  $l[$i]["time"]="今日";
                  $l[$i]["dt"]=array();
                  $l[$i]["dt"][$j]["id"]=$v["id"];
                  $l[$i]["dt"][$j]["goodsid"]=$v["goodsid"];
                  $l[$i]["dt"][$j]["title"]=$goods["title"];
                  $l[$i]["dt"][$j]["thumb"]=tomedia($goods["thumb"]);
                  $l[$i]["dt"][$j]["marketprice"]=$goods["marketprice"];
                  $j+=1;
                  $type[$typei]=$time["type"];
                  $typei+=1;
               }
           }elseif ($time["type"]==1){
               //昨天
               if (in_array($time["type"], $type)){
                   $l[$i]["dt"][$jj]["id"]=$v["id"];
                   $l[$i]["dt"][$jj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jj]["marketprice"]=$goods["marketprice"];
                   $jj+=1;
                   
               }else{
                   if ($l){
                       $i+=1;
                   }
                   $l[$i]["type"]=1;
                   $l[$i]["time"]=$time["res"];
                   $l[$i]["dt"]=array();
                   $l[$i]["dt"][$jj]["id"]=$v["id"];
                   $l[$i]["dt"][$jj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jj]["marketprice"]=$goods["marketprice"];
                   $jj+=1;
                   $type[$typei]=$time["type"];
                   $typei+=1;
               }
           }elseif ($time["type"]==2){
               if (in_array($time["type"], $type)&&in_array($time["res"], $month)){
                   //包含月份
                   $l[$i]["dt"][$jjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jjj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jjj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jjj]["marketprice"]=$goods["marketprice"];
                   $jjj+=1;
               }else{
                   //不包含月份
                   if ($l){
                       $i+=1;
                   }
                   $l[$i]["type"]=2;
                   $l[$i]["time"]=$time["res"]."月";
                   $month[$monthi]=$time["res"];
                   $monthi+=1;
                   $jjj=0;
                   $l[$i]["dt"]=array();
                   $l[$i]["dt"][$jjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jjj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jjj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jjj]["marketprice"]=$goods["marketprice"];
                   $jjj+=1;
                   $type[$typei]=$time["type"];
                   $typei+=1; 
               }
           }elseif ($time["type"]==3){
               if (in_array($time["type"], $type)&&in_array($time["res"], $year)){
                   //包括
                   $l[$i]["dt"][$jjjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjjj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jjjj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jjjj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jjjj]["marketprice"]=$goods["marketprice"];
                   $jjjj+=1;
               }else{
                   if ($l){
                       $i+=1;
                   }
                   $l[$i]["type"]=3;
                   $l[$i]["time"]=$time["res"]."年";
                   
                   $year[$yeari]=$time["res"];
                   $yeari+=1;
                   $jjjj=0;
                   $l[$i]["dt"]=array();
                   $l[$i]["dt"][$jjjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjjj]["goodsid"]=$v["goodsid"];
                   $l[$i]["dt"][$jjjj]["title"]=$goods["title"];
                   $l[$i]["dt"][$jjjj]["thumb"]=tomedia($goods["thumb"]);
                   $l[$i]["dt"][$jjjj]["marketprice"]=$goods["marketprice"];
                   $jjjj+=1;
                   $type[$typei]=$time["type"];
                   $typei+=1;
                   
               }
           }
       }
       app_error(0,$l);
   }
   //判断时间阶段
   public function judge_day($time){
       //判断是否是今年
       $today_year=date("Y",time());
       $year=date("Y",$time);
       if ($year==$today_year){
           //判断是不是当前月份
           $today_month=date("m",time());
           $month=date("m",$time);
           if ($month==$today_month){
               //判断是否是今天
               $today=date("d",time());
               $tday=date("d",$time);
               if ($today==$tday){
                   $resault["type"]=0;
                   $resault["res"]=$tday;
                   
               }elseif ($tday==$today-1){
                   $resault["type"]=1;
                   $resault["res"]=date("Y.m.d",$time);
               }else{
                   $resault["type"]=2;
                   $resault["res"]=$month;
               }
               
           }else{
               $resault["type"]=2;
               $resault["res"]=$month;
           }
       }else{
           $resault["type"]=3;
           $resault["res"]=$year;
           
       }
       return $resault;
   }
}