<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Memberlog_EweiShopV2Model
{
    /**
     * 推荐会员奖励
     * @param $memberid
     * @param $money
     * $fromopenid 被推荐人
     * $openid 推荐人
     */
	function rewardMember($openid,$money,$fromopenid){
	   // try{
	   //     pdo_begin();
            $frommember = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $fromopenid ));
            if(!$frommember) return false;
            $data['logno'] = 'RC'.$fromopenid.$openid.$money.$frommember['agentlevel'];
            $haslog = pdo_fetch("select * from " . tablename("ewei_shop_member_log") . " where logno=:logno limit 1", array( ":logno" => $data['logno']));
            if($haslog) return true;
            $data['openid'] = $openid;
	        $data['type'] = 3;//奖励
            $data['title'] = '推荐会员奖励:'.$frommember['nickname'];
            $data['createtime'] = strtotime(date('Y-m-d H:i:s'));
            $data['status'] = 1;
            $data['money'] = $money;
            $data['rechargetype'] = 'reward';
            $data['realmoney'] = $money;
            $res = pdo_insert("ewei_shop_member_log",$data);
            if($res){//更新member表的cicle2值
                $this->ts_money($fromopenid, $money, $openid);
                $member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $openid ));
                //if(!$member)  throw new PDOException('会员信息不存在');
                $memberdata['credit2'] = $member['credit2']+$money;
                pdo_update('ewei_shop_member',$memberdata,array('openid'=>$openid));
            }
           // pdo_commit();
	    //}catch (PDOException $e){
	     //   pdo_rollback();
        //}
        return true;
	}


    /**
     * 店长奖励
     * @param $memberid
     * @param $money
     */
    function rewardShowOwnerMember($openid,$money,$fromopenid){
        //try{
            $frommember = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $fromopenid ));
            if(!$frommember) return false;
            $data['logno'] = 'RC'.$fromopenid.$openid.$money.$frommember['agentlevel'];
            $haslog = pdo_fetch("select * from " . tablename("ewei_shop_member_log") . " where logno=:logno limit 1", array( ":logno" => $data['logno']));
            if($haslog) return true;
           // pdo_begin();
            $data['openid'] = $openid;
            $data['type'] = 3;//奖励
            $data['title'] = '店长奖励';
            $data['createtime'] = strtotime(date('Y-m-d H:i:s'));
            $data['status'] = 1;
            $data['money'] = $money;
            $data['rechargetype'] = 'reward';
            $data['realmoney'] = $money;
            $res = pdo_insert("ewei_shop_member_log",$data);
    
            if($res){//更新member表的cicle2值
                $this->ts_money($fromopenid, $money, $openid);
                $member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $openid ));
                //if(!$member)  throw new PDOException('会员信息不存在');
                $memberdata['credit2'] = $member['credit2']+$money;
                pdo_update('ewei_shop_member',$memberdata,array('openid'=>$openid));
            }
            //pdo_commit();

        //}catch (PDOException $e){
        //   pdo_rollback();
        //}
        return true;

    }
    
    //消息推送
    //fbb
    //openid当前openid fromopenid上级openid
    function ts_money($openid,$money,$fromopenid){
        $member=m("member")->getMember($openid);
        $postdata=array(
            'keyword1'=>array(
                'value'=>"+".$money,
                'color' => '#ff510'
            ),
            'keyword2'=>array(
                'value'=>"推荐奖励",
                'color' => '#ff510'
            ),
            'keyword3'=>array(
                'value'=>$member["nickname"],
                'color' => '#ff510'
            ),
            'keyword4'=>array(
                'value'=>date("Y-m-d",time()),
                'color' => '#ff510'
            ),
            'keyword5'=>array(
                'value'=>"会员升级",
                'color' => '#ff510'
            )
        );
        
        p("app")->mysendNotice($fromopenid, $postdata, "", "nSJSBKVYwLYN_LcsUXyvTNwD_-jAjPy7N6yq0GEQCEE");
    }
}
?>