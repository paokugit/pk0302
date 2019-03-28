<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fanbeibei 运动日记
class Sport_EweiShopV2Page extends WebPage{
    //列表
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
        if ($_GPC['enabled'] != '') {
            $condition .= ' and is_default=' . intval($_GPC['enabled']);
        }
        
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and date  = :date';
            $params[':date'] = $_GPC['date'] ;
        }
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_sport') . (' WHERE 1 ' . $condition . '  ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_sport') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    
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
            $data = array('uniacid' => $_W['uniacid'], 'date' => trim($_GPC['date']), 'thumb' => save_media($_GPC['thumb']));
            
            if (!empty($id)) {
                pdo_update('ewei_shop_member_sport', $data, array('id' => $id));
                plog('member.sport.edit', '修改幻灯片 ID: ' . $id);
            }
            else {
                pdo_insert('ewei_shop_member_sport', $data);
                $id = pdo_insertid();
                plog('member.sport.add', '添加幻灯片 ID: ' . $id);
            }
            
            show_json(1, array('url' => webUrl('member/sport')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_member_sport') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        include $this->template();
    }
    
    
}