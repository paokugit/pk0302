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
     */
	function rewardMember($openid,$money,$fromopenid){
	    try{
	        pdo_begin();
            $data['logno'] = 'RC'.$fromopenid.$openid;
            $haslog = pdo_fetch("select * from " . tablename("ewei_shop_member_log") . " where logno=:logno limit 1", array( ":logno" => $data['logno']));
            if($haslog) return true;
            $data['openid'] = $openid;
	        $data['type'] = 3;//奖励
            $data['title'] = '推荐会员奖励';
            $data['createtime'] = strtotime(date('Y-m-d H:i:s'));
            $data['status'] = 1;
            $data['money'] = $money;
            $data['rechargetype'] = 'reward';
            $data['realmoney'] = $money;
            $res = pdo_insert("ewei_shop_member_log",$data);
            if($res){//更新member表的cicle2值
                $member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $openid ));
                if(!$member)  throw new PDOException('会员信息不存在');
                $memberdata['credit2'] = $member['credit2']+$money;
                return pdo_update('ewei_shop_member',$memberdata,array('openid'=>$openid));
            }
            return $res;
	    }catch (PDOException $e){
	        pdo_rollback();
        }
	}


    /**
     * 店长奖励
     * @param $memberid
     * @param $money
     */
    function rewardShowOwnerMember($openid,$money,$fromopenid){
        try{
            $data['logno'] = 'RC'.$fromopenid.$openid;
            $haslog = pdo_fetch("select * from " . tablename("ewei_shop_member_log") . " where logno=:logno limit 1", array( ":logno" => $data['logno']));
            if($haslog) return true;
            pdo_begin();
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
                $member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array( ":openid" => $openid ));
                if(!$member)  throw new PDOException('会员信息不存在');
                $memberdata['credit2'] = $member['credit2']+$money;
                pdo_update('ewei_shop_member_log',$memberdata,array('openid'=>$openid));
            }

        }catch (PDOException $e){
           pdo_rollback();
        }

    }
}
?>