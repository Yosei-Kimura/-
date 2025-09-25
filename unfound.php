<!DOCTYPE html>
<html>
<head>
    <base target="_top">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>未発見リスト</title>
    <style>
        :root { --accent-color: #198754; --light-cream: #fdfdfd; }
        html { height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 20px;
            background-image: url('https://www.transparenttextures.com/patterns/retina-wood.png'); font-size: 16px;
        }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { text-align: center; color: #3a3a3a; font-weight: 600; }
        .info-bar { text-align: center; margin-bottom: 1.5rem; font-size: 1.1em; color: #555; background-color: rgba(255,255,255,0.8); padding: 0.5rem; border-radius: 8px; }
        
        .filter-bar { 
            text-align: center; margin-bottom: 1.5rem; background-color: rgba(255,255,255,0.9); 
            padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-title { font-size: 1rem; font-weight: 600; margin-bottom: 0.8rem; color: #333; }
        .filter-section { margin-bottom: 1rem; }
        .filter-section:last-child { margin-bottom: 0; }
        .filter-section-title { font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem; color: #666; }
        .filter-buttons { display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; }
        .filter-btn { 
            padding: 0.6rem 1rem; border: 2px solid #ddd; background: white; color: #666; 
            border-radius: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 500;
            transition: all 0.2s ease; min-width: 48px;
        }
        .filter-btn:hover { background: #f8f9fa; border-color: #bbb; }
        .filter-btn.active { background: var(--accent-color); color: white; border-color: var(--accent-color); }
        .filter-btn:disabled { background: #f8f9fa; color: #ccc; border-color: #eee; cursor: not-allowed; }
        .filter-reset { 
            margin-left: 1rem; padding: 0.6rem 1.2rem; background: #6c757d; color: white; 
            border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 500;
        }
        .filter-reset:hover { background: #5a6268; }
        .column-filter { display: none; }
        .column-filter.show { display: block; }
        
        #itemList { list-style-type: none; padding: 0; }
        .item {
            display: flex; align-items: center; justify-content: space-between; background-color: var(--light-cream);
            padding: 1rem; border-radius: 12px; margin-bottom: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: background-color 0.3s, opacity 0.3s;
        }
        .item.found { background-color: #f5f5f5; opacity: 0.7; }
        .item-info { flex-grow: 1; margin-right: 1rem; }
        .item-code { font-weight: 600; font-size: 1.1rem; }
        .item-name { font-size: 0.95em; color: #555; }
        .item.found .item-name { color: #888; }
        .find-btn {
            display: flex; align-items: center; justify-content: center; gap: 0.4rem; flex-shrink: 0; width: 95px; padding: 0.75rem 0;
            cursor: pointer; border: none; background-color: var(--accent-color); color: white; border-radius: 8px; font-weight: bold; font-size: 1rem;
            -webkit-appearance: none; transition: background-color 0.2s, transform 0.2s;
        }
        .find-btn svg { width: 18px; height: 18px; fill: white; }
        .find-btn:hover { background-color: #146c43; }
        .find-btn:active { transform: scale(0.96); }
        .find-btn:disabled { background-color: #6c757d; }
        #loadMoreContainer { text-align: center; margin-top: 1.5rem; }
        #loadMoreBtn {
            padding: 0.8rem 1.5rem; font-size: 1.1rem; cursor: pointer; border: none; background-color: #555;
            color: white; border-radius: 8px; font-weight: 500; display: none;
        }
        .nav-link { display: block; text-align: center; margin-top: 1.5rem; padding: 0.8rem; background-color: #4a90e2; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 1.1rem; }
        #loading { text-align: center; padding: 2rem; font-size: 1.2em; color: #6c757d; background-color: var(--light-cream); border-radius: 12px; }
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
        <h1>未発見リスト</h1>
        
        <div class="filter-bar">
            <div class="filter-title">ひらがなで絞り込み</div>
            
            <!-- 行選択 -->
            <div class="filter-section">
                <div class="filter-section-title">行を選択</div>
                <div class="filter-buttons">
                    <button class="filter-btn row-filter" data-row="あ">あ</button>
                    <button class="filter-btn row-filter" data-row="か">か</button>
                    <button class="filter-btn row-filter" data-row="さ">さ</button>
                    <button class="filter-btn row-filter" data-row="た">た</button>
                    <button class="filter-btn row-filter" data-row="な">な</button>
                    <button class="filter-btn row-filter" data-row="は">は</button>
                    <button class="filter-btn row-filter" data-row="ま">ま</button>
                    <button class="filter-btn row-filter" data-row="や">や</button>
                    <button class="filter-btn row-filter" data-row="ら">ら</button>
                    <button class="filter-btn row-filter" data-row="わ">わ</button>
                    <button class="filter-reset">すべて</button>
                </div>
            </div>
            
            <!-- 段選択（初期は非表示） -->
            <div class="filter-section column-filter">
                <div class="filter-section-title">段を選択（より詳細に絞り込み）</div>
                <div class="filter-buttons">
                    <button class="filter-btn column-filter-btn" data-column="あ">あ段</button>
                    <button class="filter-btn column-filter-btn" data-column="い">い段</button>
                    <button class="filter-btn column-filter-btn" data-column="う">う段</button>
                    <button class="filter-btn column-filter-btn" data-column="え">え段</button>
                    <button class="filter-btn column-filter-btn" data-column="お">お段</button>
                    <button class="filter-reset column-reset">行のみ</button>
                </div>
            </div>
        </div>
        
        <div id="infoBar" class="info-bar" style="display: none;"></div>
        <div id="loading">読み込み中...</div>
        <ul id="itemList"></ul>
        <div id="loadMoreContainer">
            <button id="loadMoreBtn">もっと見る</button>
        </div>
        <a href="index.php" class="nav-link">入力ページに戻る</a>
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
        const API_BASE = 'php/api';
        const USE_DIRECT_API = true; // 直接アクセス方式を使用
        
        let currentItemCount = 0;
        let currentRowFilter = null;
        let currentColumnFilter = null;
        const limit = 50;
        let totalUnfoundItems = 0;

        const list = document.getElementById('itemList');
        const loading = document.getElementById('loading');
        const infoBar = document.getElementById('infoBar');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const modal = document.getElementById('customConfirmModal');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');
        
        document.addEventListener('DOMContentLoaded', () => {
            loadItems(true);
            setupFilterButtons();
        });

        loadMoreBtn.addEventListener('click', () => {
            loadItems(false);
        });

        function setupFilterButtons() {
            // 行フィルターボタンのイベント
            document.querySelectorAll('.row-filter').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.dataset.row;
                    applyRowFilter(row);
                    
                    // ボタンの選択状態を更新
                    document.querySelectorAll('.row-filter').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // 段選択を表示
                    showColumnFilter();
                });
            });
            
            // 段フィルターボタンのイベント
            document.querySelectorAll('.column-filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const column = btn.dataset.column;
                    applyColumnFilter(column);
                    
                    // ボタンの選択状態を更新
                    document.querySelectorAll('.column-filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                });
            });
            
            // 全リセットボタンのイベント
            document.querySelector('.filter-reset').addEventListener('click', () => {
                resetAllFilters();
            });
            
            // 段リセットボタンのイベント
            document.querySelector('.column-reset').addEventListener('click', () => {
                resetColumnFilter();
            });
        }

        function applyRowFilter(row) {
            currentRowFilter = row;
            currentColumnFilter = null; // 段フィルターはクリア
            refreshList();
        }

        function applyColumnFilter(column) {
            currentColumnFilter = column;
            refreshList();
        }

        function resetAllFilters() {
            currentRowFilter = null;
            currentColumnFilter = null;
            
            // ボタンの選択状態をクリア
            document.querySelectorAll('.row-filter, .column-filter-btn').forEach(b => b.classList.remove('active'));
            
            // 段選択を非表示
            hideColumnFilter();
            
            refreshList();
        }

        function resetColumnFilter() {
            currentColumnFilter = null;
            
            // 段ボタンの選択状態をクリア
            document.querySelectorAll('.column-filter-btn').forEach(b => b.classList.remove('active'));
            
            refreshList();
        }

        function showColumnFilter() {
            document.querySelector('.column-filter').classList.add('show');
        }

        function hideColumnFilter() {
            document.querySelector('.column-filter').classList.remove('show');
        }

        function refreshList() {
            currentItemCount = 0;
            list.innerHTML = '';
            loading.style.display = 'block';
            infoBar.style.display = 'none';
            loadItems(true);
        }

        function getSpecificCharacter(row, column) {
            const characterMap = {
                'あ': {'あ': 'あ', 'い': 'い', 'う': 'う', 'え': 'え', 'お': 'お'},
                'か': {'あ': 'か', 'い': 'き', 'う': 'く', 'え': 'け', 'お': 'こ'},
                'さ': {'あ': 'さ', 'い': 'し', 'う': 'す', 'え': 'せ', 'お': 'そ'},
                'た': {'あ': 'た', 'い': 'ち', 'う': 'つ', 'え': 'て', 'お': 'と'},
                'な': {'あ': 'な', 'い': 'に', 'う': 'ぬ', 'え': 'ね', 'お': 'の'},
                'は': {'あ': 'は', 'い': 'ひ', 'う': 'ふ', 'え': 'へ', 'お': 'ほ'},
                'ま': {'あ': 'ま', 'い': 'み', 'う': 'む', 'え': 'め', 'お': 'も'},
                'や': {'あ': 'や', 'い': 'い', 'う': 'ゆ', 'え': 'え', 'お': 'よ'},
                'ら': {'あ': 'ら', 'い': 'り', 'う': 'る', 'え': 'れ', 'お': 'ろ'},
                'わ': {'あ': 'わ', 'い': 'い', 'う': 'う', 'え': 'え', 'お': 'を'}
            };
            
            return characterMap[row] && characterMap[row][column] ? characterMap[row][column] : null;
        }

        async function loadItems(isInitialLoad) {
            if (!isInitialLoad) {
                loadMoreBtn.textContent = '読み込み中...';
                loadMoreBtn.disabled = true;
            }
            
            try {
                let url;
                if (USE_DIRECT_API) {
                    url = `${API_BASE}/index.php?action=unfound&offset=${currentItemCount}&limit=${limit}`;
                    if (currentRowFilter) {
                        url += `&hiragana=${encodeURIComponent(currentRowFilter)}`;
                    }
                    if (currentColumnFilter) {
                        url += `&column=${encodeURIComponent(currentColumnFilter)}`;
                    }
                } else {
                    url = `${API_BASE}/unfound?offset=${currentItemCount}&limit=${limit}`;
                    if (currentRowFilter) {
                        url += `&hiragana=${encodeURIComponent(currentRowFilter)}`;
                    }
                    if (currentColumnFilter) {
                        url += `&column=${encodeURIComponent(currentColumnFilter)}`;
                    }
                }
                
                const response = await fetch(url);
                const data = await response.json();
                displayItems(data);
                
                if (!isInitialLoad) {
                    loadMoreBtn.textContent = 'もっと見る';
                    loadMoreBtn.disabled = false;
                }
            } catch (error) {
                console.error('データ取得エラー:', error);
                loading.textContent = 'エラーが発生しました';
            }
        }

        function displayItems(response) {
            loading.style.display = 'none';
            infoBar.style.display = 'block';

            totalUnfoundItems = response.total;
            const newItems = response.items;

            if (totalUnfoundItems === 0 && currentItemCount === 0) {
                let message = '';
                
                if (currentRowFilter && currentColumnFilter) {
                    // 段フィルターが適用されている場合
                    const specificChar = getSpecificCharacter(currentRowFilter, currentColumnFilter);
                    message = `「${specificChar}」で始まる未発見商品はありません`;
                } else if (currentRowFilter) {
                    // 行フィルターのみの場合
                    message = `「${currentRowFilter}行」の商品はすべて発見済みです！`;
                } else {
                    // フィルターなしの場合
                    message = 'すべての商品が発見済みです！';
                }
                
                infoBar.textContent = message;
                loadMoreBtn.style.display = 'none';
                return;
            }

            newItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'item';
                li.dataset.code = item.code;
                li.innerHTML = `
                    <div class="item-info">
                        <div class="item-code">${item.code}</div>
                        <div class="item-name">${item.name}</div>
                    </div>
                    <button class="find-btn">
                        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg> 発見
                    </button>`;
                
                li.querySelector('.find-btn').onclick = () => {
                    markAsFound(item.code, li.querySelector('.find-btn'));
                };
                list.appendChild(li);
            });
            
            currentItemCount = list.children.length;
            let displayText = '';
            if (currentRowFilter && currentColumnFilter) {
                const specificChar = getSpecificCharacter(currentRowFilter, currentColumnFilter);
                displayText = `「${specificChar}」で始まる商品 ${totalUnfoundItems}件中 ${currentItemCount}件表示`;
            } else if (currentRowFilter) {
                displayText = `「${currentRowFilter}行」 ${totalUnfoundItems}件中 ${currentItemCount}件表示`;
            } else {
                displayText = `${totalUnfoundItems}件中 ${currentItemCount}件表示`;
            }
            infoBar.textContent = displayText;
            
            if (currentItemCount < totalUnfoundItems) {
                loadMoreBtn.style.display = 'inline-block';
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }

        function showCustomConfirm(message, callback) {
            modalMessage.textContent = message;
            modal.style.display = 'flex';
            confirmBtn.onclick = () => { modal.style.display = 'none'; callback(true); };
            cancelBtn.onclick = () => { modal.style.display = 'none'; callback(false); };
        }

        async function markAsFound(code, button) {
            const message = `「${code}」を発見済みにしますか？`;
            showCustomConfirm(message, async (confirmed) => {
                if (confirmed) {
                    button.disabled = true;
                    button.textContent = '処理中...';
                    
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
                        
                        if (result.status === 'success') {
                            const li = button.closest('.item');
                            li.classList.add('found');
                            button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg> 発見済';
                        } else {
                            alert(result.message);
                            button.disabled = false;
                            button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg> 発見';
                        }
                    } catch (error) {
                        console.error('発見処理エラー:', error);
                        alert('エラーが発生しました');
                        button.disabled = false;
                        button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg> 発見';
                    }
                }
            });
        }
    </script>
</body>
</html>