<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fbb 加速包
class Acceleration_EweiShopV2Page extends WebPage{
    //列表
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
       
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_accelerate') . ('  ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_accelerate') );
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    //添加
    public function add()
    {
        $this->post();
    }
    
    public function edit()
    {
        $this->post();
    }
    
    
    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            
           
                $data = array( 'money' => trim($_GPC['money']),'accelerate_day'=>$_GPC['accelerate_day'],'duihuan' => $_GPC['duihuan']);
           
            if (!empty($id)) {
                pdo_update('ewei_shop_member_accelerate', $data, array('id' => $id));
               
            }
            else {
                pdo_insert('ewei_shop_member_accelerate', $data);
                $id = pdo_insertid();
               
            }
            
            show_json(1, array('url' => webUrl('member/acceleration')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_member_accelerate') . ' where id=:id limit 1', array(':id' => $id));
        include $this->template();
    }
    //删除
    public function delete(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        
        $items = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_accelerate') . (' WHERE id in( ' . $id . ' ) '));
        
        foreach ($items as $item) {
            
            
            pdo_delete('ewei_shop_member_accelerate', array('id' => $item['id']));
          
            
        }
        
        show_json(1, array('url' => referer()));
        
    }
}