(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var builder = document.querySelector('.wpnote-cover-builder');
        if (!builder) return;

        // === 文字封面相关 ===
        var emojiInput = builder.querySelector('.wpnote-emoji-input');
        var bgInput = builder.querySelector('.wpnote-bg-input');
        var textInput = builder.querySelector('.wpnote-text-input');
        var preview = builder.querySelector('.wpnote-cover-preview');
        var colorBtns = builder.querySelectorAll('.color-btn');
        var styleRadios = builder.querySelectorAll('input[name="wpnote_cover[style]"]');

        function getSelectedStyle() {
            var checked = builder.querySelector('input[name="wpnote_cover[style]"]:checked');
            return checked ? checked.value : 'gradient';
        }

        function updatePreview() {
            var emoji = emojiInput ? (emojiInput.value || '📝') : '📝';
            var bg = bgInput ? (bgInput.value || '#667eea') : '#667eea';
            var text = textInput ? (textInput.value || '#ffffff') : '#ffffff';
            var style = getSelectedStyle();

            if (preview) {
                preview.style.background = bg;
                var emojiEl = preview.querySelector('.emoji');
                var titleEl = preview.querySelector('.title');
                if (emojiEl) emojiEl.textContent = emoji;
                if (titleEl) titleEl.style.color = text;
                preview.className = 'wpnote-cover-preview style-' + style;
            }

            if (colorBtns.length) {
                colorBtns.forEach(function(b) {
                    b.classList.toggle('active', b.dataset.color === bg);
                });
            }
        }

        if (emojiInput) emojiInput.addEventListener('input', updatePreview);
        if (bgInput) bgInput.addEventListener('input', updatePreview);
        if (textInput) textInput.addEventListener('input', updatePreview);

        if (colorBtns.length) {
            colorBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (bgInput) bgInput.value = btn.dataset.color;
                    updatePreview();
                });
            });
        }

        if (styleRadios.length) {
            styleRadios.forEach(function(radio) {
                radio.addEventListener('change', updatePreview);
            });
        }

        if (preview) updatePreview();

        // === AI 生成封面 ===
        var genBtn = builder.querySelector('.wpnote-gen-btn');
        if (genBtn) {
            genBtn.addEventListener('click', function() {
                var postId = genBtn.dataset.postId;
                var themeSelect = builder.querySelector('.wpnote-md2card-theme');
                var theme = themeSelect ? themeSelect.value : 'glassmorphism';
                var msgEl = builder.querySelector('.wpnote-gen-msg');
                var aiSection = builder.querySelector('.wpnote-ai-section');

                genBtn.disabled = true;
                genBtn.textContent = '⏳ 生成中...';
                if (msgEl) { msgEl.style.display = 'block'; msgEl.textContent = '正在调用 MD2Card API，请稍候...'; }

                var formData = new FormData();
                formData.append('action', 'wpnote_generate_cover');
                formData.append('nonce', wpnoteAdmin.nonce);
                formData.append('post_id', postId);
                formData.append('theme', theme);
                // 实时获取标题（用户可能已编辑过）
                var titleInput = document.getElementById('title');
                var currentTitle = titleInput ? titleInput.value : '';
                formData.append('title', currentTitle);

                fetch(wpnoteAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    genBtn.disabled = false;
                    genBtn.textContent = '✨ 生成封面';
                    if (data.success && data.data.image_url) {
                        if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = '#2271b1'; msgEl.textContent = '✅ 封面生成成功！已设为特色图片'; }
                        // 显示图片预览
                        var existingImg = aiSection.querySelector('img');
                        if (existingImg) {
                            existingImg.src = data.data.image_url;
                        } else {
                            var img = document.createElement('img');
                            img.src = data.data.image_url;
                            img.style = 'max-width:100%;border-radius:8px;margin-top:8px;display:block;';
                            aiSection.appendChild(img);
                        }
                        // 刷新页面让特色图片生效
                        setTimeout(function() {
                            var thumb = document.querySelector('#set-post-thumbnail');
                            if (thumb) thumb.style.boxShadow = '0 0 0 3px #2271b1';
                        }, 500);
                    } else {
                        var msg = data.data && data.data.message ? data.data.message : '生成失败';
                        if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = '#d63638'; msgEl.textContent = '❌ ' + msg; }
                    }
                })
                .catch(function(err) {
                    genBtn.disabled = false;
                    genBtn.textContent = '✨ 生成封面';
                    if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = '#d63638'; msgEl.textContent = '❌ 网络错误: ' + err.message; }
                });
            });
        }
    });
})();
