<?php if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Index_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
        exit("Access Denied");
    }

    public function __construct()
    {
        global $_GPC;
        global $_W;
        $shopset = m("common")->getSysset("shop");
        parent::__construct();
        $mid = $_GPC['mid'];
        if (!empty($mid) && !empty($_W["openid"])) {
            $pid = m('member')->getMember($mid);
            $iset = pdo_get('ewei_shop_member_getstep', array('bang' => $_W['openid'], 'type' => 1, 'day' => date('Y-m-d'), 'openid' => $pid['openid']));
            if (!empty($pid) && empty($iset)) {
                $data = array(
                    'timestamp' => time(),
                    'openid' => trim($pid["openid"]),
                    'day' => date('Y-m-d'),
                    'uniacid' => $_W['uniacid'],
                    'step' => $shopset['qiandao'],
                    'type' => 1,
                    'bang' => $_W['openid']
                );
                pdo_insert('ewei_shop_member_getstep', $data);
            }


        }
    }

    public function cacheset()
    {
        global $_GPC;
        global $_W;
        $localversion = 1;
        $version = intval($_GPC["version"]);
        $noset = intval($_GPC["noset"]);
        if (empty($version) || $version < $localversion) {
            $arr = array("update" => 1, "data" => array("version" => $localversion, "areas" => $this->getareas()));
        } else {
            $arr = array("update" => 0);
        }
        if (empty($noset)) {
            $arr["sysset"] = array("shopname" => $_W["shopset"]["shop"]["name"], "shoplogo" => $_W["shopset"]["shop"]["logo"], "description" => $_W["shopset"]["shop"]["description"], "share" => $_W["shopset"]["share"], "texts" => array("credit" => $_W["shopset"]["trade"]["credittext"], "money" => $_W["shopset"]["trade"]["moneytext"]), "isclose" => $_W["shopset"]["app"]["isclose"]);
            $arr["sysset"]["share"]["logo"] = tomedia($arr["sysset"]["share"]["logo"]);
            $arr["sysset"]["share"]["icon"] = tomedia($arr["sysset"]["share"]["icon"]);
            $arr["sysset"]["share"]["followqrcode"] = tomedia($arr["sysset"]["share"]["followqrcode"]);
            if (!empty($_W["shopset"]["app"]["isclose"])) {
                $arr["sysset"]["closetext"] = $_W["shopset"]["app"]["closetext"];
            }
        }
        app_json($arr);
    }

    public function getareas()
    {
        global $_W;
        $set = m("util")->get_area_config_set();
        $path = EWEI_SHOPV2_PATH . "static/js/dist/area/Area.xml";
        $path_full = EWEI_SHOPV2_STATIC . "js/dist/area/Area.xml";
        if (!empty($set["new_area"])) {
            $path = EWEI_SHOPV2_PATH . "static/js/dist/area/AreaNew.xml";
            $path_full = EWEI_SHOPV2_STATIC . "js/dist/area/AreaNew.xml";
        }
        $xml = @file_get_contents($path);
        if (empty($xml)) {
            load()->func("communication");
            $getContents = ihttp_request($path_full);
            $xml = $getContents["content"];
        }
        $array = xml2array($xml);
        $newArr = array();
        if (is_array($array["province"])) {
            foreach ($array["province"] as $i => $v) {
                if (0 < $i) {
                    $province = array("name" => $v["@attributes"]["name"], "code" => $v["@attributes"]["code"], "city" => array());
                    if (is_array($v["city"])) {
                        if (!isset($v["city"][0])) {
                            $v["city"] = array($v["city"]);
                        }
                        foreach ($v["city"] as $ii => $vv) {
                            $city = array("name" => $vv["@attributes"]["name"], "code" => $vv["@attributes"]["code"], "area" => array());
                            if (is_array($vv["county"])) {
                                if (!isset($vv["county"][0])) {
                                    $vv["county"] = array($vv["county"]);
                                }
                                foreach ($vv["county"] as $iii => $vvv) {
                                    $area = array("name" => $vvv["@attributes"]["name"], "code" => $vvv["@attributes"]["code"]);
                                    $city["area"][] = $area;
                                }
                            }
                            $province["city"][] = $city;
                        }
                    }
                    $newArr[] = $province;
                }
            }
        }
        return $newArr;
    }

    public function getstreet()
    {
        global $_GPC;
        $citycode = intval($_GPC["city"]);
        $areacode = intval($_GPC["area"]);
        if (empty($citycode) || empty($areacode)) {
            app_error(AppError::$ParamsError, "城市代码或区代码为空");
        }
        $newArr = array();
        if (!empty($citycode) && !empty($areacode)) {
            $city2 = substr($citycode, 0, 2);
            $path = EWEI_SHOPV2_STATIC . "js/dist/area/list/" . $city2 . "/" . $citycode . ".xml";
            $data = $this->curl_get($path);
            if (empty($data)) {
                $data = file_get_contents($path);
            }
            $array = xml2array($data);
            if (is_array($array["city"]["county"])) {
                foreach ($array["city"]["county"] as $k => $kv) {
                    if (!is_numeric($k)) {
                        $citys[] = $array["city"]["county"];
                    } else {
                        $citys = $array["city"]["county"];
                    }
                }
                foreach ($citys as $i => $city) {
                    if ($city["@attributes"]["code"] == $areacode) {
                        if (is_array($city["street"])) {
                            foreach ($city["street"] as $ii => $street) {
                                $newArr[] = array("name" => $street["@attributes"]["name"], "code" => $street["@attributes"]["code"]);
                            }
                        }
                        break;
                    }
                }
            }
        }
        app_json(array("street" => $newArr));
    }

    public function black()
    {
        global $_GPC;
        global $_W;
        if (!empty($_W["openid"])) {
            $member = m("member")->getMember($_W["openid"]);
            if ($member["isblack"]) {
                $isblack = true;
            } else {
                $isblack = false;
            }
        } else {
            $isblack = false;
        }
        app_json(array("isblack" => $isblack));
    }

    public function curl_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    public function info()
    {
        //一个月内未推广同级用户,兑换值衰退30%
        exit('{"info":{"currency_name":"卡路里","home_background_image":"https://paoku.xingrunshidai.com/img/3.jpg","ui":{"home_background_image":"https://paoku.xingrunshidai.com/img/3.jpg","home_suspension_coin_img":"https://paoku.xingrunshidai.com/img/2.png","home_suspension_coin_color":"#554545","home_suspension_coin_describe_color":"#554545","home_my_coin_image":"https://paoku.xingrunshidai.com/img/1.png","home_my_coin_color":"#fff","home_today_step_color":"#666666","home_today_step_num_color":"#434343","home_share_start_color":"#26BCC5","home_share_end_color":"#1DD49E","home_share_color":"#fff","home_sigin_color":"#fff","home_sigin_start_color":"#26bcc5","home_sigin_end_color":"#1dd49e","left":"https://paoku.xingrunshidai.com/img/left.png","right":"https://paoku.xingrunshidai.com/img/right.png","home_understand_coin_color":"#000"}},"status":1}');
    }
   //获取今日未兑换的步数列表
    public function bushu()
    {
        global $_GPC;
        global $_W;

        $day = date('Y-m-d');
        $result = array();

        if (empty($_W['openid'])) {
            app_error(AppError::$ParamsError);
        }
        $member = m('member')->getMember($_W['openid']);
        $shopset = m("common")->getSysset("shop");

        if (empty($member['agentlevel'])) {
            $bushu = 5;
        } else {
            $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
            $bushu = $memberlevel['duihuan'];
        }

       
        $jinri = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and status=1 ", array(':today' => $day, ':openid' => $_W['openid']));

        if ($jinri / 1000 < $bushu) {
            $result = pdo_getall('ewei_shop_member_getstep', array('day' => $day, 'openid' => $_W['openid'], 'status' => 0));
        }

        foreach ($result as &$vv) {
            $vv['currency'] = $vv['step'] / 1000;
        }
        unset($vv);

        app_json(array('result' => $result, 'url' => referer()));


        //  exit('{"info":{"author":{"is_author":1},"currency":[{"id":"2","currency":"2.00","member_id":"1","uniacid":"4","today":"1546358400","source":"3","status":"1","created":"1546394205","msg":"签到奖励"},{"id":"2","currency":"2.00","member_id":"1","uniacid":"4","today":"1546358400","source":"3","status":"1","created":"1546394205","msg":"签到奖励"},{"id":"2","currency":"2.00","member_id":"1","uniacid":"4","today":"1546358400","source":"3","status":"1","created":"1546394205","msg":"签到奖励"}],"my_currency":"4.00","toady":8000},"status":1}');
    }

    public function urundata()
    {
        global $_GPC;
        global $_W;


        $encryptedData = trim($_GPC["res"]['encryptedData']);
        $iv = trim($_GPC['res']["iv"]);
        $sessionKey = trim($_GPC['res']["sessionKey"]);
        if (empty($encryptedData) || empty($iv)) {
            app_error(AppError::$ParamsError);
        }
        $appset = m("common")->getSysset("app");
        $pc = new WXBizDataCrypt($appset['appid'], $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        var_dump($errCode);
        exit;
    }
    //首页--获取用户总卡路里  今日步数
    public function userinfo()
    {
        global $_GPC;
        global $_W;
        $openid = $_W['openid'];
        if (empty($openid)) {
            app_error(AppError::$ParamsError);
        }
        $shopset = m("common")->getSysset("shop");
        $member = m('member')->getMember($openid);
        $member = array('credit1' => $member['credit1']);
        $day = date('Y-m-d');
        $bushu = pdo_get('ewei_shop_member_step', array('day' => $day, 'openid' => $openid));
        $member['todaystep'] = $bushu['step'] ? $bushu['step'] : 0;

        $yaoqing = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where  `day`=:today and openid=:openid ", array(':today' => $day, ':openid' => $openid));
        $member['yaoqing']=$yaoqing;
        $member['todaystep'] = $yaoqing;
        show_json(1, $member);

    }
  //步数兑换卡路里 
    public function getkll()
    {
        global $_GPC;
        global $_W;
        $openid = $_W['openid'];
        if (empty($openid)) {
            app_error(AppError::$ParamsError, '系统错误');
        }
        $day = date('Y-m-d');
        $member = m('member')->getMember($_W['openid']);
        $shopset = m("common")->getSysset("shop");
        if (empty($_GPC["id"])){
            app_error(-1,"id未获取");
        }else{
            if (empty($member['agentlevel'])) {
                $bushu = 5;
            } else {
                $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
                $bushu = $memberlevel['duihuan'];
            }

            $step = pdo_get('ewei_shop_member_getstep', array('day' => $day, 'openid' => trim($_GPC["openid"]), 'id' => $_GPC['id']));

            $jinri = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and status=1 ", array(':today' => $day, ':openid' => $openid));

            $proportion=pdo_get('ewei_setting',array('type_id'=>$member['agentlevel'],'type'=>'level'));
            
            $level_pro=$proportion["value"];
            if ($jinri / $level_pro > $bushu) {
                app_error(-2,"您每天最多可兑换".$bushu."卡路里");
            }
            if (!empty($step) && $step['status'] == 0) {
                if ($member['agentlevel']==0){
                    $keduihuan = $step['step'] / $level_pro;
                }else{
                    $keduihuan = ($step["step"]/$level_pro)*$memberlevel["subscription_ratio"];
                }
                if (($jinri / 1000 + $keduihuan) > $bushu) {
                    $keduihuan = $bushu - $jinri / $level_pro;
                }
                m('member')->setCredit($openid, 'credit1', $keduihuan, $day . "步数兑换");
                pdo_update('ewei_shop_member_getstep', array('status' => 1), array('id' => $step['id']));
            }
            app_error(0,"兑换成功");
        }

        app_json();

    }

    //签到
    public function sign_in(){
        global $_GPC;
        global $_W;
        $openid = trim($_W["openid"]);
       
        if (empty($openid)) {
            app_error(AppError::$ParamsError);
        }
        //获取用户信息
        $member = m("member")->getMember($openid);
       // var_dump($member);die;
        $day=date("Y-m-d",time());
        $shopset = m("common")->getSysset("shop");
        
        if ($member["qiandao"]==$day){
           
            app_error(AppError::$ParamsError, '请勿重复签到');
        }else{
            //昨天日期
            $yesterday=date("Y-m-d",strtotime("-1 day"));
            if ($member["qiandao"]==$yesterday){
                //连签天数<7
                if ($member["sign_days"]!=7){
                    if ($member["sign_days"]>0){
                    $step=[1+2*($member["sign_days"]-1)]*$shopset['qiandao'];
                    }else{
                        $step=$shopset['qiandao'];
                    }
                $data = array(
                    'timestamp' => time(),
                    'openid' => trim($_W["openid"]),
                    'day' => date('Y-m-d'),
                    'uniacid' => $_W['uniacid'],
                    'step' => $step,
                    'type' => 2
                );
                $sign_days=$member["sign_days"]+1;
                
                }else{
                    $step=$shopset['qiandao'];
                    $data = array(
                        'timestamp' => time(),
                        'openid' => trim($_W["openid"]),
                        'day' => date('Y-m-d'),
                        'uniacid' => $_W['uniacid'],
                        'step' => $step,
                        'type' => 2
                    );
                    $sign_days=1;
                }
                pdo_insert('ewei_shop_member_getstep', $data);
                pdo_update('ewei_shop_member', array('qiandao' => $day,'sign_days'=>$sign_days), array('openid' => $member['openid']));
                app_error(0,"签到成功,获取步数".$step);
            }else{
                
                $step=$shopset['qiandao'];
              //  var_dump($step);
                $data = array(
                    'timestamp' => time(),
                    'openid' => trim($_W["openid"]),
                    'day' => date('Y-m-d'),
                    'uniacid' => $_W['uniacid'],
                    'step' => $step,
                    'type' => 2
                );
                $sign_days=1;
                pdo_insert('ewei_shop_member_getstep', $data);
                pdo_update('ewei_shop_member', array('qiandao' => $day,'sign_days'=>$sign_days), array('openid' => $member['openid']));
                app_error(0,"签到成功,获取步数".$step);
            }
            
        }
    
    }
    
}

?>