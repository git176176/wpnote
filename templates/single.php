<?php if (!defined('ABSPATH')) exit;
// WPNote Single - 10 Templates + Custom CSS v2
$pid = get_the_ID();
$cover = get_post_meta($pid,'wpnote_cover',true);
$emoji = empty($cover['emoji'])?'📝':$cover['emoji'];
$bg = empty($cover['bg_color'])?'#c84b5e':$cover['bg_color'];
$txt = empty($cover['text_color'])?'#ffffff':$cover['text_color'];
$style = empty($cover['style'])?'gradient':$cover['style'];
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
$site_tpl = get_option('wpnote_site_template', 'clean');
$custom_css = get_option('wpnote_custom_css', '');

$raw_sig = get_option('wpnote_signature', '');
if ($raw_sig !== '') {
    $sig_text = str_replace('%BLOGNAME%', get_bloginfo('name'), $raw_sig);
} else {
    $sig_text = get_bloginfo('name');
    $sig_desc = get_bloginfo('description');
    if ($sig_desc) $sig_text .= ' · ' . $sig_desc;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo esc_html($title); ?></title>
<style>
/* ================================================
   SHARED BASE (all templates)
   ================================================ */
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{-webkit-font-smoothing:antialiased}
.pg{display:flex;min-height:100vh}
.col-l{flex:0 0 34%;position:sticky;top:0;height:100vh;overflow:hidden}
.col-l img{width:100%;height:100%;object-fit:cover;display:block}
.tcv{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 36px}
.tcv .ce{font-size:80px;margin-bottom:28px;line-height:1}
.tcv .ct{font-size:22px;color:#fff;line-height:1.4;text-align:center;letter-spacing:-0.02em;text-shadow:0 2px 12px rgba(0,0,0,0.2);max-width:88%}
.tcv .cc{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:20px}
.tcv .cc span{font-size:11px;padding:4px 12px;border-radius:12px;background:rgba(255,255,255,0.22);color:#fff;font-weight:600}
.tcv.grad{background:linear-gradient(145deg,<?php echo $bg; ?>,<?php echo $bg; ?>c0)}
.tcv.glass{background:linear-gradient(145deg,<?php echo $bg; ?>88,<?php echo $bg; ?>55);backdrop-filter:blur(20px)}
.tcv.magazine{background:#fff;flex-direction:row;padding:36px;gap:22px;align-items:flex-end}
.tcv.magazine .ce{font-size:88px;margin:0;flex-shrink:0}
.tcv.magazine .ct{color:<?php echo $bg; ?>;text-align:left;font-size:24px;max-width:none}
.tcv.magazine .cc{justify-content:flex-start;margin:0}
.tcv.cyberpunk{background:linear-gradient(135deg,#0d0d1a,#1a0533,<?php echo $bg; ?>44)}
.tcv.cyberpunk .ce{filter:drop-shadow(0 0 22px <?php echo $bg; ?>)}
.tcv.cyberpunk .ct{text-shadow:0 0 20px <?php echo $bg; ?>}
.tcv.minimalist{background:#111}
.tcv.minimalist .ct{text-transform:uppercase;letter-spacing:0.14em;font-weight:400}
.col-r{flex:1;min-width:0}
.art{max-width:700px;padding:72px 56px 80px}
.prog{position:fixed;top:0;left:0;height:2px;z-index:999;transition:width 0.08s linear;width:0;background:linear-gradient(90deg,<?php echo $bg; ?>,<?php echo $bg; ?>66)}
.back{position:fixed;top:22px;left:24px;z-index:300;display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:22px;background:rgba(255,255,255,0.92);backdrop-filter:blur(12px);color:#666;font-size:12px;font-weight:500;box-shadow:0 2px 16px rgba(0,0,0,0.08);text-decoration:none;transition:all 0.18s;border:1px solid rgba(0,0,0,0.06);letter-spacing:0}
.back:hover{background:#fff;color:<?php echo $bg; ?>;text-decoration:none;box-shadow:0 4px 20px rgba(0,0,0,0.12)}
.tb{display:flex;align-items:center;gap:10px;margin-bottom:28px}
.tb .cat{display:inline-flex;align-items:center;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:<?php echo $bg; ?>;padding:4px 12px;border-radius:20px;background:<?php echo $bg; ?>16}
.tb .dot{width:3px;height:3px;background:#ccc;border-radius:50%}
.tb .dt{font-size:12px;color:#aaa}
.ttl{font-size:36px;font-weight:800;line-height:1.18;color:#0a0a0a;letter-spacing:-0.04em;margin-bottom:20px}
.tgs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:44px;padding-bottom:36px;border-bottom:1px solid #eee}
.tgs .tg{font-size:12px;padding:4px 12px;border-radius:8px;background:#f0ece6;color:#777;font-weight:500;transition:all 0.15s}
.tgs .tg:hover{background:#e0dcd4;color:#444}
.bd{font-size:15.5px;line-height:2.05;color:#2d2d2d}
.bd p{margin:0 0 24px}
.bd p:last-child{margin-bottom:0}
.bd h2{font-size:20px;font-weight:700;color:#0a0a0a;margin:52px 0 18px;letter-spacing:-0.025em;padding-bottom:12px;border-bottom:1px solid #eee}
.bd h3{font-size:16px;font-weight:700;color:#1a1a1a;margin:40px 0 14px}
.bd ul,.bd ol{margin:0 0 24px 24px}
.bd li{margin-bottom:10px;line-height:1.75}
.bd li::marker{color:<?php echo $bg; ?>}
.bd a{color:<?php echo $bg; ?>;text-decoration:none;border-bottom:1px solid <?php echo $bg; ?>30}
.bd a:hover{border-color:<?php echo $bg; ?>}
.bd strong{font-weight:700;color:#0a0a0a}
.bd blockquote{margin:40px 0;padding:22px 26px 22px 42px;background:#f7f5f2;border-radius:12px;border:1px solid #e8e4de;font-size:15.5px;color:#555;line-height:1.85;position:relative}
.bd blockquote::before{content:'';position:absolute;left:22px;top:20px;bottom:20px;width:3px;background:<?php echo $bg; ?>;border-radius:3px}
.bd code{font-family:"SF Mono","Fira Code","Cascadia Code",monospace;font-size:13px;background:#f0ece6;padding:2px 7px;border-radius:5px;color:<?php echo $bg; ?>}
.bd pre{background:#1c1c1e;color:#e8e8e8;padding:28px;border-radius:14px;overflow-x:auto;margin:32px 0;font-size:13.5px;line-height:1.75;font-family:"SF Mono","Fira Code","Cascadia Code",monospace}
.bd pre code{background:none;color:inherit;padding:0}
.bd img{width:100%;border-radius:12px;margin:28px 0;box-shadow:0 4px 24px rgba(0,0,0,0.05)}
.sg{margin-top:64px;padding-top:28px;border-top:1px solid #e0dcd4;display:flex;align-items:center;gap:14px}
.sg-av{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,<?php echo $bg; ?>,<?php echo $bg; ?>cc);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;box-shadow:0 4px 14px <?php echo $bg; ?>33}
.sg-t{font-size:13px;color:#aaa;line-height:1.5}
.sg-t strong{display:block;color:#555;font-weight:600;font-size:14px}

/* ================================================
   TEMPLATE: clean - 纯净白
   ================================================ */
<?php if($site_tpl === 'clean'): ?>
body{background:#ffffff;color:#111;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.art{max-width:720px;padding:80px 72px 80px}
.ttl{font-size:40px;font-weight:800;letter-spacing:-0.05em;margin-bottom:24px}
.tb .cat{background:<?php echo $bg; ?>18}
.bd{font-size:16px;line-height:2.1;color:#1a1a1a}
.bd p{margin-bottom:28px}
.sg{border-top-color:#eee}

/* ================================================
   TEMPLATE: warm - 暖调米
   ================================================ */
<?php elseif($site_tpl === 'warm'): ?>
body{background:#faf8f5;color:#111;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.art{padding:72px 60px 80px;max-width:740px}
.ttl{font-size:38px;font-weight:700;letter-spacing:-0.04em;color:#0d0d0d}
.tgs{border-bottom-color:#e8e4de}
.tgs .tg{background:#f0ece6;color:#777}
.bd blockquote{background:linear-gradient(135deg,#f7f5f2,#f0ebe5);border-color:#e8e0d8}
.sg{border-top-color:#e8e0d8}

/* ================================================
   TEMPLATE: dark - 深色系
   ================================================ */
<?php elseif($site_tpl === 'dark'): ?>
body{background:#0d0d0d;color:#e0e0e0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.art{padding:72px 56px 80px;max-width:720px}
.ttl{color:#f0f0f0;font-weight:700;letter-spacing:-0.04em}
.tb .cat{color:#fff;background:rgba(255,255,255,0.1)}
.tb .dt{color:#888}
.tgs{border-bottom-color:#222}
.tgs .tg{background:#1a1a1a;color:#888}
.bd{color:#c8c8c8;line-height:2}
.bd h2{color:#f0f0f0;border-bottom-color:#222}
.bd h3{color:#e0e0e0}
.bd strong{color:#f0f0f0}
.bd blockquote{background:#141414;border-color:#222;color:#999}
.bd blockquote::before{background:#fff}
.bd code{background:#1a1a1a;color:#e0e0e0}
.bd a{color:#80c0ff;border-color:rgba(128,192,255,0.3)}
.sg{border-top-color:#222;color:#888}
.sg-t strong{color:#c0c0c0}

/* ================================================
   TEMPLATE: gradient - 渐变暖
   ================================================ */
<?php elseif($site_tpl === 'gradient'): ?>
body{background:linear-gradient(160deg,#fff9f5 0%,#fef6f0 40%,#fdf5ec 100%);color:#111;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.col-r{background:linear-gradient(180deg,rgba(255,255,255,0) 0%,rgba(255,249,243,0.6) 100%)}
.art{padding:72px 60px 80px;max-width:740px}
.ttl{font-size:38px;font-weight:800;color:#1a0800;letter-spacing:-0.04em}
.tgs .tg{background:#f5ede5;color:#8a6a5a}
.bd blockquote{background:linear-gradient(135deg,#faf5f0,#f5ede5);border-color:#e8d8c8}
.sg{border-top-color:#e8d8c8}

/* ================================================
   TEMPLATE: editorial - 杂志风
   ================================================ */
<?php elseif($site_tpl === 'editorial'): ?>
body{background:#f5f3ef;color:#111;font-family:"Georgia","Times New Roman",serif}
.art{padding:64px 64px 80px;max-width:760px}
.ttl{font-size:42px;font-weight:400;font-style:italic;letter-spacing:-0.03em;line-height:1.15}
.tb .cat{font-weight:400;letter-spacing:0.1em;background:none;color:<?php echo $bg; ?>;border-bottom:1px solid <?php echo $bg; ?>}
.tgs .tg{background:transparent;color:#aaa;font-weight:400}
.bd{font-family:"Georgia",serif;font-size:17px;line-height:1.95;color:#2a2a2a}
.bd p{margin-bottom:30px}
.bd h2{font-weight:400;font-style:italic;font-size:22px;color:#111}
.bd blockquote{font-style:italic;font-size:18px;color:#555;border-left-width:4px}
.sg{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}

/* ================================================
   TEMPLATE: forest - 墨绿系
   ================================================ */
<?php elseif($site_tpl === 'forest'): ?>
body{background:#f4f8f4;color:#1a2e1a;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.tb .cat{background:rgba(22,100,60,0.1);color:#16643c}
.tgs .tg{background:#e8f0ea;color:#4a7a5a}
.tgs .tg:hover{background:#d4e8da;color:#2d5a3a}
.bd li::marker{color:#16a34a}
.bd blockquote{background:#f0f6f2;border-color:#bbf7d0}
.bd blockquote::before{background:#16a34a}
.sg{border-top-color:#c8e0cc}
.sg-av{background:linear-gradient(135deg,#16a34a,#4ade80)}

/* ================================================
   TEMPLATE: wine - 暗红系
   ================================================ */
<?php elseif($site_tpl === 'wine'): ?>
body{background:#fdf5f5;color:#2a0a0a;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.ttl{color:#1a0505}
.tb .cat{background:rgba(180,40,60,0.1);color:#b4283c}
.tgs .tg{background:#f5e8ea;color:#8a4a55}
.tgs .tg:hover{background:#ebd5d9;color:#6a2a35}
.bd li::marker{color:#b4283c}
.bd blockquote{background:#faf0f2;border-color:#ebcdd4}
.bd blockquote::before{background:#b4283c}
.sg{border-top-color:#ebcdd4}
.sg-av{background:linear-gradient(135deg,#b4283c,#e87070)}

/* ================================================
   TEMPLATE: steel - 蓝灰系
   ================================================ */
<?php elseif($site_tpl === 'steel'): ?>
body{background:#f0f4f8;color:#1a2535;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei","PingFang SC",sans-serif}
.ttl{color:#0d1825}
.tb .cat{background:rgba(50,90,150,0.1);color:#325a96}
.tgs .tg{background:#e0e8f0;color:#5a7aaa}
.tgs .tg:hover{background:#d0dcef;color:#3a5a8a}
.bd li::marker{color:#325a96}
.bd blockquote{background:#f0f4f8;border-color:#c0d0e8}
.bd blockquote::before{background:#325a96}
.sg{border-top-color:#c0d0e8}
.sg-av{background:linear-gradient(135deg,#325a96,#6a9ae8)}

/* ================================================
   TEMPLATE: minimal - 极简线条
   ================================================ */
<?php elseif($site_tpl === 'minimal'): ?>
body{background:#ffffff;color:#000;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
.art{padding:80px 64px 80px;max-width:680px}
.ttl{font-size:32px;font-weight:300;letter-spacing:-0.06em;color:#000}
.tb .cat{background:none;color:#000;font-weight:600;padding:0;font-size:11px;letter-spacing:0.12em;text-transform:uppercase}
.tb .dot{display:none}
.tgs{margin-bottom:40px;padding-bottom:0;border-bottom:none}
.tgs .tg{background:none;color:#aaa;font-size:11px;padding:2px 8px}
.bd{font-size:15px;line-height:2.2;color:#333}
.bd h2{font-weight:300;font-size:18px;border-bottom:none;margin:48px 0 12px;padding-bottom:0;letter-spacing:-0.01em}
.bd blockquote{border-left-width:1px;background:none;border-radius:0;padding:16px 24px;color:#666;font-size:14px}
.bd blockquote::before{display:none}
.sg{border-top-color:#eee;margin-top:56px}
.sg-av{background:#000;border-radius:2px}
.sg-t strong{color:#000;font-weight:300}

/* ================================================
   CUSTOM CSS (always loaded last)
   ================================================ */
<?php endif; ?>
<?php if($custom_css): ?>
<?php echo $custom_css; ?>
<?php endif; ?>

/* ================================================
   RESPONSIVE
   ================================================ */
@media(max-width:900px){
    .back{top:14px;left:14px;padding:7px 14px;font-size:12px}
    .pg{flex-direction:column}
    .col-l{flex:none;width:100%;height:58vw;min-height:220px;position:static}
    .tcv{padding:24px 20px}
    .tcv .ce{font-size:52px;margin-bottom:14px}
    .tcv .ct{font-size:18px}
    .tcv.magazine{flex-direction:column;align-items:center;text-align:center;padding:24px 20px}
    .tcv.magazine .cc{justify-content:center}
    .art{padding:28px 20px 56px}
    .ttl{font-size:24px;letter-spacing:-0.03em}
    .tgs{margin-bottom:32px;padding-bottom:24px}
}
</style>
</head>
<body>

<div class="prog" id="prog"></div>
<a href="<?php echo esc_url($archive_url); ?>" class="back">← 返回</a>

<div class="pg">
    <!-- 左封面（不动） -->
    <div class="col-l">
        <?php if(!empty($show_mdimg)): ?>
            <img src="<?php echo esc_url($show_mdimg); ?>" alt="<?php echo esc_attr($title); ?>">
        <?php else: ?>
            <div class="tcv <?php echo esc_attr($style); ?>">
                <span class="ce"><?php echo esc_html($emoji); ?></span>
                <div class="ct"><?php echo esc_html($title); ?></div>
                <div class="cc">
                    <?php if($cats&&!is_wp_error($cats)): foreach($cats as $c): ?>
                        <span><?php echo esc_html($c->name); ?></span>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 右正文 -->
    <div class="col-r">
        <div class="art">
            <div class="tb">
                <?php if($primary_cat): ?>
                    <span class="cat"><?php echo esc_html($primary_cat->name); ?></span>
                    <span class="dot"></span>
                <?php endif; ?>
                <span class="dt"><?php echo esc_html($date); ?></span>
                <span class="dot"></span>
                <span class="dt"><?php echo $read_time; ?> 分钟阅读</span>
            </div>
            <h1 class="ttl"><?php echo esc_html($title); ?></h1>
            <?php if($tags&&!is_wp_error($tags)): ?>
            <div class="tgs">
                <?php foreach($tags as $tg): ?>
                    <span class="tg"># <?php echo esc_html($tg->name); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="bd"><?php echo $content; ?></div>
            <div class="sg">
                <div class="sg-av">📒</div>
                <div class="sg-t"><strong><?php echo esc_html($sig_text); ?></strong><?php echo esc_html($date); ?></div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('scroll',function(){
    var doc=document.documentElement;
    var top=(doc.scrollTop||document.body.scrollTop);
    var height=doc.scrollHeight-doc.clientHeight;
    document.getElementById('prog').style.width=(height>0?top/height*100:0)+'%';
},{passive:true});
</script>
</body>
</html>
