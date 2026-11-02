/* eslint-disable no-unused-vars */
/* eslint-disable linebreak-style */
/* eslint-disable no-undef */
/* eslint-disable no-console */
// VOID
// Author: 熊猫小A
// Link: https://blog.imalan.cn/archives/247/

console.log(' %c Theme VOID %c https://blog.imalan.cn/archives/247/ ', 'color: #fadfa3; background: #23b7e5; padding:5px;', 'background: #1c2b36; padding:5px;');

/**
 * jQuery 兼容保护
 *
 * 有些插件会在主题之后再次加载一份自己的 jQuery（例如 ExSearch 的
 * 「引入 JQuery」选项），把 window.jQuery / window.$ 整个替换掉。
 * jQuery 插件是挂在具体实例上的，替换后 masonry / pjax / fancybox
 * 在本页面上全部消失，首页卡片会停留在 opacity: 0 的初始态，
 * 看起来就像「白屏」。
 *
 * 这里在主题插件都注册完之后留一份引用；运行时若发现全局 jQuery 被
 * 换掉，就把全局指回主题实例（masonry / pjax / fancybox 等插件都挂在
 * 它上面），并在控制台留一条提示。补不回来时再降级处理。
 */
var VOID_JQ = {
    cache: null,
    restored: false,

    // 记录主题所用 jQuery 实例（本文件在 bundle.js 之后加载，
    // 此时 masonry / pjax / fancybox / hyphenate 等插件都已注册）
    snapshot: function () {
        var jq = window.jQuery;
        if (!jq || !jq.fn) {
            return;
        }
        VOID_JQ.cache = {
            version: jq.fn.jquery,
            jq: jq
        };
    },

    // 若全局 jQuery 被其他插件替换，恢复为主题所用实例
    ensure: function () {
        var jq = window.jQuery;
        if (!jq || !jq.fn) {
            return false;
        }
        var mine = VOID_JQ.cache && VOID_JQ.cache.jq;
        if (mine && mine !== jq) {
            window.jQuery = mine;
            window.$ = mine;
            if (!VOID_JQ.restored) {
                VOID_JQ.restored = true;
                VOID_JQ.warn('检测到全局 jQuery 被其他插件覆盖（' + jq.fn.jquery + '），已恢复为主题使用的 ' + mine.fn.jquery + '。');
            }
        }
        return true;
    },

    // 当前全局 jQuery 是否具备某个插件
    has: function (name) {
        var jq = window.jQuery;
        if (!jq) {
            return false;
        }
        if (name === 'fancybox') {
            return !!(jq.fancybox && typeof jq.fancybox.open === 'function');
        }
        return !!(jq.fn && typeof jq.fn[name] === 'function');
    },

    warn: function (message) {
        if (window.console && console.warn) {
            console.warn('[VOID] ' + message);
        }
    }
};

VOID_JQ.snapshot();

var VOID_Content = {
    countWords: function () {
        if ($('#totalWordCount').length) {
            var total = 0;
            $.each($('a.archive-title'), function (i, item) {
                total += parseInt($(item).attr('data-words'));
            });
            $('#totalWordCount').html(total);
        }
    },

    // 解析文章目录
    parseTOC: function () {
        if ($('.TOC').length > 0) {
            var toc_option = {
                // Where to render the table of contents.
                tocSelector: '.TOC',
                // Where to grab the headings to build the table of contents.
                contentSelector: 'div.articleBody',
                // Which headings to grab inside of the contentSelector element.
                headingSelector: 'h2, h3, h4, h5',
                // 收缩深度
                collapseDepth: 6
            };
            tocbot.init(toc_option);
            $.each($('.toc-link'), function(i, item){
                $(item).click(function(){
                    VOID_SmoothScroller.scrollTo($(this).attr('href'), -60);
                    if(window.innerWidth < 1200) {
                        TOC.close();
                    }
                    return false;
                });
            });
            // 检查目录
            if(window.innerWidth >= 1200) {
                TOC.open();
            } 
        }
    },

    // 解析照片集
    parsePhotos: function () {
        $.each($('div.articleBody figure:not(.size-parsed)'), function (i, item){
            var img = new Image();
            img.onload = function () {
                var w = parseFloat(img.width);
                var h = parseFloat(img.height);
                $(item).addClass('size-parsed');
                $(item).css('width', w + 'px');
                $(item).css('flex-grow', w * 50 / h);
                $(item).find('a').css('padding-top', h / w * 100 + '%');
            };
            img.src = $(item).find('img').attr('data-src');
        });
    },

    // 处理友链列表
    parseBoardThumbs: function () {
        $.each($('.board-thumb'), function(i, item) {
            if (VOIDConfig.lazyload) {
                if (VOIDConfig.browserLevelLoadingLazy) {
                    $(item).html('<img class="lazyload browserlevel-lazy" src="' +$(item).attr('data-thumb')+ '" loading="lazy">');
                } else {
                    $(item).html('<img class="lazyload" data-src="' +$(item).attr('data-thumb')+ '">');
                }
            } else {
                $(item).html('<img src="' +$(item).attr('data-thumb')+ '">');
            }
        });
    },

    // 解析URL
    parseUrl: function () {
        var domain = document.domain;
        $('a:not(a[href^="#"]):not(".post-like")').each(function (i, item) {
            /*
             * javascript: 与空链接不是真实超链接，其 hostname 为空串，
             * 若继续按「hostname != domain」处理会被误判为外链并加上
             * target="_blank"，点击后新开一个执行 javascript:void(0) 的空白页。
             */
            if (!item.href || item.protocol === 'javascript:') {
                return;
            }
            if ((!$(item).attr('target') || (!$(item).attr('target') == '' && !$(item).attr('target') == '_self'))) {
                if (item.hostname != domain) {
                    $(item).attr('target', '_blank');
                }
            }
        });

        VOID_Nav.attach();
    },

    highlight: function () {
        $.each($('.yue pre code'), function (i, item) {
            var classStr = $(item).attr('class');

            if (typeof(classStr) == 'undefined') {
                classStr = 'language-none';
            }

            if (classStr.indexOf('lang') == -1) {
                classStr += ' language-none';
            }

            $(item).attr('class', classStr);
        });
        
        Prism.highlightAll();
    },

    // 初始化注脚（littlefoot，原 bigfoot.js 的替代）
    footnotes: function () {
        // littlefoot 的 UMD 导出为 { default: fn }，兼容两种取法
        var littlefoot = window.littlefoot;
        if (littlefoot && typeof littlefoot.default === 'function') {
            littlefoot = littlefoot.default;
        }
        if (typeof littlefoot !== 'function') {
            return;
        }
        littlefoot({
            activateOnHover: false,
            hoverDelay: 250
        });

        // littlefoot 内置的按钮提示为英文（See Footnote N / Footnote N），此处本地化
        $('.littlefoot__button').each(function () {
            var num = $(this).attr('data-footnote-id');
            if (!num) {
                return;
            }
            $(this).attr('title', '查看注脚 ' + num);
            var caption = this.querySelector('title');
            if (caption) {
                caption.textContent = '注脚 ' + num;
            }
        });
    },

    pangu: function () {
        pangu.spacingElementByTagName('p');
    },

    math: function () {
        if (VOIDConfig.enableMath && typeof MathJax !== 'undefined') {
            MathJax.Hub.Config({
                tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}
            });
            MathJax.Hub.Queue(['Typeset', MathJax.Hub]);
        }
    },

    hyphenate: function() {
        $('div.articleBody p, div.articleBody blockquote').hyphenate('en-us');
    }
};

/*
 * 页面跳转：优先 Swup，其次回退到 jQuery.pjax，最后退化为整页跳转。
 * 所有「主题自己发起的跳转」（搜索页等）都应走 VOID_Nav.navigate()，
 * 这样两种引擎都能接管。
 */
var VOID_Nav = {
    mode: null,       // 'swup' | 'pjax' | null
    swup: null,
    attached: false,
    positions: {},    // 各页面的滚动位置，用于后退时恢复
    pendingRestore: null,

    // 当前是否支持无刷新跳转
    available: function () {
        return this.mode !== null;
    },

    /*
     * 判断某个链接是否应该交给浏览器整页处理。
     * 返回 true = 不拦截。
     */
    ignored: function (url, context) {
        var el = context && context.el;

        if (el) {
            if ($(el).attr('no-pjax') !== undefined || el.closest('[data-no-swup]')) {
                return true;
            }
            if (el.hasAttribute('download')) {
                return true;
            }
            var target = el.getAttribute('target');
            if (target && target !== '_self' && target !== '_top') {
                return true;
            }
        }

        var href = url.href || '';
        if (href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
            return true;
        }

        // 页内锚点交给浏览器原生滚动（目录、脚注等）
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search && !context) {
            return true;
        }
        if (el && el.getAttribute('href') && el.getAttribute('href').charAt(0) === '#') {
            return true;
        }

        return false;
    },

    // 绑定一次引擎与生命周期事件
    attach: function () {
        if (this.attached) {
            return;
        }
        this.attached = true;

        var mode = VOIDConfig.ajaxMode || (VOIDConfig.PJAX ? 'swup' : 'off');
        if (mode !== 'swup' && mode !== 'pjax') {
            return;
        }

        var self = this;

        // 选了 Swup 但脚本没加载出来，退到 jQuery.pjax
        if (mode === 'swup' && !window.Swup) {
            VOID_JQ.warn('Swup 未能加载，已回退到 jQuery.pjax。');
            mode = 'pjax';
        }

        if (mode === 'swup') {
            this.swup = new Swup({
                containers: ['#pjax-container'],
                linkSelector: 'a[href]',
                timeout: 8000,
                cache: true,
                requestHeaders: {
                    'X-Requested-With': 'swup',
                    'Accept': 'text/html, application/xhtml+xml',
                    'X-PJAX': '1'
                },
                ignoreVisit: function (url, context) {
                    return self.ignored(url, context);
                }
            });
            this.mode = 'swup';

            this.swup.hooks.on('visit:start', function (visit) {
                if (visit.history && visit.history.popstate) {
                    // 后退/前进：先记下即将离开页面的位置，再恢复目标页面的位置
                    visit.scroll.reset = false;
                    var leaving = visit.from && visit.from.url;
                    if (leaving) {
                        self.positions[leaving] = window.scrollY;
                    }
                    var landing = visit.to && visit.to.url;
                    self.pendingRestore = (landing && self.positions[landing] != null)
                        ? self.positions[landing]
                        : null;
                } else {
                    // 普通跳转：记住当前页面的位置
                    var current = visit.from && visit.from.url;
                    if (current) {
                        self.positions[current] = window.scrollY;
                    }
                    self.pendingRestore = null;
                }
                VOID.beforePjax();
            });

            this.swup.hooks.on('content:replace', function () {
                VOID.afterPjax();
            });

            this.swup.hooks.on('page:view', function () {
                if (self.pendingRestore === null) {
                    return;
                }
                var y = self.pendingRestore;
                self.pendingRestore = null;
                // 等一帧，让 Masonry 等布局完成后再定位
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        window.scrollTo(0, y);
                    });
                });
            });

            this.swup.hooks.on('visit:end', function () {
                VOID.endPjax();
            });

            this.swup.hooks.on('visit:fail', function (visit, args) {
                NProgress.done();
                if (!visit || visit.aborted || !visit.to || !visit.to.url) {
                    return;
                }
                /*
                 * 目标页面无法被接管时（404、服务端异常、容器缺失等），
                 * 退回整页加载，避免停在空白标题 / 半渲染状态。
                 */
                if (args && args.error) {
                    window.location.href = visit.to.url;
                }
            });

            // 让依赖 PJAX 的插件在 Swup 下也能工作
            this.installPjaxCompat();

            return;
        }

        // 回退：jQuery.pjax
        VOID_JQ.ensure();
        if (!VOID_JQ.has('pjax')) {
            VOID_JQ.warn('PJAX 不可用（jQuery 插件被其他插件覆盖且无法恢复），已退化为普通跳转。');
            this.mode = null;
            return;
        }

        this.mode = 'pjax';

        var domain = document.domain;
        $.each($('a:not(a[target="_blank"], a[no-pjax])'), function (i, item) {
            if (item.hostname == domain) {
                $(item).addClass('pjax');
            }
        });

        $(document).pjax('a.pjax', {
            container: '#pjax-container',
            fragment: '#pjax-container',
            timeout: 8000
        });

        $(document).on('pjax:send', function () {
            VOID.beforePjax();
        });

        $(document).on('pjax:complete', function () {
            VOID.afterPjax();
        });

        $(document).on('pjax:end', function () {
            VOID.endPjax();
        });
    },

    /*
     * 兼容依赖 PJAX 的插件：把它们的 $.pjax(...) 调用转交给 Swup，
     * 并在生命周期里补发 pjax:* 事件，使监听这些事件的插件照常工作。
     */
    installPjaxCompat: function () {
        var self = this;
        var jq = window.jQuery;

        if (!jq || !jq.pjax) {
            return;
        }

        var original = jq.pjax;

        jq.pjax = function (options) {
            var url = typeof options === 'string' ? options : (options && options.url);
            if (url && self.swup) {
                self.swup.navigate(url);
                return jq;
            }
            return original.apply(jq, arguments);
        };

        jq.pjax.defaults = original.defaults;
        jq.pjax.reload = function () {
            if (self.swup) {
                self.swup.reload();
            }
        };
    },

    // 主题主动发起的跳转
    navigate: function (url) {
        this.attach();

        if (this.mode === 'swup' && this.swup) {
            this.swup.navigate(url);
            return;
        }
        if (this.mode === 'pjax' && VOID_JQ.has('pjax')) {
            $.pjax({
                url: url,
                container: '#pjax-container',
                fragment: '#pjax-container',
                timeout: 8000
            });
            return;
        }
        window.open(url, '_self');
    }
};

var VOID = {
    // 初始化单页应用
    init: function () {
        /* 若 jQuery 被其他插件替换，先恢复主题实例 */
        VOID_JQ.ensure();

        /* 初始化 UI */
        VOID_Ui.checkHeader();
        VOID_Ui.MasonryCtrler.init();
        VOID_Ui.DarkModeSwitcher.checkColorScheme();
        VOID_Ui.checkScrollTop();
        VOID_Content.parseBoardThumbs();
        VOID_Ui.lazyload();
        VOID_Ui.headroom();

        VOID_Content.countWords();
        VOID_Content.parseTOC();
        VOID_Content.parsePhotos();
        VOID_Content.highlight();
        VOID_Content.parseUrl();
        VOID_Content.pangu();
        VOID_Content.footnotes();
        VOID_Content.math();
        VOID_Content.hyphenate();
        
        VOID_Vote.reload();
        Share.init();
        AjaxComment.init();
        VOID_Search.init();

        $('body').on('click', function (e) {
            if (!VOID_Util.clickIn(e, '#toggle-setting-pc') && !VOID_Util.clickIn(e, '#toggle-setting')) {
                if ($('body').hasClass('setting-panel-show') && !VOID_Util.clickIn(e, '#setting-panel')) {
                    $('body').removeClass('setting-panel-show');
                    setTimeout(function () {
                        $('#setting-panel').hide();
                    }, 300);
                    return false;
                }
            }
        });
    },

    // PJAX 开始前
    beforePjax: function () {
        if (VOID_Nav.mode === 'swup') {
            $(document).trigger('pjax:send');
        }
        NProgress.start();
        VOID_Ui.reset();
    },

    // PJAX 结束后
    afterPjax: function () {
        VOID_JQ.ensure();
        NProgress.done();

        /*
         * 片段响应里没有 <head><title>，标题放在容器内隐藏的 <title> 中，
         * 这里取出来补到 document.title（Swup 只替换容器）。
         */
        var $fragTitle = $('#pjax-container title').first();
        if ($fragTitle.length) {
            var fragTitle = $.trim($fragTitle.text());
            if (fragTitle) {
                document.title = fragTitle;
            }
        }

        VOID_Content.parseBoardThumbs();

        if ($('#loggin-form').length) {
            $('#loggin-form').addClass('need-refresh');
        }

        VOID_Ui.MasonryCtrler.init();
        VOID_Ui.lazyload();
        
        VOID_Ui.checkScrollTop();
        VOID_Ui.checkHeader();
        VOID_Content.countWords();
        VOID_Content.parseTOC();
        VOID_Content.parsePhotos();
        VOID_Content.parseUrl();
        VOID_Content.highlight();
        VOID_Content.math();
        VOID_Content.hyphenate();
        VOID_Content.pangu();
        VOID_Content.footnotes();

        VOID_Vote.reload();

        // 重载表情：由 OwO 插件负责，主题不再介入
        Share.init();

        AjaxComment.init();

        // 用户自定义的「切换后执行的 JS」
        if (typeof window.VOID_PJAX_RELOAD === 'function') {
            try {
                window.VOID_PJAX_RELOAD();
            } catch (e) {
                console.warn('[VOID] pjaxreload 执行出错：' + e.message);
            }
        }

        // 供依赖 PJAX 事件的插件使用
        if (VOID_Nav.mode === 'swup') {
            $(document).trigger('pjax:complete');
        }
    },

    endPjax: function () {
        if (VOID_Nav.mode === 'swup') {
            $(document).trigger('pjax:end');
        }
        if ($('.TOC').length < 1) {	
            TOC.close();
        }
    },

    alert: function (content, time) {
        var errTemplate = '<div class="msg" id="msg{id}">{Text}</div>';
        var id = new Date().getTime();
        $('body').prepend(errTemplate.replace('{Text}', content).replace('{id}', id));
        $.each($('.msg'), function (i, item) {
            if ($(item).attr('id') != 'msg' + id) {
                $(item).css('top', $(item).offset().top - $(document).scrollTop() + $('.msg#msg' + id).outerHeight() + 20 + 'px');
            }
        });
        $('.msg#msg' + id).addClass('show');
        var t = time;
        if (typeof (t) != 'number') {
            t = 2500;
        }
        setTimeout(function () {
            $('.msg#msg' + id).addClass('hide');
            setTimeout(function () {
                $('.msg#msg' + id).remove();
            }, 1000);
        }, t);
    },

    startSearch: function (item) {
        var c = $(item).val();
        $(item).val('');
        $(item).blur();
        if (!c || c == '') {
            $(item).attr('placeholder', '你还没有输入任何信息');
            return;
        }
        VOID_Nav.navigate(VOIDConfig.searchBase + c);
    },
    
    enterSearch: function (item) {
        var event = window.event || arguments.callee.caller.arguments[0];
        if (event.keyCode == 13) {
            VOID.startSearch(item);
        }
    }
};

var VOID_Search = {
    index: null,
    loading: false,
    links: [],
    active: -1,
    openedModal: false,

    init: function () {
        if (!VOIDConfig.searchEnabled) return;
        var self = this;

        $(document).on('input', '#void-search-input', function () {
            self.search($(this).val());
        });

        $(document).on('keydown', '#void-search-input', function (e) {
            if (e.keyCode === 27) {
                self.close();
                e.preventDefault();
            } else if (e.keyCode === 38) {
                self.move(-1);
                e.preventDefault();
            } else if (e.keyCode === 40) {
                self.move(1);
                e.preventDefault();
            } else if (e.keyCode === 13) {
                self.enter();
                e.preventDefault();
            }
        });

        $(document).on('mouseenter', '.void-search-results a', function () {
            self.setActive(parseInt($(this).attr('data-index'), 10));
        });
    },

    // 打开搜索层
    open: function () {
        if (!VOIDConfig.searchEnabled) return;
        var $search = $('#void-search');
        if (!$search.length) return;

        this.links = [];
        this.active = -1;
        $('#void-search-input').val('');
        this.render('<div class="void-search-hint">输入关键词开始搜索</div>');

        $search.prop('hidden', false);
        window.setTimeout(function () {
            $search.addClass('show');
        }, 10);

        if (!this.openedModal) {
            VOID_Ui.openModal();
            this.openedModal = true;
        }
        setTimeout(function () {
            $('#void-search-input').focus();
        }, 30);

        this.load();
    },

    // 关闭搜索层
    close: function () {
        var $search = $('#void-search');
        if (!$search.length || $search.prop('hidden')) return;

        $search.removeClass('show');
        window.setTimeout(function () {
            if (!$search.hasClass('show')) $search.prop('hidden', true);
        }, 200);

        if (this.openedModal) {
            VOID_Ui.closeModal();
            this.openedModal = false;
        }
    },

    // 懒加载索引
    load: function () {
        if (this.index || this.loading) return;
        var self = this;
        this.loading = true;

        $.getJSON(VOIDConfig.searchIndex)
            .done(function (data) {
                self.loading = false;
                self.index = data;
                self.search($('#void-search-input').val());
            })
            .fail(function () {
                self.loading = false;
                self.render('<div class="void-search-hint">搜索索引加载失败，请稍后重试。</div>');
            });
    },

    // 使用主题自带的搜索页
    gotoSearchPage: function () {
        var $input = $('#void-search-input');
        var value = $.trim($input.val() || '');
        if (!value) {
            $input.focus();
            return;
        }
        var url = VOIDConfig.searchBase + encodeURIComponent(value);
        this.close();
        VOID_Nav.navigate(url);
    },

    // 回车：有选中项则跳转该项，否则跳转搜索页
    enter: function () {
        if (this.active >= 0 && this.links[this.active]) {
            var target = this.links[this.active];
            this.close();
            window.location.href = target;
        } else {
            this.gotoSearchPage();
        }
    },

    move: function (step) {
        if (!this.links.length) return;
        var next = this.active + step;
        if (next < 0) next = this.links.length - 1;
        if (next >= this.links.length) next = 0;
        this.setActive(next);
    },

    setActive: function (index) {
        this.active = index;
        $('.void-search-results a').removeClass('active');
        var $link = $('.void-search-results a[data-index="' + index + '"]');
        $link.addClass('active');

        var scroll = $('.void-search-results')[0];
        if ($link.length && scroll) {
            var top = $link[0].offsetTop;
            if (top < scroll.scrollTop || top + $link.outerHeight() > scroll.scrollTop + scroll.clientHeight) {
                scroll.scrollTop = top - scroll.clientHeight / 2;
            }
        }
    },

    render: function (html) {
        $('#void-search-results').html(html);
    },

    search: function (query) {
        if (!this.index) return;
        var text = $.trim(String(query || ''));
        if (!text) {
            this.links = [];
            this.active = -1;
            this.render('<div class="void-search-hint">输入关键词开始搜索</div>');
            return;
        }

        var words = text.toUpperCase().split(/\s+/).filter(function (word) {
            return word.length > 0;
        });

        var posts = this.collect(this.index.posts || [], words);
        var pages = this.collect(this.index.pages || [], words);
        var categories = this.collectMeta(this.index.posts || [], words, 'categories');
        var tags = this.collectMeta(this.index.posts || [], words, 'tags');

        var self = this;
        var index = 0;
        var links = [];

        function section(title, items) {
            if (!items.length) return '';
            return '<section class="void-search-section"><div class="void-search-section-title">'
                + title + '</div>' + items.join('') + '</section>';
        }

        var postItems = posts.map(function (entry) {
            var item = entry.item;
            links.push(item.path);
            return self.buildItem(item.path, self.highlight(item.title, words),
                self.escapeHtml(item.date), self.highlight(self.excerpt(item.text, words), words), index++);
        });

        var pageItems = pages.map(function (entry) {
            var item = entry.item;
            links.push(item.path);
            return self.buildItem(item.path, self.highlight(item.title, words),
                self.escapeHtml(item.date), self.highlight(self.excerpt(item.text, words), words), index++);
        });

        var categoryItems = categories.map(function (meta) {
            links.push(meta.permalink);
            return self.buildItem(meta.permalink, self.highlight(meta.name, words), '', '', index++);
        });

        var tagItems = tags.map(function (meta) {
            links.push(meta.permalink);
            return self.buildItem(meta.permalink, self.highlight(meta.name, words), '', '', index++);
        });

        var html = section('文章', postItems)
            + section('页面', pageItems)
            + section('分类', categoryItems)
            + section('标签', tagItems);

        if (!html) {
            if (VOIDConfig.searchCacheMode === 'full') {
                html = '<div class="void-search-hint">没有找到与「' + this.escapeHtml(text) + '」相关的内容</div>';
            } else {
                html = '<div class="void-search-hint">未搜索到摘要包含“' + this.escapeHtml(text)
                    + '”的结果。<br>点击“搜索”以查看更多结果。</div>';
            }
        }

        this.links = links;
        this.active = -1;
        this.render(html);
    },

    buildItem: function (href, title, meta, excerpt, index) {
        return '<a class="void-search-item" data-index="' + index + '" href="' + this.escapeHtml(href) + '">'
            + '<span class="void-search-item-title">' + title + '</span>'
            + (meta ? '<span class="void-search-item-meta">' + meta + '</span>' : '')
            + (excerpt ? '<span class="void-search-item-excerpt">' + excerpt + '</span>' : '')
            + '</a>';
    },

    collect: function (items, words) {
        var out = [];
        items.forEach(function (item) {
            var title = (item.title || '').toUpperCase();
            var body = (item.text || '').toUpperCase();
            var hay = title + ' ' + body;

            for (var i = 0; i < words.length; i++) {
                if (hay.indexOf(words[i]) === -1) return;
            }

            var score = 0;
            words.forEach(function (word) {
                if (title.indexOf(word) !== -1) score += 3;
                if (body.indexOf(word) !== -1) score += 1;
            });
            out.push({ item: item, score: score });
        });
        out.sort(function (a, b) {
            return b.score - a.score;
        });
        return out;
    },

    collectMeta: function (posts, words, key) {
        var seen = {};
        var out = [];
        posts.forEach(function (post) {
            (post[key] || []).forEach(function (meta) {
                if (seen[meta.name]) return;
                var hay = ((meta.name || '') + ' ' + (meta.slug || '')).toUpperCase();
                var matched = words.every(function (word) {
                    return hay.indexOf(word) !== -1;
                });
                if (matched) {
                    seen[meta.name] = true;
                    out.push(meta);
                }
            });
        });
        return out;
    },

    highlight: function (text, words) {
        text = String(text || '');
        var lower = text.toLowerCase();
        var ranges = [];
        var self = this;

        words.forEach(function (word) {
            var needle = word.toLowerCase();
            var from = 0;
            var at;
            while ((at = lower.indexOf(needle, from)) !== -1) {
                ranges.push([at, at + needle.length]);
                from = at + needle.length;
            }
        });

        if (!ranges.length) return self.escapeHtml(text);

        ranges.sort(function (a, b) {
            return a[0] - b[0];
        });
        var merged = [ranges[0]];
        for (var i = 1; i < ranges.length; i++) {
            var last = merged[merged.length - 1];
            if (ranges[i][0] <= last[1]) {
                last[1] = Math.max(last[1], ranges[i][1]);
            } else {
                merged.push(ranges[i]);
            }
        }

        var html = '';
        var pos = 0;
        merged.forEach(function (range) {
            html += self.escapeHtml(text.slice(pos, range[0]));
            html += '<mark class="search-keyword">' + self.escapeHtml(text.slice(range[0], range[1])) + '</mark>';
            pos = range[1];
        });
        html += self.escapeHtml(text.slice(pos));
        return html;
    },

    excerpt: function (text, words) {
        text = String(text || '');
        if (!text) return '';
        var lower = text.toLowerCase();
        var at = -1;
        words.forEach(function (word) {
            var found = lower.indexOf(word.toLowerCase());
            if (found !== -1 && (at === -1 || found < at)) at = found;
        });
        var start = at === -1 ? 0 : Math.max(0, at - 20);
        var slice = text.substr(start, 90);
        return (start > 0 ? '…' : '') + slice + (start + 90 < text.length ? '…' : '');
    },

    escapeHtml: function (str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
};

var VOID_Vote = {
    vote: function (item) {
        var type = $(item).attr('data-type');
        var id = $(item).attr('data-item-id');
        var table = $(item).attr('data-table');

        var cookieName = 'void_vote_' + table + '_' + type;
        var voted = VOID_Util.getCookie(cookieName);
        if (voted == null) voted = ',';

        // 首先检查本地 cookie
        if (voted.indexOf(',' + id + ',') != -1) {
            $(item).addClass('done');
            VOID.alert('您已经投过票了~');
            return;
        }

        // 当是评论投票时检查是否已经投过另一个选项
        if ($(item).hasClass('comment-vote')) {
            var type_2 = '';
            if (type == 'up') type_2 = 'down';
            else type_2 = 'up';
            if (VOID_Vote.checkVoted(type_2, id, table)) {
                VOID.alert('暂不支持更改投票哦～');
                return;
            }
        }

        $.ajax({
            url: VOIDConfig.votePath + table,
            type: 'POST',
            data: JSON.stringify({
                'id': parseInt(id),
                'type': type
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function (data) {
                if (data.code >= 200 && data.code < 400) {
                    $(item).addClass('done');
                    voted += id + ',';
                    VOID_Util.setCookie(cookieName, voted, 3600 * 24 * 90);
                }
                switch (data.code) {
                case 200:
                    var prev = parseInt($(item).find('.value').text());
                    $(item).find('.value').text(prev + 1);
                    break;
                case 302:
                    VOID.alert('您好像已经投过票了呢～');
                    break;
                case 403:
                    VOID.alert('暂不支持更改投票哦～');
                    break;
                default:
                    break;
                }
            },
            error: function () {
                VOID.alert('投票失败 o(╥﹏╥)o，请稍后重试');
            }
        });
    },

    checkVoted: function (type, id, table) {
        var cookieName = 'void_vote_' + table + '_' + type;
        var voted = VOID_Util.getCookie(cookieName);
        if (voted == null) voted = ',';
        return voted.indexOf(',' + id + ',') != -1;
    },

    reload: function () {
        // 高亮已记录的
        $.each($('.vote-button'), function (i, item) {
            var type = $(item).attr('data-type');
            var id = $(item).attr('data-item-id');
            var table = $(item).attr('data-table');

            if (VOID_Vote.checkVoted(type, id, table)) {
                $(item).addClass('done');
            }
        });
    },

    toggleFoldComment: function (coid, item) {
        var sel = '#comment-'+String(coid);
        $(sel).toggleClass('fold');
        if ($(sel).hasClass('fold')) {
            $(item).text('点击展开');
        } else {
            $(item).text('还是叠上吧');
        }
    },
};

var Share = {
    parseItem: function (item) {
        item = $(item).parent();
        return {
            url: $(item).attr('data-url'),
            title: $(item).attr('data-title'),
            excerpt: $(item).attr('data-excerpt'),
            img: $(item).attr('data-img'),
            twitter: $(item).attr('data-twitter'),
            weibo: $(item).attr('data-weibo'),
        };
    },

    toWeibo: function (item) {
        var content = Share.parseItem(item);
        var url = 'http://service.weibo.com/share/share.php?appkey=&title=分享《'+ content.title + '》 @' + content.weibo + '%0a%0a' + content.excerpt
            +'&url='+content.url
            +'&pic='+content.img+'&searchPic=false&style=simple';
        window.open(url);
    },

    toTwitter: function (item) {
        var content = Share.parseItem(item);
        var url = 'https://twitter.com/intent/tweet?text=分享《'+ content.title + '》 @' + content.twitter + '%0a%0a' + content.excerpt
            + '%20' + content.url;
        window.open(url);
    },

    toQQ: function (item) {
        var content = Share.parseItem(item);
        var url = 'http://connect.qq.com/widget/shareqq/index.html?url=' + content.url
            + '&sharesource=qzone&title=' + content.title
            + '&pics=' + content.img + '&summary=' + content.excerpt;
        window.open(url);
    },

    toQzone: function (item) {
        var content = Share.parseItem(item);
        var url = 'https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=' + content.url
            + '&sharesource=qzone&title=' + content.title
            + '&pics=' + content.img + '&summary=' + content.excerpt;
        window.open(url);
    },

    // 调用系统分享面板（仅支持的浏览器）
    toNative: function (item) {
        if (!navigator.share) {
            VOID.alert('当前浏览器不支持系统分享');
            return;
        }
        var content = Share.parseItem(item);
        navigator.share({
            title: content.title,
            text: content.excerpt,
            url: content.url
        }).catch(function () {
            // 用户取消或分享失败，静默处理
        });
    },

    // 复制文章链接，优先 Clipboard API，失败回退 execCommand
    copyLink: function (item) {
        var content = Share.parseItem(item);
        var url = content.url || window.location.href;

        var done = function () {
            VOID.alert('链接已复制');
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done, function () {
                Share.legacyCopy(url, done);
            });
        } else {
            Share.legacyCopy(url, done);
        }
    },

    legacyCopy: function (url, done) {
        var ta = document.createElement('textarea');
        ta.value = url;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        ta.setSelectionRange(0, ta.value.length);

        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(ta);

        if (ok) {
            done();
        } else {
            VOID.alert('复制失败，请手动复制：<br>' + url, 5000);
        }
    },

    // 生成二维码并弹层展示（复用主题已有的 fancybox）
    showQR: function (item) {
        var holder = document.getElementById('share-qrcode-img');
        if (!holder) {
            return;
        }
        if (typeof qrcode === 'undefined') {
            VOID.alert('二维码组件未加载');
            return;
        }

        var content = Share.parseItem(item);
        var url = content.url || window.location.href;

        holder.innerHTML = '';
        // typeNumber 0 表示按内容自动选择版本，M 为中等纠错级别
        var qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        holder.innerHTML = qr.createSvgTag(6, 4);

        if (window.jQuery && $.fancybox) {
            $.fancybox.open({ src: '#share-qrcode', type: 'inline' });
        }
    },

    // 按能力显隐「系统分享」按钮（首次加载与 PJAX 后都要执行）
    init: function () {
        if (navigator.share) {
            $('.social-button-icon.native-share').removeClass('native-share-hidden');
        } else {
            $('.social-button-icon.native-share').addClass('native-share-hidden');
        }
    }
};

var AjaxComment = {
    noName: '必须填写用户名',
    noMail: '必须填写电子邮箱地址',
    noUrl: '必须填写 URL',
    noContent: '必须填写评论内容',
    invalidMail: '邮箱地址不合法',
    commentsOrder: 'DESC',
    commentList: '.comment-list',
    comments: '#comments .comments-title',
    commentReply: '.comment-reply',
    commentForm: '#comment-form',
    respond: '.respond',
    textarea: '#textarea',
    submitBtn: '#comment-submit-button',
    newID: '',
    parentID: '',

    bindClick: function () {
        $(AjaxComment.commentReply + ' a, #cancel-comment-reply-link').unbind('click');
        $(AjaxComment.commentReply + ' a').click(function () { // 回复
            AjaxComment.parentID = $(this).parent().parent().parent().attr('id');
            $(AjaxComment.textarea).focus();
        });
        $('#cancel-comment-reply-link').click(function () { // 取消
            AjaxComment.parentID = '';
        });
    },

    err: function () {
        $(AjaxComment.submitBtn).attr('disabled', false);
        AjaxComment.newID = '';
    },

    finish: function () {
        TypechoComment.cancelReply();
        $(AjaxComment.submitBtn).html('提交评论');
        $(AjaxComment.textarea).val('');
        $(AjaxComment.submitBtn).attr('disabled', false);
        if ($('#comment-' + AjaxComment.newID).length > 0) {
            VOID_SmoothScroller.scrollTo($('#comment-' + AjaxComment.newID).offset().top, -60);
            $('#comment-' + AjaxComment.newID).fadeTo(500, 1);
        }
        $('.comment-num .num').html(parseInt($('.comment-num .num').html()) + 1);
        AjaxComment.bindClick();
        VOID_Content.highlight();
    },

    init: function () {
        AjaxComment.bindClick();
        $(AjaxComment.commentForm).submit(function () { // 提交事件
            $(AjaxComment.submitBtn).attr('disabled', true);

            /* 检查 */
            if ($(AjaxComment.commentForm).find('#author')[0]) {
                if ($(AjaxComment.commentForm).find('#author').val() == '') {
                    VOID.alert(AjaxComment.noName);
                    AjaxComment.err();
                    return false;
                }

                if (typeof $(AjaxComment.commentForm).find('#mail').attr('required') != 'undefined') {
                    // 需要邮箱
                    if ($(AjaxComment.commentForm).find('#mail').val() == '') {
                        VOID.alert(AjaxComment.noMail);
                        AjaxComment.err();
                        return false;
                    }
                }

                if ($(AjaxComment.commentForm).find('#mail').val() != '') {
                    var filter = /^[^@\s<&>]+@([a-z0-9]+\.)+[a-z]{2,4}$/i;
                    if (!filter.test($(AjaxComment.commentForm).find('#mail').val())) {
                        VOID.alert(AjaxComment.invalidMail);
                        AjaxComment.err();
                        return false;
                    }
                }

                if ($(AjaxComment.commentForm).find('#url').val() == '' 
                    && typeof $(AjaxComment.commentForm).find('#url').attr('required') != 'undefined') {
                    VOID.alert(AjaxComment.noUrl);
                    AjaxComment.err();
                    return false;
                }
            }

            var textValue = $(AjaxComment.commentForm).find(AjaxComment.textarea).val().replace(/(^\s*)|(\s*$)/g, '');//检查空格信息
            if (textValue == null || textValue == '') {
                VOID.alert(AjaxComment.noContent);
                AjaxComment.err();
                return false;
            }
            $(AjaxComment.submitBtn).html('提交中');
            $.ajax({
                url: $(AjaxComment.commentForm).attr('action'),
                type: $(AjaxComment.commentForm).attr('method'),
                data: $(AjaxComment.commentForm).serializeArray(),
                error: function () {
                    VOID.alert('提交失败！请重试。');
                    $(AjaxComment.submitBtn).html('提交评论');
                    AjaxComment.err();
                    return false;
                },
                success: function (data) { //成功取到数据
                    try {
                        if (!$(AjaxComment.commentList, data).length) {
                            var msg = '提交失败！请重试。' + $($(data)[7]).text();
                            VOID.alert(msg);
                            $(AjaxComment.submitBtn).html('提交评论');
                            AjaxComment.err();
                            return false;
                        } else {
                            AjaxComment.newID = $(AjaxComment.commentList, data).html().match(/id="?comment-\d+/g).join().match(/\d+/g).sort(function (a, b) {
                                return a - b;
                            }).pop();

                            if ($('.pager .prev').length && AjaxComment.parentID == '') {
                                // 在分页对文章发表评论，无法取得最新评论内容
                                VOID.alert('评论成功！请回到评论第一页查看。');
                                AjaxComment.newID = '';
                                AjaxComment.parentID = '';
                                AjaxComment.finish();
                                return false;
                            }

                            var newCommentType = AjaxComment.parentID == '' ? 'comment-parent' : 'comment-child';
                            var newCommentData = '<div id="comment-' + AjaxComment.newID + '" style="opacity:0" class="comment-body ' + newCommentType + '">' + $(data).find('#comment-' + AjaxComment.newID).html() + '</div>';

                            // 当页面无评论，先添加一个评论容器
                            if ($(AjaxComment.commentList).length <= 0) {
                                $('#comments').append('<h3 class="comment-separator"><div class="comment-tab-current"><span class="comment-num">已有 <span class="num">0</span> 条评论</span></div></h3>')
                                    .append('<div class="comment-list"></div>');
                            }

                            if (AjaxComment.parentID == '') {
                                // 无父 id，直接对文章评论，插入到第一个 comment-list 头部
                                $('#comments>.comment-list').prepend(newCommentData);
                                VOID.alert('评论成功！');
                                AjaxComment.finish();
                                AjaxComment.newID = '';
                                return false;
                            } else {
                                if ($('#' + AjaxComment.parentID).hasClass('comment-parent')) {
                                    // 父评论是母评论
                                    if ($('#' + AjaxComment.parentID + ' > .comment-children').length > 0) {
                                        // 父评论已有子评论，插入到子评论列表头部
                                        $('#' + AjaxComment.parentID + ' > .comment-children > .comment-list').prepend(newCommentData);
                                    }
                                    else {
                                        // 父评论没有子评论，新建一层包裹
                                        newCommentData = '<div class="comment-children"><div class="comment-list">' + newCommentData + '</div></div>';
                                        $('#' + AjaxComment.parentID).append(newCommentData);
                                    }
                                } else {
                                    // 父评论是子评论，与父评论平级，并放在后面
                                    $('#' + AjaxComment.parentID).after(newCommentData);
                                }
                                VOID.alert('评论成功！');
                                AjaxComment.finish();
                                AjaxComment.parentID = '';
                                AjaxComment.newID = '';
                                return false;
                            }
                        }
                    } catch (e) {
                        window.location.reload();
                    }
                } // end success()
            }); // end ajax()
            return false;
        }); // end submit()
    }
};

(function () {
    $(document).ready(function () {
        VOID.init();
    });

    window.setInterval(function () {
        var times = new Date().getTime() - Date.parse(VOIDConfig.buildTime);
        times = Math.floor(times / 1000); // convert total milliseconds into total seconds
        var days = Math.floor(times / (60 * 60 * 24)); //separate days
        times %= 60 * 60 * 24; //subtract entire days
        var hours = Math.floor(times / (60 * 60)); //separate hours
        times %= 60 * 60; //subtract entire hours
        var minutes = Math.floor(times / 60); //separate minutes
        times %= 60; //subtract entire minutes
        var seconds = Math.floor(times / 1); // remainder is seconds
        $('#uptime').html(days + ' 天 ' + hours + ' 小时 ' + minutes + ' 分 ' + seconds + ' 秒 ');
    }, 1000);
})();