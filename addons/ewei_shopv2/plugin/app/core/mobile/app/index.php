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
        $country_id = $_GPC['country_id'];
        //$type == 1  注册   $type == 2 忘记密码
        //正则验证手机号的格式
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            app_error(1,"手机号格式不正确");
        }
        //短信类型
        $tp_id = 1;
        if($country_id != 44 && !empty($country_id)){
            $tp_id = 3;
        }
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$code,'tp_id'=>$tp_id]);
        if(!$sms){
            app_error(1,"短信验证码不正确");
        }
        if($sms['result'] == 1){
            app_error(1,"该短信已验证");
        }
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        if($type == 1){
            //注册
            $member = pdo_get('ewei_shop_member',['mobile'=>$mobile]);
            if(!empty($member)){
                pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
            }else{
                $salt = random(16);
                pdo_insert('ewei_shop_member',['mobile'=>$mobile,'password'=>md5(base64_encode($pwd)),'createtime'=>time(),'status'=>1,'salt'=>$salt]);
            }
        }else{
            //修改密码
            pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
        }
    }

    /**
     * 账号密码登录
     */
    public function login()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //接受参数
        $mobile = $_GPC['mobile'];
        $pwd = $_GPC['password'];
        //查找改手机号是否注册
        $member = pdo_get('ewei_shop_member',['mobile'=>$mobile]);
        if(!$member){
            app_error(1,"手机号未注册");
        }else{
            if(md5(base64_encode($pwd)) == $member['password']){
                //APP登录动态码  如果有人登录就更新
                $app_salt = random(36);
                pdo_update('ewei_shop_member',['app_salt'=>$app_salt],['id'=>$member['id']]);
                $token = m('app')->setLoginToken($member['id'],$app_salt);
                app_error(0,['token'=>$token,'msg'=>"登录成功"]);
            }else{
                app_error(1,"密码不正确");
            }
        }
    }

    /**
     * 验证码登录
     */
    public function code_login()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $mobile = $_GPC['mobile'];
        $country_id = $_GPC['country_id'];
        //正则验证手机号的格式
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            app_error(1,"手机号格式不正确");
        }
        $code = $_GPC['code'];
        $member = pdo_get('ewei_shop_member',['mobile'=>$mobile]);
        //短信类型
        $tp_id = 1;
        if($country_id != 44 && !empty($country_id)){
            $tp_id = 3;
        }
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$code,'tp_id'=>$tp_id]);
        if(!$sms){
            app_error(1,"短信验证码不正确");
        }
        if($sms['result'] == 1){
            app_error(1,"该短信已验证");
        }
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        $app_salt = random(36);
        if(!$member){
            //短信验证码登录 如果不存在 加入数据  然后 生成一个动态码
            $salt = random(16);
            pdo_insert('ewei_shop_member',['mobile'=>$mobile,'createtime'=>time(),'status'=>1,'salt'=>$salt,'app_salt'=>$app_salt]);
            $user_id = pdo_insertid();
            $token = m('app')->setLoginToken($user_id,$app_salt);
            app_error(0,['token'=>$token]);
        }else{
            //如果已经存在 更新app动态码
            pdo_update('ewei_shop_member',['app_salt'=>$app_salt],['id'=>$member['id']]);
            $token = m('app')->setLoginToken($member['id'],$app_salt);
            app_error(0,['token'=>$token]);
        }
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
                app_error(1,"手机号格式不正确");
            }
            $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }else{
            //发送海外短信
            $country=pdo_get("sms_country",array("id"=>$country_id));
            $tp_id = 3;
            $resault=com_run("sms::mysend", array('mobile'=>$country["phonecode"].$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }
        if ($resault["status"]==1){
            //添加短信记录
            pdo_insert('core_sendsms_log',['uniacid'=>$_W['uniacid'],'mobile'=>$mobile,'tp_id'=>$tp_id,'content'=>$code,'createtime'=>time(),'ip'=>CLIENT_IP]);
            app_error(0,"发送成功");
        }else{
            app_error(1,$resault["message"]);
        }
    }

    /**
     * 首页
     */
    public function home()
    {
        global $_GPC;
        //鉴权验证的token
        $token = $_GPC['token'];
        //icon的类别
        $icon_type = $_GPC['icon_type'];
        //秒杀的类型
        $seckill_type = $_GPC['seckill_type'];
        //附近商家的定位
        $lat = $_GPC['lat'];
        $lng = $_GPC['lng'];
        //允许的距离范围
        $range = $_GPC['range'];
        $cateid = $_GPC['cate_id'];
        $sorttype = intval($_GPC['sorttype']);
        $keyword = $_GPC['keyword'];
        //鉴权验证
        $user_id = m('app')->getLoginToken($token);
        //签到得卡路里 和  年卡会员每日得折扣宝

        //获取用户卡路里   折扣宝  自身步数  和 邀请步数  以及是都绑定手机号
        $bushu = m('app')->getbushu($user_id);
        //小图标导航   快报   和  年卡入口
        $icon = m('app')->get_icon($user_id,$icon_type);
        //门店服务
        $merch = m('app')->merch($user_id);
        //附近商家
        $near = m('app')->near($user_id,$lat,$lng,$range,$cateid,$sorttype,$keyword);
        //秒杀
        $seckill = m('app')->seckill($seckill_type);
        //边看边买
        $look_buy = m('app')->look_buy();
        //每日一推
        $every = m('app')->every();
        //跑库精选
        $choice = m('app')->choice();
        app_error(0,['bushu'=>$bushu,'icon'=>$icon,'merch'=>$merch,'near'=>$near,'seckill'=>$seckill,'look_buy'=>$look_buy,'every'=>$every,'choice'=>$choice]);
    }

    /**
     * 首页---领取卡路里  或者折扣宝  因为后期没有卡路里
     */
    public function index_getcredit()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $step_id =$_GPC['step_id'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0){
            app_error(1,'登录信息失效');
        }
        $get = m('app')->getcredit($user_id,$step_id);
        if($get){
            $error = 0;$msg = "领取成功";
        } else{
            $error = 1;$msg = "领取失败";
        }
        app_error($error,$msg);
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
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //卡路里和折扣宝余额
        $credit = pdo_get('ewei_shop_member',['id'=>$user_id],['credit1','credit3']);
        //贡献机数量 和 运行状态
        $devote_machine = m('app')->devote_machine($user_id);
        //贡献值  和  是否绑定手机号 微信
        $devote = m('app')->devote($user_id);
        app_error(0,['credit'=>$credit,'devote_machine'=>$devote_machine,'devote'=>$devote]);
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