<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage 
{
    /**
     * 对接消费折扣宝
     */
    public function main()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        //接收参数
        $mobile = $_GPC['mobile'];
        $msg = $_GPC['msg'];
        $money = $_GPC['money'];
        $token = $_GPC['token'];
        //判断参数完整性
        if($mobile == "" || $msg == "" || $money == ""){
            //show_json(201,"参数不完整");
            exit(json_encode(['code'=>201,'msg'=>"参数不完整"]));
        }
        $redis = redis();
        if($redis->get($mobile.$msg.$money."token")){
            exit(json_encode(['code'=>202,'msg'=>"请求过于频繁,请1分钟后谨慎处理"]));
        }else{
            $token = md5($mobile.$msg.$money.time().random(6));
            $redis->set($mobile.$msg.$money."token",$token,60);
        }
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$uniacid]);
        //用户不存在  用户的折扣宝余额
        if(!$member){
            //show_json(203,"用户不存在");
            exit(json_encode(['code'=>203,'msg'=>"用户不存在"]));
        }elseif($member['credit3'] < $money){
            //show_json(204,"折扣宝余额不足");
            exit(json_encode(['code'=>204,'msg'=>"折扣宝余额不足"]));
        }
        //查看短息信息
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$msg,'result'=>0]);
        if($sms){
            pdo_update('core_sendsms_log',['status'=>1],['id'=>$sms['id']]);
        }else{
            //show_json(205,"短信验证码不正确");
            exit(json_encode(['code'=>205,'msg'=>"短信验证码不正确"]));
        }
        //结算折扣宝的余额
        $data['credit3'] = bcsub($member['credit3'],$money,2);
        $res = pdo_update('ewei_shop_member',$data,['openid'=>$member['openid'],'mobile'=>$mobile]);
        if($res){
            //show_json(200,"充值成功");
            exit(json_encode(['code'=>200,'msg'=>"支付成功"]));
        }
    }

    /**
     * 发送短信
     */
    public function sms_send()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $mobile=$_GPC["mobile"];
        $country_id=$_GPC["country_id"];
        if($mobile == "" || $country_id == ""){
            //app_error(1,"参数信息不完整");
            exit(json_encode(['code'=>201,'msg'=>'参数不完善']));
        }
        $code=rand(100000,999999);
        if (empty($country_id)||$country_id==44){
            if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
                //app_error(1,"手机号格式不正确");
                exit(json_encode(['code'=>202,'msg'=>'手机号格式不正确']));
            }
            $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>1,'code'=>$code));
        }else{
            $country=pdo_get("sms_country",array("id"=>$country_id));
            $resault=com_run("sms::mysend", array('mobile'=>$country["phonecode"].$mobile,'tp_id'=>3,'code'=>$code));
        }
        if ($resault["status"]==1){
            pdo_insert('core_sendsms_log',['uniacid'=>$_W['uniacid'],'mobile'=>$mobile,'content'=>$code,'createtime'=>time()]);
            exit(json_encode(['code'=>200,'data'=>['mobile'=>$mobile,'msg'=>$code]]));
        }else{
            //app_error(1,$resault["message"]);
            exit(json_encode(['code'=>203,'msg'=>$resault['message']]));
        }
    }
}
?>