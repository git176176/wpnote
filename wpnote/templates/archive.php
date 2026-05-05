<?php if (!defined('ABSPATH')) exit;
// WPNote Archive - Warm Editorial 美学设计 v3
$site_theme = get_option('wpnote_site_theme', 'default');
$theme = isset($_GET['theme']) ? sanitize_key($_GET['theme']) : $site_theme;
$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'recent';
$cat_filter = isset($_GET['ncat']) ? intval($_GET['ncat']) : 0;

// 标签过滤
$tag_filter = 0;
if (is_tax('wpnote_tag')) {
    $tag_term = get_queried_object();
    if ($tag_term && !is_wp_error($tag_term)) {
        $tag_filter = $tag_term->term_id;
    }
}

// 分类过滤
if (is_tax('wpnote_category')) {
    $cat_term = get_queried_object();
    if ($cat_term && !is_wp_error($cat_term)) {
        $cat_filter = $cat_term->term_id;
    }
}

// 10 套完整主题
$themes = array(
    'default' => array(
        'label'=>'简约紫','emoji'=>'◈',
        'bg'=>'#faf8f5','card'=>'#ffffff','border'=>'#e8e4de',
        'accent'=>'#c84b5e','accent2'=>'#f5e6d3',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(200,75,94,0.08)','hover_shadow'=>'rgba(200,75,94,0.18)',
        'sidebar_bg'=>'#fdfcfa','sidebar_border'=>'#ede9e3',
        'tag_bg'=>'rgba(200,75,94,0.08)','tag_color'=>'#c84b5e',
        'card_txt_bg'=>'linear-gradient(145deg,#8b6b8b,#c84b5e)',
    ),
    'bytedance' => array(
        'label'=>'字节范','emoji'=>'◈',
        'bg'=>'#fffaf8','card'=>'#ffffff','border'=>'#f5e0dc',
        'accent'=>'#e8344b','accent2'=>'#fff0ed',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(232,52,75,0.08)','hover_shadow'=>'rgba(232,52,75,0.18)',
        'sidebar_bg'=>'#fffdfc','sidebar_border'=>'#f5e0dc',
        'tag_bg'=>'rgba(232,52,75,0.08)','tag_color'=>'#e8344b',
        'card_txt_bg'=>'linear-gradient(145deg,#e8344b,#ff6b81)',
    ),
    'alibaba' => array(
        'label'=>'阿里橙','emoji'=>'◈',
        'bg'=>'#fef6f0','card'=>'#ffffff','border'=>'#ffe8cc',
        'accent'=>'#ff6000','accent2'=>'#fff0d8',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(255,96,0,0.08)','hover_shadow'=>'rgba(255,96,0,0.18)',
        'sidebar_bg'=>'#fffdfc','sidebar_border'=>'#ffe8cc',
        'tag_bg'=>'rgba(255,96,0,0.08)','tag_color'=>'#ff6000',
        'card_txt_bg'=>'linear-gradient(145deg,#ff6000,#ff9a4a)',
    ),
    'xiaohongshu' => array(
        'label'=>'小红书','emoji'=>'◈',
        'bg'=>'#fff5f7','card'=>'#ffffff','border'=>'#ffd9df',
        'accent'=>'#fe1f4a','accent2'=>'#fff0f3',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(254,31,74,0.08)','hover_shadow'=>'rgba(254,31,74,0.18)',
        'sidebar_bg'=>'#fffdfc','sidebar_border'=>'#ffd9df',
        'tag_bg'=>'rgba(254,31,74,0.08)','tag_color'=>'#fe1f4a',
        'card_txt_bg'=>'linear-gradient(145deg,#fe1f4a,#ff6b8a)',
    ),
    'minimal' => array(
        'label'=>'极简灰','emoji'=>'◈',
        'bg'=>'#f4f4f4','card'=>'#ffffff','border'=>'#d0d0d0',
        'accent'=>'#111111','accent2'=>'#e0e0e0',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace',
        'shadow'=>'rgba(0,0,0,0.06)','hover_shadow'=>'rgba(0,0,0,0.14)',
        'sidebar_bg'=>'#1a1a1a','sidebar_border'=>'#333',
        'tag_bg'=>'rgba(255,255,255,0.1)','tag_color'=>'#fff',
        'card_txt_bg'=>'linear-gradient(145deg,#1a1a1a,#444)',
    ),
    'glass' => array(
        'label'=>'玻璃拟态','emoji'=>'◈',
        'bg'=>'#ede8ff','card'=>'rgba(255,255,255,0.65)','border'=>'rgba(255,255,255,0.5)',
        'accent'=>'#6c5ce7','accent2'=>'rgba(255,255,255,0.35)',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(108,92,231,0.12)','hover_shadow'=>'rgba(108,92,231,0.22)',
        'sidebar_bg'=>'rgba(255,255,255,0.5)','sidebar_border'=>'rgba(255,255,255,0.5)',
        'tag_bg'=>'rgba(108,92,231,0.1)','tag_color'=>'#6c5ce7',
        'card_txt_bg'=>'linear-gradient(145deg,#6c5ce7,#a29bfe)',
    ),
    'cyberpunk' => array(
        'label'=>'赛博朋克','emoji'=>'◈',
        'bg'=>'#0d0d1a','card'=>'rgba(20,15,40,0.85)','border'=>'rgba(0,240,255,0.12)',
        'accent'=>'#00f0ff','accent2'=>'rgba(0,240,255,0.06)',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(0,240,255,0.08)','hover_shadow'=>'rgba(0,240,255,0.18)',
        'sidebar_bg'=>'rgba(10,10,20,0.95)','sidebar_border'=>'rgba(0,240,255,0.15)',
        'tag_bg'=>'rgba(0,240,255,0.08)','tag_color'=>'#00f0ff',
        'card_txt_bg'=>'linear-gradient(145deg,#0d0d1a,#1a0533,#00f0ff44)',
    ),
    'warm' => array(
        'label'=>'温暖柔和','emoji'=>'◈',
        'bg'=>'#fdf2f4','card'=>'#ffffff','border'=>'#f8d8dc',
        'accent'=>'#f5576c','accent2'=>'#fff0f3',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(245,87,108,0.08)','hover_shadow'=>'rgba(245,87,108,0.18)',
        'sidebar_bg'=>'#fffdfc','sidebar_border'=>'#f8d8dc',
        'tag_bg'=>'rgba(245,87,108,0.08)','tag_color'=>'#f5576c',
        'card_txt_bg'=>'linear-gradient(145deg,#f5576c,#faa49e)',
    ),
    'magazine' => array(
        'label'=>'杂志风','emoji'=>'◈',
        'bg'=>'#ffffff','card'=>'#ffffff','border'=>'#e0e0e0',
        'accent'=>'#1a1a1a','accent2'=>'#f0f0f0',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(0,0,0,0.05)','hover_shadow'=>'rgba(0,0,0,0.12)',
        'sidebar_bg'=>'#ffffff','sidebar_border'=>'#e0e0e0',
        'tag_bg'=>'rgba(0,0,0,0.06)','tag_color'=>'#1a1a1a',
        'card_txt_bg'=>'linear-gradient(145deg,#1a1a1a,#444)',
    ),
    'nature' => array(
        'label'=>'清新自然','emoji'=>'◈',
        'bg'=>'#f0fdf4','card'=>'#ffffff','border'=>'#bbf7d0',
        'accent'=>'#16a34a','accent2'=>'#dcfce7',
        'font_head'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif','font_body'=>'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", "PingFang SC", sans-serif',
        'shadow'=>'rgba(22,163,74,0.08)','hover_shadow'=>'rgba(22,163,74,0.18)',
        'sidebar_bg'=>'#fafefc','sidebar_border'=>'#bbf7d0',
        'tag_bg'=>'rgba(22,163,74,0.08)','tag_color'=>'#16a34a',
        'card_txt_bg'=>'linear-gradient(145deg,#16a34a,#4ade80)',
    ),
);

if (!isset($themes[$theme])) $theme = 'default';
$t = $themes[$theme];

$args = array('post_type'=>'wpnote','posts_per_page'=>30,'post_status'=>'publish');
if ($cat_filter>0) $args['tax_query'] = array(array('taxonomy'=>'wpnote_category','field'=>'term_id','terms'=>$cat_filter));
if ($tag_filter>0) {
    if (!isset($args['tax_query'])) $args['tax_query'] = array();
    $args['tax_query'][] = array('taxonomy'=>'wpnote_tag','field'=>'term_id','terms'=>$tag_filter);
}
if ($tab==='popular'){ $args['orderby']='comment_count'; $args['order']='DESC'; }
else{ $args['orderby']='date'; $args['order']='DESC'; }
$notes = new WP_Query($args);
$cats = get_terms(array('taxonomy'=>'wpnote_category','hide_empty'=>false));
$archive_url = get_post_type_archive_link('wpnote');

// 首页布局设置
$archive_columns = get_option('wpnote_archive_columns', 4);
$archive_style = get_option('wpnote_archive_style', 'waterfall');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>📒 WPNote</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:<?php echo $t['font_body'];?>;background:<?php echo $t['bg'];?>;min-height:100vh;color:inherit}
a{text-decoration:none;color:inherit}
a:hover{text-decoration:none}

/* dark sidebar text */
<?php if($theme==='minimal'||strpos($theme,'cyberpunk')!==false): ?>
.sidebar,.sidebar-head,.logo,.nav-item,.cat-item,.section-label{color:#fff}
.nav-item{color:rgba(255,255,255,0.65)}.nav-item:hover,.nav-item.active{color:#fff}
.cat-item{color:rgba(255,255,255,0.45)}.cat-item:hover,.cat-item.active{color:rgba(255,255,255,0.9)}
.section-label{color:rgba(255,255,255,0.3)}
.sidebar-foot\{display:none\}
.sidebar input{background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.12);color:rgba(255,255,255,0.8)}
<?php endif; ?>

/* ===== LAYOUT ===== */
.wrap{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
    width:230px;flex-shrink:0;
    background:<?php echo $t['sidebar_bg'];?>;
    border-right:1px solid <?php echo $t['sidebar_border'];?>;
    position:sticky;top:0;height:100vh;overflow-y:auto;
    display:flex;flex-direction:column;
    backdrop-filter:blur(16px);
}
.sidebar-head{
    padding:32px 22px 22px;
    border-bottom:1px solid <?php echo $t['sidebar_border'];?>;
}
.logo{
    font-family:<?php echo $t['font_head'];?>;
    font-size:24px;line-height:1;
    color:#1a1a1a;letter-spacing:-0.03em;
}
<?php if($theme!=='minimal'&&strpos($theme,'cyberpunk')===false): ?>
.logo{color:#1a1a1a}
<?php endif; ?>
.logo em{display:block;font-style:normal;font-size:11px;font-family:<?php echo $t['font_body'];?>;font-weight:400;opacity:0.5;margin-top:5px;letter-spacing:0}

.section{padding:18px 14px 0}
.section-label{
    font-size:10px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;
    opacity:0.4;margin-bottom:7px;padding-left:4px;
}

.nav-item{
    display:flex;align-items:center;gap:10px;
    padding:10px 11px;border-radius:10px;
    font-size:13px;font-weight:500;
    color:inherit;transition:all 0.15s;cursor:pointer;border:none;background:transparent;width:100%;text-align:left;font-family:inherit;opacity:0.65;
}
.nav-item .ico{font-size:15px;flex-shrink:0}
.nav-item .lb{flex:1}
.nav-item:hover{background:<?php echo $t['accent2'];?>;color:<?php echo $t['accent'];?>;opacity:1}
.nav-item.active{background:<?php echo $t['accent'];?>;color:#fff;font-weight:600;opacity:1}
.nav-item.active .ico{filter:brightness(10)}

.cat-item{
    display:flex;align-items:center;gap:9px;
    padding:7px 11px;border-radius:8px;
    font-size:12px;opacity:0.5;transition:all 0.15s;cursor:pointer;
}
.cat-item:hover,.cat-item.active{opacity:1}
.cat-item .lb{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cat-item .ct{font-size:10px;opacity:0.6}

.sidebar input{
    width:100%;padding:8px 11px;border:1px solid <?php echo $t['border'];?>;
    border-radius:8px;font-size:12px;
    background:<?php echo $t['card'];?>;
    color:inherit;font-family:inherit;outline:none;
}
.sidebar input::placeholder{opacity:0.4}

/* 主题切换 */







/* ===== MAIN ===== */
.main{flex:1;min-width:0;padding:40px 44px 60px}
.main-head{
    display:flex;align-items:baseline;justify-content:space-between;
    margin-bottom:32px;padding-bottom:18px;
    border-bottom:1px solid <?php echo $t['border'];?>;
}
.main-title{
    font-family:<?php echo $t['font_head'];?>;
    font-size:28px;color:#1a1a1a;letter-spacing:-0.04em;line-height:1.1;
}
.main-title small{font-family:<?php echo $t['font_body'];?>;font-size:13px;font-weight:400;opacity:0.4;letter-spacing:0;margin-left:10px}
.theme-chip{
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 12px;border-radius:20px;
    font-size:11px;font-weight:500;
    background:<?php echo $t['tag_bg'];?>;color:<?php echo $t['tag_color'];?>;
}

/* ===== GRID ===== */
.grid{<?php echo $archive_style === 'grid' ? 'display:grid;grid-template-columns:repeat(' . intval($archive_columns) . ',1fr);gap:16px' : 'columns:' . intval($archive_columns) . ';column-gap:16px'; ?>}
@media(max-width:960px){.grid{<?php echo $archive_style === 'grid' ? 'grid-template-columns:repeat(3,1fr)' : 'columns:3'; ?>}}
@media(max-width:768px){.grid{<?php echo $archive_style === 'grid' ? 'grid-template-columns:repeat(2,1fr);gap:12px' : 'columns:2;column-gap:12px'; ?>}}

/* ===== CARD ===== */
.card{
    break-inside:avoid;margin-bottom:16px;
    border-radius:12px;overflow:hidden;
    background:<?php echo $t['card'];?>;
    border:1px solid <?php echo $t['border'];?>;
    box-shadow:0 2px 10px <?php echo $t['shadow'];?>;
    display:block;
    transition:transform 0.22s,box-shadow 0.22s;
}
.card:hover{transform:translateY(-4px);box-shadow:0 10px 28px <?php echo $t['hover_shadow'];?>}

/* 封面图 */
.card-img{width:100%;aspect-ratio:3/4;object-fit:cover;display:block}

/* 文字封面 */
.card-cv{width:100%;aspect-ratio:3/4;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px 14px}
.card-cv .cv-emoji{font-size:38px;margin-bottom:10px;line-height:1}
.card-cv .cv-title{
    font-family:<?php echo $t['font_head'];?>;
    font-size:13px;color:#fff;line-height:1.45;text-align:center;
    display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;
    text-shadow:0 1px 4px rgba(0,0,0,0.15);
}

/* 标题在封面下方 */
.card-bot{padding:10px 13px 12px}
.card-title{font-weight:700;
    font-family:<?php echo $t['font_head'];?>;
    font-size:13px;color:#1a1a1a;line-height:1.4;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
    margin-bottom:8px;
}
.card-foot{display:flex;align-items:center;justify-content:space-between}
.card-cat{font-size:10px;font-weight:700;color:<?php echo $t['tag_color'];?>;letter-spacing:0.02em}
.card-time{font-size:10px;opacity:0.35}

/* 网格布局 */
.grid.is-grid .card{display:flex;flex-direction:column}
.grid.is-grid .card-cv,.grid.is-grid .card-img{flex:1;min-height:0}

/* 空状态 */
.empty{text-align:center;padding:80px 20px;opacity:0.3;font-size:15px}

/* ===== MOBILE ===== */
@media(max-width:768px){
    .wrap{flex-direction:column}
    .sidebar{width:100%;height:auto;position:static;flex-direction:row;flex-wrap:wrap;padding:10px;gap:8px;overflow:visible;backdrop-filter:none}
    .sidebar-head{flex:0 0 100%;border:none;padding:4px 0}
    .section{display:none}
    .sidebar-foot{display:none}
    .main{padding:18px 14px}
    .main-title{font-size:20px}
    .grid{<?php echo $archive_style === 'grid' ? 'grid-template-columns:repeat(2,1fr)' : 'columns:2'; ?>}
}
</style>
</head>
<body>
<div class="wrap">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-head">
        <div class="logo">📒 WPNote<em>发现好东西，随手记</em></div>
    </div>

    <!-- 发现 -->
    <div class="section">
        <div class="section-label">发现</div>
        <a href="<?php echo esc_url(add_query_arg(array('tab'=>'recent','ncat'=>0),$archive_url)); ?>" class="nav-item <?php echo $tab==='recent'&&$cat_filter===0?'active':''; ?>">
            <span class="ico">◷</span><span class="lb">近期笔记</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('tab'=>'popular','ncat'=>0),$archive_url)); ?>" class="nav-item <?php echo $tab==='popular'&&$cat_filter===0?'active':''; ?>">
            <span class="ico">🔥</span><span class="lb">热门笔记</span>
        </a>
    </div>

    <!-- 分类 -->
    <div class="section">
        <div class="section-label">分类</div>
        <a href="<?php echo esc_url(add_query_arg(array('ncat'=>0,'tab'=>$tab),$archive_url)); ?>" class="cat-item <?php echo $cat_filter===0?'active':''; ?>">
            <span>☰</span><span class="lb">全部笔记</span>
        </a>
        <?php foreach($cats as $c): if(is_wp_error($c))continue; ?>
            <?php $cnt=get_posts(array('post_type'=>'wpnote','tax_query'=>array(array('taxonomy'=>'wpnote_category','field'=>'term_id','terms'=>$c->term_id)),'fields'=>'ids','posts_per_page'=>-1)); ?>
            <a href="<?php echo esc_url(add_query_arg(array('ncat'=>$c->term_id,'tab'=>$tab),$archive_url)); ?>" class="cat-item <?php echo $cat_filter===$c->term_id?'active':''; ?>">
                <span>▸</span><span class="lb"><?php echo esc_html($c->name); ?></span><span class="ct"><?php echo count($cnt); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 搜索 -->
    <div class="section">
        <div class="section-label">搜索</div>
        <form method="get" action="<?php echo esc_url($archive_url); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
            <input type="hidden" name="ncat" value="<?php echo esc_attr($cat_filter); ?>">
            <input type="text" name="s" placeholder="搜索笔记…" value="<?php echo isset($_GET['s'])?esc_attr($_GET['s']):''; ?>">
        </form>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="main-head">
        <div class="main-title">
            <?php if($tag_filter>0){ $tg=get_term($tag_filter,'wpnote_tag'); echo '🏷️ ' . esc_html($tg?$tg->name:'标签'); }
                  elseif($cat_filter>0){ $o=get_term($cat_filter,'wpnote_category'); echo esc_html($o?$o->name:'笔记'); }
                  else{ echo $tab==='popular'?'🔥 热门笔记':'◷ 最新笔记'; } ?>
            <small><?php echo $notes->found_posts; ?> 篇</small>
        </div>
        <div class="theme-chip"><?php echo esc_html($t['emoji'].' '.$t['label']); ?></div>
    </div>

    <div class="grid<?php echo $archive_style === 'grid' ? ' is-grid' : ''; ?>">
        <?php if($notes->have_posts()): while($notes->have_posts()): $notes->the_post(); ?>
            <?php
            $pid=get_the_ID();
            $cover=get_post_meta($pid,'wpnote_cover',true);
            $emoji=empty($cover['emoji'])?'📝':$cover['emoji'];
            $bg=empty($cover['bg_color'])?$t['accent']:$cover['bg_color'];
            $md=empty($cover['image'])?'':$cover['image'];
            // 封面类型判断
            $cover_type=empty($cover['cover_type']) ? get_option('wpnote_default_cover_type', 'md2card') : $cover['cover_type'];
            $show_md=($cover_type==='md2card' && !empty($md)) ? $md : '';
            $time=esc_html(get_the_date('m-d'));
            $cats2=get_the_terms($pid,'wpnote_category');
            $fc=($cats2&&!is_wp_error($cats2))?$cats2[0]->name:'';
            $title_plain=wp_strip_all_tags(get_the_title());
            ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" class="card" target="_self">
                <?php if($show_md): ?>
                    <img src="<?php echo esc_url($show_md); ?>" alt="<?php echo esc_attr($title_plain); ?>" class="card-img">
                <?php else: ?>
                    <div class="card-cv" style="background:<?php echo esc_attr($bg); ?>;">
                        <span class="cv-emoji"><?php echo esc_html($emoji); ?></span>
                        <div class="cv-title"><?php the_title(); ?></div>
                    </div>
                <?php endif; ?>
                <div class="card-bot">
                    <div class="card-title"><?php the_title(); ?></div>
                    <div class="card-foot">
                        <?php if($fc): ?><span class="card-cat"><?php echo esc_html($fc); ?></span><?php else: ?><span></span><?php endif; ?>
                        <span class="card-time"><?php echo $time; ?></span>
                    </div>
                </div>
            </a>
        <?php endwhile; else: ?>
            <p class="empty">暂无笔记，写下第一条吧 ✍️</p>
        <?php endif; ?>
    </div>
</main>
</div>
<script>
// 确保卡片链接在当前标签页打开（防止浏览器扩展/主题脚本干扰）
document.querySelectorAll('.card').forEach(function(card){
    card.setAttribute('target', '_self');
    card.setAttribute('rel', '');
});
</script>
</body>
</html>
