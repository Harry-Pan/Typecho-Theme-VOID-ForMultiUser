# Typecho Theme VOID for MultiUser

> ✏ 一款可能没那么简洁的 Typecho 主题。拿去和朋友们搞点文学结社也许不错。

作为计算机术语时，VOID 的意思是「无类型」。

![](https://raw.githubusercontent.com/AlanDecode/Typecho-Theme-VOID/master/screenshot.png)

由[AlanDecode](https://github.com/AlanDecode/)设计的Typecho主题。这里是经过一些改造后更适合多作者环境的版本。

快叫上你的小伙伴一起搞文学社吧。

## 特性

> 介绍文章：[VOID-ForMultiUser发布](https://www.harrypan.cn/archives/1742/)。

* 响应式设计
* 全新无刷新引擎：Swup，在主题层面兼容PJAX
* PJAX 无刷新体验
* AJAX 评论
* 前台无跳转登陆（兼容 PJAX）
* 自动夜间模式
* 优秀的可读性
* 衬线、非衬线两种文字风格
* 代码高亮（浅色暗色两种风格，随主题切换）
* Mac 风格代码块（可开启或关闭）
* 代码行号
* 站点样式设置面板（日夜转换、字体、字号）
* MathJax 公式
* 图片排版（可用作相册）
* 图片懒加载
* 灵活的头图设置
* 文章目录解析
* 完整的结构化数据支持
* 站内即时搜索（浏览器端检索标题、正文、分类、标签）
* 多作者适配
* 够用的后台设置与丰富的高级设置

结合附带的配套专用插件，还有更多拓展功能：

* 浏览量统计
* 文章点赞
* 文章字数统计
* 评论投票与自动折叠
* 访客互动展示
* 图片显示优化

以及其他很多细节，总之用起来还算舒服。你可以参考这个博客，在这里你可以看到 VOID最新版的运行演示：[示例页面](https://www.harrypan.cn/)。

## 开始使用

​	使用前，请确保你的博客使用 PHP 7.0 及以上版本搭配 MySQL 数据库。更低版本的PHP以及其它数据库可能出现未知问题（并且我不会去修复）。

1. 下载主题：[发布版](https://github.com/Harry-Pan/Typecho-Theme-VOID-ForMultiUser/releases)，注意是下载 VOID-FMU-x.x.x.zip 这个压缩包，而不是 Source code
2. 下载插件：[VOID-FMU-Plugin](https://github.com/Harry-Pan/VOID-ForMultiUser-Plugin)
3. 解压
4. 将**主题文件夹下**的 VOID-FMU 文件夹上传至站点 /usr/themes 目录下
5. 后台启用主题
6. 将**配套插件文件夹下**的 VOID-FMU 文件夹上传至站点 /usr/plugins 目录下
7. 后台启用插件

* 可选：将主题 `assets` 文件夹下的 `VOIDCacheRule.js` 复制一份到站点根目录，并在主题设置中启用 Service Worker 缓存。
* 可选：主题文件夹下 advanceSetting.sample.json 中有一些高级设置，可以看看。

## **常见问题（请务必仔细阅读）**

<details><summary>如何开启文章点赞/文章浏览量统计/文章字数统计/点赞功能？</summary><br>


这些功能依赖配套插件，请上传至插件目录并启用。插件一般会随主题包发布。

</details>

<details><summary>下载安装后样式不对？</summary><br>


仓库中的是未压缩的源代码，包含大量实际使用中不需要的文件，并且可能无法直接使用。请一定通过这个链接下载主题：[发布版](https://github.com/Harry-Pan/Typecho-Theme-VOID-ForMultiUser/releases)。注意其中发布版是下载 VOID-FMU-x.x.x.zip 这个压缩包，而不是 Source code。

</details>

<details><summary>添加归档页面</summary><br>


新建独立页面，自定义模板选择 `Archives`，内容留空。

</details>

<details><summary>添加友情链接</summary><br>


新建独立页面，然后如此书写：

```
[links]
[熊猫小A](https://www.imalan.cn)+(https://secure.gravatar.com/avatar/1741a6eef5c824899e347e4afcbaa75d?s=200&r=G&d=)
[熊猫小A的博客](https://blog.imalan.cn)+(https://secure.gravatar.com/avatar/1741a6eef5c824899e347e4afcbaa75d?s=64&r=G&d=)
[/links]
```

文章中、独立页面中都可以通过该语法插入类似的展示块。在某些 Typecho 版本中 HTML 会被转义后输出，请使用 `!!!` 包裹以上代码，例如：

```
!!!
[links]
···
[/links]
!!!
```

`!!!` 需要单独占一行。

</details>

<details><summary>图片排版</summary><br>


在文章中，使用 `[photos][/photos]` 包起来的图片可显示在同一行。例如：

```
[photos]
![](https://cdn.imalan.cn/img/post/2018-10-26/IMG_0073.jpeg)
![](https://cdn.imalan.cn/img/post/2018-10-26/IMG_0053.jpeg)
[/photos]

[photos]
![](https://cdn.imalan.cn/img/post/2018-10-26/IMG_0039.jpeg)
![](https://cdn.imalan.cn/img/post/2018-10-26/IMG_0051.jpeg)
![](https://cdn.imalan.cn/img/post/2018-10-26/IMG_0005.jpeg)
[/photos]
```

在某些 Typecho 版本中 HTML 会被转义后输出，请使用 `!!!` 包裹以上代码，例如：

```
!!!
[photos]
···
[/photos]
!!!
```

`!!!` 需要单独占一行。

</details>

<details><summary>增强的 Markdown 语法</summary><br>


* 注音语法：`{{文本:zhu yin}}`，会渲染为：文本 (zhu yin)<ruby>文本<rp> (</rp><rt>zhu yin</rt><rp>)</rp></ruby>
* notice 提示块：`[notice]提示内容[/notice]`

</details>

## 更新

同[开始使用](#开始使用)，区别是你可以直接覆盖主题文件。大多数情况下无需禁用主题，这样你的主题设置就不会丢失。

某些版本由于改用幅度较大需要重启主题与插件，请参见对应版本的发布日志。

## 开发与自定义

**首先注意：我不保证提供任何自定义修改相关的指导与帮助。You are on your own.**

<details><summary>展开详情</summary><br>


如果你有不错的想法，可以定制自己的版本。首先你需要准备好 NodeJS 环境，然后 clone 这个 repo：

```bash
git clone https://github.com/Harry-Pan/VOID-ForMultiUser-Plugin ./VOID-FMU && cd ./VOID
```

安装依赖：

```bash
npm install -g gulp
npm install
```

用以下命令打包依赖的 JS 和 CSS：

```bash
gulp dev
```

主题的样式是用 SCSS 写的，你可以使用自己喜欢的方式编译 SCSS，或者使用：

```bash
gulp sass
```

监听 SCSS 更改然后实时编译。尽请添加自己想要的功能，满意后就提交代码。然后：

```bash
gulp build
```

构建你的主题，生成的主题位于 `./build` 目录下。如果你对自己的更改很满意，**欢迎提出 Pull Request**。

</details>

## 更新日志

**🍰 2026-10-27 Version 3.5.3**

* 新增：引入PJAX的现代化替代品：Swup，启用时依赖PJAX的功能和插件会通过主题适配转接给Swup。目前PJAX暂未移除，可以在**设置外观**处进行配置。目前并未发现Swup导致的兼容性问题，如果你在使用中遇到此类问题，请提交issue。
* 新增：ExSearch整合进主题，可以自行选择开关和缓存策略。
* 新增：代码块复制功能。
* 优化：jQuery兼容保护：当有外部插件覆盖jQuery时，会自动指回主题实例，并在控制台进行提示。
* 优化：优化了**设置外观**的样式。

**🍰 2026-10-27 Version 3.5.2**

fork。

更新了一些组件，并进行了一些适配多作者的改动。

* 新增：首页和各类归档页可配置头图。
* 新增：彩蛋文章（不在主页展示，不影响个人页与搜索）
* 优化：分享按钮部分重做。
* 优化：分类 / 标签 / 作者 / 搜索页新增文章数。
* 优化：归档页新增作者名称。
* 优化：首页文章卡片新增作者名称。
* 优化：导航栏新增作者栏。
* 优化：文章过时提醒可以在配置项中进行配置。
* 优化：注脚方案 bigfoot更换为littlefoot 4.1.4，解决了窄屏下某些注脚无法正确显示的问题。
* 优化：MathJax升级至2.7.9。
* 移除：owo，现在表情组件完全由**[Typecho_Plugin_OwO](https://github.com/Harry-Pan/Typecho_Plugin_OwO)**提供。

https://github.com/AlanDecode/Typecho-Theme-VOID/blob/master/change-log.md)

## 鸣谢

### 开源项目

[JQuery](https://github.com/jquery/jquery) | [PrismJS](https://prismjs.com/index.html) | [MathJax](https://www.mathjax.org/) | [fancyBox](http://fancyapps.com/fancybox/3/) | [littlefoot](https://github.com/goblindegook/littlefoot) | [pjax](https://github.com/defunkt/jquery-pjax) | [yue.css](https://github.com/lepture/yue.css) | [tocbot](https://tscanlin.github.io/tocbot/) | [pangu.js](https://github.com/vinta/pangu.js) | [social](https://github.com/lepture/social) | [Headroom.js](http://wicky.nillia.ms/headroom.js/) | [hypher](https://github.com/bramstein/hypher)

### 其他

[RAW](https://github.com/AlanDecode/Typecho-Theme-RAW) | [Mirages](https://get233.com/archives/mirages-intro.html) | [handsome](https://www.ihewro.com/archives/489/) | [Card](https://blog.shuiba.co/bitcron-theme-card) | [Casper](https://github.com/TryGhost/Casper) | [Typlog](https://typlog.com/) | [FORMA](https://justgoodthemes.com/ghost-themes/forma/)

## 捐助

**如果本项目对你有所帮助，请考虑捐助原作者**

![谢谢支持](https://wx1.sinaimg.cn/large/0060lm7Tly1g0c4cbi71lj30sc0iv453.jpg)

## License

MIT © [AlanDecode](https://github.com/AlanDecode)