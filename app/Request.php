<?php
namespace app;

// 应用请求对象类
class Request extends \think\Request
{
      protected $filter = ['trim','htmlspecialchars','strip_tags'];

      protected $proxyServerIp = ['127.0.0.1'];
}
