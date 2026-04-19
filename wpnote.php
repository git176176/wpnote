<?php
/*
Plugin Name: WPNote
Description: 图文笔记插件，支持emoji文字封面和瀑布流展示
Version: 1.2.4
*/

if (!defined('ABSPATH')) exit;

define('WPNOTE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPNOTE_PLUGIN_URL', plugin_dir_url(__FILE__));

class WPNote_Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'add_rewrite_rules'), 2);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('rest_api_init', array($this, 'register_api'));
        add_action('template_redirect', array($this, 'load_template'), 1);
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('add_meta_boxes', array($this, 'add_cover_metabox'));
        add_action('save_post_wpnote', array($this, 'save_cover_meta'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_ajax_wpnote_generate_cover', array($this, 'ajax_generate_cover'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        $this->register_cpt();
        flush_rewrite_rules();
    }

    public function register_cpt() {
        register_post_type('wpnote', array(
            'label' => 'WPNote',
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'wpnote', 'with_front' => true),
            'show_in_rest' => true,
            'rest_base' => 'wpnote',
            'labels' => array(
                'name' => 'WPNote',
                'singular_name' => 'WPNote',
                'add_new' => '写笔记',
                'add_new_item' => '写新笔记',
                'edit_item' => '编辑笔记',
                'new_item' => '新笔记',
                'view_item' => '查看笔记',
                'search_items' => '搜索笔记',
                'not_found' => '暂无笔记',
            ),
        ));

        register_taxonomy('wpnote_category', 'wpnote', array(
            'label' => '笔记分类',
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'wpnote-category'),
        ));

        register_taxonomy('wpnote_tag', 'wpnote', array(
            'label' => '笔记标签',
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'wpnote-tag'),
        ));
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('wpnote/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$', 'index.php?post_type=wpnote&wpnote_date=$matches[1]', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'wpnote_date';
        return $vars;
    }

    public function register_api() {
        register_rest_route('wpnote/v1', '/posts', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_create'),
            'permission_callback' => function() {
                $key = isset($_SERVER['HTTP_X_WPNOTE_KEY']) ? $_SERVER['HTTP_X_WPNOTE_KEY'] : '';
                return $key === get_option('wpnote_api_key', '');
            },
        ));

        register_rest_route('wpnote/v1', '/posts/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('wpnote/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_categories'),
            'permission_callback' => '__return_true',
        ));
    }

    public function api_create($request) {
        $params = $request->get_json_params();
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $content = isset($params['content']) ? $params['content'] : '';

        if (empty($title)) {
            return new WP_Error('error', '标题不能为空', array('status' => 400));
        }

        $post_data = array(
            'post_type' => 'wpnote',
            'post_title' => $title,
            'post_content' => $content ? wpautop(wp_kses_post($content)) : '',
            'post_status' => isset($params['status']) ? $params['status'] : 'publish',
        );

        if (!empty($params['slug'])) {
            $post_data['post_name'] = sanitize_title($params['slug']);
        }

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            return new WP_Error('error', '创建失败', array('status' => 500));
        }

        // 设置分类和标签（先设置，因为MD2Card需要分类名）
        if (!empty($params['category'])) {
            wp_set_object_terms($post_id, $params['category'], 'wpnote_category');
        }
        if (!empty($params['tags'])) {
            wp_set_object_terms($post_id, $params['tags'], 'wpnote_tag');
        }

        // 封面处理
        $cover_data = array();
        
        // 自动生成MD2Card封面
        if (!empty($params['auto_cover'])) {
            $md2card_result = $this->generate_md2card_cover($post_id, $title, $params);
            if (!is_wp_error($md2card_result)) {
                $cover_data = array_merge($cover_data, $md2card_result);
            }
        }
        
        // 手动设置的封面（优先级更高）
        if (!empty($params['cover']) && is_array($params['cover'])) {
            $valid_styles = array('gradient','glass','magazine','cyberpunk','minimalist');
            $style = isset($params['cover']['style']) ? $params['cover']['style'] : 'gradient';
            if ($style === 'random') {
                $style = $valid_styles[array_rand($valid_styles)];
            } elseif (!in_array($style, $valid_styles)) {
                $style = 'gradient';
            }
            $cover_data['emoji'] = isset($params['cover']['emoji']) ? sanitize_text_field($params['cover']['emoji']) : '📝';
            $cover_data['bg_color'] = $this->sanitize_hex_color($params['cover']['bg_color']);
            $cover_data['text_color'] = $this->sanitize_hex_color($params['cover']['text_color']);
            $cover_data['style'] = $style;
        }
        
        if (!empty($cover_data)) {
            update_post_meta($post_id, 'wpnote_cover', $cover_data);
        }

        return array(
            'success' => true, 
            'post_id' => $post_id, 
            'url' => get_permalink($post_id),
            'cover' => $cover_data,
        );
    }
    
    /**
     * 生成MD2Card封面
     */
    private function generate_md2card_cover($post_id, $title, $params = array()) {
        $api_key = get_option('wpnote_md2card_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'MD2Card API Key 未设置');
        }
        
        // 获取分类名
        $cats = get_the_terms($post_id, 'wpnote_category');
        $cat_name = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';
        
        // 获取主题
        $md2card_themes = array(
            'apple-notes','coil-notebook','pop-art','bytedance','alibaba','art-deco',
            'glassmorphism','warm','minimal','minimalist','dreamy','nature','xiaohongshu',
            'notebook','darktech','typewriter','watercolor','traditional-chinese','fairytale',
            'business','japanese-magazine','cyberpunk','meadow-dawn'
        );
        $theme = isset($params['cover_theme']) && in_array($params['cover_theme'], $md2card_themes) 
            ? $params['cover_theme'] 
            : 'glassmorphism';
        
        $payload = array(
            'text' => mb_substr($title, 0, 80),
            'keywords' => $cat_name,
            'count' => 1,
            'theme' => $theme,
            'width' => 600,
            'height' => 800,
        );

        $response = wp_remote_post('https://md2card.cn/api/generate/cover', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'MD2Card API请求失败');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success']) || empty($body['images'][0]['url'])) {
            return new WP_Error('gen_failed', '封面生成失败');
        }

        return array(
            'image' => $body['images'][0]['url'],
            'md2card_theme' => $theme,
        );
    }

    private function sanitize_hex_color($color) {
        $color = ltrim($color, '#');
        if (preg_match('/^[a-f0-9]{6}$/i', $color)) {
            return '#' . $color;
        }
        return '#667eea';
    }

    public function api_get($request) {
        $post = get_post($request['id']);
        if (!$post || $post->post_type !== 'wpnote') {
            return new WP_Error('not_found', '笔记不存在', array('status' => 404));
        }
        $cover = get_post_meta($post->ID, 'wpnote_cover', true);
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'cover' => $cover,
            'date' => $post->post_date,
            'url' => get_permalink($post->ID),
        );
    }

    public function api_categories() {
        $cats = get_terms(array('taxonomy' => 'wpnote_category', 'hide_empty' => false));
        $tags = get_terms(array('taxonomy' => 'wpnote_tag', 'hide_empty' => false));
        return array('categories' => $cats, 'tags' => $tags);
    }

    public function add_menu() {
        add_submenu_page('edit.php?post_type=wpnote', 'WPNote设置', '设置', 'manage_options', 'wpnote_settings', array($this, 'settings_page'));
    }

    public function add_cover_metabox() {
        add_meta_box('wpnote_cover_box', '文字封面', array($this, 'render_cover_metabox'), 'wpnote', 'normal', 'high');
    }

    public function render_cover_metabox($post) {
        wp_nonce_field('wpnote_cover', 'wpnote_cover_nonce');
        $cover = get_post_meta($post->ID, 'wpnote_cover', true);
        $emoji = empty($cover['emoji']) ? '📝' : $cover['emoji'];
        $bg_color = empty($cover['bg_color']) ? '#667eea' : $cover['bg_color'];
        $text_color = empty($cover['text_color']) ? '#ffffff' : $cover['text_color'];
        $style = empty($cover['style']) ? 'gradient' : $cover['style'];
        $md2card_theme = empty($cover['md2card_theme']) ? 'glassmorphism' : $cover['md2card_theme'];
        $colors = get_option('wpnote_cover_colors', array('#667eea','#f093fb','#f5576c','#4facfe','#43e97b','#fa709a','#fee140','#30cfd0','#a8edea','#ff9a9e','#ffecd2','#a18cd1','#d299c2','#fef9d7','#89f7fe'));
        $title_short = mb_substr($post->post_title, 0, 8);
        $existing_image = empty($cover['image']) ? '' : $cover['image'];

        $styles = array(
            'gradient' => '柔和渐变',
            'glass'    => '玻璃拟态',
            'magazine' => '杂志风',
            'cyberpunk'=> '赛博朋克',
            'minimalist' => '极简黑白',
        );

        // MD2Card 主题列表
        $md2card_themes = array(
            'apple-notes'   => '苹果备忘录',
            'coil-notebook' => '线圈笔记本',
            'pop-art'       => '波普艺术',
            'bytedance'     => '字节范',
            'alibaba'       => '阿里橙',
            'art-deco'      => '艺术装饰',
            'glassmorphism' => '玻璃拟态',
            'warm'          => '温暖柔和',
            'minimal'       => '简约高级灰',
            'minimalist'    => '极简黑白',
            'dreamy'        => '梦幻渐变',
            'nature'        => '清新自然',
            'xiaohongshu'   => '紫色小红书',
            'notebook'      => '笔记本',
            'darktech'      => '暗黑科技',
            'typewriter'    => '复古打字机',
            'watercolor'    => '水彩艺术',
            'traditional-chinese' => '中国传统',
            'fairytale'     => '儿童童话',
            'business'      => '商务简报',
            'japanese-magazine' => '日本杂志',
            'cyberpunk'     => '赛博朋克',
            'meadow-dawn'   => '青野晨光',
        );

        $presets_html = '';
        foreach ($colors as $c) {
            $is_active = ($c === $bg_color) ? 'active' : '';
            $presets_html .= '<button type="button" class="color-btn ' . $is_active . '" data-color="' . esc_attr($c) . '" style="background:' . esc_attr($c) . '" title="' . esc_attr($c) . '"></button>';
        }

        $style_options = '';
        foreach ($styles as $k => $v) {
            $checked = ($k === $style) ? 'checked' : '';
            $style_options .= '<label class="style-radio"><input type="radio" name="wpnote_cover[style]" value="' . esc_attr($k) . '" ' . $checked . '><span>' . esc_html($v) . '</span></label>';
        }

        $md2card_options = '';
        foreach ($md2card_themes as $k => $v) {
            $sel = ($k === $md2card_theme) ? 'selected' : '';
            $md2card_options .= '<option value="' . esc_attr($k) . '" ' . $sel . '>' . esc_html($v) . '</option>';
        }

        $preview_img_html = $existing_image ? '<img src="' . esc_url($existing_image) . '" style="max-width:100%;border-radius:8px;margin-top:8px;">' : '';

        echo '<div class="wpnote-cover-builder" data-post-id="' . $post->ID . '" data-title="' . esc_attr($title_short) . '" data-style="' . esc_attr($style) . '">';

        // === AI 生成区域 ===
        echo '<div class="wpnote-ai-section" style="background:#f0f4ff;border:1px solid #d0d8f0;border-radius:10px;padding:14px;margin-bottom:16px;">';
        echo '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">';
        echo '<label style="font-size:13px;font-weight:600;color:#333;">🖼️ AI封面</label>';
        echo '<select name="wpnote_cover[md2card_theme]" class="wpnote-md2card-theme" style="flex:1;min-width:150px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;font-size:13px;">
            ' . $md2card_options . '
            </select>';
        echo '<button type="button" class="button button-primary wpnote-gen-btn" style="background:#667eea;border-color:#667eea;" data-post-id="' . $post->ID . '">✨ 生成封面</button>';
        echo '</div>';
        echo '<div class="wpnote-gen-msg" style="font-size:12px;margin-top:8px;color:#666;display:none;"></div>';
        echo $preview_img_html;
        echo '</div>';

        // === 文字封面区域 ===
        echo '<div style="border-top:1px solid #eee;padding-top:14px;">';
        echo '<label style="font-size:13px;font-weight:600;color:#333;display:block;margin-bottom:10px;">📝 文字封面</label>';
        echo '<div class="wpnote-cover-row">';
        echo '<div class="wpnote-cover-field"><label>Emoji</label>';
        echo '<input type="text" class="wpnote-emoji-input" name="wpnote_cover[emoji]" value="' . esc_attr($emoji) . '" maxlength="10"></div>';
        echo '<div class="wpnote-cover-field"><label>背景色</label>';
        echo '<input type="color" class="wpnote-bg-input" name="wpnote_cover[bg_color]" value="' . esc_attr($bg_color) . '"></div>';
        echo '<div class="wpnote-cover-field"><label>文字色</label>';
        echo '<input type="color" class="wpnote-text-input" name="wpnote_cover[text_color]" value="' . esc_attr($text_color) . '"></div>';
        echo '<div class="wpnote-cover-preview style-' . esc_attr($style) . '" style="background:' . esc_attr($bg_color) . '">';
        echo '<span class="emoji">' . esc_html($emoji) . '</span>';
        echo '<span class="title" style="color:' . esc_attr($text_color) . '">' . esc_html($title_short) . '</span></div>';
        echo '</div>';
        echo '<div class="wpnote-color-presets">';
        echo '<label style="font-size:12px;color:#666;margin-bottom:6px;display:block;">快捷颜色：</label>';
        echo $presets_html;
        echo '</div>';
        echo '<div class="wpnote-style-selector">';
        echo '<label style="font-size:12px;color:#666;margin-bottom:8px;display:block;">文字风格：</label>';
        echo '<div class="wpnote-styles">' . $style_options . '</div>';
        echo '</div></div>';
        echo '</div>';
    }

    public function save_cover_meta($post_id) {
        if (!isset($_POST['wpnote_cover_nonce']) || !wp_verify_nonce($_POST['wpnote_cover_nonce'], 'wpnote_cover')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!empty($_POST['wpnote_cover']) && is_array($_POST['wpnote_cover'])) {
            $valid_styles = array('gradient','glass','magazine','cyberpunk','minimalist');
            $style = in_array($_POST['wpnote_cover']['style'], $valid_styles) ? $_POST['wpnote_cover']['style'] : 'gradient';
            $existing = get_post_meta($post_id, 'wpnote_cover', true);
            if (!is_array($existing)) $existing = array();
            $cover = array(
                'emoji' => sanitize_text_field($_POST['wpnote_cover']['emoji']),
                'bg_color' => $this->sanitize_hex_color($_POST['wpnote_cover']['bg_color']),
                'text_color' => $this->sanitize_hex_color($_POST['wpnote_cover']['text_color']),
                'style' => $style,
                'image' => isset($existing['image']) ? $existing['image'] : '',
                'md2card_theme' => isset($_POST['wpnote_cover']['md2card_theme']) ? sanitize_text_field($_POST['wpnote_cover']['md2card_theme']) : (isset($existing['md2card_theme']) ? $existing['md2card_theme'] : 'glassmorphism'),
            );
            update_post_meta($post_id, 'wpnote_cover', $cover);
        }
    }

    public function admin_assets($hook) {
        global $post_type;
        if ($post_type === 'wpnote' && in_array($hook, array('post.php','post-new.php'))) {
            wp_enqueue_style('wpnote-admin', WPNOTE_PLUGIN_URL . 'assets/css/wpnote.css', array(), '1.0.0');
            wp_enqueue_script('wpnote-admin', WPNOTE_PLUGIN_URL . 'assets/js/wpnote.js', array('jquery'), '1.0.0', true);
            wp_localize_script('wpnote-admin', 'wpnoteAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpnote_cover'),
            ));
        }
    }

    public function ajax_generate_cover() {
        check_ajax_referer('wpnote_cover', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $theme = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : 'glassmorphism';

        if (!$post_id) {
            wp_send_json_error(array('message' => '无效的帖子ID'));
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'wpnote') {
            wp_send_json_error(array('message' => '帖子不存在'));
        }

        // MD2Card API Key
        $api_key = get_option('wpnote_md2card_api_key', '');

        // 优先使用AJAX传来的当前标题（用户可能已编辑过），否则用数据库标题
        $title = isset($_POST['title']) && !empty(trim($_POST['title'])) ? sanitize_text_field($_POST['title']) : $post->post_title;
        $cats = get_the_terms($post_id, 'wpnote_category');
        $cat_name = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';

        $payload = array(
            'text' => mb_substr($title, 0, 80),
            'keywords' => $cat_name,
            'count' => 1,
            'theme' => $theme,
            'width' => 600,
            'height' => 800,
        );

        $response = wp_remote_post('https://md2card.cn/api/generate/cover', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'API请求失败: ' . $response->get_error_message()));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success']) || empty($body['images'][0]['url'])) {
            $msg = isset($body['message']) ? $body['message'] : '生成失败';
            wp_send_json_error(array('message' => $msg));
        }

        $image_url = $body['images'][0]['url'];

        // 直接存URL，不依赖媒体库
        $cover = array(
            'image' => $image_url,
            'md2card_theme' => $theme,
        );
        update_post_meta($post_id, 'wpnote_cover', $cover);

        wp_send_json_success(array(
            'message' => '封面生成成功',
            'image_url' => $image_url,
        ));
    }

    public function settings_page() {
        if (isset($_POST['save']) && wp_verify_nonce($_POST['nonce'], 'wpnote_settings')) {
            update_option('wpnote_api_key', sanitize_text_field(empty($_POST['api_key']) ? wp_generate_uuid4() : $_POST['api_key']));
            update_option('wpnote_md2card_api_key', sanitize_text_field($_POST['md2card_api_key']));
            $valid_themes = array('default','bytedance','alibaba','xiaohongshu','minimal','glass','cyberpunk','warm','magazine','nature');
            $site_theme = isset($_POST['site_theme']) && in_array($_POST['site_theme'], $valid_themes) ? $_POST['site_theme'] : 'default';
            update_option('wpnote_site_theme', $site_theme);
            $colors = array();
            if (!empty($_POST['cover_colors']) && is_array($_POST['cover_colors'])) {
                foreach ($_POST['cover_colors'] as $c) {
                    $c = trim($c);
                    if (preg_match('/^#[a-f0-9]{6}$/i', $c)) $colors[] = $c;
                }
            }
            if (count($colors) < 3) $colors = array('#667eea','#f093fb','#f5576c','#4facfe','#43e97b','#fa709a','#fee140','#30cfd0','#a8edea','#ff9a9e','#ffecd2','#a18cd1','#d299c2','#fef9d7','#89f7fe');
            update_option('wpnote_cover_colors', $colors);
            update_option('wpnote_signature', isset($_POST['wpnote_signature']) && !empty(trim($_POST['wpnote_signature'])) ? sanitize_text_field($_POST['wpnote_signature']) : '');
            $valid_st = array('clean','warm','dark','gradient','editorial','forest','wine','steel','minimal','card','custom');
            $site_tpl = isset($_POST['wpnote_site_template']) && in_array($_POST['wpnote_site_template'], $valid_st) ? $_POST['wpnote_site_template'] : 'clean';
            update_option('wpnote_site_template', $site_tpl);
            update_option('wpnote_custom_css', isset($_POST['wpnote_custom_css']) ? wp_strip_all_tags($_POST['wpnote_custom_css']) : '');
            // 卡片模板设置
            update_option('wpnote_card_width', isset($_POST['wpnote_card_width']) ? intval($_POST['wpnote_card_width']) : 1200);
            update_option('wpnote_card_height', isset($_POST['wpnote_card_height']) ? intval($_POST['wpnote_card_height']) : 85);
            update_option('wpnote_page_bg_color', isset($_POST['wpnote_page_bg_color']) ? sanitize_hex_color($_POST['wpnote_page_bg_color']) : '#f5f5f7');
            update_option('wpnote_cover_ratio', isset($_POST['wpnote_cover_ratio']) ? sanitize_text_field($_POST['wpnote_cover_ratio']) : '3/4');
            update_option('wpnote_cover_width', isset($_POST['wpnote_cover_width']) ? intval($_POST['wpnote_cover_width']) : 45);
            echo '<div class="notice notice-success"><p>设置已保存</p></div>';
        }

        $api_key = get_option('wpnote_api_key', '');
        $md2card_key = get_option('wpnote_md2card_api_key', '');
        $site_theme = get_option('wpnote_site_theme', 'default');
        $colors = get_option('wpnote_cover_colors', array('#667eea','#f093fb','#f5576c','#4facfe','#43e97b','#fa709a','#fee140','#30cfd0','#a8edea','#ff9a9e','#ffecd2','#a18cd1','#d299c2','#fef9d7','#89f7fe'));
        $colors_html = '';
        foreach ($colors as $c) {
            $colors_html .= '<input type="color" name="cover_colors[]" value="' . esc_attr($c) . '">';
        }
        $rest_url = esc_url(rest_url('wpnote/v1/posts'));
        $api_key_disp = esc_html($api_key);
        $md2card_key_disp = esc_html($md2card_key);

        echo '<div class="wrap"><h1>WPNote 设置</h1>';
        echo '<style>.wpnote-settings{max-width:700px;margin-top:20px}.wpnote-card2{background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:24px;margin-bottom:24px}.wpnote-card2 h2{margin:0 0 16px;font-size:16px}.wpnote-api-box{background:#f6f7f7;border:1px solid #c3c4c7;border-radius:8px;padding:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}.wpnote-api-box code{font-size:13px;flex:1;word-break:break-all;min-width:200px}.wpnote-color-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:12px}.wpnote-color-grid input[type="color"]{width:100%;height:40px;border:none;cursor:pointer;border-radius:8px}</style>';
        echo '<form method="post" class="wpnote-settings">';
        echo '<div class="wpnote-card2"><h2>WPNote REST API Key</h2>';
        echo '<div class="wpnote-api-box"><code id="api_key_display">' . $api_key_disp . '</code>';
        echo '<input type="hidden" name="api_key" value="' . $api_key_disp . '">';
        echo '<button type="button" class="button button-secondary" onclick="var k=Math.random().toString(36).substr(2,36);document.getElementById(\'api_key_display\').textContent=k;this.form.api_key.value=k;">重置Key</button></div>';
        echo '<p style="margin:12px 0 0;font-size:12px;color:#646970;"><strong>调用示例：</strong><br><code>curl -X POST ' . $rest_url . ' -H "Content-Type: application/json" -H "X-WPNote-Key: ' . $api_key_disp . '" -d \'{"title":"笔记标题","content":"正文","cover":{"emoji":"📝","bg_color":"#667eea"}}\'</code></p>';
        echo '</div>';
        echo '<div class="wpnote-card2"><h2>MD2Card API Key <span style="font-size:12px;font-weight:400;color:#666;">（生成AI封面用）</span></h2>';
        echo '<div class="wpnote-api-box">';
        echo '<input type="text" name="md2card_api_key" value="' . $md2card_key_disp . '" placeholder="sk-xxxxx" style="flex:1;min-width:200px;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">';
        echo '<a href="https://md2card.cn/zh?inviteCode=556677" target="_blank" class="button" style="background:#ff6b35;color:#fff;border-color:#ff6b35;text-decoration:none;padding:10px 16px;border-radius:8px;">访问官网获取Key →</a>';
        echo '</div>';
        echo '<p style="margin:8px 0 0;font-size:12px;color:#888;">请填写自己的 MD2Card API Key，积分需自行在 <a href="https://md2card.cn/zh?inviteCode=556677" target="_blank">官网</a> 充值，使用 邀请码 556677 注册奖励 30积分（¥5）。</p>';
        echo '</div>';

        // 主题皮肤设置
        $theme_labels = array(
            'default'    => '🟣 简约紫',
            'bytedance' => '🔴 字节范',
            'alibaba'   => '🟠 阿里橙',
            'xiaohongshu' => '🔴 小红书',
            'minimal'   => '⚫ 极简灰',
            'glass'     => '🔮 玻璃拟态',
            'cyberpunk' => '💜 赛博朋克',
            'warm'      => '🧡 温暖柔和',
            'magazine'  => '📰 杂志风',
            'nature'    => '🌿 清新自然',
        );
        $theme_thumb_colors = array(
            'default'    => '#667eea',
            'bytedance' => '#ff2d55',
            'alibaba'   => '#ff6000',
            'xiaohongshu' => '#fe1f4a',
            'minimal'   => '#1a1a1a',
            'glass'     => '#c4b5fd',
            'cyberpunk' => '#00f0ff',
            'warm'      => '#f5576c',
            'magazine'  => '#1a1a1a',
            'nature'    => '#43e97b',
        );
        $theme_opts = '';
        foreach ($theme_labels as $k => $v) {
            $sel = ($k === $site_theme) ? 'selected' : '';
            $c = $theme_thumb_colors[$k];
            $theme_opts .= '<option value="' . esc_attr($k) . '" ' . $sel . '>' . esc_html($v) . '</option>';
        }
        echo '<div class="wpnote-card2"><h2>🏠 首页主题皮肤</h2>';
        echo '<select name="site_theme" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">' . $theme_opts . '</select>';
        echo '<p style="margin:8px 0 0;font-size:12px;color:#888;">设置首页的默认皮肤，访客进入时会应用此主题。笔记页面皮肤的设置在写笔记时的「文字封面」面板中。</p>';
        echo '</div>';

        echo '<div class="wpnote-card2"><h2>📝 笔记页底部签名</h2>';
        $sig_val = esc_attr(get_option('wpnote_signature', ''));
        $site_template = get_option('wpnote_site_template', 'clean');
        $custom_css = get_option('wpnote_custom_css', '');
        // 卡片模板设置
        $card_width = get_option('wpnote_card_width', 1200);
        $card_height = get_option('wpnote_card_height', 85);
        $page_bg_color = get_option('wpnote_page_bg_color', '#f5f5f7');
        $cover_ratio = get_option('wpnote_cover_ratio', '3/4');
        $cover_width = get_option('wpnote_cover_width', 45);
        echo '<input type="text" name="wpnote_signature" value="' . $sig_val . '" placeholder="留空则显示站点名称和描述" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:8px;">';
        echo '<p style="font-size:12px;color:#888;margin:0;">';
        $blogname = htmlspecialchars(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
        $blogdesc = htmlspecialchars(get_bloginfo('description'), ENT_QUOTES, 'UTF-8');
        echo '支持文字和 emoji。留空默认显示：' . $blogname . ' · ' . $blogdesc . '</p>';
        echo '</div>';
        $st_labels = array('clean'=>'clean - 纯净白','warm'=>'warm - 暖调米','dark'=>'dark - 深色系','gradient'=>'gradient - 渐变暖','editorial'=>'editorial - 杂志风','forest'=>'forest - 墨绿系','wine'=>'wine - 暗红系','steel'=>'steel - 蓝灰系','minimal'=>'minimal - 极简线条','card'=>'card - 卡片悬浮','custom'=>'custom - 自定义CSS');
        $st_opts = '';
        foreach ($st_labels as $tk => $tl) { $sel = ($tk === $site_template) ? 'selected' : ''; $st_opts .= '<option value="' . esc_attr($tk) . '" ' . $sel . '>' . esc_html($tl) . '</option>'; }
        $custom_css_val = esc_textarea($custom_css);
        $tpl_display = ($site_template === 'custom') ? 'block' : 'none';
        echo '<div class="wpnote-card2"><h2>笔记页模板 + 自定义CSS</h2>';
        echo '<select name="wpnote_site_template" id="st_select" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:12px;">' . $st_opts . '</select>';
        echo '<textarea name="wpnote_custom_css" id="custom_css_area" rows="6" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:12px;font-family:monospace;resize:vertical;display:' . $tpl_display . ';">' . $custom_css_val . '</textarea>';
        echo '<p style="font-size:12px;color:#888;margin:8px 0 0;">自定义CSS叠加在笔记页模板之上。选择「custom」后可在下方输入自定义样式。</p>';
        echo '</div>';
        
        // 卡片模板设置
        echo '<div class="wpnote-card2" id="card-settings" style="display:' . ($site_template === 'card' ? 'block' : 'none') . ';">';
        echo '<h2>🎨 卡片模板设置</h2>';
        echo '<p style="font-size:12px;color:#888;margin:0 0 16px;">以下设置仅在选择「card - 卡片悬浮」模板时生效</p>';
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">';
        echo '<div><label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">卡片宽度 (px)</label>';
        echo '<input type="number" name="wpnote_card_width" value="' . esc_attr($card_width) . '" min="600" max="1600" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;"></div>';
        echo '<div><label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">卡片高度 (%)</label>';
        echo '<input type="number" name="wpnote_card_height" value="' . esc_attr($card_height) . '" min="50" max="95" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;"></div>';
        echo '</div>';
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">';
        echo '<div><label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">封面占比 (%)</label>';
        echo '<input type="number" name="wpnote_cover_width" value="' . esc_attr($cover_width) . '" min="25" max="60" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;"></div>';
        echo '<div><label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">封面比例</label>';
        $ratio_opts = array('3/4'=>'3:4 (小红书)','2/3'=>'2:3','4/5'=>'4:5','1/1'=>'1:1 正方形','9/16'=>'9:16 竖屏');
        $ratio_select = '<select name="wpnote_cover_ratio" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;">';
        foreach ($ratio_opts as $rv => $rl) { $rsel = ($rv === $cover_ratio) ? 'selected' : ''; $ratio_select .= '<option value="' . esc_attr($rv) . '" ' . $rsel . '>' . esc_html($rl) . '</option>'; }
        $ratio_select .= '</select>';
        echo $ratio_select . '</div>';
        echo '</div>';
        echo '<div><label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">页面背景色</label>';
        echo '<input type="color" name="wpnote_page_bg_color" value="' . esc_attr($page_bg_color) . '" style="width:100%;height:44px;border:1px solid #ddd;border-radius:8px;cursor:pointer;"></div>';
        echo '</div>';
        
        // JS控制显示隐藏
        echo '<script>
        document.getElementById("st_select").addEventListener("change", function(){
            var cardSettings = document.getElementById("card-settings");
            cardSettings.style.display = this.value === "card" ? "block" : "none";
        });
        </script>';
        
        echo '<div class="wpnote-card2"><h2>文字封面预设颜色</h2>';
        echo '<p style="font-size:12px;color:#646970;margin:0 0 12px;">最多15个颜色，写笔记时可选</p>';
        echo '<div class="wpnote-color-grid">' . $colors_html . '</div></div>';
        echo '<p class="submit"><input type="submit" name="save" class="button button-primary" value="保存设置"></p>';
        echo wp_nonce_field('wpnote_settings', 'nonce', true, false);
        echo '</form></div>';
    }

    public function load_template() {
        if (is_singular('wpnote')) {
            add_filter('body_class', function($classes) { $classes[] = 'wpnote-single'; return $classes; });
            
            // 支持card模板
            $site_tpl = get_option('wpnote_site_template', 'clean');
            if ($site_tpl === 'card') {
                $file = WPNOTE_PLUGIN_DIR . 'templates/single-card.php';
            } else {
                $file = WPNOTE_PLUGIN_DIR . 'templates/single.php';
            }
            
            if (file_exists($file)) {
                include $file;
                exit;
            }
        }

        if (is_post_type_archive('wpnote') || is_tax('wpnote_category') || is_tax('wpnote_tag')) {
            add_filter('body_class', function($classes) { $classes[] = 'wpnote-archive'; return $classes; });
            
            // 确保查询有结果
            global $wp_query;
            if ($wp_query->is_404()) {
                $wp_query->is_404 = false;
                status_header(200);
            }
            
            $file = WPNOTE_PLUGIN_DIR . 'templates/archive.php';
            if (file_exists($file)) {
                include $file;
                exit;
            }
        }
    }
}

function wpnote_plugin() { return WPNote_Plugin::instance(); }
wpnote_plugin();
