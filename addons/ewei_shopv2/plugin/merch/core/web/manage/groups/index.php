@@ -0,0 +1,21 @@
<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';
class Index_EweiShopV2Page extends MerchWebPage
{
    public function main()
    {
        global $_W;
        $this->model->CheckPlugin('groups');
        
        if (mcv('groups')) {
            header('location: ' . webUrl('groups/goods'));
        }
        
        include $this->template('groups/goods');
    }
}

?>