<?php
/**
 * 外观设置页的整理层
 *
 * 只在「设置外观」页面输出，用卡片与分组标题把 Typecho 原生表单重新组织一遍。
 * 不改变任何 input 的 name / 顺序语义，提交仍然由 Typecho 核心处理。
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
?>
<style>
    html.void-cfg-pending form.typecho-form {
        visibility: hidden;
    }

    form.void-cfg {
        counter-reset: void-cfg;
    }

    .void-cfg-group {
        margin: 0 0 26px;
    }

    .void-cfg-head {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 0 10px;
        margin: 0 0 10px;
        padding: 0 2px;
    }

    .void-cfg-num {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .08em;
        color: #bd6262;
        font-variant-numeric: tabular-nums;
    }

    .void-cfg-title {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #262626;
        line-height: 1.3;
    }

    .void-cfg-sub {
        flex: 1 1 100%;
        margin: 2px 0 0;
        font-size: 12px;
        color: #9a9a9a;
        line-height: 1.6;
    }

    .void-cfg-group > ul.typecho-option {
        background: #fff;
        border: 1px solid #e9e9e9;
        border-radius: 8px;
        padding: 15px 18px;
        margin: 0 0 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .03);
        transition: border-color .2s, box-shadow .2s;
    }

    .void-cfg-group > ul.typecho-option:hover {
        border-color: #dcdcdc;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .05);
    }

    .void-cfg-group > ul.typecho-option > li {
        padding: 0;
    }

    .void-cfg-group .typecho-label {
        display: block;
        margin: 0 0 8px;
        font-size: 13.5px;
        font-weight: 600;
        color: #333;
    }

    .void-cfg-group .description {
        margin: 8px 0 0;
        font-size: 12px;
        line-height: 1.7;
        color: #8a8a8a;
        word-break: break-word;
    }

    .void-cfg-group .description code {
        padding: 1px 4px;
        border-radius: 3px;
        background: #f4f4f4;
        color: #666;
        font-size: 11.5px;
        word-break: break-all;
    }

    .void-cfg-group input[type="text"],
    .void-cfg-group textarea {
        width: 100%;
        box-sizing: border-box;
    }

    .void-cfg-group ul.typecho-option li > ul li {
        margin-bottom: 4px;
    }

    .void-cfg-group .description a.btn {
        display: inline-block;
        height: 32px;
        line-height: 32px;
        padding: 0 14px;
        margin-top: 8px;
        font-size: 14px;
        text-align: center;
        vertical-align: middle;
    }

    form.void-cfg > ul.typecho-option.void-cfg-submit {
        margin-top: 0;
        background: transparent;
        border: 0;
        box-shadow: none;
        padding: 0;
    }
</style>
<script>
    (function () {
        var GROUPS = [
            {key: 'look', title: '外观', sub: '首页头图、标题、配色与版式。'},
            {key: 'nav', title: '导航栏', sub: '在现有导航前后追加自定义菜单（JSON）。'},
            {key: 'search', title: '搜索', sub: '主题内置的即时搜索，以及索引的生成方式。'},
            {key: 'ajax', title: '无刷新跳转', sub: '页面切换使用哪种引擎，以及切换后要执行的脚本。'},
            {key: 'content', title: '正文样式', sub: '文章页的提示、字体与内容增强。'},
            {key: 'other', title: '其他', sub: '统计代码、备案号等零散设置。'}
        ];

        var MAP = {
            '导航栏前置自定义菜单': 'nav',
            '导航栏后置自定义菜单': 'nav',
            '顶部大图（随机图池）': 'look',
            '顶部大图模糊度': 'look',
            '首页顶部大标题': 'look',
            '首页顶部小标题': 'look',
            '首页列表悬停色': 'look',
            '主题颜色模式': 'look',
            '首页版式': 'look',
            '打赏二维码': 'content',
            '即时搜索': 'search',
            '搜索索引缓存': 'search',
            'swup/PJAX 选项': 'ajax',
            'swup/PJAX 重载函数': 'ajax',
            '文章过时提醒': 'content',
            '过时提醒阈值（天）': 'content',
            '彩蛋文章提示内容': 'content',
            '文章内容使用衬线体': 'content',
            '图片懒加载': 'content',
            '启用数学公式解析': 'content',
            'head 标签输出内容': 'other',
            'footer 标签输出内容': 'other',
            '自定义 Service Worker': 'other',
            '超高级设置': 'other'
        };

        document.documentElement.classList.add('void-cfg-pending');

        function pad(n) {
            return n < 10 ? '0' + n : String(n);
        }

        function build() {
            var forms = document.querySelectorAll('form');
            var form = null;

            for (var i = 0; i < forms.length; i++) {
                if (forms[i].querySelector('ul.typecho-option')) {
                    form = forms[i];
                    break;
                }
            }

            if (!form) {
                return;
            }

            var all = [].slice.call(form.querySelectorAll(':scope > ul.typecho-option'));
            var submit = null;
            var items = [];

            all.forEach(function (ul) {
                if (ul.querySelector('input[type="submit"], button[type="submit"], button.btn') && !ul.querySelector('.typecho-label')) {
                    submit = ul;
                } else {
                    items.push(ul);
                }
            });

            if (!items.length) {
                return;
            }

            form.classList.add('void-cfg');

            var buckets = {};
            GROUPS.forEach(function (g) {
                buckets[g.key] = [];
            });

            items.forEach(function (ul) {
                var label = ul.querySelector('.typecho-label');
                var text = label ? label.textContent.replace(/\s+/g, ' ').trim() : '';
                var key = MAP[text] || 'other';
                buckets[key].push(ul);
            });

            var wrap = document.createElement('div');
            wrap.className = 'void-cfg-wrap';
            form.insertBefore(wrap, form.firstChild);

            GROUPS.forEach(function (g, index) {
                var list = buckets[g.key];

                if (!list.length) {
                    return;
                }

                var block = document.createElement('div');
                block.className = 'void-cfg-group';
                block.setAttribute('data-void-group', g.key);

                var head = document.createElement('div');
                head.className = 'void-cfg-head';
                head.innerHTML = '<span class="void-cfg-num">' + pad(index + 1) + '</span>'
                    + '<h3 class="void-cfg-title"></h3>'
                    + '<p class="void-cfg-sub"></p>';
                head.querySelector('.void-cfg-title').textContent = g.title;
                head.querySelector('.void-cfg-sub').textContent = g.sub;
                block.appendChild(head);

                wrap.appendChild(block);
                list.forEach(function (ul) {
                    block.appendChild(ul);
                });
            });

            if (submit) {
                submit.classList.add('void-cfg-submit');
                form.appendChild(submit);
            }
        }

        function reveal() {
            document.documentElement.classList.remove('void-cfg-pending');
        }

        function start() {
            try {
                build();
            } catch (e) {
                if (window.console && console.warn) {
                    console.warn('[VOID] 外观设置分组失败', e);
                }
            }
            reveal();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }

        setTimeout(reveal, 1500);
    })();
</script>
