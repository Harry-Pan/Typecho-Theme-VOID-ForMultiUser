<?php
/**
 * main.php
 * 
 * 内容页面主要区域，PJAX 作用区域
 * 
 * @author      熊猫小A
 * @version     2019-01-15 0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$setting = $GLOBALS['VOIDSetting'];
?>

<main id="pjax-container">
    <title hidden>
        <?php Contents::title($this); ?>
    </title>

    <?php $this->need('includes/ldjson.php'); ?>
    <?php $this->need('includes/banner.php'); ?>

    <div class="wrapper container">
        <div class="contents-wrap"> <!--start .contents-wrap-->
            <section id="post" class="float-up">
                <article class="post yue">

                    <?php if($setting['outdatedNotice']): ?>
                        <?php $postCheck = Utils::isOutdated($this, $setting['outdatedDays']); if($postCheck["is"] && $this->is('post')): ?>
                            <p class="notice">请注意，本文编写于 <?php echo $postCheck["created"]; ?> 天前，最后修改于 <?php echo $postCheck["updated"]; ?> 天前，其中某些信息可能已经过时。</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($this->fields->showMode == 1): ?>
                        <p class="notice"><?php echo $setting['eggNotice']; ?></p>
                    <?php endif; ?>

                    <div class="articleBody" class="full">
                        <?php $this->content(); ?>
                    </div>
                    
                    <?php $tags = Contents::getTags($this->cid); if (count($tags) > 0) { 
                        echo '<section class="tags">';
                        foreach ($tags as $tag) {
                            echo '<a href="'.$tag['permalink'].'" rel="tag" class="tag-item">'.$tag['name'].'</a>';
                        }
                        echo '</section>';
                    } ?>

                    <div class="social-button" 
                        data-url="<?php $this->permalink(); ?>"
                        data-title="<?php Contents::title($this); ?>" 
                        data-excerpt="<?php $this->fields->excerpt(); ?>"
                        data-img="<?php $this->fields->banner(); ?>" 
                        data-twitter="<?php if($setting['twitterId']!='') echo $setting['twitterId']; else $this->author(); ?>"
                        data-weibo="<?php if($setting['weiboId']!='') echo $setting['weiboId']; else $this->author(); ?>"
                        <?php if($this->fields->banner != '') echo 'data-image="'.$this->fields->banner.'"';?>>
                        <?php if(!empty($setting['reward'])):?>
                            <a data-fancybox="gallery-reward" role=button aria-label="赞赏" data-src="#reward" href="javascript:;" class="btn btn-normal btn-highlight">赏杯咖啡</a>
                            <div hidden id="reward"><img src="<?php echo $setting['reward']; ?>"></div>
                        <?php endif; ?>
                        <?php if($setting['VOIDPlugin']):?>
                            <a role=button 
                                aria-label="为文章点赞" 
                                id="social" 
                                href="javascript:void(0);" onclick="VOID_Vote.vote(this);" 
                                data-item-id="<?php echo $this->cid;?>" 
                                data-type="up"
                                data-table="content"
                                class="btn btn-normal post-like vote-button"
                            >ENJOY <span class="value"><?php echo $this->likes; ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <?php /* 分享按钮组：微博 / QQ / QQ空间 / 复制链接 / 二维码 / 系统分享（仅支持的浏览器显示） */ ?>
                        <a aria-label="分享到微博" title="分享到微博" href="javascript:void(0);" onclick="Share.toWeibo(this);" class="social-button-icon"><i class="voidicon-weibo"></i></a>
                        <a aria-label="分享到QQ" title="分享到QQ" href="javascript:void(0);" onclick="Share.toQQ(this);" class="social-button-icon"><i class="voidicon-qq"></i></a>
                        <a aria-label="分享到QQ空间" title="分享到QQ空间" href="javascript:void(0);" onclick="Share.toQzone(this);" class="social-button-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.3 6.2 20.4l1.1-6.5L2.6 9.3l6.5-.9z"/></svg>
                        </a>
                        <a aria-label="复制链接" title="复制链接" href="javascript:void(0);" onclick="Share.copyLink(this);" class="social-button-icon"><i class="voidicon-link"></i></a>
                        <a aria-label="二维码" title="二维码" href="javascript:void(0);" onclick="Share.showQR(this);" class="social-button-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h7v7H3V3zm2 2v3h3V5H5zm9-2h7v7h-7V3zm2 2v3h3V5h-3zM3 14h7v7H3v-7zm2 2v3h3v-3H5zm9-2h3v3h-3v-3zm5 0h2v2h-2v-2zm-5 5h2v2h-2v-2zm3 0h4v2h-4v-2z"/></svg>
                        </a>
                        <a aria-label="系统分享" title="系统分享" href="javascript:void(0);" onclick="Share.toNative(this);" class="social-button-icon native-share">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l4 4h-3v7h-2V7H8l4-4zM5 12h2v7h10v-7h2v9H5v-9z"/></svg>
                        </a>
                    </div>
                    <div hidden id="share-qrcode" class="share-qrcode-wrap">
                        <div id="share-qrcode-img" class="share-qrcode-img"></div>
                        <p class="share-qrcode-tip">扫码在手机上打开本文</p>
                    </div>
                </article>

                <script>
                (function () {
                    $.each($('iframe'), function(i, item){
                        var src = $(item).attr('src');
                        if (typeof src === 'string' && src.indexOf('player.bilibili.com') > -1) {
                            // $(item).addClass('bili-player');
                            // if (src.indexOf('&high_quality') < 0) {
                            //     src += '&high_quality=1'; // 启用高质量
                            //     $(item).attr('src', src);
                            // }
                            $(item).wrap('<div class="bili-player"></div>');
                        }
                    });
                })();
                </script>

                <!--分页-->
                <?php if(!$this->is('page')): ?>
                <div class="post-pager"><?php $prev = Contents::thePrev($this); ?>
                    <?php if($prev): ?>
                        <div class="prev">
                            <a href="<?php $prev->permalink(); ?>"><h2><?php $prev->title(); ?></h2></a>
                            <?php echo $prev->fields->excerpt != '' ? "<p>{$prev->fields->excerpt}</p>" : ''; ?>
                        </div>
                    <?php else: ?>
                        <div class="prev">
                            <h2>没有了</h2>
                        </div>
                    <?php endif; ?>
                    <?php $next = Contents::theNext($this); ?>
                    <?php if($next): ?>
                        <div class="next">
                            <a href="<?php $next->permalink(); ?>"><h2><?php $next->title(); ?></h2></a>
                            <?php echo $next->fields->excerpt != '' ? "<p>{$next->fields->excerpt}</p>" : ''; ?>
                        </div>
                    <?php else: ?>
                        <div class="next">
                            <h2>没有了</h2>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
        </div> <!--end .contents-wrap-->
        <!--目录，可选-->
        <?php if($this->fields->showTOC == '1'): ?>
            <div class="toc-mask" onclick="TOC.close();"></div>
            <div aria-label="文章目录" class="TOC"></div>
            <style>
            #toggle-toc { display: block; }
            </style>
        <?php endif;?>
    </div>
    <!--评论区，可选-->
    <?php $this->need('includes/comments.php'); ?>
</main>
