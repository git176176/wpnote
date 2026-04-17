# WPNote - 小红书风格图文笔记

小红书风格的 WordPress 图文笔记插件，支持 Emoji 文字封面、瀑布流布局、MD2Card AI 封面生成。

## 功能特性

- 🎨 **10 套主题皮肤**：简约紫、字节范、阿里橙、小红书、极简灰、玻璃拟态、赛博朋克、温暖柔和、杂志风、清新自然
- 📝 **Emoji 文字封面**：无需配图，用 Emoji + 彩色背景即可生成精美封面
- ✨ **MD2Card AI 封面**：输入标题，一键生成专属 AI 封面（需配置 API Key）
- 📱 **瀑布流布局**：首页 Masonry 瀑布流展示，3 栏自适应
- 🏷️ **分类 + 标签**：支持分类目录和标签归档
- 📖 **左右分栏阅读**：竖版封面 + 正文，沉浸式阅读体验
- ⚡ **REST API 发布**：支持远程创建笔记，接口见设置页
- 🎨 **10 套笔记页模板**：纯净白、暖调米、深色系、渐变暖、杂志风、墨绿系、暗红系、蓝灰系、极简线条、自定义 CSS
- 🖋️ **自定义 CSS**：笔记页支持完全自定义 CSS 样式

## 安装

1. 下载最新版本，上传到 WordPress 插件目录
2. 后台激活插件
3. 进入 **WPNote → 设置** 配置 API Key

## MD2Card AI 封面

前往 [MD2Card](https://md2card.cn/zh?inviteCode=556677) 注册获取 API Key，使用邀请码 `556677` 注册奖励 30 积分（¥5）。

## 版本

当前版本：**v1.1.9**

## 截图

- 首页：侧边栏 + 瀑布流卡片
- 笔记页：左侧竖版封面 + 右侧正文

## 接口示例

```bash
curl -X POST https://yoursite.com/wp-json/wpnote/v1/posts \
  -H "Content-Type: application/json" \
  -H "X-WPNote-Key: YOUR_API_KEY" \
  -d '{
    "title": "笔记标题",
    "content": "正文内容",
    "cover": {
      "emoji": "📝",
      "bg_color": "#667eea"
    }
  }'
```
