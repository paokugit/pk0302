<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';

class Index_EweiShopV2Page extends MerchmanageMobilePage
{
    
    //充值金额
    public function purchaseset(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        
        $list = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_purchase') . (' order by money asc ') );
        $l["set"]=$list;
        show_json(1,$l);
    }
    
    
  //充值--创建订单
  public function order(){
      header('Access-Control-Allow-Origin:*');
      global $_W;
      global $_GPC;
       $merchid = $_W['merchmanage']['merchid'];
//       var_dump($merchid);die;
      if (empty($merchid)){
          $merchid=$_GPC['merchid'];
      }
      $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
      if ($merch["member_id"]==0){
          show_json(0,"未绑定小程序账户");
      }
      $data["merch_id"]=$merchid;
      $data["purchase_id"]=$_GPC["purchase_id"];
      if ($data["purchase_id"]!=0){
          $purchase=pdo_get("ewei_shop_merch_purchase",array('id'=>$data["purchase_id"]));
          if (empty($purchase)){
              show_json(0,"充值id不正确");
          }
          $data["purchase"]=$purchase["money"];
          $data["give"]=$purchase["give"];
          $data["money"]=$purchase["money"];
      }else{
          $data["money"]=$_GPC["money"];
          $data["purchase"]=$_GPC["money"];
      }
        $data["order_sn"]="GP".date("Ymdhis").rand(100,999).$merchid;
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_merch_purchaselog",$data)){
            show_json(1,$data["order_sn"]);
        }else{
            show_json(0,$data);
        }
      
  }
   //充值--微信支付
   public function order_wx(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       
       $openid=$_W["openid"];
       if (empty($openid)){
           $result = mc_oauth_userinfo();
           $openid=$result["openid"];
       }

       if (empty($openid)){
           $openid=$_GPC["openid"];
       }
       $order_sn=$_GPC["order_sn"];
       $log=pdo_get("ewei_shop_merch_purchaselog",array('order_sn'=>$order_sn));
//        if (empty($log)){
//            show_json(0,"订单编号不正确");
//        }
//        if ($log["status"]==1){
//            show_json(0,"该订单已被支付");
//        }

       $params["openid"]=$openid;
       $params["fee"] =$log["money"];
       $params["title"]="商家充值";
       $params["tid"]=$order_sn;
       load()->model("payment");
       $setting = uni_setting($_W["uniacid"], array( "payment" ));
       if( is_array($setting["payment"]) )
       {
           $options = $setting["payment"]["wechat"];
           $options["appid"] = $_W["account"]["key"];
           $options["secret"] = $_W["account"]["secret"];
       }
       $options["mch_id"]=$options["mchid"];
       // 	    var_dump($options);die;
       
       $wechat = m("common")->fwechat_child_build($params, $options, 0);

//        if (is_error($wechat)){
//            show_json(0,$wechat);
// //               var_dump($wechat);
//        }
       include $this->template();
   }
   
   //充值--成功回调
   public function order_wxback(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $order_sn=$_GPC["order_sn"];
       $log=pdo_get("ewei_shop_merch_purchaselog",array("order_sn"=>$order_sn));
//        if (empty($log)) {
//            show_json(0,"订单编号错误");
//        }
//        if ($log["status"]==1){
//            show_json(0,"该订单已被支付");
//        }
       $r=m("merch")->set_cardlog($order_sn,0);
//       if ($r){
//           show_json(1,"成功");
//        } else{
//            show_json(0,"失败");
//        }
        
       header('location: ' . mobileUrl('merchmanage/reward/home/index'));
       exit();
       
   }
   
   //发布赏金--选择商品
   public function sel_good(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
       //赏金类型 1全部商品 2指定商品
       $extend_type=$_GPC["extend_type"];
       
       $pindex = max(1, intval($_GPC['page']));
       $psize = 10;
       
       $condition="and merchid=:merchid";
       $title=$_GPC["title"];
       if (!empty($title)){
           $condition.=" and title like :title";
       }
       if (empty($title)){
           
          $params = array(':merchid' => $merchid);
       
       }else{
           
           $params = array(':merchid' => $merchid,':title'=>"%".$title."%");
       }
//        $sql = 'SELECT id,title,thumb,total,sales,deduct,minprice,maxprice FROM ' . tablename('ewei_shop_goods') . ' where 1 ' . $condition . ' ORDER BY id desc LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
       $sql = 'SELECT id,title,thumb,total,sales,deduct,minprice,maxprice FROM ' . tablename('ewei_shop_goods') . ' where 1 ' . $condition . ' ORDER BY id desc ';
       
       $list = pdo_fetchall($sql, $params);
//        var_dump($list);
       $total=pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_goods').' where 1 '.$condition,$params);
       if ($extend_type==2){
           
       //获取商家赏金
       $merch=pdo_fetchall('select * from'.tablename('ewei_shop_merch_reward').'where is_end=0 and type=1 and merch_id=:merchid',array(':merchid'=>$merchid));
       
       $g=array();
       if (!empty($merch)){
       foreach ($merch as $k=>$v){
           $g[$k]=unserialize($v["goodid"]);
       }
       }
       
       }
       foreach ($list as $k=>$v){
           
           $list[$k]["thumb"]=tomedia($v["thumb"]);
           
           if ($extend_type==2){
               
           if ($g){
               if (m("merch")->good($g,$v["id"])){
                   $list[$k]["isadd"]=1;
               }else{
                   $list[$k]["isadd"]=0;
               }
               
           }else{
               $list[$k]["isadd"]=0;
           }
           
           }else{
               $list[$k]["isadd"]=0;
           }
           
       }
       $l["total"]=$total;
       $l["good"]=$list;
       show_json(1,$l);
   }
   //指定商品--选择后商品列表
   public function good(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $goodid=$_GPC["goodid"];
       $goodid=explode(",", $goodid);
       $list=array();
       $kk=0;
       foreach ($goodid as $k=>$v){
           if (!empty($v)){
           $g=pdo_get("ewei_shop_goods",array('id'=>$v));
           $list[$kk]["id"]=$v;
           $list[$kk]["title"]=$g["title"];
           $list[$kk]["thumb"]=tomedia($g["thumb"]);
           $list[$kk]["total"]=$g["total"];
           $list[$kk]["sales"]=$g["sales"];
           $list[$kk]["deduct"]=$g["deduct"];
           $list[$kk]["minprice"]=$g["minprice"];
           $list[$kk]["maxprice"]=$g["maxprice"];
           $kk=$kk+1;
           }
       }
       $l["good"]=$list;
       show_json(1,$l);
       
   }
   //指定商品--发布赏金任务
   public function good_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
       $data["merch_id"]=$merchid;
       $goodid=$_GPC["goodid"];
       if (empty($goodid)){
           show_json(0,"商品不可为空");
       }
       $goodid=explode(",", $goodid);
       $data["goodid"]=serialize($goodid);
       $data["share_price"]=$_GPC["share_price"];
       $data["click_price"]=$_GPC["click_price"];
       if ($data["share_price"]<0.1||$data["click_price"]<0.1){
           show_json(0,"金额不可小于0.1");
       }
       $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
       if ($merch["member_id"]){
           $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
           $yue=$member["credit1"];
       }else{
           $yue=$merch["card"];
       }
       
       if ($data["share_price"]+$data["click_price"]>$yue){
           show_json(0,"余额不足");
       }
       $data["commission"]=$_GPC["commission"];
       $data["type"]=1;
       $data["create_time"]=time();
       if (pdo_insert("ewei_shop_merch_reward",$data)){
           //更新商家
           pdo_update("ewei_shop_merch_user",array('reward_type'=>1),array('id'=>$merchid));
           //更新全部赏金
          pdo_update("ewei_shop_merch_reward",array('is_end'=>1),array('merch_id'=>$merchid,'type'=>2));
          show_json(1,"发布成功");
       }else{
           show_json(0,"发布失败");
       }
       
    }
   //全部商品--发布赏金任务
   public function whole_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
       $data["merch_id"]=$merchid;
       $data["share_price"]=$_GPC["share_price"];
       $data["click_price"]=$_GPC["click_price"];
       if ($data["share_price"]<0.1||$data["click_price"]<0.1){
           show_json(0,"分享奖励|点击奖励金额不可小于0.1");
       }
       $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
       if ($merch["member_id"]){
           $member=pdo_get("ewei_shop_member",array("id"=>$merch["member_id"]));
           $yue=$member["credit1"];
       }else{
           $yue=$merch["card"];
       }
       
       if ($data["share_price"]+$data["click_price"]>$yue){
           show_json(0,"余额不足");
       }
       
       $data["commission"]=$_GPC["commission"];
       $data["type"]=2;
       $data["create_time"]=time();
       pdo_insert("ewei_shop_merch_reward",$data);
       $id=pdo_insertid();
       
       if ($id){
           //更新商家
           pdo_update("ewei_shop_merch_user",array('reward_type'=>2),array('id'=>$merchid));
           //更新全部赏金
          
           pdo_query("update ".tablename('ewei_shop_merch_reward')." SET is_end =1 WHERE id != :id and merch_id=:merchid", array(':id' => $id,':merchid'=>$merchid));
           show_json(1,"发布成功");
       }else{
           show_json(0,"发布失败");
       }
   }
   
   //获取商家余额
   public function shop_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
       $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
       
       if (empty($merch)){
           show_json(0,"商户不存在");
       }
       //获取绑定的用户
       if ($merch["member_id"]!=0){
       $member=pdo_get("ewei_shop_member",array('id'=>$merch["member_id"]));
       $l["card"]=$member["credit1"];
       }else{
       $l["card"]=0;
       }
      
       $l["reward_type"]=$merch["reward_type"];
       show_json(1,$l);
   }
   //获取商家当前进行任务
   public function go_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
       $pageindex=max(1,intval($_GPC["page"]));
       $pagesize=6;
//        $sql="select id,type,share,click,commission_count,create_time from ".tablename("ewei_shop_merch_reward")." where merch_id=:merch_id and is_end=0 order by create_time desc limit ".($pageindex-1)*$pagesize.','.$pagesize;
       $sql="select id,type,share,click,commission_count,create_time from ".tablename("ewei_shop_merch_reward")." where merch_id=:merch_id and is_end=0 order by create_time desc";
       
       $param=array(':merch_id'=>$merchid);
       $list["list"]=pdo_fetchall($sql,$param);
       foreach ($list["list"] as $k=>$v){
           $list["list"][$k]["create_time"]=date("Y-m-d H:i:s",$v["create_time"]);
       }
       $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_merch_reward")." where merch_id=:merch_id and is_end=0",$param);
       $list["count"]=$count;
       show_json(1,$list);
   }
   
   //获取指定商品赏金任务--商品
   public function appoint_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $reward_id=$_GPC["reward_id"];
       $reward=pdo_get("ewei_shop_merch_reward",array("id"=>$reward_id));
       if (empty($reward)){
           show_json(0,"赏金任务id不正确");
       }
        if ($reward["type"]==2){
            show_json(0,"该赏金任务为全部商品");
        }
        $goodid=unserialize($reward["goodid"]);
        
        $g=array();
        foreach ($goodid as $k=>$v){
            $good=pdo_get("ewei_shop_goods",array('id'=>$v));
            $g[$k]["id"]=$good["id"];
            $g[$k]["title"]=$good["title"];
            $g[$k]["thumb"]=tomedia($good["thumb"]);
            $g[$k]["total"]=$good["total"];
            $g[$k]["sales"]=$good["sales"];
            $g[$k]["deduct"]=$good["deduct"];
            $g[$k]["minprice"]=$good["minprice"];
            $g[$k]["maxprice"]=$good["maxprice"];
        }
        
        $list["good"]=$g;
        show_json(1,$list);
   }
   //赏金任务--结束
   public function end_reward(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $reward_id=$_GPC["reward_id"];
       $reward=pdo_get("ewei_shop_merch_reward",array("id"=>$reward_id));
       if (empty($reward)) {
           show_json(0,"赏金任务id不正确");
       }
       if (pdo_update("ewei_shop_merch_reward",array('is_end'=>1),array("id"=>$reward_id))){
          show_json(1,"成功");
       }else{
           show_json(0,"失败");
       }
   }
   
   //商家明细
   public function reward_log(){
       header('Access-Control-Allow-Origin:*');
       global $_W;
       global $_GPC;
       $merchid = $_W['merchmanage']['merchid'];
       //       var_dump($merchid);die;
       if (empty($merchid)){
           $merchid=$_GPC['merchid'];
       }
      
       $pageindex=max(1,intval($_GPC["page"]));
       $pagesize=10;
       $sql="select * from ".tablename("ewei_shop_merch_rewardlog")." where merch_id=:merch_id order by create_time desc limit ".($pageindex-1)*$pagesize.",".$pagesize;
       $param=array(':merch_id'=>$merchid);
       $list["log"]=pdo_fetchall($sql,$param);
       foreach ($list["log"] as $k=>$v){
           $list["log"][$k]["create_time"]=date("Y-m-d H:i:s",$v["create_time"]);
       }
       show_json(1,$list);
   }
   
  public function cs(){
      var_dump(date("Ymdhis"));
  }  
  
  
  public function reward(){
      global $_W;
      global $_GPC;
      $list=pdo_fetchall('select * from '.tablename("ewei_shop_merch_reward").' where merch_id=:merch_id',array(':merch_id'=>3));
//       var_dump($list);
      foreach ($list as $k=>$v){
          $goodid=unserialize($v["goodid"]);
          $list[$k]["good"]=$goodid;
      }
      show_json(1,$list);
  }
}