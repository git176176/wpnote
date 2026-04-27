# WPNote - WordPress 图文笔记插件

一款专业的 WordPress 图文笔记插件，支持 emoji 文字封面、瀑布流展示、REST API 发布，适合构建个人笔记站、知识库、灵感收集站。

## 主要功能

- 📝 **图文笔记**：支持富文本编辑、图片上传、分类标签
- 🎨 **Emoji 文字封面**：自动生成 emoji 文字封面，支持自定义颜色
- 🌊 **瀑布流展示**：Masonry 瀑布流布局，响应式设计
- 📅 **每日笔记汇总**：按日期汇总笔记页面
- 🔌 **REST API 发布**：支持 API Key 认证，快速发布笔记
- 🏷️ **分类标签管理**：支持自定义分类和标签
- 📱 **响应式设计**：适配桌面和移动端
- 🔍 **SEO 优化**：自定义页面标题、Meta 信息
- 🎯 **MD2Card 封面**：自动生成 Markdown 风格封面

## 安装

1. 下载最新版本的 `wpnote-v*.zip`
2. 在 WordPress 后台上传并安装插件
3. 启用插件后访问「WPNote → 笔记列表」
4. （首次安装需重新保存固定链接以刷新伪静态规则）

## API 发布笔记

```bash
curl -X POST https://your-site.com/wp-json/wpnote/v1/posts \
  -H "Content-Type: application/json" \
  -H "X-WPNote-Key: YOUR_API_KEY" \
  -d '{
    "title": "笔记标题",
    "content": "笔记正文内容",
    "emoji": "💡",
    "category": "技术,学习",
    "status": "publish"
  }'
```

## 页面结构

| 页面 | URL | 说明 |
|------|-----|------|
| 笔记列表 | `/wpnote/` | 瀑布流展示所有笔记 |
| 每日笔记 | `/wpnote/2026-04-17/` | 指定日期的笔记汇总 |
| 单篇笔记 | `/wpnote/xxx/` | 单篇笔记详情页 |

## 更新日志

### v1.3.2
- 修复变量未定义警告

### v1.3.1
- 首页布局可配置（列数+样式）

### v1.3.0
- 自动生成 MD2Card 封面

### v1.2.5
- URL slug 自动生成
- 封面生成错误提示

### v1.2.4
- 标签页修复

### v1.2.3
- 基础功能优化

## License

GPL-2.0+
