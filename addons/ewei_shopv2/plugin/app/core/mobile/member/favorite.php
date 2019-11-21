<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Favorite_EweiShopV2Page extends AppMobilePage
{
    public function get_list()
    {
        global $_W;
        global $_GPC;
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        //编辑修改
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        $condition = ' and f.uniacid = :uniacid and (f.openid=:openid or f.user_id=:user_id) and f.deleted=0';
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $condition = ' and f.uniacid = :uniacid and (f.openid=:openid or f.user_id=:user_id) and f.deleted=0 and f.type=0';
        }
        
        // 		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
        $params = array(':uniacid' => $_W['uniacid'], ':openid' =>$member["openid"],':user_id'=>$member["id"]);
        $sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_member_favorite') . (' f where 1 ' . $condition);
        $total = pdo_fetchcolumn($sql, $params);
        $list = array();
        $result = array(
            'list'     => array(),
            'total'    => $total,
            'pagesize' => $psize,
            'page'=>$pindex
        );
        
        if (!empty($total)) {
            $sql = 'SELECT f.id,f.goodsid,g.title,g.thumb,g.marketprice,g.productprice,g.merchid FROM ' . tablename('ewei_shop_member_favorite') . ' f ' . ' left join ' . tablename('ewei_shop_goods') . ' g on f.goodsid = g.id ' . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
            $list = pdo_fetchall($sql, $params);
            $list = set_medias($list, 'thumb');
            if (!empty($list) && $merch_plugin && $merch_data['is_openmerch']) {
                $result['openmerch'] = 1;
                $merch_user = $merch_plugin->getListUser($list, 'merch_user');
                
                foreach ($list as &$row) {
                    $row['merchname'] = $merch_user[$row['merchid']]['merchname'] ? $merch_user[$row['merchid']]['merchname'] : $_W['shopset']['shop']['name'];
                    $row['openmerch'] = 1;
                }
                
                unset($row);
            }
        }
        
        $result['list'] = $list;
        if ($_GPC["type"]==1){
            apperror(0,"",$result);
        }else{
        app_json($result);
        }
    }
    
    public function toggle()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (empty($id)) {
            apperror(1,"为传入id");
        }
        //修改
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        $isfavorite = intval($_GPC['isfavorite']);
        $goods = pdo_fetch('select * from ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        
        if (empty($goods)) {
            apperror(1,"商品不存在");
        }
        
        $data = pdo_fetch('select id,deleted from ' . tablename('ewei_shop_member_favorite') . ' where uniacid=:uniacid and goodsid=:id and (openid=:openid or user_id=:user_id) limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid'],':user_id'=>$member["id"],':id' => $id));
        
        if (empty($data)) {
            if (!empty($isfavorite)) {
                $data = array('uniacid' => $_W['uniacid'], 'goodsid' => $id, 'openid' => $member['openid'],'user_id'=>$member["id"],'createtime' => time());
                pdo_insert('ewei_shop_member_favorite', $data);
            }
        }
        else {
            pdo_update('ewei_shop_member_favorite', array('deleted' => $isfavorite ? 0 : 1,'openid'=>$member["openid"],'user_id'=>$member["id"]), array('id' => $data['id'], 'uniacid' => $_W['uniacid']));
        }
        
        if ($_GPC["type"]==1){
            apperror(0,"成功");
        }else{
        app_json(array('isfavorite' => $isfavorite == 1));
        }
    }
    
    public function remove()
    {
        global $_W;
        global $_GPC;
        $ids = $_GPC['ids'];
        if (empty($ids) || !is_array($ids)) {
            app_error(AppError::$ParamsError);
        }
        //修改
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        // 		$sql = 'update ' . tablename('ewei_shop_member_favorite') . ' set deleted=1 where openid=:openid and id in (' . implode(',', $ids) . ')';
        $sql = 'update ' . tablename('ewei_shop_member_favorite') . ' set deleted=1 where (openid=:openid or user_id=:user_id) and id in (' . implode(',', $ids) . ')';
        pdo_query($sql, array(':openid' => $member['openid'],':user_id'=>$member["id"]));
        if ($_GPC["type"]==1){
            apperror(0,"");
        }else{
        app_json();
        }
    }
    
    public function get_merchlist()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        //修改
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        $condition = ' and  (f.openid=:openid or user_id=:user_id)';
        $params = array(':openid' =>$member["openid"],':user_id'=>$member["id"]);
        $sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_merch_follow') . (' f where 1 ' . $condition);
        $total = pdo_fetchcolumn($sql, $params);
        $list = array();
        
        if (!empty($total)) {
            $sql = 'SELECT f.id,f.merch_id,g.merchname,g.logo,g.desc FROM ' . tablename('ewei_shop_merch_follow') . ' f ' . ' left join ' . tablename('ewei_shop_merch_user') . ' g on f.merch_id = g.id ' . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
            $list = pdo_fetchall($sql, $params);
            $list = set_medias($list, 'logo');
            $merch_plugin = p('merch');
            $merch_data = m('common')->getPluginset('merch');
            if (!empty($list) && $merch_plugin && $merch_data['is_openmerch']) {
                $merch_user = pdo_fetchall('select id,merchname from ' . tablename('ewei_shop_merch_user') . ' where id in(' . implode(',', array_unique(array_column($list, 'merch_id'))) . ')', array(), 'id');
                // 				var_dump(array_column($list, 'merch_id'));
                // 				var_dump($merch_user);
                foreach ($list as &$row) {
                    $row['merchname'] = $merch_user[$row['merch_id']]['merchname'] ? $merch_user[$row['merch_id']]['merchname'] : $_W['shopset']['shop']['name'];
                }
                
                unset($row);
            }
        }
        if ($_GPC["type"]==1){
            apperror(0,"",array('list' => $list, 'total' => $total, 'pagesize' => $psize,'page'=>$pindex));
        }else{
        app_json(array('list' => $list, 'total' => $total, 'pagesize' => $psize));
        }
    }
    
    public function remove_shop()
    {
        global $_W;
        global $_GPC;
        $ids = $_GPC['ids'];
//         var_dump($ids);die;
     
        if (empty($ids) || !is_array($ids)) {
           
            apperror(1,"ids未传");
        }
        //修改
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                apperror(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
       
        // 		$sql = 'update ' . tablename('ewei_shop_member_favorite') . ' set deleted=1 where openid=:openid and id in (' . implode(',', $ids) . ')';
        $sql = "DELETE  from " . tablename("ewei_shop_merch_follow") . 'where (openid=:openid or user_id=:user_id) and id in (' . implode(',', $ids) . ')';
        if (pdo_query($sql, array(':openid' => $member['openid'],':user_id'=>$member["id"]))){
           
               apperror(0,"");
           
        }else{
          
                apperror(1,"删除失败");
            
        }
    }
    
}

?>
