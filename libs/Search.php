<?php
/**
 * Search.php
 *
 * 站内搜索索引
 *
 * 前端搜索框（VOID_Search）在浏览器里做即时匹配，需要一份包含标题、
 * 摘要、分类与标签的索引。索引在文章变化后按需重建，写入 theme/cache，
 * 由前端按需拉取并缓存。
 *
 * @author      熊猫小A
 */

class Search
{
    /** 索引格式版本，结构变动时递增 */
    const VERSION = 2;

    /** 摘要模式下正文截断长度（字符） */
    const EXCERPT = 600;

    /** 自动检查间隔（秒）：小于该时间不再查库 */
    const TTL = 120;

    /** 可选的索引模式 */
    const MODES = array(
        'excerpt' => '摘要缓存',
        'full' => '全量缓存',
    );

    /**
     * 索引所在目录
     */
    public static function dir()
    {
        return dirname(__DIR__) . '/cache';
    }

    /**
     * 当前索引模式
     */
    public static function mode()
    {
        $setting = isset($GLOBALS['VOIDSetting']) ? $GLOBALS['VOIDSetting'] : array();
        $mode = isset($setting['searchCacheMode']) ? $setting['searchCacheMode'] : 'excerpt';
        return isset(self::MODES[$mode]) ? $mode : 'excerpt';
    }

    /**
     * 模式的可读名称
     */
    public static function label($mode = null)
    {
        $mode = $mode ? $mode : self::mode();
        return isset(self::MODES[$mode]) ? self::MODES[$mode] : $mode;
    }

    /**
     * 指定模式的索引文件名
     */
    public static function fileFor($mode = null)
    {
        $mode = $mode ? $mode : self::mode();
        return 'search-index-' . (isset(self::MODES[$mode]) ? $mode : 'excerpt') . '.json';
    }

    /**
     * 索引文件绝对路径
     */
    public static function path($mode = null)
    {
        return self::dir() . '/' . self::fileFor($mode);
    }

    /**
     * 索引文件是否存在
     */
    public static function exists($mode = null)
    {
        return is_file(self::path($mode));
    }

    /**
     * 输出索引文件地址（带文件修改时间，便于刷新缓存）
     */
    public static function url()
    {
        echo self::urlString();
    }

    /**
     * 索引文件地址（字符串）
     */
    public static function urlString($mode = null)
    {
        $file = self::path($mode);
        $path = '/cache/' . self::fileFor($mode);
        if (is_file($file)) {
            $path .= '?v=' . filemtime($file);
        }
        return rtrim(Helper::options()->themeUrl, '/') . $path;
    }

    /**
     * 是否启用站内搜索
     */
    public static function enabled()
    {
        $setting = $GLOBALS['VOIDSetting'];
        return !empty($setting['searchEnabled']);
    }

    /**
     * 索引是否为空（没有任何文章）
     */
    public static function isEmpty($mode = null)
    {
        $meta = self::meta($mode);
        return !$meta || empty($meta['count']);
    }

    /**
     * 索引状态摘要，供后台展示
     */
    public static function status($mode = null)
    {
        $meta = self::meta($mode);
        if (!$meta) {
            return null;
        }
        $meta['bytes'] = @filesize(self::path($mode));
        $meta['label'] = self::label($mode);
        return $meta;
    }

    /**
     * 读取索引元信息（只解析文件开头，避免整份 JSON 进内存）
     */
    public static function meta($mode = null)
    {
        if (!self::exists($mode)) {
            return null;
        }
        $head = @file_get_contents(self::path($mode), false, null, 0, 4096);
        if ($head === false) {
            return null;
        }
        $meta = array();
        foreach (array('version', 'built', 'maxModified', 'excerpt', 'count') as $key) {
            if (preg_match('/"' . $key . '":(\d+)/', $head, $match)) {
                $meta[$key] = (int)$match[1];
            }
        }
        if (preg_match('/"mode":"(excerpt|full)"/', $head, $match)) {
            $meta['mode'] = $match[1];
        }
        if (!isset($meta['version'])) {
            return null;
        }
        return $meta;
    }

    /**
     * 生成索引
     *
     * @return array|false 成功返回 ['count'=>条目数, 'bytes'=>文件大小]
     */
    public static function build($mode = null)
    {
        $mode = $mode ? $mode : self::mode();
        if (!isset(self::MODES[$mode])) {
            $mode = 'excerpt';
        }
        $db = Typecho_Db::get();
        $options = Helper::options();

        // 分类与标签
        $metas = array();
        $rows = $db->fetchAll($db->select('table.metas.mid', 'table.metas.type',
                'table.metas.name', 'table.metas.slug')
            ->from('table.metas')
            ->where('table.metas.type = ?', 'category')
            ->orWhere('table.metas.type = ?', 'tag'));
        foreach ($rows as $row) {
            $metas[$row['mid']] = array(
                'type' => $row['type'],
                'name' => (string)$row['name'],
                'slug' => (string)$row['slug'],
                'permalink' => self::route($row['type'], array('slug' => $row['slug']), $options),
            );
        }

        // 内容与分类/标签的关联
        $relations = array();
        if (!empty($metas)) {
            $rows = $db->fetchAll($db->select('table.relationships.cid', 'table.relationships.mid')
                ->from('table.relationships'));
            foreach ($rows as $row) {
                if (isset($metas[$row['mid']])) {
                    $relations[$row['cid']][] = $metas[$row['mid']];
                }
            }
        }

        $data = array(
            'version' => self::VERSION,
            'mode' => $mode,
            'built' => time(),
            'excerpt' => $mode === 'excerpt' ? self::EXCERPT : 0,
            'count' => 0,
            'maxModified' => 0,
            'posts' => array(),
            'pages' => array(),
        );

        foreach (array('post' => 'posts', 'page' => 'pages') as $type => $bucket) {
            $rows = $db->fetchAll($db->select('table.contents.cid', 'table.contents.title',
                    'table.contents.slug', 'table.contents.created', 'table.contents.modified',
                    'table.contents.text')
                ->from('table.contents')
                ->where('table.contents.type = ?', $type)
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.password IS NULL')
                ->order('table.contents.created', Typecho_Db::SORT_DESC));

            foreach ($rows as $row) {
                $text = (string)$row['text'];
                $text = strip_tags($text);
                // 去掉 Markdown 图片与链接的语法，只留可见文字
                $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $text);
                $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $text);
                $text = preg_replace('/\s+/u', ' ', $text);
                $text = trim($text);

                $item = array(
                    'title' => (string)$row['title'],
                    'date' => date('Y-m-d', (int)$row['created']),
                    'path' => self::route($type, array(
                        'cid' => $row['cid'],
                        'slug' => $row['slug'],
                    ), $options),
                    'text' => $mode === 'excerpt'
                        ? mb_substr($text, 0, self::EXCERPT, 'UTF-8')
                        : $text,
                );

                if ($type === 'post') {
                    $item['categories'] = array();
                    $item['tags'] = array();
                    if (!empty($relations[$row['cid']])) {
                        foreach ($relations[$row['cid']] as $meta) {
                            $entry = array(
                                'name' => $meta['name'],
                                'slug' => $meta['slug'],
                                'permalink' => $meta['permalink'],
                            );
                            if ($meta['type'] === 'category') {
                                $item['categories'][] = $entry;
                            } else {
                                $item['tags'][] = $entry;
                            }
                        }
                    }
                }

                $data[$bucket][] = $item;
                $data['maxModified'] = max($data['maxModified'],
                    (int)$row['modified'], (int)$row['created']);
            }
        }

        $data['count'] = count($data['posts']) + count($data['pages']);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $target = self::path($mode);
        $tmp = $target . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, $json) === false) {
            return false;
        }
        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            return false;
        }

        return array(
            'count' => $data['count'],
            'bytes' => strlen($json),
            'mode' => $mode,
        );
    }

    /**
     * 按需重建：索引缺失、过期或内容有更新时重建
     */
    public static function refresh()
    {
        $mode = self::mode();
        $path = self::path($mode);

        if (is_file($path) && time() - filemtime($path) < self::TTL) {
            return;
        }

        $lock = @fopen(self::dir() . '/.search.lock', 'c');
        if (!$lock) {
            return;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return;
        }

        try {
            if (!is_file($path) || self::isStale($path, $mode)) {
                self::build($mode);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * 索引是否落后于数据库
     */
    private static function isStale($path, $mode)
    {
        $meta = self::meta($mode);
        if (!$meta || !isset($meta['version']) || $meta['version'] !== self::VERSION) {
            return true;
        }
        if (!isset($meta['mode']) || $meta['mode'] !== $mode) {
            return true;
        }

        $db = Typecho_Db::get();
        $latest = 0;

        $row = $db->fetchRow($db->select('table.contents.created')
            ->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.password IS NULL')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit(1));
        if ($row) {
            $latest = max($latest, (int)$row['created']);
        }

        $row = $db->fetchRow($db->select('table.contents.modified')
            ->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.password IS NULL')
            ->order('table.contents.modified', Typecho_Db::SORT_DESC)
            ->limit(1));
        if ($row) {
            $latest = max($latest, (int)$row['modified']);
        }

        return $latest > (int)$meta['maxModified'];
    }

    /**
     * 反解析路由，失败时退回首页地址
     */
    private static function route($type, array $value, $options)
    {
        try {
            $url = \Typecho\Router::url($type, $value);
            if (strpos($url, '{') === false) {
                return $url;
            }
        } catch (\Exception $e) {
            // 路由不存在，退回首页
        } catch (\Throwable $e) {
            // 同上
        }
        return $options->index;
    }
}
