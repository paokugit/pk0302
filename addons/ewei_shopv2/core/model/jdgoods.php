<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Jdgoods_EweiShopV2Model
{
    //商品主图地址
    public function homeaddr(){
        $url="http://img13.360buyimg.com/n0/";
        return $url;
    }
    //批量获取价格
    public function batch_price($sku){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getSellPrice";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["sku"]=$sku;
        $res=$this->posturl($url, $data);
        return $res;
        
    }
    //获取可售验证
    public function sale($skuIds){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/check";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["skuIds"]=$skuIds;
        $res=$this->posturl($url, $data);
        return $res;
    }
    function posturl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }
}