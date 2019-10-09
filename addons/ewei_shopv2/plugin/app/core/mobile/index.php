<?php
if (!defined("IN_IA")) {
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
//        $shopset = m("common")->getSysset("shop");
        parent::__construct();
//        $mid = $_GPC['mid'];
//        if (!empty($mid) && !empty($_W["openid"])) {
//            $pid = m('member')->getMember($mid);
//            $iset = pdo_get('ewei_shop_member_getstep', array('bang' => $_W['openid'], 'type' => 1, 'day' => date('Y-m-d'), 'openid' => $pid['openid']));
//            if (!empty($pid) && empty($iset)) {
//                $data = array(
//                    'timestamp' => time(),
//                    'openid' => trim($pid["openid"]),
//                    'day' => date('Y-m-d'),
//                    'uniacid' => $_W['uniacid'],
//                    'step' => $shopset['qiandao'],
//                    'type' => 1,
//                    'bang' => $_W['openid']
//                );
//                pdo_insert('ewei_shop_member_getstep', $data);
//            }
//
//
//        }
       
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
        $openid=$_W["openid"];
        if (empty($_W['openid'])) {
            app_error(AppError::$ParamsError);
        }
        $member = m('member')->getMember($_W['openid']);
        $shopset = m("common")->getSysset("shop");
       // $exchange=exchange($_W['openid']);
      
        if (empty($member['agentlevel'])) {
           // $bushu = 5;
             $subscription_ratio=0.5;
            $exchange=0.5/1500;
            $exchange_step=m("member")->exchange_step($openid);
         //   var_dump($exchange_step);
            $bushu=ceil($exchange_step*1500/0.5);
           
           
        } else {
            $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
           // $bushu = $memberlevel['duihuan'];
           $subscription_ratio=$memberlevel["subscription_ratio"];
           $exchange=$subscription_ratio/1500;
           $exchange_step=m("member")->exchange_step($openid);
           $bushu=ceil($exchange_step*1500/$subscription_ratio);
           //可兑换的步数
//            var_dump($bushu);
        }
     // var_dump($bushu);
        //已兑换的bushu
      //  $jinri = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and type!=:type and status=1 ", array(':today' => $day, ':openid' => $_W['openid'],':type'=>2));
        //获取今日已兑换的卡路里
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        
        $cardtoday=pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where `createtime`>=:beginToday and `createtime`<=:endToday and openid=:openid and credittype=:credittype and (remark like :remark1 or remark like :remark2)",array(":beginToday"=>$beginToday,":endToday"=>$endToday,":credittype"=>"credit1",":openid"=>$openid,":remark1"=>'%步数兑换%',":remark2"=>'%好友助力%'));
       // var_dump($cardtoday);
        if (empty($cardtoday)){
            $jinri=0;
        }else{
            $jinri=$cardtoday*1500/$subscription_ratio;
        }
      // var_dump($jinri);
        
        $step_number=$jinri;
       
        if ($step_number < $bushu) {
    //        $result = pdo_getall('ewei_shop_member_getstep', array('day' => $day, 'openid' => $_W['openid'], 'status' => 0));
              $result=pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day=:day and openid=:openid and status=0 order by step asc",array(":day"=>$day,":openid"=>$_W["openid"]));
        }else{
            $result=pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day=:day and openid=:openid and status=0 and type=2 order by step asc",array(":day"=>$day,":openid"=>$_W["openid"]));
        }
        $r=array();
        $i=0;
        foreach ($result as &$vv) {
            
            //var_dump($vv['step'] / $proportion["value"]);
//             if ($vv["type"]!=2){
//                 $r=$vv['step']*$exchange;
//                 if ($r>0.01){
//                  $vv['currency'] = round($vv['step']*$exchange,2);
//                 }else{
//                 $vv['currency'] = round($r,4);
//                 }
//             }else{
//                 $vv['currency'] =1;
//             }
            
            if ($i<3){
            if ($vv["type"]!=2){
                //步数小于今日步数
                if ($step_number<$bushu){
                    if ($step_number+$vv["step"]>=$bushu){
                        //大于
                        $r[$i]["id"]=$vv["id"];
                        $r[$i]["step"]=$bushu-$step_number;
                        $card1=($bushu-$step_number)*$exchange;
                        if ($card1>0.01){
                        $r[$i]["currency"]=round($card1,2);
                        }else{
                            $r[$i]["currency"]=round($card1,4);
                        }
                        $r[$i]["type"]=$vv["type"];
                        $step_number=$bushu;
                    }else{
                        //小于
                        $r[$i]["id"]=$vv["id"];
                        $r[$i]["step"]=$vv["step"];
                        $card1=$vv["step"]*$exchange;
                        if ($card1>0.01){
                            $r[$i]["currency"]=round($card1,2);
                        }else{
                            $r[$i]["currency"]=round($card1,4);
                        }
                        $step_number=$step_number+$vv["step"];
                        $r[$i]["type"]=$vv["type"];
                    }
                    $i=$i+1;
                }
                   
            }else{
                $r[$i]["id"]=$vv["id"];
                $r[$i]["step"]=$vv["step"];
                
                $r[$i]["currency"]=1;
                $r[$i]["type"]=$vv["type"];
                $i=$i+1; 
            }
            
            }
            
        }
        unset($vv);
        
        app_json(array('result' => $r, 'url' => referer()));
        
        
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
        $bushu = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where  `day`=:today and openid=:openid and type!=:type", array(':today' => $day, ':openid' => $openid,':type'=>2));
        if (empty($bushu)){
            $member['todaystep'] =0;
        }else{
        $member['todaystep'] = $bushu;
        }
        $yaoqing = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where  `day`=:today and openid=:openid ", array(':today' => $day, ':openid' => $openid));
        if(empty($yaoqing)){
            $yaoqing=0;
        }
        $member['yaoqing']=$yaoqing;
       
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
        //获取步数
        $now_setp=$_GPC["step"];
        
        $cs["step"]=$_GPC["step"];
        $cs["step_id"]=$_GPC["id"];
        $cs["create_time"]=time();
        $cs["openid"]=$_W["openid"];
        pdo_insert("ewei_shop_member_getsteplog",$cs);
        $day = date('Y-m-d');
        $member = m('member')->getMember($_W['openid']);
        $shopset = m("common")->getSysset("shop");
        //获取当前用户卡路里兑换比例
    
        if (empty($_GPC["id"])){
            app_error(-1,"id未获取");
        }else{
            
            if (empty($member['agentlevel'])) {
               // $bushu = 5;
                $subscription_ratio=0.5;
                $exchange=0.5/1500;
                $exchange_step=m("member")->exchange_step($openid);
                $bushu=ceil($exchange_step*1500/0.5);
            } else {
                $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
              //  $bushu = $memberlevel['duihuan'];
                $subscription_ratio=$memberlevel["subscription_ratio"];
                //兑换比例
                $exchange=$subscription_ratio/1500;
                $exchange_step=m("member")->exchange_step($openid);
                //今日可兑换步数
                $bushu=ceil($exchange_step*1500/$subscription_ratio);
            }

            $step = pdo_get('ewei_shop_member_getstep', array('id' => $_GPC['id']));
            //今日步数
//             $jinri = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and type!=:type and status=1 ", array(':today' => $day, ':openid' => $openid,':type'=>2));
            
//             if (empty($jinri)){
//                 $jinri=0;
//             }
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            
            $cardtoday=pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where `createtime`>=:beginToday and `createtime`<=:endToday and openid=:openid and credittype=:credittype and (remark like :remark1 or remark like :remark2)",array(":beginToday"=>$beginToday,":endToday"=>$endToday,":credittype"=>"credit1",":openid"=>$openid,":remark1"=>'%步数兑换%',":remark2"=>'%好友助力%'));
            
            if (empty($cardtoday)){
                $jinri=0;
            }else{
                $jinri=$cardtoday*1500/$subscription_ratio;
            }
            
            
            if ($step["type"]!=2){
                
            if ($jinri>=$bushu) {
                app_error(-2,"您每天最多可兑换".$bushu."卡路里");
            }
            
            }
            
            if (!empty($step) && $step['status'] == 0) {
                
                
                
                if ($step["type"]!=2){
                    //不是签到
                     //添加传入step字段
                if (!empty($now_setp)){
                    
                    if (($jinri + $now_setp) >$bushu) {
                        
                        $keduihuan = ($bushu-$jinri)*$exchange;
                        
                    }else{
                        
                        $keduihuan =$now_setp*$exchange;
                        
                    }
                    
                    
                }else{
                    
                if (($jinri + $step["step"]) > $bushu) {
                    
                    $keduihuan = ($bushu-$jinri)*$exchange;
                    
                }else{
                    
                    $keduihuan =$step["step"]*$exchange;
                    
                }
                
                }
                
                }else{
                    //签到 1卡路里
                    $keduihuan=1;
                }

                if ($step["type"]==0){
                    m('member')->setCredit($openid, 'credit1', $keduihuan, "步数兑换");
                }elseif ($step["type"]==1){
                    m('member')->setCredit($openid, 'credit1', $keduihuan, "好友助力");
                }elseif ($step["type"]==2) {
                    m('member')->setCredit($openid, 'credit1', $keduihuan, "签到获取");
                }
                pdo_update('ewei_shop_member_getstep', array('status' => 1), array('id' => $step['id']));
                app_error(0,$keduihuan);
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
//             wxmessage($openid, 1);
            app_error(AppError::$ParamsError, '请勿重复签到');
        }else{
            //昨天日期
            $yesterday=date("Y-m-d",strtotime("-1 day"));
            if ($member["qiandao"]==$yesterday){
                //连签天数<7
                if ($member["sign_days"]!=7){

//                     if ($member["sign_days"]>0){
//                     $step=[1+2*($member["sign_days"]-1)]*$shopset['qiandao'];
//                     }else{
//                         $step=$shopset['qiandao'];
//                     }
                    $step=$shopset['qiandao'];
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
//                     $sign_days=1;
                    $sign_days=$member["sign_days"]+1;
                }
                $update = array('qiandao' => $day,'sign_days'=>$sign_days);
// 因为setcredit里面加的有数值 //如果是年卡会员  则折扣宝加10  否则是原来的credit3
//                $update['credit3'] = $member['is_open'] == 1 ? bcadd($member['credit3'],10,2) : $member['credit3'] ;
                pdo_insert('ewei_shop_member_getstep', $data);
                pdo_update('ewei_shop_member', $update , array('openid' => $member['openid']));
                //签到消息提醒
                wxmessage($openid, $sign_days,'1卡路里');
                if($member['is_open'] == 1){
                    m('member')->setCredit($openid,'credit3',10,"年卡会员每日10折扣宝");
                    wxmessage($openid,date('Y-m-d',time()),'年卡会员每日登陆领取10折扣宝');
                }
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
                $update = [
                    'qiandao' => $day,
                    'sign_days'=>$sign_days
                ];
                //因为setcredit里面加的有数值
                //$update['credit3'] = $member['is_open'] == 1 ? bcadd($member['credit3'],10,2) : $member['credit3'] ;
                //如果过期时间 小于 当前时间  并且  is_open == 1  然后更改is_open
                if(!empty($member['expire_time']) && $member['expire_time'] < time() && $member['is_open'] == 1){
                    $update['is_open'] = 2;
                }
                pdo_update('ewei_shop_member', $update, array('openid' => $member['openid']));
                wxmessage($openid, $sign_days,'1卡路里');
                //如果是年卡会员   则给会员发送小程序消息
                if($member['is_open'] == 1){
                    m('member')->setCredit($openid,'credit3',10,"年卡会员每日10折扣宝");
                    wxmessage($openid,$sign_days,"年卡会员每日登陆领取10折扣宝");
                }
                app_error(0,"签到成功,获取步数".$step);
            }
            
        }
        
    }
    //刷新步数
    public function refresh_step(){
        global $_GPC;
        global $_W;
        $openid = trim($_GPC["openid"]);
        
        if (empty($openid)) {
            app_error(AppError::$ParamsError);
        }
        //兑换比例
       // $exchange=exchange($openid);
       
        $member=pdo_get('ewei_shop_member',array('openid'=>$openid));
        if ($member["agentlevel"]==0){
          //  $step_count=floor(5/$exchange);
            $exchange=0.5/1500;
            $exchange_step=m("member")->exchange_step($openid);
            $step_count=ceil($exchange_step/$exchange);
        }else{
           $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
           $exchange=$level["subscription_ratio"]/1500;
           $exchange_step=m("member")->exchange_step($openid);
           $step_count=ceil($exchange_step/$exchange);
        }
        //获取用户今天总步数
        $day=date("Y-m-d",time());
        $step_today = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and type!=:type", array(':today' => $day, ':openid' => $openid,':type'=>2));
        if (empty($step_today)){
            $step_today=0;
        }
        $step=$step_count-$step_today;
        if ($step<0){
            $step=0;
        }
        $m["openid"]=$openid;
        $m["step"]=$step;
        $m["step_count"]=$step_count;
        $m["step_today"]=$step_today; 
        //获取会员累计的卡路里
//        $count= pdo_fetchcolumn("select sum(num) from " . tablename('mc_credits_record') . " where `credittype`=:credittype and  'num'>:num", array(':credittype' =>"credit1", ':num' =>0,));
//        $count=3+round($count/10000,2);
//        $m["count"]=$count;
        $m["count"]='1268966';
        show_json(1, $m);
    }


    /**
     * 助力海报
     */
    public function share_help()
    {
        global $_W;
        global $_GPC;
        $idarray=pdo_fetchall("select id from ".tablename("ewei_shop_share_help"));
        $k=array_rand($idarray);
        $id=$idarray[$k]["id"];
        $data = pdo_get('ewei_shop_share_help',['id'=>$id],['title','thumb','image']);
        $data['thumb'] = tomedia($data['thumb']);
        $data['image'] = tomedia($data['image']);
        !empty($data)?show_json(1,$data):show_json(0);
    }


    /**
     * 测试
     */
    public function ceshi(){
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $ids = [44,89,90,4164,41683];
        $id = pdo_getcolumn('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],'id');
        //if(in_array($id,$ids)){
            show_json(1);
        //}else{
        //    show_json(0);
        //}
    }
}

//签到消息
function wxmessage($openid,$sign_days,$remark){
    //获取用户信息
    $member = m("member")->getMember($openid);
    $postdata=array(
        'keyword1'=>array(
            'value'=>$member["nickname"],
            'color' => '#ff510'
        ),
        'keyword2'=>array(
            'value'=>$remark,
            'color' => '#ff510'
        ),
        'keyword3'=>array(
            'value'=>$sign_days,
            'color' => '#ff510'
        ),
        'keyword4'=>array(
            'value'=>date("Y-m-d",time()),
            'color' => '#ff510'
        )

    );
   p("app")->mysendNotice($openid, $postdata, "", "BJtaHWXzIvH3j6NfAO56TPnULBeZyYJhX2h9XoYSs6g");
    return true;
}

?>