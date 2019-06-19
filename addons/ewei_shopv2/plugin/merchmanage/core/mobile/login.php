<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';
class Login_EweiShopV2Page extends MerchmanageMobilePage
{
    
	public function main()
	{
		global $_W;
		global $_GPC;
    
// 		var_dump(mobileUrl('merchmanage/login'));
// 		var_dump(mobileUrl('merchmanage/login/wx_login'));
		$check = $this->isLogin();
// 		var_dump($_W["openid"]);
		if ($check) {
			header('location: ' . mobileUrl('merchmanage'));
		}
		$backurl = trim($_GPC['backurl']);

		if ( strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')== false ) {
		    
		    $is_wei=0;
		}else{
		    $is_wei=1;
		}
		
		include $this->template();
	}

	public function logout()
	{
		global $_W;
		global $_GPC;
		$session_key = '__merchmanage_' . $_W['uniacid'] . '_session';
		isetcookie($session_key, false, -100);
		unset($GLOBALS['_W']['merchmanage']);

		if ($_W['isajax']) {
			show_json(1);
		}else{
			header('location: ' . mobileUrl('merchmanage/login'));
		}
	}
	//密码--页面
	public function pwlogin(){
	    global $_W;
	    global $_GPC;

	    $check = $this->isLogin();
	    include $this->template();
	}
	//密码登录
	public function loginapi(){
	    header('Access-Control-Allow-Origin:*');
	    global $_W;
	    global $_GPC;
	    $username = trim($_GPC['username']);
	    $password = trim($_GPC['password']);
	    if (empty($username)) {
	        show_json(0, '请填写用户名');
	    }
	    
	    
	    if (empty($password)) {
	        show_json(0, '请填写密码');
	    }
	    
	    
	    if (!($this->model->merch_user_check(array('username' => $username)))) {
	        show_json(0, '用户不存在');
	    }
	    
	    
	    if (!($this->model->merch_user_check(array('username' => $username, 'pwd' => $password)))) {
	        show_json(0, '用户名或密码错误');
	    }
	    
	    $account = $this->model->merch_user_single(array('username' => $username));
	    $account['hash'] = md5($account['pwd'] . $account['salt']);
	    $session = base64_encode(json_encode($account));
	    $session_key = '__merchmanage_' . $_W['uniacid'] . '_session';
	    
	    isetcookie($session_key, $session, 7200);
	    $status = array();
	    $status['lastvisit'] = TIMESTAMP;
	    $status['lastip'] = CLIENT_IP;
	    pdo_update('ewei_shop_merch_account', $status, array('id' => $account['id']));
	    show_json(1,"登陆成功");
	    
	}
	//短信验证码页面
	public function mobile_code(){
	    global $_W;
	    global $_GPC;
	   
	    include $this->template();
	}
	//短信消息
	public function send(){
	    header('Access-Control-Allow-Origin:*');
	    global $_W;
	    global $_GPC;
	    $mobile=$_GPC["mobile"];
	    if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
	        show_json(0,"手机号格式不正确");
	    }
	    //判断是否又该商户
	    $account=pdo_get("ewei_shop_merch_user",array('mobile'=>$mobile));
	    if (empty($account)){
	        show_json(0,"该手机号不存在商户");
	    }
	    $code=rand(100000,999999);
	    $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>1,'code'=>$code));
	    if ($resault["status"]==1){
	        $resault["result"]["code"]=$code;
	        $resault["result"]["mobile"]=$mobile;
	        exit(json_encode($resault));
	    }else{
	        show_json($resault['status'],$resault["message"]);
	    }
	   
	}
	//密码页面登录
	public function reset(){
	    global $_W;
	    global $_GPC;
	    $mobile=$_GPC["mobile"];
	   
	    include $this->template();
	}
	//重置密码
	public function reset_pwd(){
	    header('Access-Control-Allow-Origin:*');
	    global $_W;
	    global $_GPC;
	    $mobile=$_GPC["mobile"];
	    $pwd=$_GPC["pwd"];
	    //判断是否又该商户
	    $account=pdo_get("ewei_shop_merch_user",array('mobile'=>$mobile));
	    if (empty($account)){
	        show_json(0,"该手机号不存在商户");
	    }
	    if (empty($pwd)){
	        show_json(0,"重置密码不可为空");
	    }
	    $user=pdo_get("ewei_shop_merch_account",array('merchid'=>$account["id"]));
	    $pwd=md5($pwd.$user['salt']);
	    if ($pwd==$user["pwd"]){
	        show_json(0,"修改密码不可与原密码一致");
	    }
	    if (pdo_update("ewei_shop_merch_account",array("pwd"=>$pwd),array('id'=>$user["id"]))){
	        show_json(1,"修改成功");
	    }else{
	        show_json(0,"修改失败");
	    }
	}
	//微信
	public function wx_login(){
	    global $_W;
	    global $_GPC;
	    
	        $result = mc_oauth_userinfo();
	        $openid=$result["openid"];
	        if (empty($openid)){
	            
	            echo "<script>alert('授权登录失败');</script>";
	            header('location: ' . mobileUrl('merchmanage'));
	            exit();
	        }
	        
	        $user=pdo_get("ewei_shop_merch_account",array('openid'=>$openid));
	        if (empty($user)){
	            //未绑定账户
	            $member=pdo_get("mc_mapping_fans",array("openid"=>$openid));
	            $m=pdo_get("mc_members",array("uid"=>$member["uid"]));
	            include $this->template();
	            
	        }else{
	            //已绑定账户
	            $account = $this->model->merch_user_single(array('username' => $user["username"]));
	            $account['hash'] = md5($account['pwd'] . $account['salt']);
	            $session = base64_encode(json_encode($account));
	            $session_key = '__merchmanage_' . $_W['uniacid'] . '_session';
	            isetcookie($session_key, $session, 7200);
	            $status = array();
	            $status['lastvisit'] = TIMESTAMP;
	            $status['lastip'] = CLIENT_IP;
	            pdo_update('ewei_shop_merch_account', $status, array('id' => $account['id']));
	           
	            $resault["openid"]=$openid;
	            $resault["binding"]=1;
	            
 	            header('location: ' . mobileUrl('merchmanage'));
                exit();
	    }
	   
	    
	}
	//微信绑定商户
	public function shopbinding(){
	    header('Access-Control-Allow-Origin:*');
	    global $_W;
	    global $_GPC;
	    $mobile=$_GPC["mobile"];
	    $openid=$_GPC["openid"];
	    //判断是否又该商户
	    $account=pdo_get("ewei_shop_merch_user",array('mobile'=>$mobile));
	    if (empty($account)){
	        show_json(0,"该手机号不存在商户");
	    }
	    if (empty($openid)){
	        show_json(0,"openid不可为空");
	    }
	    if (pdo_update("ewei_shop_merch_user",array("openid"=>$openid),array("mobile"=>$mobile))){
	        pdo_update("ewei_shop_merch_account",array("openid"=>$openid),array("merchid"=>$account["id"]));
	        //获取商户
	        $acc=pdo_get("ewei_shop_merch_account",array("merchid"=>$account["id"]));
	        //已绑定账户
	        $account = $this->model->merch_user_single(array('username' => $acc["username"]));
	        $account['hash'] = md5($account['pwd'] . $account['salt']);
	        $session = base64_encode(json_encode($account));
	        $session_key = '__merchmanage_' . $_W['uniacid'] . '_session';
	        isetcookie($session_key, $session, 7200);
	        $status = array();
	        $status['lastvisit'] = TIMESTAMP;
	        $status['lastip'] = CLIENT_IP;
	        pdo_update('ewei_shop_merch_account', $status, array('id' => $account['id']));
	        show_json(1,"绑定成功");
	    }else{
	        show_json(0,"绑定失败");
	    }
	}
	
	
	//收集站
	public function m(){
	    global $_W;
	    global $_GPC;
	    $params["openid"] =$_W["openid"];
	    $params["fee"] =100;
	    $params["title"]="购买";
	    $params["tid"]=$_GPC["order_sn"];
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
// 	    var_dump($wechat);
	    include $this->template();
	}
	
}


?>