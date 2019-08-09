<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

//fbb
class Devote_EweiShopV2Page extends AppMobilePage{
    //返回用户手机号以及微信
    public function msg(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member = m('member')->getMember($openid);
        if (empty($member)){
            app_error(1,"openid不正确");
        }else{
            $res["weixin"]=$member["weixin"];
            $res["mobile"]=$member["mobile"];
            $res["credit4"]=$member["credit4"];
            if (empty($member["weixin"])||empty($member["mobile"])){
                $res["bind"]=0;
            }else{
                $res["bind"]=1;
            }
            //折扣宝提现金额
            $res["tixian"]=pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where openid=:openid and credittype=:credittype and remark like :remark",array(":openid"=>$openid,":credittype"=>"credit3",":remark"=>'折扣宝提现%'));
            if (!$res["tixian"]){
                $res["tixian"]=0;
            }
            if ($res["tixian"]<0){
                $res["tixian"]=abs($res["tixian"]);
            }
        }
        app_error(0,$res);
    }
    //绑定微信号
    public function wx(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member = m('member')->getMember($openid);
        if (empty($member)){
            app_error(1,"openid不正确");
        }
        $weixin=$_GPC["weixin"];
        if (empty($weixin)){
            app_error(1,"微信号不可为空");
        }
        if (pdo_update("ewei_shop_member",array("weixin"=>$weixin),array("openid"=>$openid))){
            app_error(0,"成功");
        }else{
            app_error(1,"失败");
        }
        
    }
    //贡献值
    public function detail(){
        global $_W;
        global $_GPC;
        $detail=pdo_get("ewei_shop_member_devote",array("id"=>1));
        app_error(0,$detail);
    }

    /**
     * 折扣宝提现
     */
    public function withdrawal(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member = m('member')->getMember($openid);
        if (empty($member)){
            app_error(1,"openid不正确");
        }
        $money=$_GPC["money"];
        //防止折扣宝提现重复
        $redis = redis();
        if($redis->get($openid.$money.'rebate_withdraw')){
            app_error(1,"您的".$money."提现已提交，为防止重复操作,请1分钟后谨慎操作");
        }else{
            $token = md5($openid.$money.time().random(6));
            $redis->set($openid.$money.'rebate_withdraw',$token,45);
        }
        if ($money<1){
            app_error(1,"提现金额不可小于1元");
        }
        if ($member["credit3"] < $money || $member["credit4"] < $money){
            app_error(1,"提现余额或贡献值不足");
        }
        //添加提现记录
        $log["uniacid"]=1;
        $log["openid"]=$openid;
        $log["type"]=1;
        $log["logno"]="CA".date("YmdHis").rand(100000,999999);
        $log["title"]="折扣宝提现";
        $log["createtime"]=time();
        $log["status"]=0;
        $log["money"]=$money;
        $log["realmoney"]=$money;
        $log["deductionmoney"]=bcmul($money,0.03,2);
        $log["realmoney"]=bcsub($money,$log['deductionmoney'],2);
        $log["remark"]="折扣宝提现";
        $log['draw_type'] = 2;
        if (pdo_insert("ewei_shop_member_log",$log)){
            //增加记录
            m('member')->setCredit($openid, 'credit3', -$money, "折扣宝提现:提现编号".$log["logno"]);
            m('member')->setCredit($openid, 'credit4', -$money, "折扣宝提现扣除:提现编号".$log["logno"]);
           app_error(0,"成功");
        }else{
            app_error(1,"失败");
        }
        
    }

    /**
     * 贡献值记录
     */
    public function dovate_log(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member = m('member')->getMember($openid);
        if (empty($member)){
            app_error(1,"openid不正确");
        }
        $page=$_GPC["page"];
        if (empty($page)){
            app_error(1,"页数不可为空");
        }
        $first=($page-1)*8;
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_credit_record")." where openid=:openid and credittype=:credittype order by createtime desc limit ".$first.",8",array(":openid"=>$openid,":credittype"=>"credit4"));
        if (!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
            }
        }
        $re["list"]=$list;
        $re["pagesize"]=8;
        app_error(0,$re);
        //show_json(1,$list);
    }
    
    
}