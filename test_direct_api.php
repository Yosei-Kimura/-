<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API直接テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; background: #f8f9fa; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API直接テスト（.htaccess無効化）</h1>
        
        <div class="result">
            <p><strong>テスト方法:</strong></p>
            <ul>
                <li>リライトルールを使わずに直接APIファイルにアクセス</li>
                <li>PATH_INFOを使わずにGETパラメータで制御</li>
            </ul>
        </div>
        
        <button onclick="testDirectAPI()">直接APIテスト</button>
        
        <div id="results"></div>
    </div>

    <script>
        async function testDirectAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '';
            
            // 直接 index.php に action パラメータを渡す
            const tests = [
                {
                    name: '未発見リスト',
                    url: 'php/api/index.php?action=unfound&offset=0&limit=5'
                },
                {
                    name: '検索候補',
                    url: 'php/api/index.php?action=suggestions&prefix=ま'
                }
            ];
            
            for (const test of tests) {
                try {
                    const response = await fetch(test.url);
                    const text = await response.text();
                    
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'result';
                    resultDiv.innerHTML = `
                        <h3>${test.name} - Status: ${response.status}</h3>
                        <p><strong>URL:</strong> ${test.url}</p>
                        <p><strong>Response:</strong></p>
                        <pre style="background: white; padding: 10px; border: 1px solid #ddd; white-space: pre-wrap;">${text}</pre>
                    `;
                    resultsDiv.appendChild(resultDiv);
                } catch (error) {
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'result';
                    resultDiv.style.background = '#f8d7da';
                    resultDiv.innerHTML = `
                        <h3>${test.name} - エラー</h3>
                        <p>${error.message}</p>
                    `;
                    resultsDiv.appendChild(resultDiv);
                }
            }
        }
    </script>
</body>
</html>