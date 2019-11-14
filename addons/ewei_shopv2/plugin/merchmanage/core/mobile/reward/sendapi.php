<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';

class Sendapi_EweiShopV2Page extends MerchmanageMobilePage{
     //短信发送
     public function cs(){
         $content="尊敬的会员，您收到一份来自跑库店铺的好运祝福，请查收>>登录小程序店铺领取100元折现金券。快致电13460300820退订回T【跑库】";
         $mobile="13460300820,18236962763";
         $resault=m("market")->send_out($mobile,$content);
         show_json(1,$resault);
     }
    
     //短信模板
     public function codetemplate(){
         header('Access-Control-Allow-Origin:*');
         $list["template"]=pdo_fetchall("select id,content from ".tablename("ewei_shop_codetemplate")." where is_delete=0 order by id asc");
         foreach ($list["template"] as $k=>$v){
             $list["template"][$k]["content"]="【跑库】".$v["content"];
         }
         show_json(1,$list);
     }
     //短信详情
     public function template_detail(){
         header('Access-Control-Allow-Origin:*');
         global $_W;
         global $_GPC;
         $template_id=$_GPC["template_id"];
         if (empty($template_id)){
             show_json(0,"模板id不可为空");
         }
         
         $detail=pdo_get("ewei_shop_codetemplate",array('id'=>$template_id));
         if (empty($detail)){
             show_json(0,"模板id不正确");
         }
        
         $list["id"]=$detail["id"];
         $list["content"]="【跑库】".$detail["content"];
         $list["variable"]=m("market")->handle($detail["content"]);
         show_json(0,$list);
     }
     //获取用户列表
     public function member(){
         header('Access-Control-Allow-Origin:*');
         global $_W;
         global $_GPC;
         $merchid = $_W['merchmanage']['merchid'];
         //       var_dump($merchid);die;
         if (empty($merchid)){
             $merchid=$_GPC['merchid'];
         }
         
         //获取商户订单
         $order=pdo_fetchall("select DISTINCT openid from ".tablename("ewei_shop_order")." where merchid=:merchid and status>=1",array(':merchid'=>$merchid));
         $member=array();
         $i=0;
         foreach ($order as $k=>$v){
            $m=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            if ($m["mobile"]){
                $member[$i]["mobile"]=$m["mobile"];
                $member[$i]["nickname"]=$m["nickname"];
                $member[$i]["avatar"]=$m["avatar"];
                $member[$i]["openid"]=$m["openid"];
                $i=$i+1;
            }else{
                $address=pdo_fetch("select * from ".tablename("ewei_shop_member_address")."where openid=:openid and deleted=0 order by isdefault desc",array("openid"=>$v["openid"]));
                if ($address){
                    $member[$i]["mobile"]=$address["mobile"];
                    $member[$i]["nickname"]=$m["nickname"];
                    $member[$i]["avatar"]=$m["avatar"];
                    $member[$i]["openid"]=$m["openid"];
                    $i=$i+1;
                }
                
            }
         }
         $list["member"]=$member;
         show_json(1,$list);
     }
    //生成订单
    public function create_order(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $merchid = $_W['merchmanage']['merchid'];
        //       var_dump($merchid);die;
        if (empty($merchid)){
            $merchid=$_GPC['merchid'];
        }
        $template_id=$_GPC["template_id"];
        $template=pdo_get("ewei_shop_codetemplate",array('id'=>$template_id));
        if (empty($template)){
            show_json(0,"模板不存在");
        }
        $data["tempalte_id"]=$template_id;
        $data["merch_id"]=$merchid;
        $openid=$_GPC["openid"];
        if (empty($openid)){
            show_json(0,"请选择用户");
        }
        $openid=explode(",", $openid);
        //获取手机号
        $data["mobile"]="";
        foreach ($openid as $k=>$v){
            $member=pdo_get("ewei_shop_member",array("openid"=>$v));
            if (!empty($member["mobile"])){
                if (empty($data["mobile"])){
                    $data["mobile"]=$member["mobile"];
                }else{
                    $data["mobile"]=$data["mobile"].",".$member["mobile"];
                }
            }else{
                $address=pdo_fetch("select * from ".tablename("ewei_shop_member_address")."where openid=:openid and deleted=0 order by isdefault desc",array("openid"=>$v));
                if (empty($data["mobile"])){
                    $data["mobile"]=$address["mobile"];
                }else{
                    $data["mobile"]=$data["mobile"].",".$address["mobile"];
                }
            }
        }
        $count=count($openid);
        $data["num"]=$count;
        $data["openid"]=serialize($openid);
        $data["money"]=$count*0.2;
        //$data["money"]=0.01;
        //模板变量
        $content=m("market")->handle($template["content"]);
        //接收的变量
        $c=$_GPC["content"];
        $c=explode(",", $c);
        if (count($c)!=count($content)){
            show_json(0,"变量填写不完整");
        }
        if (count($content)>1){
            $data["content"]=serialize($c);
            //获取发送末班
             $data["template_content"]= m("market")->replace($template["content"],$c);

        }else{
            $data["template_content"]=$template["content"];
        }
        $data["order_sn"]="CD".date("Ymdhis").rand(100,999).$merchid;
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_codeorder",$data)){
            show_json(1,$data["order_sn"]);
        }else{
            show_json(0,"失败");
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
        $log=pdo_get("ewei_shop_codeorder",array('order_sn'=>$order_sn));
       
        $params["openid"]=$openid;
        $params["fee"] =$log["money"];
        $params["title"]="商家购买短信";
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
        include $this->template();
    }
    //微信支付成功回调
    public function order_wxback(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $order_sn=$_GPC["order_sn"];
        $order=pdo_get("ewei_shop_codeorder",array("order_sn"=>$order_sn));
       
            //发送短信
            $resault=m("market")->send_out($order["mobile"],$order["template_content"]);
            if ($resault){
                pdo_update("ewei_shop_codeorder",array("status"=>2),array("order_sn"=>$order_sn));
                header('location: ' . mobileUrl('merchmanage/reward/sendapi/index'));
                exit();
//                  var_dump($resault);
            }
//             var_dump($resault);
            header('location: ' . mobileUrl('merchmanage/reward/sendapi/index'));
            exit();
    }
    //短信模板页面
    public function index(){
        global $_W;
        global $_GPC;
        
        include $this->template();
     
    }
    //短信模板详情
    public function detail(){
        global $_W;
        global $_GPC;
        $template_id=$_GPC["id"];
        $openid=$_GPC["openid"];
        $num=$_GPC["num"];
        $date=date("Y-m-d");
        include $this->template();
    }
    //选择会员
    public function select_member(){
        global $_W;
        global $_GPC;
        $template_id=$_GPC["id"];
        
        include $this->template();
    }
}