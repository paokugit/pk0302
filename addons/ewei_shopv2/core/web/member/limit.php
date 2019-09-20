<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fbb 加速包
class Limit_EweiShopV2Page extends WebPage{
    //列表
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
       
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_limit') . ('  ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_limit') );
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
            
           
                $data = array( 'money' => trim($_GPC['money']),'limit'=>$_GPC['limit']);
           
            if (!empty($id)) {
                pdo_update('ewei_shop_member_limit', $data, array('id' => $id));
               
            }
            else {
                pdo_insert('ewei_shop_member_limit', $data);
                $id = pdo_insertid();
               
            }
            
            show_json(1, array('url' => webUrl('member/limit')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_member_limit') . ' where id=:id limit 1', array(':id' => $id));
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
        $items = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_limit') . (' WHERE id in( ' . $id . ' ) '));
        foreach ($items as $item) {
            pdo_delete('ewei_shop_member_limit', array('id' => $item['id']));
        }
        show_json(1, array('url' => referer()));
    }

    /**
     * 修改状态
     */
    public function status()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_member_limit') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_update('ewei_shop_member_limit', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('member.limit.edit', '修改限额宝<br/>ID: ' . $item['id'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
        }
        show_json(1, array('url' => referer()));
    }
}