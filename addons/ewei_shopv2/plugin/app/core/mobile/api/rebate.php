<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Rebate_EweiShopV2Page extends AppMobilePage
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
        $apitoken = $_GPC['apitoken'];
        //判断参数完整性
        $add = ['mobile'=>$mobile,'msg'=>$msg,'money'=>$money,'token'=>$token];
        if($mobile == "" || $msg == "" || $money == "" || $token == ""){
            //show_json(201,"参数不完整");
            $this->addlog($add,201,'参数不完整');
            exit(json_encode(['code'=>201,'msg'=>"参数不完整"]));
        }
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$uniacid]);
        //计算用户的额度
        $limit = m('game')->checklimit($member['openid'],$member['agentlevel']);
        //计算用户已经消费的额度
        $sale = pdo_fetchall('select * from '.tablename('mc_credits_record').' where openid = :openid and remark = "RV钱包充值" and createtime > 1570776300',[':openid'=>$member['openid']]);
        $sale_sum = abs(array_sum(array_column($sale,'num')));
        if($sale_sum + $money > $limit){
            exit(json_encode(['code'=>207,'msg'=>'请前往跑库-折扣付-额度购买额度']));
        }
        if($token != md5(md5(base64_encode($mobile.$msg.$member['openid'])))){
            $this->addlog($add,202,'折扣宝充值鉴权验证失败');
            exit(json_encode(['code'=>202,'msg'=>'折扣宝充值鉴权验证失败']));
        }
        $redis = redis();
        if($redis->get($mobile.$msg.$money."token")){
           $this->addlog($add,203,"请求过于频繁,请1分钟后谨慎处理");
           exit(json_encode(['code'=>203,'msg'=>"请求过于频繁,请1分钟后谨慎处理"]));
        }else{
            $token = md5($mobile.$msg.$money.time().random(6));
            $redis->set($mobile.$msg.$money."token",$token,60);
        }
        //用户不存在  用户的折扣宝余额
        if(!$member){
            //show_json(204,"用户不存在");
            $this->addlog($add,204,"用户信息不正确");
            exit(json_encode(['code'=>204,'msg'=>"用户信息不正确"]));
        }elseif($member['credit3'] < $money){
            //show_json(205,"折扣宝余额不足");
            $this->addlog($add,205,"折扣宝余额不足");
            exit(json_encode(['code'=>205,'msg'=>"折扣宝余额不足"]));
        }
        //查看短息信息
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$msg,'result'=>0]);
        if($sms){
            if($sms['result'] == 0){
                pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
            }
        }else{
            //show_json(206,"短信验证码不正确");
            $this->addlog($add,206,"短信验证码不正确");
            exit(json_encode(['code'=>206,'msg'=>"短信验证码不正确"]));
        }
        //结算折扣宝的余额
        $data['credit3'] = bcsub($member['credit3'],$money,2);
        $res = pdo_update('ewei_shop_member',$data,['openid'=>$member['openid'],'mobile'=>$mobile]);
        if($res){
            //show_json(200,"支付成功");
            $company = pdo_get('core_company',['apisecret'=>$apitoken,'uniacid'=>$_W['uniacid'],'status'=>1]);
            $message = empty($apitoken) ? "RV钱包充值" : $company['company']."钱包充值";
            m('game')->addCreditlog($member['openid'],3,-$money,$message);
            $this->addlog($add,200,"支付成功");
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
        $apitoken = $_GPC['apitoken'];
        $country_id=$_GPC["country_id"];
        if($mobile == "" || $apitoken == "" ){
            //app_error(1,"参数信息不完整");
            exit(json_encode(['code'=>201,'msg'=>'参数不完善']));
        }
        //查找对接公司
        $company = pdo_get('core_company',['apisecret'=>$apitoken,'uniacid'=>$_W['uniacid'],'status'=>1]);
        if(!$company){
            exit(json_encode(['code'=>202,'msg'=>'短信鉴权验证失败']));
        }
        $member = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$_W['uniacid']]);
        //生成短信验证码
        $code=rand(100000,999999);
        if (empty($country_id) || $country_id == 44){
            if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
                //app_error(203,"手机号格式不正确");
                exit(json_encode(['code'=>203,'msg'=>'手机号格式不正确']));
            }
            $tp_id = 4;
            $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }else{
            $tp_id = 6;
            $country=pdo_get("sms_country",array("id"=>$country_id));
            $resault=com_run("sms::mysend", array('mobile'=>$country["phonecode"].$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }
        if ($resault["status"]==1){
            pdo_insert('core_sendsms_log',['uniacid'=>$_W['uniacid'],'mobile'=>$mobile,'tp_id'=>$tp_id,'content'=>$code,'createtime'=>time(),'ip'=>CLIENT_IP]);
            $token = md5(md5(base64_encode($mobile.$code.$member['openid'])));
            exit(json_encode(['code'=>200,'msg'=>"发送成功",'token'=>$token]));
        }else{
            //app_error(204,$resault["message"]);
            exit(json_encode(['code'=>204,'msg'=>$resault['message']]));
        }
    }

    /**
     * @param $add
     * @param $code
     * @param $msg
     * @return bool
     */
    public function addlog($add,$code,$msg)
    {
        $data = [
            'request'=>json_encode($add,true),
            'response'=>json_encode(['code'=>$code,'msg'=>$msg],true),
            'createtime'=>time()
        ];
        return pdo_insert('core_rebate_log',$data);
    }
}
?>