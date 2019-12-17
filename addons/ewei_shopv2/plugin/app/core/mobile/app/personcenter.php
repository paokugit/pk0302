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
            apperror(1,"无此用户");
        }
        $member=pdo_fetch("select nickname,avatar,credit1,credit2,credit3,credit4,agentlevel,is_open,expire_time,mobile,weixin,rvc,qiandao,gender from ".tablename("ewei_shop_member")." where id=:id",array(":id"=>$member_id));
       
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
        apperror(0,"",$resault);
    }
    //粉丝
    public function fans(){
        global $_GPC;
        global $_W;
        $token=$_GPC["token"];
        $member_id=m('member')->getLoginToken($token);
        if ($member_id==0){
            apperror(1,"无此用户");
        }
        $member=pdo_get("ewei_shop_member",array("id"=>$member_id));
        if (empty($member)){
            apperror(1,"无此用户");
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
//         $member_agentcount=pdo_get("ewei_shop_member_agentcount",array("openid"=>$member["openid"]));
        if (empty($member["openid"])){
            $member["openid"]=0;
        }
        $member_agentcount=pdo_fetch("select * from ".tablename("ewei_shop_member_agentcount")." where openid=:openid or user_id=:user_id",array(":openid"=>$member["openid"],":user_id"=>$member["id"]));
        
        if ($member_agentcount){
           $resault["shopkeeperallcount"]=$member_agentcount["shopkeeperallcount"];
           $resault["agentallcount"]=$member_agentcount["agentallcount"];
        }else{
            $resault["shopkeeperallcount"]=0;
            $resault["agentallcount"]=0;
        }
        apperror(0,"",$resault);
    }
    //粉丝--列表
    public function fans_list(){
        global $_GPC;
        global $_W;
        $token=$_GPC["token"];
        $member_id=m('member')->getLoginToken($token);
        if ($member_id==0){
            apperror(1,"无此用户");
        }
//         $member_id=89;
        $member=pdo_get("ewei_shop_member",array("id"=>$member_id));
        if (empty($member)){
            apperror(1,"无此用户");
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
        $re["list"]=$list;
        //获取总数量
        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_member")." where agentid=:agentid",array(":agentid"=>$member_id));
        $re["pagetotal"]=ceil($total/15);
        $re["page"]=$page;
        apperror(0,"",$re);
    }
    //签到
    public function sign_in(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
        $member_id=m('member')->getLoginToken($openid);
        if ($member_id==0){
            apperror(1,"无此用户");
        }
        $openid=$member_id;
        }
        //         $member_id=89;
        $member=m("member")->getMember($openid);
        if (empty($member)){
            apperror(1,"无此用户");
        }
        //判断是否已签到
        if ($member["qiandao"]==date("Y-m-d",time())){
            apperror(1,"不可重复签到");
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
            apperror(0,"签到成功");
        }else{
            apperror(1,"签到失败");
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
           apperror(1,"无此用户");
       }
       $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           apperror(1,"无此用户");
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
       
       apperror(0,"",$d);
   }
   //设置--个人中心--性别
   public function mes_gender(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               apperror(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           apperror(1,"无此用户");
       }
       $data["gender"]=$_GPC["gender"];
       if (pdo_update("ewei_shop_member",$data,array("id"=>$member["id"]))){
           apperror(0,"设置成功");
       }else{
           apperror(1,"设置失败");
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
               apperror(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           apperror(1,"无此用户");
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
       apperror(0,"",$d);
   }
   //设置--消息推送--提交
   public function setnews_submit(){
       global $_GPC;
       global $_W;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               apperror(1,"无此用户");
           }
           $openid=$member_id;
       }
       //         $member_id=89;
       $member=m("member")->getMember($openid);
       if (empty($member)){
           apperror(1,"无此用户");
       }
       if ($member["news"]){
           $set=unserialize($member["news"]);
       }else{
           $set=array();
       }
       $mode=$_GPC["mode"];
       $open=$_GPC["open"];
       if ($set[$mode]==$open){
           apperror(1,"不可重复操作");
       }
       $set[$mode]=$open;
       $data["news"]=serialize($set);
       if (pdo_update("ewei_shop_member",$data,array("id"=>$member["id"]))){
           apperror(0,"成功");
       }else{
           apperror(1,"失败");
       }
   }
   //设置--关于跑库（隐私注册|软许）
   public  function about(){
       global $_GPC;
       global $_W;
       $id=$_GPC["id"];
       if (empty($id)){
           apperror(1,"id未传入");
       }
       $notice=pdo_get("ewei_shop_member_devote",array("id"=>$id));
       if (empty($notice)){
           apperror(1,"id不正确");
       }
       apperror(0,$notice);
   }
   //足迹
   public function footprint(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       if ($_GPC["type"]==1){
           $member_id=m('member')->getLoginToken($openid);
           if ($member_id==0){
               apperror(1,"无此用户");
           }
           $openid=$member_id;
       }
       $member=m("member")->getMember($openid);
       if (empty($member)){
           apperror(1,"无此用户");
       }
       $pindex = max(1, intval($_GPC['page']));
       $psize = 20;
       $first=($pindex-1)*20;
       if (!$member["openid"]){
           $member["openid"]=0;
       }
       $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_history")." where (openid=:openid or user_id=:user_id) and deleted=0 order by createtime desc limit ".$first.",20",array(":openid"=>$member["openid"],":user_id"=>$member["id"]));
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
       $res["list"]=$l;
       //获取总条数
       $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_member_history")." where (openid=:openid or user_id=:user_id) and deleted=0 ",array(":openid"=>$member["openid"],":user_id"=>$member["id"]));
       $res["page"]=$pindex;
       $res["pagetotal"]=ceil($total/20);
       $res["pagesize"]=20;
       $res["total"]=$total;
       apperror(0,"",$res);
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
                   $resault["res"]=$year."年".$month;
               }
               
           }else{
               $resault["type"]=2;
               $resault["res"]=$year."年".$month;
           }
       }else{
           $resault["type"]=3;
           $resault["res"]=$year;
           
       }
       return $resault;
   }
   //购物车--列表
   public function cart(){
       global $_W;
       global $_GPC;
      
       $openid = $_GPC['openid'];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       if (!$member["openid"]){
           $member["openid"]=0;
       }
       //获取商家id
       $list=pdo_fetchall("select merchid from ".tablename("ewei_shop_member_cart")." where (openid=:openid or user_id=:user_id) and deleted=0  group by merchid order by createtime desc",array(":openid"=>$member["openid"],":user_id"=>$member["id"]));
       
       foreach ($list as $k=>$v){
           //获取商家
           if ($v["merchid"]!=0){
               $merch=pdo_get("ewei_shop_merch_user",array("id"=>$v["merchid"]));
               $list[$k]["merchname"]=$merch["merchname"];
           }else{
               $list[$k]["merchname"]="跑库";
           }
           //获取优惠券
           //获取优惠券
           $coupon=pdo_fetch("select id,enough,deduct from ".tablename("ewei_shop_coupon")." where merchid=:merchid  and timelimit=1 and timestart<=:time and timeend>=:time and coupontype=0 order by deduct desc limit 1",array(":merchid"=>$merch_id,":time"=>time()));
           if ($coupon){
           $list[$k]["coupon"]["enough"]=$coupon["enough"];
           $list[$k]["coupon"]["deduct"]=$coupon["deduct"];
           }else{
               $list[$k]["coupon"]["enough"]=0;
               $list[$k]["coupon"]["deduct"]=0;
           }
           $list[$k]["selected"]=1;
           //获取列表
           $goods=pdo_fetchall("select * from ".tablename("ewei_shop_member_cart")." where (openid=:openid or user_id=:user_id) and deleted=0 and merchid=:merchid order by createtime desc",array(":openid"=>$member["openid"],":user_id"=>$member["id"],"merchid"=>$v["merchid"]));
           foreach ($goods as $kk=>$vv){
               if ($vv["selected"]==0){
                   $list[$k]["selected"]=0;
               }
               $list[$k]["goods"][$kk]["id"]=$vv["id"];
               $list[$k]["goods"][$kk]["goodsid"]=$vv["goodsid"];
               $list[$k]["goods"][$kk]["total"]=$vv["total"];
               $list[$k]["goods"][$kk]["marketprice"]=$vv["marketprice"];
               $list[$k]["goods"][$kk]["optionid"]=$vv["optionid"];
               $list[$k]["goods"][$kk]["selected"]=$vv["selected"];
               //获取规格属性
               if ($vv["optionid"]!=0){
                   $opt=pdo_get("ewei_shop_goods_option",array("id"=>$vv["optionid"]));
                   $list[$k]["goods"][$kk]["optionname"]=$opt["title"]; 
                   $list[$k]["goods"][$kk]["specs"]=explode("_", $opt["specs"]);
               }else{
                   $list[$k]["goods"][$kk]["optionname"]="";
                   $list[$k]["goods"][$kk]["specs"]=array();
               }
               //获取商品
               $g=pdo_get("ewei_shop_goods",array("id"=>$vv["goodsid"]));
               $list[$k]["goods"][$kk]["goodsname"]=$g["title"];
               $list[$k]["goods"][$kk]["thumb"]=tomedia($g["thumb"]);
               //获取商品总归个
               //获取规格
               $spec=pdo_fetchall("select * from ".tablename("ewei_shop_goods_spec")."  where goodsid=:goodsid order by id asc",array(":goodsid"=>$vv["goodsid"]));
               //          var_dump($spec);
               $list[$k]["goods"][$kk]["spec"]=array();
               foreach ($spec as $kkk=>$vvv){
                   $list[$k]["goods"][$kk]["spec"][$kkk]["id"]=$vvv["id"];
                   $list[$k]["goods"][$kk]["spec"][$kkk]["title"]=$vvv["title"];
                   $value=unserialize($vvv["content"]);
                   $list[$k]["goods"][$kk]["spec"][$kkk]["value"]=array();
                   foreach ($value as $kkkk=>$vvvv){
                       $spec_item=pdo_get("ewei_shop_goods_spec_item",array("id"=>$vvvv));
                       $list[$k]["goods"][$kk]["spec"][$kkk]["value"][$kkkk]["item_id"]=$vvvv;
                       $list[$k]["goods"][$kk]["spec"][$kkk]["value"][$kkkk]["item_name"]=$spec_item["title"];
                   }
               }
               
           }
       }
       $l["list"]=$list;
       apperror(0,"",$l);
   }
   //购物车--更换规格属性
   public function cart_option(){
       global $_W;
       global $_GPC;
       //规格
       $spec_id=$_GPC["spec_id"];
       $cart_id=$_GPC["cart_id"];
       $total=$_GPC["total"]?$_GPC["total"]:1;
       $cart=pdo_get("ewei_shop_member_cart",array("id"=>$cart_id));
          
       $option=pdo_get("ewei_shop_goods_option",array("id"=>$spec_id,"goodsid"=>$cart["goodsid"]));
          
//            var_dump($option);
           if (empty($option)){
               apperror(1,"规格id不正确");
           }
      
       if (empty($cart)){
           apperror(1,"购物车id不正确");
       }
       //判断该规格的商品是否还有库存
       if ($option["stock"]!=-1&&$option["stock"]<$cart["total"]){
           apperror(1,"该规格商品仅剩".$option["stock"]."件");
       }
       
       //判断购物车是否还有此商品
       if ($cart["user_id"]){
           $member=m("member")->getMember($cart["user_id"]);
       }else{
           $member=m("member")->getMember($cart["openid"]);
       }
       if (empty($member["openid"])){
           $member["openid"]=0;
       }
//        var_dump($option);die;
       //判断是否有此规格商品
       if ($option){
       $my=pdo_fetch("select * from ".tablename("ewei_shop_member_cart")." where (openid=:openid or user_id=:user_id) and goodsid=:goodsid and optionid=:optionid and deleted=0 and id!=:id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":goodsid"=>$cart["goodsid"],":optionid"=>$option["id"],":id"=>$cart_id));
       $d["optionid"]=$option["id"];
       $d["total"]=$total;
       }else{
           $my=pdo_fetch("select * from ".tablename("ewei_shop_member_cart")." where (openid=:openid or user_id=:user_id) and goodsid=:goodsid  and deleted=0 and id!=:id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":goodsid"=>$cart["goodsid"],":id"=>$cart_id));
           
           $d["optionid"]=0;
           $d["total"]=$total;
       }
       
//        var_dump($my);
//        var_dump($option);
//        var_dump($cart);die;
       if (!$my){
       if (pdo_update("ewei_shop_member_cart",$d,array("id"=>$cart_id))){
           $list["optionname"]=$option["title"];
           apperror(0,"",$list);
       }else{
           apperror(1,"更新失败");
       }
       }else{
           //由此规格
           $dd["total"]=$my["total"]+$total;
           
           if (pdo_update("ewei_shop_member_cart",$dd,array("id"=>$my["id"]))){
               pdo_delete("ewei_shop_member_cart",array("id"=>$cart_id));
               apperror(0,"",$list);
           }else{
               apperror(1,"更新失败！");
           }
       }
       
   }
   //领劵中心
   public function coupon(){
       global $_W;
       global $_GPC;
       
       $merchid=$_GPC["merchid"];
       
       $condition=" and gettype=1 and timelimit=1 and coupontype=0 and backtype=0 and  timeend>:time and  timestart<=:time ";
       $param=array(":time"=>time());
       if ($merchid){
           $condition=$condition." and merchid=:merchid";
           $param[":merchid"]=$merchid;
       }
       $list=pdo_fetchall("select id,couponname,timestart,timeend,deduct,enough,backtype,merchid from ".tablename("ewei_shop_coupon")." where 1 ".$condition,$param);
       foreach ($list as $k=>$v){
           $list[$k]["timestart"]=date("Y-m-d",$v["timestart"]);
           $list[$k]["timeend"]=date("Y-m-d",$v["timeend"]);
           if ($list[$k]["timeend"]==date("Y-m-d")){
               $list[$k]["expire"]=1;
           }else{
               $list[$k]["expire"]=0;
           }
           $list[$k]["deduct"]=(int)$v["deduct"];
           $list[$k]["enough"]=(int)$v["enough"];
       }
       if (!$list){
           $list=new ArrayObject();
       }
       apperror(0,"",$list);
   }
   //我的优惠券
   public function mycoupon(){
       global $_W;
       global $_GPC;
       $openid = $_GPC['openid'];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       if (!$member["openid"]){
           $member["openid"]=0;
       }
       $condition=" and (d.openid=:openid or d.user_id=:user_id) and c.coupontype=0 and c.backtype=0 and c.timelimit=1 and c.uniacid=1";
       $param=array(":openid"=>$member["openid"],":user_id"=>$member["id"]);
       //获取类别
       $use=$_GPC["use"];
       if ($use==0){
           $condition=$condition." and d.used=0 and c.timeend>=:time";
           $param[":time"]=time();
       }elseif ($use==1){
           $condition=$condition." and d.used=1";
       }elseif ($use==2){
           $condition=$condition." and d.used=0 and c.timeend<:time";
           $param[":time"]=time();
       }
       
       $list=pdo_fetchall("select c.id,c.enough,c.deduct,c.backtype,c.couponname,c.timestart,c.timeend,c.merchid from ".tablename("ewei_shop_coupon_data")." d "." left join ".tablename("ewei_shop_coupon")." c on d.couponid=c.id where 1 ".$condition,$param);
       foreach ($list as $k=>$v){
           
           $list[$k]["timestart"]=date("Y-m-d",$v["timestart"]);
           $list[$k]["timeend"]=date("Y-m-d",$v["timeend"]);
           $list[$k]["deduct"]=(int)$v["deduct"];
           $list[$k]["enough"]=(int)$v["enough"];
           if ($list[$k]["timeend"]==date("Y-m-d")){
               $list[$k]["expire"]=1;
           }else{
               $list[$k]["expire"]=0;
           }
       }
       if (!$list){
           $list=array();
       }
       $res["list"]=$list;
       //未使用的
       $res["total"]=pdo_fetchcolumn("select count(d.id) from ".tablename("ewei_shop_coupon_data")." d "." left join ".tablename("ewei_shop_coupon")." c on d.couponid=c.id where (d.openid=:openid or d.user_id=:user_id) and c.coupontype=0 and c.backtype=0 and c.timelimit=1 and d.used=0 and c.timeend>=:time",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":time"=>time()));
       if (!$res["total"]){
           $res["total"]=0;
       }
       apperror(0,"",$res);
   }
   //消息
   public function news(){
       global $_W;
       global $_GPC;
       $openid = $_GPC['openid'];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       if (!$member["openid"]){
           $member["openid"]=0;
       }
       $list["logistic"]=0;
       $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_notice")." where status=1");
       //获取已读数据
       $view=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_notice")." n left join ".tablename("ewei_shop_notice_view")." v on v.notice_id=n.id where n.status=1 and v.user_id=:user_id",array(":user_id"=>$member["id"]));
       $list["notice"]=$total-$view;
       //评论留言
       $comment=pdo_fetchall("select *  from ".tablename("ewei_shop_member_drcomment")." where (comment_openid=:comment_openid or comment_openid=:user_id) and is_del=0 and is_view=0",array(":comment_openid"=>$member["openid"],":user_id"=>$member["id"]));
//        var_dump($comment);
       $i=0;
       foreach ($comment as $k=>$v){
           $log=pdo_fetch("select * from ".tablename("ewei_shop_member_drcomment")." where (openid=:openid or user_id=:user_id) and type=2 and parent_id=:parent_id",array(":openid"=>$member["openid"],":user_id"=>$member["id"],":parent_id"=>$v["id"]));
           if (empty($log)){
               $i=$i+1;
           }
       }
       $list["comment"]=$i;
       apperror(0,"",$list);
   }
   //系统消息
   public function notice(){
       global $_W;
       global $_GPC;
       $page=$_GPC["page"]?$_GPC["page"]:1;
       $first=($page-1)*20;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       if (!$member["openid"]){
           $member["openid"]=0;
       }
       $list=pdo_fetchall("select id,title,thumb from ".tablename("ewei_shop_notice")." where status=1 order by displayorder desc limit ".$first.",20");
       foreach ($list as $k=>$v){
           $list[$k]["thumb"]=tomedia($v["thumb"]);
           $log=pdo_get("ewei_shop_notice_view",array("user_id"=>$member["id"],"notice_id"=>$v["id"]));
           if ($log){
               $list[$k]["view"]=1;
           }else{
               $list[$k]["view"]=0;
           }
       }
       $res["list"]=$list;
       $res["page"]=$page;
       $res["pagesize"]=20;
       $res["total"]=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_notice")." where status=1");
       $res["pagetotal"]=ceil($res["total"]/20);
       apperror(0,"",$res);
   }
   //详情
   public function notice_detail(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $notice_id=$_GPC["notice_id"];
       $notice=pdo_get("ewei_shop_notice",array("id"=>$notice_id,"status"=>1));
       if ($notice){
           $log=pdo_get("ewei_shop_notice_view",array("notice_id"=>$notice_id,"user_id"=>$member["id"]));
           if (empty($log)){
               $l["user_id"]=$member["id"];
               $l["notice_id"]=$notice_id;
               $l["createtime"]=time();
               pdo_insert("ewei_shop_notice_view",$l);  
           }
           pdo_update('ewei_shop_notice',['click_num'=>bcadd($notice['click_num'],1)],['id'=>$notice_id]);
           $notice["thumb"]=tomedia($notice["thumb"]);
           $res["detail"]=$notice;
           apperror(0,"",$res);
       }else{
           apperror(1,"id不正确");
       }
   }
   //通知消息--全部已读
   public function notice_read(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $type=$_GPC["type"]?$_GPC["type"]:0;//1表示app接口
       $member=m("appnews")->member($openid,$type);
       if (!$member){
           apperror(1,"用户不存在");
       }
       $notice_id=$_GPC["notice_id"];
       if (!is_array($notice_id)){
           apperror(1,"","消息id必须为数组");
       }
       foreach ($notice_id as $k=>$v){
           $l["user_id"]=$member["id"];
           $l["notice_id"]=$v;
           $l["createtime"]=time();
           pdo_insert("ewei_shop_notice_view",$l);  
       }
       apperror(0,"成功");
   }
}
