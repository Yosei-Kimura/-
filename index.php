<!DOCTYPE html>
<html>
<head>
    <base target="_top">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>棚卸入力システム</title>
    <style>
        :root { --accent-color: #4a90e2; --light-cream: #fdfdfd; --danger-color: #dc3545; }
        html { height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 20px;
            background-image: url('https://www.transparenttextures.com/patterns/retina-wood.png');
            font-size: 16px;
        }
        .container { max-width: 500px; margin: 0 auto; }
        h1 { text-align: center; color: #3a3a3a; margin-bottom: 2rem; font-weight: 600; }
        .card {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 1.5rem; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2);
        }
        label { display: block; margin-bottom: 0.75rem; font-weight: 600; color: #555; }
        .form-row { display: flex; align-items: center; gap: 0.75rem; }
        input[type="text"], input[type="tel"] {
            width: 100%; padding: 0.8rem 1rem; font-size: 1.1rem; border: 1px solid #ccc; border-radius: 8px;
            background-color: #fff;
            -webkit-appearance: none;
        }
        .separator { font-size: 1.5rem; color: #888; }
        button {
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            flex-shrink: 0; padding: 0.8rem 1rem; font-size: 1.1rem; cursor: pointer; border: none; background-color: var(--accent-color); color: white; border-radius: 8px; font-weight: bold;
            -webkit-appearance: none; transition: background-color 0.2s, transform 0.2s;
        }
        button svg { width: 16px; height: 16px; fill: white; }
        button:hover { background-color: #357abd; }
        button:active { transform: scale(0.96); }
        button:disabled { background-color: #a0bdf0; }
        #status { margin-top: 1.5rem; padding: 1rem; text-align: center; font-weight: bold; border-radius: 8px; display: none; }
        .success { color: #0f5132; background-color: #d1e7dd; display: block; }
        .error { color: #842029; background-color: #f8d7da; display: block; }
        #suggestions {
            margin-top: 0.75rem;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .suggestion-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #eee; background-color: #fff; }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background-color: #f5f5f5; }
        .suggestion-item.used { text-decoration: line-through; color: #aaa; pointer-events: none; }
        .suggestion-item .name { font-size: 0.9em; color: #6c757d; }
        .nav-link { display: block; text-align: center; margin-top: 1.5rem; padding: 0.8rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 1.1rem; transition: background-color 0.2s, transform 0.2s; }
        .nav-link:hover { background-color: #5a6268; }
        .nav-link:active { transform: scale(0.98); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 2rem; border-radius: 12px; text-align: center; max-width: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-message { margin-bottom: 1.5rem; font-size: 1.1rem; line-height: 1.6; }
        .modal-buttons { display: flex; gap: 1rem; justify-content: center; }
        .modal-btn { padding: 0.7rem 1.5rem; border-radius: 8px; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; }
        #modalConfirmBtn { background-color: var(--accent-color); color: white; }
        #modalCancelBtn { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>棚卸入力</h1>

        <div class="card">
            <label for="hiraCode1">スペース商品</label>
            <form id="hiraganaForm">
                <div class="form-row">
                    <input type="text" id="hiraCode1" placeholder="ま38">
                    <span class="separator">-</span>
                    <input type="tel" id="hiraCode2" placeholder="6" style="max-width: 80px;">
                    <button type="submit">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path></svg>
                        追加
                    </button>
                </div>
            </form>
            <div id="suggestions"></div>
        </div>

        <div class="card">
            <label for="kataCode1">委託商品</label>
            <form id="katakanaForm">
                <div class="form-row">
                    <input type="text" id="kataCode1" placeholder="ｶ8">
                    <span class="separator">-</span>
                    <input type="tel" id="kataCode2" placeholder="611" style="max-width: 80px;">
                    <button type="submit">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path></svg>
                        追加
                    </button>
                </div>
            </form>
        </div>
        
        <div id="status"></div>
        <a href="unfound.php" class="nav-link">未発見リストを見る</a>
    </div>

    <div id="customConfirmModal" class="modal-overlay">
        <div class="modal-content">
            <p id="modalMessage" class="modal-message"></p>
            <div class="modal-buttons">
                <button id="modalCancelBtn" class="modal-btn">キャンセル</button>
                <button id="modalConfirmBtn" class="modal-btn">OK</button>
            </div>
        </div>
    </div>

    <script>
        // API エンドポイントのベースURL（.htaccess対応とフォールバック）
        const API_BASE = 'php/api';
        const USE_DIRECT_API = true; // 直接アクセス方式を使用
        
        const modal = document.getElementById('customConfirmModal');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');
        
        function showCustomConfirm(message, callback) {
            modalMessage.textContent = message;
            modal.style.display = 'flex';

            confirmBtn.onclick = () => {
                modal.style.display = 'none';
                callback(true);
            };
            cancelBtn.onclick = () => {
                modal.style.display = 'none';
                callback(false);
            };
        }

        const hiraganaForm = document.getElementById('hiraganaForm');
        const hiraCode1 = document.getElementById('hiraCode1');
        const hiraCode2 = document.getElementById('hiraCode2');
        const suggestionsDiv = document.getElementById('suggestions');
        
        // 検索候補の取得
        hiraCode1.addEventListener('input', async () => {
            const prefix = hiraCode1.value;
            if (prefix.length < 2) {
                suggestionsDiv.innerHTML = '';
                return;
            }
            
            try {
                const url = USE_DIRECT_API 
                    ? `${API_BASE}/index.php?action=suggestions&prefix=${encodeURIComponent(prefix)}`
                    : `${API_BASE}/suggestions?prefix=${encodeURIComponent(prefix)}`;
                const response = await fetch(url);
                const data = await response.json();
                
                // APIレスポンスから suggestions 配列を取得
                const suggestions = data.suggestions || [];
                showSuggestions(suggestions);
            } catch (error) {
                console.error('検索候補取得エラー:', error);
            }
        });

        function showSuggestions(suggestions) {
            suggestionsDiv.innerHTML = '';
            if (!suggestions || !Array.isArray(suggestions) || suggestions.length === 0) return;

            suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `<span class="code">${item.code}</span><br><span class="name">${item.name}</span>`;
                
                div.onclick = () => {
                    const message = `「${item.code}」を発見済みにしますか？`;
                    showCustomConfirm(message, (confirmed) => {
                        if (confirmed) {
                            const [code1, code2] = item.code.split('-');
                            hiraCode1.value = code1;
                            hiraCode2.value = code2;
                            hiraganaForm.dispatchEvent(new Event('submit'));
                            div.classList.add('used');
                        }
                    });
                };
                suggestionsDiv.appendChild(div);
            });
        }
        
        const katakanaForm = document.getElementById('katakanaForm');
        const statusDiv = document.getElementById('status');

        hiraganaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const combinedCode = (`${hiraCode1.value}-${hiraCode2.value}`).normalize('NFKC');
            processInput(combinedCode, this, [hiraCode1, hiraCode2]);
        });
        
        katakanaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const code1 = this.querySelector('#kataCode1').value;
            const code2 = this.querySelector('#kataCode2').value;
            const combinedCode = `${code1}-${code2}`;
            processInput(combinedCode, this, [this.querySelector('#kataCode1'), this.querySelector('#kataCode2')]);
        });

        // 商品追加処理
        async function processInput(code, formElement, inputElements) {
            const button = formElement.querySelector('button');
            button.disabled = true;
            statusDiv.style.display = 'none';
            
            try {
                const url = USE_DIRECT_API 
                    ? `${API_BASE}/index.php?action=items`
                    : `${API_BASE}/items`;
                    
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ code: code })
                });
                
                const result = await response.json();
                onSuccess(result, button, inputElements);
                
            } catch (error) {
                onFailure(error, button, inputElements[0]);
            }
        }

        function onSuccess(response, button, inputElements) {
            statusDiv.textContent = response.message;
            statusDiv.className = response.status;
            button.disabled = false;

            // フォームが委託商品（katakanaForm）の場合
            if (button.form.id === 'katakanaForm') {
                document.getElementById('kataCode1').value = '';
                document.getElementById('kataCode2').value = '';
            }

            inputElements[0].focus();
        }

        function onFailure(error, button, firstInputElement) {
            statusDiv.textContent = 'エラー: ' + error.message;
            statusDiv.className = 'error';
            button.disabled = false;
            firstInputElement.focus();
        }
        
        // 委託商品のリアルタイム変換機能
        const kataCode1Input = document.getElementById('kataCode1');
        const kataCode2Input = document.getElementById('kataCode2');

        const realtimeHankakuConvert = (event) => {
            const input = event.target;
            const selectionStart = input.selectionStart;
            const selectionEnd = input.selectionEnd;
            let value = input.value;

            // ひらがなを全角カタカナに変換
            value = value.replace(/[\u3041-\u3096]/g, match => {
                return String.fromCharCode(match.charCodeAt(0) + 0x60);
            });

            // 全角文字を半角に正規化
            const normalizedValue = value.normalize('NFKC');

            if (input.value !== normalizedValue) {
                input.value = normalizedValue;
                input.setSelectionRange(selectionStart, selectionEnd);
            }
        };

        kataCode1Input.addEventListener('input', realtimeHankakuConvert);
        kataCode2Input.addEventListener('input', realtimeHankakuConvert);
    </script>
</body>
</html>