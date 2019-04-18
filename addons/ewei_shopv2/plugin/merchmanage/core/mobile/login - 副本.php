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

		if ($_W['ispost']) {
			
			if (!(empty($backurl))) {
				$backurl = base64_decode(urldecode($backurl));
				$backurl = './index.php?' . $backurl;
			}

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
			
			show_json(1, array('backurl' => $backurl));
		}

		$shopset = $_W['shopset'];
		$logo = tomedia($shopset['shop']['logo']);
		if (is_weixin() || (!(empty($shopset['wap']['open'])) && empty($shopset['wap']['inh5app']))) {
			$goshop = true;
			
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
	//短信消息
	public function send(){
	    header('Access-Control-Allow-Origin:*');
	    global $_W;
	    global $_GPC;
	    $mobile=$_GPC["mobile"];
	    if (!preg_match("/^1[345678]{1}\d{9}$/",$mobile)){
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
	    }
	    exit(json_encode($resault));
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
	    if ( strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')== false ) {
            
	        show_json(0,"请在微信端打开");
	    }else{
	        $result = mc_oauth_userinfo();
	        $openid=$result["openid"];
	        if (empty($openid)){
	            show_json(0,"授权失败");
	        }
	        
	        $user=pdo_get("ewei_shop_merch_account",array('openid'=>$openid));
	        if (empty($user)){
	            //未绑定账户
	            $resault["openid"]=$openid;
	            $resault["binding"]=0;
	            show_json(1,$resault);
	            
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
	            show_json(1,$resault);
// 	            header('location: ' . mobileUrl('merchmanage'));
// 	            exit;
	        }
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
}


?>