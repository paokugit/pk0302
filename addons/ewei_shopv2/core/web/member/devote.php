<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fbb 贡献值
class Devote_EweiShopV2Page extends WebPage{
    
    public function main(){
        global $_W;
        global $_GPC;
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>1));
        if ($_W['ispost']){
            $detail=$_GPC["detail"];
            pdo_update("ewei_shop_member_devote",array("content"=>$detail),array("id"=>1));
                show_json(1, array('url' => webUrl('member/devote')));
            
        }
        include $this->template();
    }
    //贡献值--奖励
    public function reward(){
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_devotejl"));
     
        include $this->template();
    }
    
    //贡献值--奖励编辑
    public function edit(){
       
        $this->post();
    }
    
    protected function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC["id"]);
       
        $item=pdo_get("ewei_shop_member_devotejl",array("id"=>$id));
//         var_dump($item);die;
        $level=pdo_fetchall("select * from ".tablename("ewei_shop_commission_level")." where id=1 or id=2 or id=5");
    //    var_dump($level);
        if ($_W['ispost']) {
            $data["count"]=$_GPC['count'];
            $data["num"]=$_GPC["num"];
            $data["level"]=$_GPC["level"];
            $data["start_date"]=$_GPC["start_date"];
            $data["end_date"]=$_GPC["end_date"];
            if (pdo_update("ewei_shop_member_devotejl",$data,array("id"=>$id))){
                show_json(1, array('url' => webUrl('member/devote/reward')));
            }else{
                show_json(0,"失败");
            }
        }
        
        include $this->template();
    }
    
    
}