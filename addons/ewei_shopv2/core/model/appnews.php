<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Appnews_EweiShopV2Model
{
    //判断用户登录信息
    public function member($openid,$type){
        if ($type==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                app_error(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            return false;
        }else {
            if (!$member["openid"]){
                $member["openid"]=0;
            }
            return $member;
        }
    }
   //获取图片
   public function img($data){
       preg_match_all('/<img.*?src="(.*?)".*?>/is',$data,$array);
       return $array[1];
   }
   //拼团列表
   public function group_list($goods_id,$first,$total){
       $good=pdo_fetch("select id,ccate,title,freight,thumb_url,price,groupsprice,single,singleprice,groupnum,content,more_spec,merchid,gid from ".tablename("ewei_shop_groups_goods")." where id=:goods_id and status=1 and deleted=0",array(":goods_id"=>$goods_id));
       $group=pdo_fetchcolumn("select * from ".tablename("ewei_shop_groups_order")." where goodid=:goodid and status=1 and success=0 and heads=1 and is_team=1 order by createtime desc limit ".$first.",".$total,array(":goodid"=>$goods_id));
       $list=array();
       foreach ($group as $k=>$v){
           $list[$k]["teamid"]=$group["id"];
           $list[$k]["endtime"]=$v["endtime"];
           if ($v["user_id"]){
               $m=pdo_get("ewei_shop_member",array("id"=>$v["user_id"]));
           }else{
               $m=pdo_get("ewei_shop_member",array("id"=>$v["openid"]));
           }
           $list[$k]["nickname"]=$m["nickname"]?$m["nickname"]:"昵称";
           //获取总数量
           $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_groups_order")." where is_team=1 and status=1 and teamid=:teamid",array(":teamid"=>$v["id"]));
           $list[$k]["count"]=$count;
           $list[$k]["number"]=$good["groupnum"]-$count;
           //获取头像
           $team=pdo_fetchall("select openid,user_id from ".tablename("ewei_shop_group_order")." where status=1 and (id=:teamid or teamid=:teamid) and is_team=1",array(":teamid"=>$v["id"]));
           $good["group"]["list"][$k]["avatar"]=array();
           foreach ($team as $kk=>$vv){
               if ($vv["user_id"]){
                   $team_member=pdo_get("ewei_shop_member",array("id"=>$vv["user_id"]));
               }else{
                   $team_member=pdo_get("ewei_shop_member",array("openid"=>$vv["openid"]));
               }
               $list[$k]["avatar"][$kk]=$team_member["avatar"];
           }
       }
       
       return $list;
   }
   //评价
   public function group_comment($goods_id,$first,$num,$label){
       $good=pdo_get("ewei_shop_groups_goods",array("id"=>$goods_id));
       $condition="and  checked=0 and deleted=0 and (goodsid=:gid or group_goodsid=:good_id)";
       $param=array(":gid"=>$good["gid"],":good_id"=>$goods_id);
       if ($label){
           $condition=$condition." and label like :label"; 
           $param[":label"]="%".$label."%";
       }
       $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_order_comment")." where 1 ".$condition,$param);
       
       $list=pdo_fetchall("select id,openid,level,type,user_id,content,images,createtime,append_content,orderid,group_orderid from ".tablename("ewei_shop_order_comment")." where 1 ".$condition." order by createtime desc limit ".$first.",".$num,$param);
       foreach ($list as $k=>$v){
           if ($v["anonymous"]==0){
               if ($v["user_id"]){
                   $member=pdo_get("ewei_shop_member",array("id"=>$v["user_id"]));
                   
               }else{
                   $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
                   
               }
               $list[$k]["nickname"]=$member["nickname"];
               $list[$k]["avatar"]=$member["avatar"];
           }else{
               $list[$k]["nickname"]="匿名";
               $list[$k]["avatar"]="";
           }
           //获取规格
           if ($v["type"]==0){
               $order_goods=pdo_get("ewei_shop_order_goods",array("orderid"=>$v["orderid"],"goodsid"=>$good["gid"]));
               $list[$k]["optionname"]=$order_goods["optionname"];
           }else{
               $order_goods=pdo_get("ewei_shop_groups_order_goods",array("groups_goods_id"=>$goods_id,"groups_order_id"=>$v["group_orderid"]));
               $list[$k]["optionname"]=$order_goods["option_name"];
           }
           $list[$k]["createtime"]=date("Y-m-d",$v["createtime"]);
           $image=iunserializer($v["images"]); 
//            var_dump($image); 
           $list[$k]["images"]=array();
           foreach ($image as $kk=>$vv){
               $list[$k]["images"][$kk]=tomedia($vv);
           }
       }
       //获取商品规格
       $good_cate=pdo_get("ewei_shop_category",array("id"=>$good["ccate"]));
       
       if ($good_cate["label"]){
           $list_label=explode(",", $good_cate["label"]);
           
           foreach ($list_label as $k=>$v){
               //获取评价数目
               $c="and  checked=0 and deleted=0 and (goodsid=:gid or group_goodsid=:good_id)";
               $p=array(":gid"=>$good["gid"],":good_id"=>$goods_id);
               $c=$c." and label like :label";
               $p[":label"]="%".$v."%";
               $t=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_order_comment")." where 1 ".$c,$p);
               $relabel[$k]["label"]=$v;
               $relabel[$k]["total"]=$t;
           }
       }else{
           $relabel["label"]=array();
       }
       $res["label"]=$relabel;
      
       $res["list"]=$list;
       $res["total"]=$total;
       $res["pagetotal"]=ceil($total/$num);
       $res["pagesize"]=$num;
       //获取好评率
       $condition="and  checked=0 and deleted=0 and (goodsid=:gid or group_goodsid=:good_id)";
       $param=array(":gid"=>$good["gid"],":good_id"=>$goods_id);
       $goodnum=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_order_comment")." where 1 ".$condition,$param);

       if ($total==0){
           $res["rate"]=100;
       }else{
           $res["rate"]=($goodnum%$total)*100;
       }
       if ($res["rate"]==0){
           $res["rate"]=100;
       }
      
       return $res;
   }
}