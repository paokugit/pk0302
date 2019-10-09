<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage
{
    /**
     * 注册  或者  忘记密码
     */
    public function reg_forget()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $mobile = $_GPC['mobile'];
        $code = $_GPC['code'];
        $type = $_GPC['type'];
        $pwd = $_GPC['pwd'];
        //$type == 1  注册   $type == 2 忘记密码
        //正则验证手机号的格式
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            show_json(0,"手机号格式不正确");
        }
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'code'=>$code,'tp_id'=>5]);
        if(!$sms){
            show_json(0,"短信验证码不正确");
        }
        if($sms['result'] == 1){
            show_json(0,"该短信已验证");
        }
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        if($type == 1){

        }
    }

    /**
     * 登录
     */
    public function login()
    {
        header('Access-Control-Allow-Origin:*');
    }

    /**
     * 验证码登录
     */
    public function code_login()
    {
        header('Access-Control-Allow-Origin:*');
    }

    /**
     * 发送短信
     */
    public function sms_send()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //手机号
        $mobile = $_GPC['mobile'];
        $country_id = $_GPC['country_id'];
        //生成短信验证码
        $code=rand(100000,999999);
        $tp_id = 1;
        if (empty($country_id) || $country_id == 44){
            //阿里云的短信 在我们平台的模板i
            if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
                show_json(0,"手机号格式不正确");
            }
            $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }else{
            $country=pdo_get("sms_country",array("id"=>$country_id));
            $resault=com_run("sms::mysend", array('mobile'=>$country["phonecode"].$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }
        if ($resault["status"]==1){
            //添加短信记录
            pdo_insert('core_sendsms_log',['uniacid'=>$_W['uniacid'],'mobile'=>$mobile,'tp_id'=>5,'content'=>$code,'createtime'=>time(),'ip'=>CLIENT_IP]);
            show_json(1,"发送成功");
        }else{
            show_json(0,$resault["message"]);
        }
    }

    /**
     * 首页
     */
    public function home()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
    }

    /**
     * 商城
     */
    public function shop()
    {

    }

    /**
     * 折扣付
     */
    public function rebate()
    {

    }

    /**
     * 专享
     */
    public function exclusive()
    {

    }

    /**
     * 我的个人中心
     */
    public function my()
    {

    }

    /**
     * 订单加支付
     */
    public function order(){

    }
}
?>