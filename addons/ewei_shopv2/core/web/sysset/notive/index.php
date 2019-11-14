<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage
{
    //攻略
    public function main(){
        $root=ATTACHMENT_ROOT."videos/ffmpeg.exe";
        $file=ATTACHMENT_ROOT."videos/0e472f2758291e4aefe279edcc9f36d3.mp4";
        $vtime = exec("ffmpeg  -i ".$file." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");
        var_dump($vtime);die;
        include($this->template());
    }
    
    
    
}