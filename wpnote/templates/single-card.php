<?php if (!defined('ABSPATH')) exit;
// WPNote Single - Card Float 模板（大卡片，左封面右文案）
$pid = get_the_ID();
$cover = get_post_meta($pid,'wpnote_cover',true);
$emoji = empty($cover['emoji'])?'📝':$cover['emoji'];
$bg = empty($cover['bg_color'])?'#667eea':$cover['bg_color'];
$txt = empty($cover['text_color'])?'#ffffff':$cover['text_color'];
$mdimg = empty($cover['image'])?'':$cover['image'];
// 封面类型：md2card 或 text
$cover_type = empty($cover['cover_type']) ? get_option('wpnote_default_cover_type', 'md2card') : $cover['cover_type'];
// 根据类型决定最终显示
$show_mdimg = ($cover_type === 'md2card' && !empty($mdimg)) ? $mdimg : '';
$cats = get_the_terms($pid,'wpnote_category');
$tags = get_the_terms($pid,'wpnote_tag');
$content = apply_filters('the_content',get_post_field('post_content',$pid));
$title = get_the_title();
$date = get_the_date('Y年m月d日');
$archive_url = get_post_type_archive_link('wpnote');
$word_count = mb_strlen(strip_tags($content));
$read_time = max(1, ceil($word_count / 400));
$primary_cat = ($cats&&!is_wp_error($cats)) ? $cats[0] : null;

// 从设置读取卡片配置
$card_width = get_option('wpnote_card_width', 1200);
$card_height = get_option('wpnote_card_height', 85);
$page_bg = get_option('wpnote_page_bg_color', '#f5f5f7');
$cover_ratio = get_option('wpnote_cover_ratio', '3/4');
$cover_width = get_option('wpnote_cover_width', 45);

// 计算动态值
$card_max_height = $card_height . 'vh';
$card_min_height = ($card_height - 10) . 'vh';
$cover_percent = $cover_width . '%';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo esc_html($title); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif;
    background:<?php echo esc_attr($page_bg); ?>;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 24px;
    -webkit-font-smoothing:antialiased;
}
a{text-decoration:none;color:inherit}

/* 返回按钮 */
.back-btn{
    position:fixed;
    top:20px;
    left:20px;
    z-index:100;
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:10px 20px;
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(12px);
    border-radius:24px;
    font-size:13px;
    font-weight:500;
    color:#666;
    box-shadow:0 2px 16px rgba(0,0,0,0.08);
    border:1px solid rgba(0,0,0,0.05);
    transition:all 0.2s;
}
.back-btn:hover{
    background:#fff;
    color:<?php echo esc_attr($bg); ?>;
}

/* 主卡片 - 可配置尺寸 */
.main-card{
    width:100%;
    max-width:<?php echo esc_attr($card_width); ?>px;
    background:#fff;
    border-radius:28px;
    box-shadow:0 12px 60px rgba(0,0,0,0.12),0 4px 16px rgba(0,0,0,0.06);
    overflow:hidden;
    display:flex;
    min-height:<?php echo esc_attr($card_min_height); ?>;
    max-height:<?php echo esc_attr($card_max_height); ?>;
}

/* 左侧封面 - 可配置占比 */
.cover-section{
    flex:0 0 <?php echo esc_attr($cover_percent); ?>;
    min-width:300px;
    max-width:520px;
    display:flex;
    align-items:stretch;
}

/* 封面卡片 - 撑满整个左侧，可配置比例 */
.cover-card{
    width:100%;
    aspect-ratio:<?php echo esc_attr($cover_ratio); ?>;
    border-radius:0;
    overflow:hidden;
}
.cover-card img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.cover-emoji{
    width:100%;
    height:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    background:linear-gradient(145deg,<?php echo esc_attr($bg); ?>,<?php echo esc_attr($bg); ?>dd);
    padding:40px 32px;
}
.cover-emoji .emoji{
    font-size:96px;
    margin-bottom:28px;
    filter:drop-shadow(0 6px 16px rgba(0,0,0,0.25));
}
.cover-emoji .cover-title{
    font-size:28px;
    font-weight:700;
    color:<?php echo esc_attr($txt); ?>;
    text-align:center;
    line-height:1.4;
    text-shadow:0 3px 16px rgba(0,0,0,0.35);
}

/* 右侧内容 - 可滚动 */
.content-section{
    flex:1;
    min-width:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

.content-scroll{
    flex:1;
    overflow-y:auto;
    padding:48px 56px 32px;
}

/* 元信息 */
.meta-row{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:20px;
    flex-wrap:wrap;
}
.meta-cat{
    font-size:12px;
    font-weight:600;
    color:<?php echo esc_attr($bg); ?>;
    background:<?php echo esc_attr($bg); ?>15;
    padding:6px 14px;
    border-radius:16px;
}
.meta-dot{width:4px;height:4px;background:#ddd;border-radius:50%}
.meta-date{font-size:13px;color:#999}
.meta-read{font-size:13px;color:#999}

/* 标题 */
.article-title{
    font-size:34px;
    font-weight:800;
    color:#1a1a1a;
    line-height:1.25;
    letter-spacing:-0.03em;
    margin-bottom:24px;
}

/* 标签 */
.tag-row{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:32px;
    padding-bottom:24px;
    border-bottom:1px solid #f0f0f0;
}
.tag-row .tag{
    font-size:12px;
    padding:5px 14px;
    background:#f5f5f7;
    color:#666;
    border-radius:10px;
}
.tag-row .tag:hover{background:#e8e8eb;color:#333}

/* 正文 */
.article-body{
    font-size:16px;
    line-height:2;
    color:#333;
}
.article-body p{margin:0 0 24px}
.article-body p:last-child{margin-bottom:0}
.article-body h2{font-size:24px;font-weight:700;color:#1a1a1a;margin:48px 0 18px}
.article-body h3{font-size:18px;font-weight:600;color:#1a1a1a;margin:36px 0 14px}
.article-body ul,.article-body ol{margin:0 0 24px 24px}
.article-body li{margin-bottom:8px;line-height:1.8}
.article-body li::marker{color:<?php echo esc_attr($bg); ?>}
.article-body a{color:<?php echo esc_attr($bg); ?>;border-bottom:1px solid <?php echo esc_attr($bg); ?>40}
.article-body a:hover{border-color:<?php echo esc_attr($bg); ?>}
.article-body strong{font-weight:700;color:#1a1a1a}
.article-body blockquote{
    margin:32px 0;
    padding:24px 28px 24px 44px;
    background:#fafafa;
    border-radius:16px;
    border:1px solid #f0f0f0;
    font-size:15px;
    color:#555;
    position:relative;
}
.article-body blockquote::before{
    content:'';
    position:absolute;
    left:24px;
    top:20px;
    bottom:20px;
    width:4px;
    background:<?php echo esc_attr($bg); ?>;
    border-radius:4px;
}
.article-body code{
    font-family:"SF Mono","Fira Code",monospace;
    font-size:13px;
    background:#f5f5f7;
    padding:3px 8px;
    border-radius:6px;
    color:<?php echo esc_attr($bg); ?>;
}
.article-body pre{
    background:#1c1c1e;
    color:#e8e8e8;
    padding:28px;
    border-radius:16px;
    overflow-x:auto;
    margin:32px 0;
    font-size:14px;
    line-height:1.7;
    font-family:"SF Mono","Fira Code",monospace;
}
.article-body pre code{background:none;color:inherit;padding:0}
.article-body img{max-width:100%;border-radius:16px;margin:28px 0;box-shadow:0 4px 20px rgba(0,0,0,0.06)}

/* 签名 */
.signature{
    margin-top:48px;
    padding:24px 0 0;
    border-top:1px solid #f0f0f0;
    display:flex;
    align-items:center;
    gap:16px;
}
.sig-avatar{
    width:48px;
    height:48px;
    border-radius:14px;
    background:linear-gradient(135deg,<?php echo esc_attr($bg); ?>,<?php echo esc_attr($bg); ?>cc);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    box-shadow:0 4px 16px <?php echo esc_attr($bg); ?>30;
}
.sig-info{line-height:1.5}
.sig-info .name{font-size:14px;font-weight:600;color:#333}
.sig-info .date{font-size:13px;color:#999}

/* 响应式 */
@media(max-width:900px){
    body{padding:16px;align-items:flex-start}
    .back-btn{top:12px;left:12px;padding:8px 16px;font-size:12px}
    .main-card{
        flex-direction:column;
        max-height:none;
        min-height:auto;
        border-radius:24px;
    }
    .cover-section{
        flex:none;
        max-width:none;
        min-width:0;
    }
    .cover-card{
        max-width:none;
        margin:0;
        aspect-ratio:4/5;
    }
    .cover-emoji .emoji{font-size:72px}
    .cover-emoji .cover-title{font-size:22px}
    .content-scroll{padding:24px 20px 40px}
    .article-title{font-size:26px}
}
</style>
</head>
<body>

<a href="<?php echo esc_url($archive_url); ?>" class="back-btn">← 返回</a>

<div class="main-card">
    <!-- 左侧封面 -->
    <div class="cover-section">
        <div class="cover-card">
            <?php if(!empty($show_mdimg)): ?>
                <img src="<?php echo esc_url($show_mdimg); ?>" alt="<?php echo esc_attr($title); ?>">
            <?php else: ?>
                <div class="cover-emoji">
                    <span class="emoji"><?php echo esc_html($emoji); ?></span>
                    <div class="cover-title"><?php echo esc_html($title); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 右侧内容 -->
    <div class="content-section">
        <div class="content-scroll">
            <div class="meta-row">
                <?php if($primary_cat): ?>
                    <span class="meta-cat"><?php echo esc_html($primary_cat->name); ?></span>
                    <span class="meta-dot"></span>
                <?php endif; ?>
                <span class="meta-date"><?php echo esc_html($date); ?></span>
                <span class="meta-dot"></span>
                <span class="meta-read"><?php echo $read_time; ?> 分钟阅读</span>
            </div>
            
            <h1 class="article-title"><?php echo esc_html($title); ?></h1>
            
            <?php if($tags&&!is_wp_error($tags)): ?>
            <div class="tag-row">
                <?php foreach($tags as $tg): ?>
                    <span class="tag"># <?php echo esc_html($tg->name); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="article-body"><?php echo $content; ?></div>
            
            <div class="signature">
                <div class="sig-avatar">📒</div>
                <div class="sig-info">
                    <div class="name"><?php echo esc_html(get_bloginfo('name')); ?></div>
                    <div class="date"><?php echo esc_html($date); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
