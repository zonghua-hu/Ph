<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/16
 * Time: 12:18
 */

class TopDnsServer extends DnsServer
{

    protected function isLocal($str_domain)
    {
        // TODO: Implement isLocal() method.
        return true;
    }

    protected function getsRecoder($str_domain)
    {
        $recoder =  parent::getsRecoder($str_domain); // TODO: Change the autogenerated stub
        $recoder->setOwner("全球顶级域名服务解析");
        return $recoder;
    }

}