<?php
/**
 * Nav.php
 *
 * 导航栏自定义菜单
 *
 * 主题设置项「导航栏前置/后置自定义菜单」的解析与渲染。
 * 两项共用同一套 JSON 结构，区别只在插入位置：
 *   - 前置：桌面端在站名之后、现有导航之前；竖屏在 PAGES 之前
 *   - 后置：桌面端在现有导航之后；竖屏在最末尾
 *
 * JSON 支持两种写法：
 *   1) 数组：[ {...}, {...} ]
 *   2) 对象：{ "mobileTitle": "MORE", "items": [ {...}, {...} ] }
 *
 * 菜单项字段：
 *   title        必填，显示文字
 *   url          链接地址；省略则渲染为无 href 的 <a>（配合 class 做 JS 触发器）
 *   class        附加 CSS 类名
 *   target       链接 target，默认 _self
 *   mobileTitle  仅下拉项可用：竖屏下该栏的标题，留空则用 title
 *   columns      下拉分栏：二维数组，外层每一项是一栏，内层是该栏的菜单项
 *   items        下拉不分栏时的平铺写法（等效于只有一栏）
 *   loginOnly    仅登录用户可见
 *   guestOnly    仅游客可见
 *
 * columns 示例（两栏，第一栏 1 项、第二栏 2 项）：
 *   "columns": [
 *     [ {"title": "归档页", "url": "/227.html"} ],
 *     [ {"title": "全站指南", "url": "/a/"}, {"title": "本站指南", "url": "/b/"} ]
 *   ]
 *
 * @package Typecho-Theme-VOID
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Nav
{
    /** 显式分栏时允许的最大栏数（与 _header.scss 中生成的 cols-N 对应） */
    const MAX_COLUMNS = 6;

    /**
     * 解析配置
     *
     * @param string|array|null $json 设置项原始值
     * @return array ['mobileTitle' => string, 'items' => array]
     */
    public static function parse($json)
    {
        $default = array('mobileTitle' => 'MORE', 'items' => array());

        if (is_array($json)) {
            // 兼容直接传入已解析的数组
            return array('mobileTitle' => $default['mobileTitle'], 'items' => $json);
        }

        if (!is_string($json)) {
            return $default;
        }

        $json = trim($json);
        if ($json === '') {
            return $default;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $default;
        }

        // 对象写法：{"mobileTitle": "...", "items": [...]}
        if (isset($data['items']) && is_array($data['items'])) {
            $title = $default['mobileTitle'];
            if (isset($data['mobileTitle']) && is_string($data['mobileTitle']) && trim($data['mobileTitle']) !== '') {
                $title = trim($data['mobileTitle']);
            }

            return array('mobileTitle' => $title, 'items' => $data['items']);
        }

        // 数组写法
        return array('mobileTitle' => $default['mobileTitle'], 'items' => $data);
    }

    /**
     * 桌面端渲染
     *
     * @param array $cfg Nav::parse() 的结果
     * @param bool $loggedIn 当前是否登录
     * @return string
     */
    public static function desktop($cfg, $loggedIn = false)
    {
        if (empty($cfg['items']) || !is_array($cfg['items'])) {
            return '';
        }

        $html = '';
        foreach ($cfg['items'] as $item) {
            if (!self::visible($item, $loggedIn)) {
                continue;
            }

            $title = self::title($item);
            if ($title === '') {
                continue;
            }

            if (!self::isDropdown($item)) {
                $html .= self::link($item);
                continue;
            }

            $columns = self::columns($item, $loggedIn);
            if (empty($columns)) {
                continue;
            }

            $count = max(1, min(count($columns), self::MAX_COLUMNS));

            $html .= '<span class="dropdown">' . $title
                . '<ul class="nav-columns cols-' . $count . '">';

            foreach ($columns as $index => $links) {
                foreach ($links as $pos => $sub) {
                    $class = ($index > 0 && $pos === 0) ? ' class="nav-column-break"' : '';
                    $html .= '<li' . $class . '>' . self::link($sub, false) . '</li>';
                }
            }

            $html .= '</ul></span>';
        }

        return $html;
    }

    /**
     * 竖屏端渲染
     *
     * 单独项合并为一栏（栏目标题取 mobileTitle），每个下拉项各占一栏；
     * 下拉内部的显式分栏在竖屏下按顺序平铺。
     *
     * @param array $cfg Nav::parse() 的结果
     * @param bool $loggedIn 当前是否登录
     * @return string
     */
    public static function mobile($cfg, $loggedIn = false)
    {
        if (empty($cfg['items']) || !is_array($cfg['items'])) {
            return '';
        }

        $links = array();
        $drops = array();

        foreach ($cfg['items'] as $item) {
            if (!self::visible($item, $loggedIn)) {
                continue;
            }
            if (self::isDropdown($item)) {
                $drops[] = $item;
            } elseif (self::title($item) !== '') {
                $links[] = $item;
            }
        }

        $html = '';

        // 单独项：合成一栏
        if (!empty($links)) {
            $html .= '<section data-title="' . self::escape($cfg['mobileTitle']) . '"><nav>';
            foreach ($links as $item) {
                $html .= self::link($item);
            }
            $html .= '</nav></section>';
        }

        // 下拉项：各占一栏，内部各分栏顺序平铺
        foreach ($drops as $item) {
            $mobileTitle = isset($item['mobileTitle']) && trim((string) $item['mobileTitle']) !== ''
                ? trim((string) $item['mobileTitle'])
                : self::title($item);

            $html .= '<section data-title="' . self::escape($mobileTitle) . '"><nav>';
            foreach (self::columns($item, $loggedIn) as $links) {
                foreach ($links as $sub) {
                    $html .= self::link($sub);
                }
            }
            $html .= '</nav></section>';
        }

        return $html;
    }

    /**
     * 取下拉的二维分栏结构
     *
     * 返回 [[item, ...], [item, ...]]，已剔除不可见项与空栏。
     * 优先使用 columns（显式分栏），否则把 items 当作单栏。
     *
     * @param array $item 菜单项
     * @param bool $loggedIn
     * @return array
     */
    private static function columns($item, $loggedIn)
    {
        $raw = array();

        if (isset($item['columns']) && is_array($item['columns'])) {
            foreach ($item['columns'] as $column) {
                if (is_array($column)) {
                    $raw[] = $column;
                }
            }
        } elseif (isset($item['items']) && is_array($item['items'])) {
            $raw[] = $item['items'];
        }

        $result = array();
        foreach ($raw as $column) {
            $links = array();
            foreach ($column as $sub) {
                if (self::visible($sub, $loggedIn) && self::title($sub) !== '') {
                    $links[] = $sub;
                }
            }
            if (!empty($links)) {
                $result[] = $links;
            }
        }

        return $result;
    }

    /**
     * 是否下拉菜单
     */
    private static function isDropdown($item)
    {
        if (!is_array($item)) {
            return false;
        }

        return (!empty($item['columns']) && is_array($item['columns']))
            || (!empty($item['items']) && is_array($item['items']));
    }

    /**
     * 权限可见性
     */
    private static function visible($item, $loggedIn)
    {
        if (!is_array($item)) {
            return false;
        }
        if (!empty($item['loginOnly']) && !$loggedIn) {
            return false;
        }
        if (!empty($item['guestOnly']) && $loggedIn) {
            return false;
        }

        return true;
    }

    /**
     * 取并转义标题
     */
    private static function title($item)
    {
        return isset($item['title']) ? self::escape($item['title']) : '';
    }

    /**
     * 渲染单个链接
     *
     * @param array $item
     * @param bool $allowEmptyUrl 无 url 时是否渲染为不带 href 的锚点
     * @return string
     */
    private static function link($item, $allowEmptyUrl = true)
    {
        $title = self::title($item);
        $class = isset($item['class']) && trim((string) $item['class']) !== ''
            ? ' class="' . self::escape(trim((string) $item['class'])) . '"'
            : '';
        $url = isset($item['url']) ? trim((string) $item['url']) : '';

        // 无 url：渲染为不带 href 的锚点，便于作为 JS 触发器
        if ($url === '') {
            return '<a' . $class . '>' . $title . '</a>';
        }

        $target = isset($item['target']) && trim((string) $item['target']) !== ''
            ? trim((string) $item['target'])
            : '_self';

        return '<a' . $class . ' target="' . self::escape($target) . '" href="'
            . self::escape($url) . '">' . $title . '</a>';
    }

    /**
     * HTML 转义
     */
    private static function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
