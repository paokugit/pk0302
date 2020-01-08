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
            app_error1(1,"手机号格式不正确",[]);
        }
        if(!preg_match('/^[a-zA-Z0-9]{8,20}$/',$pwd)){
            app_error1(1,"密码必须大小写加数字8到20位",[]);
        }
        //短信类型
        $tp_id = 1;
        if($country_id != 44 && !empty($country_id)){
            $tp_id = 3;
        }
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$code,'tp_id'=>$tp_id]);
        if(!$sms){
            app_error1(1,"短信验证码不正确",[]);
        }
        if($sms['result'] == 1){
            app_error1(1,"该短信已验证",[]);
        }
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        if($type == 1){
            //注册
            $member = pdo_get('ewei_shop_member',['mobile'=>$mobile]);
            if(!empty($member)){
	    	//pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd.$member['salt']))],['mobile'=>$mobile]);
                //pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
                app_error1(1,'手机号已注册',[]);
            }else{
                $salt = random(16);
                //pdo_insert('ewei_shop_member',['mobile'=>$mobile,'password'=>md5(base64_encode($pwd.$salt)),'createtime'=>time(),'status'=>1,'salt'=>$salt]);
                pdo_insert('ewei_shop_member',['mobile'=>$mobile,'password'=>md5(base64_encode($pwd)),'createtime'=>time(),'status'=>1,'salt'=>$salt]);
            }
        }else{
            //修改密码
            pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['mobile'=>$mobile]);
        }
        app_error1(0);
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
            app_error1(1,"手机号未注册",[]);
        }else{
            //if(md5(base64_encode($pwd.$member['salt'])) == $member['password']){
            if(md5(base64_encode($pwd)) == $member['password']){
                //APP登录动态码  如果有人登录就更新
                $app_salt = random(36);
                pdo_update('ewei_shop_member',['app_salt'=>$app_salt],['id'=>$member['id']]);
                $token = m('app')->setLoginToken($member['id'],$app_salt);
                //app_error1(0,'登录成功',['token'=>$token,'member'=>$member]);
                app_error1(0,'登录成功',['token'=>$token]);
            }else{
                app_error1(1,"密码不正确",[]);
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
            app_error1(1,"手机号格式不正确");
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
            app_error1(1,"短信验证码不正确");
        }
        if($sms['result'] == 1){
            app_error1(1,"该短信已验证");
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
            //app_error1(0,'登录成功',['token'=>$token,'member'=>pdo_get('ewei_shop_member',['id'=>$user_id])]);
            app_error1(0,'登录成功',['token'=>$token]);
        }else{
            //如果已经存在 更新app动态码
            pdo_update('ewei_shop_member',['app_salt'=>$app_salt],['id'=>$member['id']]);
            $token = m('app')->setLoginToken($member['id'],$app_salt);
            app_error1(0,'登陆成功',['token'=>$token]);
        }
    }
    /**
     * 国家区号
     */
    public function country()
    {
        $data = pdo_fetchall("select id,phonecode,name_zh from ".tablename("sms_country")." where name_zh=:name_zh1 or name_zh=:name_zh2 or name_zh=:name_zh3",array(":name_zh1"=>"中国",":name_zh2"=>"马来西亚",":name_zh3"=>"泰国"));
        app_error1(0,"",['data'=>$data]);
    }

    /**
     * 手机号是否存在
     */
    public function mobile()
    {
        global $_GPC;
        $mobile = $_GPC['mobile'];
        //type  == 1 注册   == 2 忘记密码
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        $member = pdo_get('ewei_shop_member',['uniacid'=>1,'mobile'=>$mobile]);
        //如果是注册   用户存在  则报错 手机号已存在
        if($type == 1 ){
            if($member) app_error1(1,'手机号已存在',[]);
        }elseif($type == 2){
            //如果用户不存在  是忘记密码  手机号未注册
            if(!$member) app_error1(1,'手机号未注册',[]);
        }
        app_error1(0,'',[]);
    }

    /**
     * 手机号是否存在
     */
    public function mobile_reg()
    {
        global $_GPC;
        $mobile = $_GPC['mobile'];
        $member = pdo_get('ewei_shop_member',['uniacid'=>1,'mobile'=>$mobile]);
        app_error1(0,'',['is_reg'=>$member ? 1 : 0]);
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
                app_error1(1,"手机号格式不正确",[]);
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
            app_error1(0,"发送成功",[]);
        }else{
            app_error1(1,$resault["message"],[]);
        }
    }

    /**
     * 设置新密码
     */
    public function set_pwd()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //获取token  并且获取user_id  判断登录是否失效
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录信息失效',[]);
        //用户信息
        $member = m('member')->getMember($user_id);
        //老密码 新密码  确认新密码
        $old_pwd = $_GPC['old_pwd'];
        //判断老密码是否正确
        if(!empty($member['password']) && $member['password'] != md5(base64_encode($old_pwd))) app_error1(1,'旧密码错误',[]);
        $pwd =$_GPC['pwd'];
        $password = $_GPC['password'];
        //密码正则
        if(!preg_match('/^[a-zA-Z0-9]{8,20}$/',$pwd)) app_error1(1,"密码必须大小写加数字8到20位",[]);
        //新密码和确认新密码是否一致
        if($pwd != $password) app_error1(1,'两次新密码不一致',[]);
        //更改密码
        pdo_update('ewei_shop_member',['password'=>md5(base64_encode($pwd))],['id'=>$member['id']]);
        app_error1(0,'修改成功',[]);
    }

    /**
     * 更换手机号
     */
    public function change_mobile()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //获取token  并且获取user_id  判断登录是否失效
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录信息失效',[]);
        //用户信息
        $member = m('member')->getMember($user_id);
        //接受手机号   验证码  国家id
        $mobile = $_GPC['mobile'];
        $code = $_GPC['code'];
        $country_id = $_GPC['country_id'];
        if(pdo_exists('ewei_shop_member',['mobile'=>$mobile])) app_error1(1,'该手机号已存在',[]);
        //短信类型
        $tp_id = $country_id != 44 && !empty($country_id) ? 3 : 1;
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$code,'tp_id'=>$tp_id]);
        if(!$sms) app_error1(1,"短信验证码不正确");
        if($sms['result'] == 1) app_error1(1,"该短信已验证");
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        //更改手机号
        pdo_update('ewei_shop_member',['mobile'=>$mobile],['id'=>$member['id']]);
        app_error1(0,'修改成功',[]);
    }

    /**
     * 微信授权绑定
     */
    public function wx_login()
    {
        global $_GPC;
        //用户信息
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //if(empty($user_id)) app_error1(2,'登录信息失效',[]);
        $member = m('member')->getMember($user_id);
        //请求的code
        $code = $_GPC['code'];
        //$type == 1 登录  $type == 2绑定微信
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        //APPID   和   appsecret
        $appid = "wx60621be200d12658";
        $secret = "8feb476f19350701b934faa5eaa78396";
        $grant_type = "authorization_code";
        //获取access_token
        $access_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=".$grant_type;
        $access_response = ihttp_get($access_url)['content'];
        if(json_decode($access_response,true)['errcode'] != 0){
            $status = json_decode($access_response,true)['errcode'];
            $msg = json_decode($access_response,true)['errmsg'];
            app_error1($status,$msg,[]);
        }
        $access_token = json_decode($access_response,true)['access_token'];
        $openid = json_decode($access_response,true)['openid'];
        //获取用户的unionID
        $union_url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid;
        $union_response = ihttp_get($union_url)['content'];
        if(json_decode($union_response,true)['errcode'] != 0){
            $status = json_decode($union_response,true)['errcode'];
            $msg = json_decode($union_response,true)['errmsg'];
            app_error1($status,$msg,[]);
        }
        //用户的unionid  nickname  headimgurl
        $unionid = json_decode($union_response,true)['unionid'];
        $nickname = json_decode($union_response,true)['nickname'];
        $headimgurl = json_decode($union_response,true)['headimgurl'];
        //是否存在这个微信unionid
        $exists = pdo_get('ewei_shop_member',['unionid'=>$unionid]);
        //绑定微信
        if($type == 2){
            //不存在  更新unionid  微信昵称
            if(!$exists){
                $param = [
                    'unionid'=>$unionid,
                    'wx_nickname'=>$nickname,
                    'wx_headimgurl'=>$headimgurl
                ];
                if(empty($member['nickname'])){
                    $param['nickname'] = $nickname;
                }
                if(empty($member['headimgurl'])){
                    $param['headimgurl'] = $headimgurl;
                }
                pdo_update('ewei_shop_member',$param,['id'=>$member['id']]);
                app_error1(0,'',[]);
            }else{
                app_error1(1,'用户已存在 不可绑定',[]);
            }
        }elseif ($type == 1){
            $app_salt = random(36);
            $salt = random(16);
            //如果不存在  就插入数据  昵称 头像 微信昵称 微信头像  unionid  等
            if(!$exists){
                pdo_insert('ewei_shop_member',['app_salt'=>$app_salt,'unionid'=>$unionid,'wx_headimgurl'=>$headimgurl,'nickname'=>$nickname,'headimgurl'=>$headimgurl,'wx_nickname'=>$nickname,'createtime'=>time(),'status'=>1,'salt'=>$salt]);
                $user_id = pdo_insertid();
                $token = m('app')->setLoginToken($user_id,$app_salt);
            }else{
                pdo_update('ewei_shop_member',['app_salt'=>$app_salt,'wx_headimgurl'=>$headimgurl,'wx_nickname'=>$nickname],['id'=>$exists['id']]);
                $token = m('app')->setLoginToken($exists['id'],$app_salt);
            }
            app_error1(0,'',['token'=>$token]);
        }
    }

    /**
     * 微信授权绑定
     */
    public function mob_login()
    {
        global $_GPC;
        //用户信息
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $member = m('member')->getMember($user_id);
        //请求的code
        $unionid = $_GPC['unionid'];
        //$type == 1 登录  $type == 2绑定微信
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        //微信昵称 微信头像
        $nickname = $_GPC['nickname'];
        $headimgurl = $_GPC['headimgurl'];
        //是否存在这个微信unionid
        $exists = pdo_get('ewei_shop_member',['unionid'=>$unionid]);
        //绑定微信
        if($type == 2){
            //不存在  更新unionid  微信昵称
            if(!$exists){
                $param = [
                    'unionid'=>$unionid,
                    'wx_nickname'=>$nickname,
                    'wx_headimgurl'=>$headimgurl
                ];
                if(empty($member['nickname'])){
                    $param['nickname'] = $nickname;
                }
                if(empty($member['headimgurl'])){
                    $param['headimgurl'] = $headimgurl;
                }
                pdo_update('ewei_shop_member',$param,['id'=>$member['id']]);
                app_error1(0,'',[]);
            }else{
                app_error1(1,'用户已存在 不可绑定',[]);
            }
        }elseif ($type == 1){
            $app_salt = random(36);
            $salt = random(16);
            //如果不存在  就插入数据  昵称 头像 微信昵称 微信头像  unionid  等
            if(!$exists){
                pdo_insert('ewei_shop_member',['app_salt'=>$app_salt,'unionid'=>$unionid,'wx_headimgurl'=>$headimgurl,'nickname'=>$nickname,'headimgurl'=>$headimgurl,'wx_nickname'=>$nickname,'createtime'=>time(),'status'=>1,'salt'=>$salt]);
                $user_id = pdo_insertid();
                $token = m('app')->setLoginToken($user_id,$app_salt);
            }else{
                pdo_update('ewei_shop_member',['app_salt'=>$app_salt,'wx_headimgurl'=>$headimgurl,'wx_nickname'=>$nickname],['id'=>$exists['id']]);
                $token = m('app')->setLoginToken($exists['id'],$app_salt);
            }
            app_error1(0,'',['token'=>$token]);
        }
    }

    /**
     * access_token 登录授权
     */
    public function access_login()
    {
        global $_GPC;
        //用户信息
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //if(empty($user_id)) app_error1(2,'登录信息失效',[]);
        $member = m('member')->getMember($user_id);
        //请求的access_token
        $access_token = $_GPC['access_token'];
        //请求的openid
        $openid = $_GPC['openid'];
        //$type == 1 登录  $type == 2绑定微信
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        //检验授权凭证（access_token）是否有效
        $access_url = "https://api.weixin.qq.com/sns/auth?access_token=".$access_token."&openid=".$openid;
        $access_response = ihttp_get($access_url)['content'];
        if(json_decode($access_response,true)['errcode'] != 0){
            $status = json_decode($access_response,true)['errcode'];
            $msg = json_decode($access_response,true)['errmsg'];
            app_error1($status,$msg,[]);
        }
        //获取用户的unionID
        $union_url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid;
        $union_response = ihttp_get($union_url)['content'];
        if(json_decode($union_response,true)['errcode'] != 0){
            $status = json_decode($union_response,true)['errcode'];
            $msg = json_decode($union_response,true)['errmsg'];
            app_error1($status,$msg,[]);
        }
        //用户的unionid  nickname  headimgurl
        $unionid = json_decode($union_response,true)['unionid'];
        $nickname = json_decode($union_response,true)['nickname'];
        $headimgurl = json_decode($union_response,true)['headimgurl'];
        //是否存在这个微信unionid
        $exists = pdo_get('ewei_shop_member',['unionid'=>$unionid]);
        //绑定微信
        if($type == 2){
            //不存在  更新unionid  微信昵称
            if(!$exists){
                $param = [
                    'unionid'=>$unionid,
                    'wx_nickname'=>$nickname,
                    'wx_headimgurl'=>$headimgurl
                ];
                if(empty($member['nickname'])){
                    $param['nickname'] = $nickname;
                }
                if(empty($member['headimgurl'])){
                    $param['headimgurl'] = $headimgurl;
                }
                pdo_update('ewei_shop_member',$param,['id'=>$member['id']]);
                app_error1(0,'',[]);
            }else{
                app_error1(1,'用户已存在 不可绑定',[]);
            }
        }elseif ($type == 1){
            $app_salt = random(36);
            $salt = random(16);
            //如果不存在  就插入数据  昵称 头像 微信昵称 微信头像  unionid  等
            if(!$exists){
                pdo_insert('ewei_shop_member',['app_salt'=>$app_salt,'unionid'=>$unionid,'wx_headimgurl'=>$headimgurl,'nickname'=>$nickname,'headimgurl'=>$headimgurl,'wx_nickname'=>$nickname,'createtime'=>time(),'status'=>1,'salt'=>$salt]);
                $user_id = pdo_insertid();
                $token = m('app')->setLoginToken($user_id,$app_salt);
            }else{
                pdo_update('ewei_shop_member',['app_salt'=>$app_salt,'wx_headimgurl'=>$headimgurl,'wx_nickname'=>$nickname],['id'=>$exists['id']]);
                $token = m('app')->setLoginToken($exists['id'],$app_salt);
            }
            app_error1(0,'',['token'=>$token]);
        }
    }

    /**
     * 微信解除绑单
     */
    public function wx_unbind()
    {
        global $_GPC;
        //用户信息
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录信息失效',[]);
        $member = m('member')->getMember($user_id);
        //$unionid = $_GPC['unionid'];
        //$exists = pdo_get('ewei_shop_member',['unionid'=>$unionid]);
        //if(!$exists) app_error1(1,'微信信息错误',[]);
        pdo_update('ewei_shop_member',['unionid'=>''],['id'=>$member['id']]);
        app_error1(0,'解除绑定成功',[]);
    }
}
?>