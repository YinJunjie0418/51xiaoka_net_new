<?php
 namespace app\common;

 use think\Paginator;

 class LayuiPager extends Paginator
 {
     protected $uri;
     /**
      * 上一页按钮
      * @param string $text
      * @return string
      */
     protected function getPreviousButton($text = "上一页")
     {

         if ($this->currentPage() <= 1) {
             return $this->getDisabledTextWrapper($text);
         }

         $url = $this->new_url(
             $this->currentPage() - 1
         );

         return $this->getPageLinkWrapper($url, $text);
     }

     /**
      * 下一页按钮
      * @param string $text
      * @return string
      */
     protected function getNextButton($text = '下一页')
     {
         if (!$this->hasMore) {
             return $this->getDisabledTextWrapper($text);
         }

         $url = $this->new_url($this->currentPage() + 1);

         return $this->getPageLinkWrapper($url, $text);
     }

     /**
      * 页码按钮
      * @return string
      */
     protected function getLinks()
     {
         if ($this->simple)
             return '';

         $block = [
             'first' => null,
             'slider' => null,
             'last' => null
         ];

         $side = 3;
         $window = $side * 2;

         if ($this->lastPage < $window + 6) {
             $block['first'] = $this->getUrlRange_new(1, $this->lastPage);
         } elseif ($this->currentPage <= $window) {
             $block['first'] = $this->getUrlRange_new(1, $window + 2);
             $block['last'] = $this->getUrlRange_new($this->lastPage - 1, $this->lastPage);
         } elseif ($this->currentPage > ($this->lastPage - $window)) {
             $block['first'] = $this->getUrlRange_new(1, 2);
             $block['last'] = $this->getUrlRange_new($this->lastPage - ($window + 2), $this->lastPage);
         } else {
             $block['first'] = $this->getUrlRange_new(1, 2);
             $block['slider'] = $this->getUrlRange_new($this->currentPage - $side, $this->currentPage + $side);
             $block['last'] = $this->getUrlRange_new($this->lastPage - 1, $this->lastPage);
         }

         $html = '';

         if (is_array($block['first'])) {
             $html .= $this->getUrlLinks($block['first']);
         }

         if (is_array($block['slider'])) {
             $html .= $this->getDots();
             $html .= $this->getUrlLinks($block['slider']);
         }

         if (is_array($block['last'])) {
             $html .= $this->getDots();
             $html .= $this->getUrlLinks($block['last']);
         }

         return $html;
     }

     /**
      * 渲染分页html
      * @return mixed
      */
     public function render()
     {
         if ($this->hasPages()) {
             if ($this->simple) {
                 return sprintf(
                     '<div class="page">%s %s</div>',
                     $this->getPreviousButton(),
                     $this->getNextButton()
                 );
             } else {
                 return sprintf(
                     '<div class="page">%s %s %s %s</div>',
                     $this->getPreviousButton(),
                     $this->getLinks(),
                     $this->getNextButton(),
                     $this->getTotal($this->total)
                 );
             }
         }
     }

     /**
      * 生成一个可点击的按钮
      *
      * @param  string $url
      * @param  int $page
      * @return string
      */
     protected function getAvailablePageWrapper($url, $page)
     {
         if ($page == '上一页')
             return '<a href="' . htmlentities($url) . '" class="laypage-prev">' . $page . '</a>';
         else if ($page == '下一页')
             return '<a href="' . htmlentities($url) . '" class="laypage-next">' . $page . '</a>';
         else
             return '<a href="' . htmlentities($url) . '">' . $page . '</a>';
     }

     /**
      * 生成一个禁用的按钮
      *
      * @param  string $text
      * @return string
      */
     protected function getDisabledTextWrapper($text)
     {
         return '<a class="disabled" >' . $text . '</a>';
     }

     /**
      * 生成一个激活的按钮
      *
      * @param  string $text
      * @return string
      */
     protected function getActivePageWrapper($text)
     {
		 return '<strong class="bc">' . $text . '</strong>';
     }

     /**
      * 生成省略号按钮
      *
      * @return string
      */
     protected function getDots()
     {
         return $this->getDisabledTextWrapper('...');
     }

     /**
      * 批量生成页码按钮.
      *
      * @param  array $urls
      * @return string
      */
     protected function getUrlLinks(array $urls)
     {
         $html = '';

         foreach ($urls as $page => $url) {
             $html .= $this->getPageLinkWrapper($url, $page);
         }

         return $html;
     }
     
    protected function getUrlRange_new($start,$end)
    {
        $urls = [];

        for ($page = $start; $page <= $end; $page++) {
            $urls[$page] = $this->new_url($page);
        }

        return $urls;
    }
     /**
     *  生成总条数
     * @param $num
     * @return string
     */
    protected function getTotal($num)
    {
        return '<em>共'.$num.'条</em>';
    }
    
     /**
      * 生成普通页码按钮
      *
      * @param  string $url
      * @param  int $page
      * @return string
      */
     protected function getPageLinkWrapper($url, $page)
     {
         if ($page == $this->currentPage()) {
             return $this->getActivePageWrapper($page);
         }

         return $this->getAvailablePageWrapper($url, $page);
     }
     
    /**
     * 获取url
     */
    private function getUri(){
        $url=$_SERVER["REQUEST_URI"].(strpos($_SERVER["REQUEST_URI"], '?')?'':"?");
        $parse=parse_url($url);
 
        if(isset($parse["query"])){
            parse_str($parse['query'],$params);
            unset($params["page"]);
            $url=$parse['path'] . '?' . http_build_query($params) .'&';
        }else{
            $url=$parse['path'] . '?';
        }
 
        $this->uri = $url;
 
    }
    
    function new_url($page) {
        if (!$this->uri) {
            $pageVar = 'page';
            $get = input('get.');
            unset($get[$pageVar]);
            $get[$pageVar] = '';

            $this->uri = request()->baseUrl().'?' . http_build_query($get);
        }

        return $this->uri . $page;
    }
	
 }