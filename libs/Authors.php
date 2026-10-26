<?php
/**
 * Authors.php
 *
 * 作者列表工具
 *
 * 原实现依赖向 Typecho 核心新增的 Widget\Users\AuthorList（var/Widget/Users/AuthorList.php），
 * 属于对核心的侵入式修改。这里改为主题自带的普通工具类，不依赖任何 Widget，
 * 因此无需改动 Typecho 核心即可使用。
 *
 * @package Typecho-Theme-VOID
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Authors
{
    /**
     * 获取作者列表
     *
     * 返回顺序与后台成员列表一致（按 uid 升序）。permalink 由 Typecho 的
     * author 路由生成，与官方 Widget\Base\Users::filter() 中的做法保持一致；
     * 若站点未启用 author 路由则退化为 '#'。
     *
     * @param int|null $limit 限制条数，null 表示不限
     * @return array 每项包含 uid / name / screenName / permalink
     */
    public static function all($limit = null)
    {
        $db = Typecho_Db::get();

        $select = $db->select('uid', 'name', 'screenName')
            ->from('table.users')
            ->order('table.users.uid', Typecho_Db::SORT_ASC);

        if (!empty($limit)) {
            $select->limit(intval($limit));
        }

        $rows = $db->fetchAll($select);
        if (empty($rows)) {
            return array();
        }

        $index = Helper::options()->index;
        $routeExists = (null != \Typecho\Router::get('author'));

        foreach ($rows as $key => $row) {
            // screenName 为空时回退到用户名，避免下拉里出现空白项
            if (empty($row['screenName'])) {
                $row['screenName'] = $row['name'];
            }

            $row['permalink'] = $routeExists
                ? \Typecho\Router::url('author', $row, $index)
                : '#';

            $rows[$key] = $row;
        }

        return $rows;
    }

    /**
     * 作者数量
     *
     * @return int
     */
    public static function count()
    {
        $db = Typecho_Db::get();

        return intval($db->fetchObject($db->select(array('COUNT(uid)' => 'num'))
            ->from('table.users'))->num);
    }
}
