<?php if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/error_code.php");
require(EWEI_SHOPV2_PLUGIN . "app/core/wxapp/wxBizDataCrypt.php");

class Wxapp_EweiShopV2Page extends Page
{
    protected $appid = NULL;
    protected $appsecret = NULL;
    
    public function __construct()
    {
        $data = m("common")->getSysset("app");
      //  var_dump($data);
        $this->appid = $data["appid"];
        $this->appsecret = $data["secret"];
    }
    
    public function login()
    {
        global $_GPC;
        global $_W;
        $code = trim($_GPC["code"]);
        if (empty($code)) {
            app_error(AppError::$ParamsError,"login");
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $this->appid . "&secret=" . $this->appsecret . "&js_code=" . $code . "&grant_type=authorization_code";
        load()->func("communication");
        $resp = ihttp_request($url);
        if (is_error($resp)) {
            app_error(AppError::$SystemError, $resp["message"]);
        }
        $arr = @json_decode($resp["content"], true);
        $arr["isclose"] = $_W["shopset"]["app"]["isclose"];
        if (!empty($_W["shopset"]["app"]["isclose"])) {
            $arr["closetext"] = $_W["shopset"]["app"]["closetext"];
        }
        if (!is_array($arr) || !isset($arr["openid"])) {
            app_error(AppError::$WxAppLoginError);
        }
        //判断是否是第一次登录
        $openid="sns_wa_".$arr["openid"];
        $member=pdo_get('ewei_shop_member',array('openid'=>$openid));
        if ($member){
            $arr["nickname"]=$member["nickname"];
            $arr["mobile"]=$member["mobile"];
            $arr["avatar"]=$member["avatar"];
            $arr["id"]=$member["id"];
            $arr["login"]=$member["is_login"];
           $arr["agentid"]=$member["agentid"];
           $arr["is_own"]=$member["is_own"];
           $arr["agentlevel"]=$member["agentlevel"];
           $arr['is_open'] = $member['is_open'];
            //判断用户是否是商家
            $merchUser=pdo_get('ewei_shop_merch_user',array('member_id'=>$member['id']));
            if($merchUser){
                $arr['merchInfo'] = $merchUser;
            }else{
                $arr['merchInfo'] = false;
            }
//             if($arr['login'] == 0){
//                 pdo_update('ewei_shop_member',['credit3'=>100],['openid'=>$member['openid']]);
//             }
        }else{
            //第一次登录
            $arr["login"]=0;
        }

        app_json($arr, $arr["openid"]);
    }
    
    //登陆成功--获取微信步数 
    public function urundata()
    {
        global $_GPC;
        global $_W;
        
        $encryptedData = trim($_GPC["res"]['encryptedData']);
        $iv = trim($_GPC['res']["iv"]);
        $sessionKey = trim($_GPC['res']["sessionKey"]);
        if (empty($encryptedData) || empty($iv) || empty(trim($_GPC["openid"]))) {
            app_error(AppError::$ParamsError,"urundata");
        }
        $appset = m("common")->getSysset("app");
        $pc = new WXBizDataCrypt($appset['appid'], $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $data = json_decode($data, true);
            foreach ($data['stepInfoList'] as $vv) {
                $set = pdo_get('ewei_shop_member_step', array('timestamp' => $vv['timestamp'], 'openid' => trim($_GPC["openid"])));
                //set表中不存在该时间戳的步数
                if (empty($set)) {
                    $array = array(
                        'timestamp' => $vv['timestamp'],
                        'openid' => trim($_GPC["openid"]),
                        'day' => date('Y-m-d', $vv['timestamp']),
                        'uniacid' => $_W['uniacid'],
                        'step' => $vv['step']
                    );
                    pdo_insert('ewei_shop_member_step', $array);
                    //判断是否是当日
                    if (date('Y-m-d', $vv['timestamp'])==date('Y-m-d')&&$vv['step']>0) {
                        if ($vv["step"]>=1000){
                            spin_step(trim($_GPC["openid"]),$vv["step"],$vv['timestamp'],$_W['uniacid']);
                        }else{
                        $data = array(
                            'timestamp' => time(),
                            'openid' => trim($_GPC["openid"]),
                            'day' => date('Y-m-d', $vv['timestamp']),
                            'uniacid' => $_W['uniacid'],
                            'step' => $vv['step']
                        );
                        pdo_insert('ewei_shop_member_getstep', $data);
                        }
                    }
                    
                }else if (date('Y-m-d', $vv['timestamp'])==date('Y-m-d')){
                    
                    if ($vv['step']>$set['step']){
                        //获取数据库中未兑换步数总数
                        $count=pdo_fetchcolumn("select count(*) from ".tablename('ewei_shop_member_getstep')."where openid=:openid and day=:day and type=:type and status=:status",array(':openid'=>trim($_GPC["openid"]),':day'=>date('Y-m-d', $vv['timestamp']),':type'=>0,':status'=>0));
                        $current_step=$vv["step"]-$set["step"];
                        if ($current_step>=1000){
//                         if ($count<2&&$current_step>=1000){
                            spin_step(trim($_GPC["openid"]),$current_step,$vv['timestamp'],$_W['uniacid']);
                        }else{
                        $data = array(
                            'timestamp' => time(),
                            'openid' => trim($_GPC["openid"]),
                            'day' => date('Y-m-d', $vv['timestamp']),
                            'uniacid' => $_W['uniacid'],
                            'step' => $vv['step']-$set['step']
                        );
                        pdo_insert('ewei_shop_member_getstep', $data);
                        }
                        
                    }
                    
                    pdo_update('ewei_shop_member_step',array('step'=>$vv['step']), array( 'timestamp' => $vv['timestamp'], 'openid' => trim($_GPC["openid"])));
                    
                    
                }
            }
        }
        
        show_json();
    }
    
   
    public function auth()
    {
        $path = IA_ROOT . "/addons/ewei_shopv2/data/poster_wxapp/sport/" ;
      
        global $_GPC;
        global $_W;
        $encryptedData = trim($_GPC["data"]);
        $iv = trim($_GPC["iv"]);
        $sessionKey = trim($_GPC["sessionKey"]);
        if (empty($encryptedData) || empty($iv)) {
            app_error(AppError::$ParamsError,"auth");
        }
        $pc = new WXBizDataCrypt($this->appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        if ($errCode == 0) {
            $data = json_decode($data, true);

            $member = m("member")->getMember("sns_wa_" . $data["openId"]);
            if (empty($member)) {
                $member = array("uniacid" => $_W["uniacid"],"uid" => 0, "openid" => "sns_wa_" . $data["openId"], "nickname" => (!empty($data["nickName"]) ? $data["nickName"] : ""), "avatar" => (!empty($data["avatarUrl"]) ? $data["avatarUrl"] : ""), "gender" => (!empty($data["gender"]) ? $data["gender"] : "-1"), "openid_wa" => $data["openId"], "comefrom" => "sns_wa", "unionid"=>$data["unionId"],"createtime" => time(), "status" => 0);
                pdo_insert("ewei_shop_member", $member);
            
                $id = pdo_insertid();
                $data["id"] = $id;
                $data["uniacid"] = $_W["uniacid"];
                if (method_exists(m("member"), "memberRadisCountDelete")) {
                    m("member")->memberRadisCountDelete();
                }
            } else {
                //新用户奖励贡献值
//                 if ($member["agentid"]!=0&&empty($member["nickname"])){
//                     $agent=pdo_get("ewei_shop_member",array("id"=>$member["agentid"]));
//                     if ($agent){
//                     m('member')->setCredit($agent["openid"], 'credit4', 1, "推荐新用户");
//                     }
//                 }
                $updateData = array("nickname" => (!empty($data["nickName"]) ? $data["nickName"] : ""), "avatar" => (!empty($data["avatarUrl"]) ? $data["avatarUrl"] : ""), "gender" => (!empty($data["gender"]) ? $data["gender"] : "-1"),"unionid"=> (!empty($data["unionId"]) ? $data["unionId"] : ""));
                pdo_update("ewei_shop_member", $updateData, array("id" => $member["id"], "uniacid" => $member["uniacid"]));
                $data["id"] = $member["id"];
                $data["uniacid"] = $member["uniacid"];
                $data["isblack"] = $member["isblack"];
                
            }
            if (p("commission")) {
                p("commission")->checkAgent($member["openid"]);
            }
            //判断用户是否是商家
            $merchUser=pdo_get('ewei_shop_merch_user',array('member_id'=>$data['id']));
            if($merchUser){
                $data['merchInfo'] = $merchUser;
            }else{
                $data['merchInfo'] = false;
            }
            $data["agentid"]=$member["agentid"];
            $data['is_own'] = $member['is_own'];
            $data['agentlevel'] = $member['agentlevel'];
            $data['is_open'] = $member['is_open'];
            app_json($data, $data["openId"]);
        }
        app_error(AppError::$WxAppError, "登录错误, 错误代码: " . $errCode);
    }
    
    public function check()
    {
        global $_GPC;
        global $_W;
        $openid = trim($_GPC["openid"]);
        if (empty($openid)) {
            app_error(AppError::$ParamsError,"check");
        }
        $openid=str_replace("sns_wa_", '', $openid);

        $wxopenid = "sns_wa_" . $openid;
        
        $member = m("member")->getMember($wxopenid);
       
        if (empty($member)) {
            $member = array("uniacid" => $_W["uniacid"],"uid" => 0, "openid" => $wxopenid, "openid_wa" => $openid, "comefrom" => "sns_wa", "createtime" => time(), "status" => 0);
            pdo_insert("ewei_shop_member", $member);

            $member["id"] = pdo_insertid();
            if (method_exists(m("member"), "memberRadisCountDelete")) {
                m("member")->memberRadisCountDelete();
            }
        }
        $merchUser=pdo_get('ewei_shop_merch_user',array('member_id'=>$member['id']));
        if($merchUser){
            $merchInfo = $merchUser;
        }else{
            $merchInfo = false;
        }
        app_json(array("agentid"=>$member['agentid'],"merchInfo"=>$merchInfo,"uniacid" => $member["uniacid"], "openid" => $member["openid"], "id" => $member["id"], "nickname" => $member["nickname"], "avatarUrl" => tomedia($member["avatar"]), "isblack" => $member["isblack"],'is_own'=>$member['is_own'],'is_open'=>$member['is_open'],'agentlevel'=>$member['agentlevel']), $member["openid"]);
    }
    
    /**
     * 判断是否是第一次登陆
     */
    public function is_login()
    {
        global $_W;
        global $_GPC;
        $openid = "sns_wa_".$_GPC['openid'];
        $uniacid = $_W['uniacid'];
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        $data['is_login'] = empty($member) ? 0 : 1;
        show_json(1,$data);
    }
}

    function app_error($errcode = 0, $message = "")
    {
        exit(json_encode(array("error" => $errcode, "message" => (empty($message) ? AppError::getError($errcode) : $message))));
    }

    function app_json($result = NULL, $openid)
    {
        global $_GPC;
        global $_W;
        $ret = array();
        if (!is_array($result)) {
            $result = array();
        }
        $ret["error"] = 0;
        $key = time() . "@" . $openid;
        $auth = array("authkey" => base64_encode(authcode($key, "ENCODE", "ewei_shopv2_wxapp")));
        m("cache")->set($auth["authkey"], 1);
        exit(json_encode(array_merge($ret, $auth, $result)));
    }
     //步数分拆
    function spin_step($openid="",$step=0,$timestep="",$uniacid=""){
             $step1=intval(rand(50,70)*$step/100);
             $step2=$step-$step1;
             if ($step1!=0){
                 $data = array(
                     'timestamp' => time(),
                     'openid' => trim($openid),
                     'day' => date('Y-m-d', $timestep),
                     'uniacid' => $uniacid,
                     'step' => $step1
                 );
                 pdo_insert('ewei_shop_member_getstep', $data);
             }
             if ($step2!=0){
                 $data = array(
                     'timestamp' => time(),
                     'openid' => trim($openid),
                     'day' => date('Y-m-d', $timestep),
                     'uniacid' => $uniacid,
                     'step' => $step2
                 );
                 pdo_insert('ewei_shop_member_getstep', $data);
             }
    }
 
?>