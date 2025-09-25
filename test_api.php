<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API接続テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API接続テスト</h1>
        
        <div class="info">
            <p><strong>テスト対象API:</strong></p>
            <ul>
                <li>GET /php/api/unfound - 未発見リスト取得</li>
                <li>GET /php/api/suggestions?prefix=ま - 検索候補取得</li>
                <li>POST /php/api/items - 商品追加</li>
            </ul>
        </div>
        
        <div id="results"></div>
        
        <button onclick="testUnfoundAPI()">未発見リストAPI テスト</button>
        <button onclick="testSuggestionsAPI()">検索候補API テスト</button>
        <button onclick="testAddItemAPI()">商品追加API テスト</button>
        <button onclick="runAllTests()">すべてテスト</button>
    </div>

    <script>
        const API_BASE = 'php/api';
        const resultsDiv = document.getElementById('results');
        
        function addResult(title, success, data) {
            const div = document.createElement('div');
            div.className = `test-result ${success ? 'success' : 'error'}`;
            div.innerHTML = `
                <h3>${title}</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
            resultsDiv.appendChild(div);
        }
        
        async function testUnfoundAPI() {
            try {
                const response = await fetch(`${API_BASE}/unfound?offset=0&limit=5`);
                const data = await response.json();
                addResult('未発見リストAPI', response.ok, {
                    status: response.status,
                    data: data
                });
            } catch (error) {
                addResult('未発見リストAPI', false, {
                    error: error.message
                });
            }
        }
        
        async function testSuggestionsAPI() {
            try {
                const response = await fetch(`${API_BASE}/suggestions?prefix=ま`);
                const data = await response.json();
                addResult('検索候補API', response.ok, {
                    status: response.status,
                    data: data
                });
            } catch (error) {
                addResult('検索候補API', false, {
                    error: error.message
                });
            }
        }
        
        async function testAddItemAPI() {
            try {
                const response = await fetch(`${API_BASE}/items`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ code: 'テスト-999' })
                });
                const data = await response.json();
                addResult('商品追加API', response.ok || data.status === 'error', {
                    status: response.status,
                    data: data
                });
            } catch (error) {
                addResult('商品追加API', false, {
                    error: error.message
                });
            }
        }
        
        async function runAllTests() {
            resultsDiv.innerHTML = '';
            await testUnfoundAPI();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testSuggestionsAPI();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testAddItemAPI();
        }
        
        // ページ読み込み時に基本情報を表示
        window.addEventListener('load', () => {
            addResult('基本情報', true, {
                currentURL: window.location.href,
                apiBase: API_BASE,
                timestamp: new Date().toISOString()
            });
        });
    </script>
</body>
</html>