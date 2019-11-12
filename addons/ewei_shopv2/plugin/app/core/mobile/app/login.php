<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Login_EweiShopV2Page extends AppMobilePage
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
        $pwd = preg_replace('# #','',$_GPC['pwd']);
        $country_id = $_GPC['country_id'];
        //$type == 1  注册   $type == 2 忘记密码
        //正则验证手机号的格式
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            app_error(1,"手机号格式不正确");
        }
        if(!preg_match('/^[a-zA-Z0-9]{8,20}$/',$pwd)){
            app_error(1,"密码必须大小写加数字8到20位");
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
                //pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd.$member['salt']))],['mobile'=>$mobile]);
                pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
            }else{
                $salt = random(16);
                //pdo_insert('ewei_shop_member',['mobile'=>$mobile,'password'=>md5(base64_encode($pwd.$salt)),'createtime'=>time(),'status'=>1,'salt'=>$salt]);
                pdo_insert('ewei_shop_member',['mobile'=>$mobile,'password'=>md5(base64_encode($pwd)),'createtime'=>time(),'status'=>1,'salt'=>$salt]);
            }
        }else{
            //修改密码
            pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
        }
        app_error(0);
    }

    /**
     * 账号密码登录
     */
    public function main()
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
            //if(md5(base64_encode($pwd.$member['salt'])) == $member['password']){
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
        global $_W;
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
}
?>