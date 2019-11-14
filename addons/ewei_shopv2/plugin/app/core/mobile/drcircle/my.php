<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//fanbeibei
class My_EweiShopV2Page extends AppMobilePage{
    //选择商品
    public function sel_good(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member=pdo_get("ewei_shop_member",array("openid"=>$openid));
        //获取商户信息
        $merch=pdo_get("ewei_shop_merch_user",array("member_id"=>$member["id"]));
        if (empty($merch)){
            app_error(1,"您还不是商户，请先入驻商户");
        }elseif ($merch["status"]==0){
            app_error(1,"您的商户账户处于审核中");
        }elseif ($merch["status"]==2){
            app_error(1,"您的商户账户已被暂停");
        }
        $page=$_GPC["page"];
        $first=($page-1)*10;
        $good_id=$_GPC["goods_id"];
        $goods=pdo_fetchall("select id,title,thumb,productprice,marketprice from ".tablename("ewei_shop_goods")." where status=1 and deleted=0 and merchid=:merchid and checked=0 order by id desc limit ".$first." , 10",array(":merchid"=>$merch["id"]));
        foreach ($goods as $k=>$v){
            $goods[$k]["thumb"]=tomedia($v["thumb"]);
            if ($good_id){
                if ($good_id==$v["id"]){
                    $goods[$k]["select"]=1;
                }else{
                    $goods[$k]["select"]=0;
                }
            }else{
                $goods[$k]["select"]=0;
            }
        }
        $total=pdo_fetch("select count(*) as a from ".tablename("ewei_shop_goods")." where status=1 and deleted=0 and merchid=:merchid order by id desc limit ".$first." , 10",array(":merchid"=>$merch["id"]));
        $l["list"]=$goods;
        $l["total"]=$total["a"];
        app_error(0,$l);
    }
    //判断用户
    public function shop(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member=pdo_get("ewei_shop_member",array("openid"=>$openid));
        //获取商户信息
        $merch=pdo_get("ewei_shop_merch_user",array("member_id"=>$member["id"]));
        if (empty($merch)){
            app_error(1,"温馨提示：亲亲，你暂未开通店主“开通店主，用户可在线购买自己的商品哦”！");
        }elseif ($merch["status"]==0){
            app_error(1,"您的商户账户处于审核中");
        }elseif ($merch["status"]==2){
            app_error(1,"您的商户账户已被暂停");
        }else{
            app_error(0,"店主");
        }
       
    }
    
    //商品信息
    public function good(){
        global $_W;
        global $_GPC;
        $good_id=$_GPC["goods_id"];
        if (empty($good_id)){
            app_error(1,"goods_id不可为空");
        }
        $goods=pdo_fetch("select id,title,thumb,productprice,marketprice,sales,subtitle from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$good_id));
        $goods["thumb"]=tomedia($goods["thumb"]);
        app_error(0,$goods);
    }
    //发布
    public function fabu(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $data["openid"]=$openid;
        if (empty($openid)){
            app_error(1,"未传openid");
        }
        $data["content"]=$_GPC["content"];
        if ($_GPC["content"]){
       
        $count=$this->sensitive($data["content"]);
        if ($count>0){
            app_error(1,"发布内容含有敏感词，请修改后发布");
        }
        }
        if ($_GPC["img"]){
        $img=serialize($_GPC["img"]);
        $data["img"]=$img;
        }
        $data["goods_id"]=$_GPC["goods_id"];
        $data["create_time"]=time();
        $data["create_day"]=date("Y-m-d H:i:s");
        if (pdo_insert("ewei_shop_member_drcircle",$data)){
            pdo_delete("ewei_shop_member_drcirclelog",array("openid"=>$openid));
            app_error(0,"发布成功");
        }else{
            app_error(1,"发布失败");
        }
    }
    
    function sensitives($string){
        //获取敏感词
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>2));
        $list=unserialize($notice["content"]);
        $count = 0; //违规词的个数
        $sensitiveWord = '';  //违规词
        $stringAfter = $string;  //替换后的内容
        $pattern = "/".implode("|",$list)."/i"; //定义正则表达式
        if(preg_match_all($pattern, $string, $matches)){ //匹配到了结果
            $patternList = $matches[0];  //匹配到的数组
            $count = count($patternList);
            $sensitiveWord = implode(',', $patternList); //敏感词数组转字符串
            $replaceArray = array_combine($patternList,array_fill(0,count($patternList),'*')); //把匹配到的数组进行合并，替换使用
            $stringAfter = strtr($string, $replaceArray); //结果替换
        }
        
        return $count;
    }
    
    //我的发布列表
    public function mylist(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $page=$_GPC["page"];
        $first=($page-1)*10;
        $list=pdo_fetchall("select id,content,img,goods_id,view_count,comment_count,zan_count,create_day,create_time from ".tablename("ewei_shop_member_drcircle")." where is_view=0 and is_del=0 and openid=:openid order by create_time desc limit ".$first." ,10",array(":openid"=>$openid));
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
        foreach ($list as $k=>$v){
           //判断时间阶段
           $time=$this->judge_day($v["create_time"]);
           if ($time["type"]==0){
               if (in_array($time["type"], $type)){
//                $l[$i]["type"]=0;
//                $l[$i]["time"]=date("Y-m-d",time());
//               var_dump($j);
               $l[$i]["dt"][$j]["id"]=$v["id"];
               $l[$i]["dt"][$j]["content"]=$v["content"];
               $l[$i]["dt"][$j]["view_count"]=$v["view_count"];
               $l[$i]["dt"][$j]["comment_count"]=$v["comment_count"];
               $l[$i]["dt"][$j]["zan_count"]=$v["zan_count"];
               $l[$i]["dt"][$j]["create_time"]=date("H:i",$v["create_time"]);
               if ($v["img"]){
               $img=unserialize($v["img"]);
               $l[$i]["dt"][$j]["img"]=tomedia($img[0]);
               }else{
                   $l[$i]["dt"][$j]["img"]="";
               }
               
               $j+=1;
               }else{
                  
                   $l[$i]["type"]=0;
                   $l[$i]["time"]=date("Y-m-d",time());
                   $l[$i]["dt"]=array();
                   $l[$i]["dt"][$j]["id"]=$v["id"];
                   $l[$i]["dt"][$j]["content"]=$v["content"];
                   $l[$i]["dt"][$j]["view_count"]=$v["view_count"];
                   $l[$i]["dt"][$j]["comment_count"]=$v["comment_count"];
                   $l[$i]["dt"][$j]["zan_count"]=$v["zan_count"];
                   $l[$i]["dt"][$j]["create_time"]=date("H:i",$v["create_time"]);
                   if ($v["img"]){
                   $img=unserialize($v["img"]);
                   $l[$i]["dt"][$j]["img"]=tomedia($img[0]);
                   }else{
                       $l[$i]["dt"][$j]["img"]="";
                   }
                   $j+=1;
                   $type[$typei]=$time["type"];
                   $typei+=1;
                  
               }
               
              
           }elseif ($time["type"]==1){
//               var_dump($time);var_dump($v["id"]);
               if (in_array($time["type"], $type)){
//                $l[$i]["type"]=1;
//                $l[$i]["time"]=$time["res"];
                  
                   $l[$i]["dt"][$jj]["id"]=$v["id"];
                   $l[$i]["dt"][$jj]["content"]=$v["content"];
                   $l[$i]["dt"][$jj]["view_count"]=$v["view_count"];
                   $l[$i]["dt"][$jj]["comment_count"]=$v["comment_count"];
                   $l[$i]["dt"][$jj]["zan_count"]=$v["zan_count"];
                   $l[$i]["dt"][$jj]["create_time"]=date("H:i",$v["create_time"]);
                   if ($v["img"]){
                   $img=unserialize($v["img"]);
                   $l[$i]["dt"][$jj]["img"]=tomedia($img[0]);
                   }else{
                    $l[$i]["dt"][$jj]["img"]="";
                   }
                   
               $jj+=1;
               }else{
                   if ($l){
                   $i+=1;
                   }
                   $l[$i]["type"]=1;
                   $l[$i]["time"]=$time["res"];
                   $l[$i]["dt"]=array();
                   $l[$i]["dt"][$jj]["id"]=$v["id"];
                   $l[$i]["dt"][$jj]["content"]=$v["content"];
                   $l[$i]["dt"][$jj]["view_count"]=$v["view_count"];
                   $l[$i]["dt"][$jj]["comment_count"]=$v["comment_count"];
                   $l[$i]["dt"][$jj]["zan_count"]=$v["zan_count"];
                   $l[$i]["dt"][$jj]["create_time"]=date("H:i",$v["create_time"]);
                   if ($v["img"]){
                   $img=unserialize($v["img"]);
                   $l[$i]["dt"][$jj]["img"]=tomedia($img[0]);
                   }else{
                       $l[$i]["dt"][$jj]["img"]="";
                   }
                   $jj+=1;
                   $type[$typei]=$time["type"];
                   $typei+=1; 
                  
               }
           //  var_dump($l);
           }elseif ($time["type"]==2){
               
               if (in_array($time["type"], $type)&&in_array($time["res"], $month)){
//                $l[$i]["type"]=2;
//                $l[$i]["time"]=$time["res"];
                   //包含月份
                   $l[$i]["dt"][$jjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjj]["content"]=$v["content"];
                   $l[$i]["dt"][$jjj]["view_count"]=$v["view_count"];
                   $l[$i]["dt"][$jjj]["comment_count"]=$v["comment_count"];
                   $l[$i]["dt"][$jjj]["zan_count"]=$v["zan_count"];
                   $l[$i]["dt"][$jjj]["create_time"]=date("m-d H:i",$v["create_time"]);
                   if ($v["img"]){
                   $img=unserialize($v["img"]);
                   $l[$i]["dt"][$jjj]["img"]=tomedia($img[0]);
                   }else{
                       $l[$i]["dt"][$jjj]["img"]="";
                   }
                   $jjj+=1;
               }else{
                   //不包含月份
                 if ($l){
                $i+=1;
                 }
                $l[$i]["type"]=2;
                $l[$i]["time"]=$time["res"];
               $month[$monthi]=$time["res"];
               $monthi+=1;
               $jjj=0;
               $l[$i]["dt"]=array();
               $l[$i]["dt"][$jjj]["id"]=$v["id"];
               $l[$i]["dt"][$jjj]["content"]=$v["content"];
               $l[$i]["dt"][$jjj]["view_count"]=$v["view_count"];
               $l[$i]["dt"][$jjj]["comment_count"]=$v["comment_count"];
               $l[$i]["dt"][$jjj]["zan_count"]=$v["zan_count"];
               $l[$i]["dt"][$jjj]["create_time"]=date("m-d H:i",$v["create_time"]);
               if ($v["img"]){
               $img=unserialize($v["img"]);
               $l[$i]["dt"][$jjj]["img"]=tomedia($img[0]);
               }else{
                   
                   $l[$i]["dt"][$jjj]["img"]="";
               }
               $jjj+=1;
               $type[$typei]=$time["type"];
               $typei+=1; 
              
               }
             
           }elseif ($time["type"]==3){
               if (in_array($time["type"], $type)&&in_array($time["res"], $year)){
//                $l[$i]["type"]=3;
//                $l[$i]["time"]=$time["res"];
                   
                   $l[$i]["dt"][$jjjj]["id"]=$v["id"];
                   $l[$i]["dt"][$jjjj]["content"]=$v["content"];
                   $l[$i]["dt"][$jjjj]["view_count"]=$v["view_count"];
                   $l[$i]["dt"][$jjjj]["comment_count"]=$v["comment_count"];
                   $l[$i]["dt"][$jjjj]["zan_count"]=$v["zan_count"];
                   $l[$i]["dt"][$jjjj]["create_time"]=date("Y.m.d H:i",$v["create_time"]);
                   if ($v["img"]){
                   $img=unserialize($v["img"]);
                   $l[$i]["dt"][$jjjj]["img"]=tomedia($img[0]);
                   }else{
                       $l[$i]["dt"][$jjjj]["img"]="";
                   }
                   $jjjj+=1;
               }else{
                   if ($l){
                   $i+=1;
                   }
                $l[$i]["type"]=3;
                $l[$i]["time"]=$time["res"];
                
               $year[$yeari]=$time["res"];
               $yeari+=1;
               $jjjj=0;
               $l[$i]["dt"]=array();
               $l[$i]["dt"][$jjjj]["id"]=$v["id"];
               $l[$i]["dt"][$jjjj]["content"]=$v["content"];
               $l[$i]["dt"][$jjjj]["view_count"]=$v["view_count"];
               $l[$i]["dt"][$jjjj]["comment_count"]=$v["comment_count"];
               $l[$i]["dt"][$jjjj]["zan_count"]=$v["zan_count"];
               $l[$i]["dt"][$jjjj]["create_time"]=date("Y.m.d H:i",$v["create_time"]);
               if ($v["img"]){
               $img=unserialize($v["img"]);
               $l[$i]["dt"][$jjjj]["img"]=tomedia($img[0]);
               }else{
                   $l[$i]["dt"][$jjjj]["img"]="";
               }
               $jjjj+=1;
               $type[$typei]=$time["type"];
               $typei+=1;
               
               }
               
               
           }
           
        }
        $z["list"]=$l;
        $total=pdo_fetch("select count(*) as a from ".tablename("ewei_shop_member_drcircle")." where is_view=0 and is_del=0 and openid=:openid order by create_time desc",array(":openid"=>$openid));;
        $z["total"]=$total["a"];
        app_error(0,$z);
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
    //删除--达人圈信息
    public function del_drcircle(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $circle_id=$_GPC["circle_id"];
        $circle=pdo_get("ewei_shop_member_drcircle",array("id"=>$circle_id,"is_del"=>0,"is_view"=>0));
        if ($circle["openid"]!=$openid){
            app_error(1,"只能删除自己的朋友圈");
        }
        if (pdo_update("ewei_shop_member_drcircle",array("is_del"=>1),array("id"=>$circle_id))){
            app_error(0,"删除成功");
        }else{
            app_error(1,"删除失败");
        }
    }
    //删除--评论
    public function del_comment(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $comment_id=$_GPC["comment_id"];
        $comment=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment_id,"is_del"=>0,"is_view"=>0));
        if (empty($comment)){
            app_error(1,"评论id不正确");
        }
        if ($comment["openid"]!=$openid){
            app_error(1,"无权限删除");
        }
        if (pdo_update("ewei_shop_member_drcomment",array("is_del"=>1),array("id"=>$comment_id))){
            //更新上级评论数目
            $count=$comment["comment_count"]+1;
            if ($comment["levelid"]){
            $parent_id=unserialize($comment["levelid"]);
            $p="";
            foreach ($parent_id as $k=>$v){
                if (empty($p)){
                    $p=$v;
                }else{
                    $p=$p.",".$v;
                }
            }
            pdo_query('update '.tablename("ewei_shop_member_drcomment").' set comment_count=comment_count-'.$count.' where id in('.$p.')'); 
            }
            //更新动态
            if ($comment["classA_id"]){
                $c=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment["classA_id"]));
                $circle_id=$c["parent_id"];
            }else{
                $circle_id=$comment["parent_id"];
            }
            pdo_query('update '.tablename("ewei_shop_member_drcircle").' set comment_count=comment_count-'.$count.'  where id='.$circle_id);
            
            app_error(0,"删除成功");
        }else{
            app_error(1,"删除失败");
        }
    }
    //评论
    public function comment(){
        global $_W;
        global $_GPC;
        $type=$_GPC["type"];
        $parent_id=$_GPC["parent_id"];
        $data["type"]=$type;//1表示达人圈 2表示评论
        if (empty($type)){
            app_error(1,"type未传");
        }
        $data["parent_id"]=$parent_id;
        $data["openid"]=$_GPC["openid"];
        $data["content"]=$_GPC["content"];
        $data["create_time"]=time();
        if (empty($data["content"])){
            app_error(1,"评论信息不可为空");
        }
        $c=$this->sensitive($data["content"]);
        if ($c>0){
            app_error(1,"含有敏感词不可提交");
        }
        if ($type==1){
            $circle=pdo_get("ewei_shop_member_drcircle",array("id"=>$parent_id,"is_del"=>0,"is_view"=>0));
            if (empty($circle)){
                app_error(1,"信息已不存在");
            }
            $data["comment_openid"]=$circle["openid"];
            //获取达人圈id
            $circle_id=$parent_id;
        }
        if ($type==2){
            $comment=pdo_get("ewei_shop_member_drcomment",array("id"=>$parent_id,"is_del"=>0,"is_view"=>0));
            if (empty($comment)){
                app_error(1,"信息已不存在");
            }
            $data["comment_openid"]=$comment["openid"];
            if (empty($comment["classA_id"])){
                //回复一级评论
                $data["classA_id"]=$comment["id"];
                $levelid[0]=$comment["id"];
                $data["levelid"]=serialize($levelid);
                $circle_id=$comment["parent_id"];
            }else{
                $data["classA_id"]=$comment["classA_id"];
                $levelid=unserialize($comment["levelid"]);
                $len=sizeof($levelid);
                $levelid[$len]=$comment["id"];
                $data["levelid"]=serialize($levelid);
                //获取达人圈id
                $parent=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment["classA_id"]));
                $circle_id=$parent["parent_id"];
            }
        }
        $l="";
        foreach ($levelid as $k=>$v){
            if (empty($l)){
                $l=$v;
            }else{
                $l=$l.",".$v;
            }
        }
        if (pdo_insert("ewei_shop_member_drcomment",$data)){
          
               //更新达人圈
               pdo_query('update '.tablename("ewei_shop_member_drcircle").' set comment_count=comment_count+1 where id='.$circle_id);
               if ($type==2){
                   //更新上级评论数目
                   pdo_query("update ".tablename("ewei_shop_member_drcomment").' set comment_count=comment_count+1 where id in ('.$l.')');
               }
               app_error(0,"成功");
        }else{
            app_error(1,"失败");
        }
        
    }
    
    //通知--评论
    public function mycomment(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $page=$_GPC["page"];
        $first=($page-1)*8;
        $list=pdo_fetchall("select id,openid,content,is_del,is_view,create_time,comment_openid,type,classA_id,parent_id from ".tablename("ewei_shop_member_drcomment")." where comment_openid=:comment_openid order by create_time desc limit ".$first.",8",array(":comment_openid"=>$openid));
        $a=pdo_fetch("select count(*) as a from ".tablename("ewei_shop_member_drcomment")." where comment_openid=:comment_openid",array(":comment_openid"=>$openid));
        foreach ($list as $k=>$v){
            $list[$k]["create_time"]=$this->timeFormat($v["create_time"]);
            //获取评论人信息
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["comment_nickname"]=$member["nickname"];
            $list[$k]["comment_avatar"]=$member["avatar"];
            //获取被回复
            $c=pdo_get("ewei_shop_member",array("openid"=>$v["comment_openid"]));
            $list[$k]["bcommnet_openid"]=$c["nickname"];
            if ($v["type"]==1){
                //达人圈
                $circle=pdo_get("ewei_shop_member_drcircle",array("id"=>$v["parent_id"]));
                $list[$k]["circle"]["is_del"]=$circle["is_del"];
                $list[$k]["circle"]["is_view"]=$circle["is_view"];
                $list[$k]["circle"]["circle_id"]=$circle["id"];
                $list[$k]["circle"]["content"]=$circle["content"];
                $img=unserialize($circle["img"]);
                $list[$k]["circle"]["img"]=tomedia($img[0]);
                $circle_member=pdo_get("ewei_shop_member",array("openid"=>$circle["openid"]));
                $list[$k]["circle"]["nickname"]=$circle_member["nickname"];
                //获取回复内容
                $list[$k]["comment"]=array();
               
                
            }else{
                //获取达人圈
                $pcomment=pdo_get("ewei_shop_member_drcomment",array("id"=>$v["classA_id"]));
                $circle=pdo_get("ewei_shop_member_drcircle",array("id"=>$pcomment["parent_id"]));
                $list[$k]["circle"]["is_del"]=$circle["is_del"];
                $list[$k]["circle"]["is_view"]=$circle["is_view"];
                $list[$k]["circle"]["circle_id"]=$circle["id"];
                $list[$k]["circle"]["content"]=$circle["content"];
                $img=unserialize($circle["img"]);
                $list[$k]["circle"]["img"]=tomedia($img[0]);
                $circle_member=pdo_get("ewei_shop_member",array("openid"=>$circle["openid"]));
                $list[$k]["circle"]["nickname"]=$circle_member["nickname"];
                //获取回复内容
                $comment=pdo_get("ewei_shop_member_drcomment",array("id"=>$v["parent_id"]));
                $cnickname=pdo_get("ewei_shop_member",array("openid"=>$comment["openid"]));
                $list[$k]["comment"]["comment_nickname"]=$cnickname["nickname"];
                if ($comment["comment_openid"]){
                $bnickname=pdo_get("ewei_shop_member",array("openid"=>$comment["comment_openid"]));
                $list[$k]["comment"]["bcomment_nickname"]=$bnickname["nickname"];
                }else{
                    $list[$k]["comment"]["bcomment_nickname"]="";
                }
                $list[$k]["comment"]["content"]=$comment["content"];
                $list[$k]["comment"]["is_view"]=$comment["is_view"];
                $list[$k]["comment"]["is_del"]=$comment["is_del"];
                
            }
            //判断是否已被回复
            $log=pdo_get("ewei_shop_member_drcomment",array("openid"=>$openid,"type"=>2,"parent_id"=>$v["id"]));
            if ($log){
                $list[$k]["reply"]=1;
            }else{
                $list[$k]["reply"]=0;
            }
        }
        $l["list"]=$list;
        $l["total"]=$a["a"];
        app_error(0,$l);
    }
    
    function timeFormat( $timestamp ) {
        $curTime = time();
        $space = $curTime - $timestamp;
        if($space < 60) { // 一分钟以内
            $string = "刚刚";
            return $string;
        } elseif( $space < 3600 ) { // 一小时前之内
            $string = floor($space / 60) . "分钟前";
            return $string;
        }
        $curtimeArray = getdate($curTime);
        $timeArray = getDate($timestamp);
        if( $curtimeArray['year'] == $timeArray['year'] ) { // 同一年
            if($curtimeArray['yday'] == $timeArray['yday']) { // 同一天
                $format = "%H:%M";
                $string = strftime($format, $timestamp);
                return "今天 {$string}";
            } elseif(($curtimeArray['yday'] - 1) == $timeArray['yday']) { // 昨天
                $format = "%H:%M";
                $string = strftime($format, $timestamp);
                return "昨天 {$string}";
            } else  {
                $string = sprintf("%d月%d日 %d:%d", $timeArray['mon'], $timeArray['mday'], $timeArray['hours'], $timeArray['minutes']);
                return $string;
            }
        }
        $string = sprintf("%d年%d月%d日 %d:%d", $timeArray['year'], $timeArray['mon'], $timeArray['mday'], $timeArray['hours'], $timeArray['minutes']);
        return $string;
    }
    //点赞
    public function support(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"];
        $content_id=$_GPC["content_id"];
        if (empty($type)){
            app_error(2,"type不可为空");
        }
        if (empty($openid)||empty($content_id)){
            app_error(2,"openid|content_id不可为空");
        }
        $log=pdo_get("ewei_shop_member_drsupport",array("openid"=>$openid,"type"=>$type,"content_id"=>$content_id));
        if ($log){
            app_error(1,"不可重复点赞");
        }
        $m=pdo_get("ewei_shop_member",array("openid"=>$openid));
        $data["openid"]=$openid;
        $data["type"]=$type;
        $data["content_id"]=$content_id;
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_member_drsupport",$data)){
            //更新点赞的信息’
            if ($type==1){
                $l=pdo_get("ewei_shop_member_drcircle",array("id"=>$content_id));
                $zan_count=$l["zan_count"]+1;
                $d["zan_count"]=$zan_count;
                pdo_update("ewei_shop_member_drcircle",$d,array("id"=>$content_id));
              //  pdo_query('update '.tablename("ewei_shop_member_drcircle").' set zan_count=zan_count+1  where id='.$content_id);
                
            }else {
                $l=pdo_get("ewei_shop_member_drcomment",array("id"=>$content_id));
                $d["zan_count"]=$l["zan_count"]+1;
                pdo_update("ewei_shop_member_drcomment",$d,array("id"=>$content_id));
//                 pdo_query('update '.tablename("ewei_shop_member_drcomment").' set zan_count=zan_count+1  where id='.$content_id); 
            }
            $mes["avatar"]=$m["avatar"];
            $mes["message"]="点赞成功";
            app_error(0,$mes);
        }else{
            app_error(2,"点赞失败");
        }
    }
    //取消点赞
    public function del_support(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $type=$_GPC["type"];
        $content_id=$_GPC["content_id"];
        if (empty($type)){
            app_error(2,"type不可为空");
        }
        if (empty($openid)||empty($content_id)){
            app_error(2,"openid|content_id不可为空");
        }
        $log=pdo_get("ewei_shop_member_drsupport",array("openid"=>$openid,"type"=>$type,"content_id"=>$content_id));
        if (empty($log)){
            app_error(1,"未曾点赞");
        }
        if (pdo_delete("ewei_shop_member_drsupport",array("id"=>$log["id"]))){
            //更新点赞的信息’
            if ($type==1){
                $l=pdo_get("ewei_shop_member_drcircle",array("id"=>$content_id));
                $zan_count=$l["zan_count"]-1;
                if ($zan_count<0){
                    $zan_count=0;
                }
                $d["zan_count"]=$zan_count;
               // pdo_query('update '.tablename("ewei_shop_member_drcircle").' set zan_count=zan_count-1  where id='.$content_id);
                pdo_update("ewei_shop_member_drcircle",$d,array("id"=>$content_id));
            }else {
                $l=pdo_get("ewei_shop_member_drcomment",array("id"=>$content_id));
                $d["zan_count"]=$l["zan_count"]-1;
                 if ($d["zan_count"]<0){
                     $d["zan_count"]=0;
                 }
                 pdo_update("ewei_shop_member_drcomment",$d,array("id"=>$content_id));
                // pdo_query('update '.tablename("ewei_shop_member_drcomment").' set zan_count=zan_count-1  where id='.$content_id);
            }
            app_error(0,"取消成功");
        }else{
            app_error(2,"取消失败");
        }
        
    }
    //发布退出--保留
    public function savelog(){
        
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $data["openid"]=$openid;
        if (empty($openid)){
            app_error(1,"未传openid");
        }
        $data["content"]=$_GPC["content"];
        
        if ($_GPC["img"]){
            $img=serialize($_GPC["img"]);
            $data["img"]=$img;
        }
        $data["goods_id"]=$_GPC["goods_id"];
        $data["create_time"]=time();
        pdo_delete("ewei_shop_member_drcirclelog",array("openid"=>$openid));
        if (pdo_insert("ewei_shop_member_drcirclelog",$data)){
            app_error(0,"发布成功");
        }else{
            app_error(1,"发布失败");
        }
        
    }
    //获取发布保留信息
    public function log(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
       $log= pdo_get("ewei_shop_member_drcirclelog",array("openid"=>$openid));
        if (empty($log)){
            app_error(1,"无信息");
        }
        //获取图片
        if ($log["img"]){
        $img=unserialize($log["img"]);
        $log["img"]=array();
        foreach ($img as $k=>$v){
            $log["img"][$k]["filename"]=$v;
            $log["img"][$k]["url"]=tomedia($v);
        }
        }else{
            $log["img"]=array();
        }
        app_error(0,$log);
    }
    //删除暂时保留
    public function del_log(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        pdo_delete("ewei_shop_member_drcirclelog",array("openid"=>$openid));
        app_error(0,"成功");
        
    }
     //获取access_token
    public function access_token(){
        $res=file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx4b602a36aa1c67d1&secret=e68369138b66cdae48729e6a996dd17d");
//         var_dump($res);
        $res=json_decode($res);
//         var_dump($res->errcode);
        return $res;
    }
    
    public function sensitive($string){
        $res=$this->access_token();
        if ($res->errcode==0){
            
            $data["content"]=$string;
            //  var_dump(json_encode($data,JSON_UNESCAPED_UNICODE));
            $url="https://api.weixin.qq.com/wxa/msg_sec_check?access_token=".$res->access_token;
            $re=$this->curl_post_raw($url,json_encode($data,JSON_UNESCAPED_UNICODE));
            $re=json_decode($re);
            if($re->errcode==87014){
                return 10;
            }else{
                $count=$this->sensitives($string);
                return $count;
            }
        }else{
            $count=$this->sensitives($string);
            return $count;
        }
    }
	
	function curl_post_raw($url,$rawData){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
	}
}