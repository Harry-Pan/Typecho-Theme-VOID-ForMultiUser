<?php
/**
 * Utils.php
 * 
 * 工具类
 * 
 * @author      熊猫小A
 * @version     2019-01-15 0.01
 */

class Utils
{
    /**
     * 输出相对首页路由，本方法会自适应伪静态
     * 
     * @return void
     */
    public static function index($path)
    {
        Helper::options()->index($path);
    }

    /**
     * 输出相对首页路径，本方法不处理伪静态，用于静态文件
     * 
     * @return void
     */
    public static function indexHome($path)
    {
        Helper::options()->siteUrl($path);
    }

    /**
     * 输出相对主题目录路径，用于静态文件
     * 
     * @return void
     */
    public static function indexTheme($path)
    {
        Helper::options()->themeUrl($path);
    }

    /**
     * 输出带版本号的静态资源路径
     *
     * 源码模式下资源文件名不含哈希，而浏览器/nginx 可能长期缓存，
     * 这里按文件 mtime 追加 ?v= 参数，保证改动后立刻生效；
     * 构建模式（文件名带哈希）下追加也无副作用。
     *
     * @return void
     */
    public static function asset($path)
    {
        $file = dirname(__DIR__) . '/' . ltrim($path, '/');
        $time = is_file($file) ? filemtime($file) : false;
        self::indexTheme($time ? $path . '?v=' . $time : $path);
    }

    /**
     * 输出头像链接
     * 
     * @return void
     */
    public static function gravatar($mail, $size = 64, $d = '')
    {
        echo Typecho_Common::gravatarUrl($mail, $size, '', urlencode($d), true);
    }

    /**
     * 判断插件是否可用
     * 
     * @return bool
     */
    public static function isPluginAvailable($name) 
    {
        $plugins = Typecho_Plugin::export();
        $plugins = $plugins['activated'];
        return is_array($plugins) && array_key_exists($name, $plugins);
    }

    /**
     * PJAX判定
     * 
     * @return bool
     */
    public static function isPjax()
    {
        return array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'];
    }

    /**
     * 使用衬线体判定
     */
    public static function isSerif($setting)
    {
        if(isset($_COOKIE['serif'])) {
            if ($_COOKIE['serif']=='1') return true; 
        } else {
            if ($setting['serifincontent']) return true;
        }
        return false;
    }

    /**
     * 界面大小风格
     * 1: 14px, 2: 16px, 3: 18px, 4: 20px, 5: 22px
     */
    public static function getTextSize($setting) {
        if(isset($_COOKIE['textsize'])) {
            return $_COOKIE['textsize'];
        } else {
            return $setting['defaultFontSize'];
        }
    }

    /**
     * 移动端判定
     * 
     * @return bool
     */
    public static function isMobile()
    { 
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
            return TRUE;
        }
        
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array ('mobile','nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap'
                ); 
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
                return TRUE;
            }
        }
        if (isset ($_SERVER['HTTP_ACCEPT'])){
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== FALSE) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === FALSE || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * iOS 判定
     */
    public static function isIosSafari () {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad');
    }

    /**
     * 编辑界面添加Button
     * 
     * @return void
     */
    public static function addButton()
    {
        // 表情编辑器按钮与样式由 OwO 插件（TypechoPluginOwO）提供，
        // 主题仅保留「图集」按钮与评论表单的响应式样式
        echo '<script src="';
        self::asset('/assets/editor.js');
        echo '"></script>';
       
        echo '<style>#custom-field textarea,#custom-field input{width:100%}
        .OwO span{background:none!important;width:unset!important;height:unset!important}
        .OwO .OwO-body .OwO-items{
            -webkit-overflow-scrolling: touch;
            overflow-x: hidden;
        }
        .OwO .OwO-body .OwO-items-image .OwO-item{
            max-width:-moz-calc(20% - 10px);
            max-width:-webkit-calc(20% - 10px);
            max-width:calc(20% - 10px)
        }
        @media screen and (max-width:767px){	
            .comment-info-input{flex-direction:column;}
            .comment-info-input input{max-width:100%;margin-top:5px}
            #comments .comment-author .avatar{
                width: 2.5rem;
                height: 2.5rem;
            }
        }
        @media screen and (max-width:760px){
            .OwO .OwO-body .OwO-items-image .OwO-item{
                max-width:-moz-calc(25% - 10px);
                max-width:-webkit-calc(25% - 10px);
                max-width:calc(25% - 10px)
            }
        }
        .wmd-button-row{height:unset}</style>';
    }

    /**
     * 判定内容是否过时
     *
     * @param object $archive 文章对象
     * @param int|null $days 自定义阈值（天），为 null 时读取主题设置
     * @return array
     */
    public static function isOutdated($archive, $days = null)
    {
        if ($days === null) {
            $setting = $GLOBALS['VOIDSetting'];
            $days = isset($setting['outdatedDays']) ? intval($setting['outdatedDays']) : 90;
        }
        if ($days <= 0) {
            $days = 90;
        }

        $created = round((time()- $archive->created) / 3600 / 24);
        $updated = round((time()- $archive->modified) / 3600 / 24);

        return array("is" => $created > $days,
                    "created" => $created,
                    "updated" => $updated);
    }

    /**
     * 解析顶部大图随机图池
     *
     * @return array
     */
    public static function getBannerImages($setting)
    {
        $raw = isset($setting['indexBannerImages']) ? $setting['indexBannerImages'] : '';
        if (empty($raw)) {
            return array();
        }

        $images = array();
        foreach (preg_split('/[\r\n]+/', $raw) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $images[] = $line;
            }
        }

        return $images;
    }

    /**
     * 从随机图池中取一张作为默认顶部大图，未配置时返回空字符串
     *
     * @return string
     */
    public static function getDefaultBanner($setting)
    {
        $images = self::getBannerImages($setting);
        if (empty($images)) {
            return '';
        }

        return $images[array_rand($images)];
    }

    /**
     * 输出建站时间（最早一篇文章的写作时间）
     * 
     * @return array
     */
    public static function getBuildTime()
    {
        $db = Typecho_Db::get();
        $content = $db->fetchRow($db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.password IS NULL')
            ->order('table.contents.created', Typecho_Db::SORT_ASC)
            ->limit(1));
        if (count($content))
            echo date('Y-m-d\TH:i', $content['created']);
        else
            echo date('Y-m-d\TH:i');
    }

    /**
     * 已发布文章数量
     * 
     * @return int
     */
    public static function getPostNum()
    {
        $db = Typecho_Db::get();
        return $db->fetchObject($db->select(array('COUNT(cid)' => 'num'))
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish'))->num;
    }

    /**
     * 分类数量
     * 
     * @return int
     */
    public static function getCatNum()
    {
        $db = Typecho_Db::get();
        return $db->fetchObject($db->select(array('COUNT(mid)' => 'num'))
                    ->from('table.metas')
                    ->where('table.metas.type = ?', 'category'))->num;
    }

    /**
     * 标签数量
     * 
     * @return int
     */
    public static function getTagNum()
    {
        $db = Typecho_Db::get();
        return $db->fetchObject($db->select(array('COUNT(mid)' => 'num'))
                    ->from('table.metas')
                    ->where('table.metas.type = ?', 'tag'))->num;
    }

    /**
     * 作者数量
     * 
     * @return int
     */
    public static function getAuthorNum()
    {
        $db = Typecho_Db::get();
        return $db->fetchObject($db->select(array('COUNT(uid)' => 'num'))
                    ->from('table.users'))->num;
    }

    /**
     * 存在 VOID 插件且满足要求
     */
    public static function hasVOIDPlugin($req)
    {
        if(self::isPluginAvailable('VOID')) {
            $version_have = VOID_Plugin::$VERSION;
            if($version_have >= $req) return true;
        }

        return false;
    }

    /**
     * 超高级设置
     * 
     * @return array
     */
    public static function getVOIDSettings()
    {
        $options = Helper::options();

        // 主题设置
        $themeSetting = array(
            'indexBannerImages' => '',
            'indexBannerBlur' => 3,
            'indexHoverColor' => '#bd6262',
            'outdatedNotice' => '1',
            'outdatedDays' => 90,
            'eggNotice' => '恭喜你！<br>本文是一篇彩蛋文章。',
            'enableMath' => false,
            'head' => '',
            'footer' => '',
            'serifincontent' => false,
            'pjax' => false,
            'pjaxreload' => '',
            'indexStyle' => 0,
            'lazyload' => false,
            'indexBannerTitle' => '',
            'indexBannerSubtitle' => '',
            'navBefore' => '',
            'navAfter' => '',
            'serviceworker' => '',
            'colorScheme' => 0, // 0: 自动，1: 日间，2: 夜间
            'reward' => ''
        );

        $keys = array_keys($themeSetting);
        foreach ($keys as $key) {
            if(!empty($options->{$key})){
                $themeSetting[$key] = $options->{$key};
            }
        }

        // 允许取值为 "0" 的设置项，不能被上面的 empty() 判断过滤掉
        $zeroAllowed = array('outdatedNotice', 'indexBannerBlur', 'outdatedDays');
        foreach ($zeroAllowed as $key) {
            $value = $options->{$key};
            if ($value !== null && $value !== '') {
                $themeSetting[$key] = $value;
            }
        }

        // 一些类型变换
        $themeSetting['enableMath'] = boolval($themeSetting['enableMath']);
        $themeSetting['lazyload'] = boolval($themeSetting['lazyload']);
        $themeSetting['colorScheme'] = intval($themeSetting['colorScheme']);
        $themeSetting['pjax'] = boolval($themeSetting['pjax']);
        $themeSetting['serifincontent'] = boolval($themeSetting['serifincontent']);
        $themeSetting['indexStyle'] = intval($themeSetting['indexStyle']);
        $themeSetting['outdatedNotice'] = boolval($themeSetting['outdatedNotice']);
        $themeSetting['outdatedDays'] = intval($themeSetting['outdatedDays']);
        $themeSetting['indexBannerBlur'] = intval($themeSetting['indexBannerBlur']);

        // 高级设置
        $advanceSetting = array(
            'nav' => '',
            'name' => '',
            'brandFont' => array(
                'src' => '',
                'style' => 'normal',
                'weight' => 'normal'
            ),
            'desktopBannerHeight' => '',
            'mobileBannerHeight' => '',
            'twitterId' => '',
            'weiboId' => '',
            'headerMode' => 1,
            'defaultFontSize' => 3,
            'useFiraCodeFont' => false,
            'followSystemColorScheme' => false,
            'largePhotoSet' => true,
            'macStyleCodeBlock' => true,
            'lineNumbers' => true,
            'parseFigcaption' => true,
            'darkModeTime' => array (
                'start' => 22.0,
                'end' => 7.0
            ),
            'link' => array(),
            'commentFoldThreshold' => array(5, 1.5),
            'commentNotification' => '',
            'bluredLazyload' => false,
            'browserLevelLoadingLazy' => false,
            'CDNType' => array()
        );

        if(!empty($options->advance)){
            $settings = json_decode($options->advance, true);
            foreach ($settings as $key => $value) {
                $advanceSetting[$key] = $value;
            }
        }

        if(self::isMobile() && array_key_exists('headerModeMobile', $advanceSetting)){
            $advanceSetting['headerMode'] = $advanceSetting['headerModeMobile'];
        }

        $output = array_merge($themeSetting, $advanceSetting);
        $output['VOIDPlugin'] = self::hasVOIDPlugin($GLOBALS['VOIDPluginREQ']);

        return $output;
    }
}