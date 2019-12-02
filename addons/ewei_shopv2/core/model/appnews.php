<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Appnews_EweiShopV2Model
{
    //判断用户登录信息
    public function member($openid,$type){
        if ($type==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                app_error(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            return false;
        }else {
            if (!$member["openid"]){
                $member["openid"]=0;
            }
            return $member;
        }
    }
    
}