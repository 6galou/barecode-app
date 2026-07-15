<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecteur Code-barre Disques Laser</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .input-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .success {
            background: #4caf50;
            padding: 12px;
            border-radius: 8px;
            color: white;
            margin-bottom: 20px;
            display: none;
        }
        .error {
            background: #f44336;
            padding: 12px;
            border-radius: 8px;
            color: white;
            margin-bottom: 20px;
            display: none;
        }
        .barcode-list {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #e0e0e0;
        }
        .barcode-item {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .barcode-info {
            flex: 1;
        }
        .barcode-code {
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .barcode-date {
            color: #999;
            font-size: 12px;
            margin-top: 4px;
        }
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .delete-btn:hover {
            background: #da190b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱Lecteur Code-barre</h1>
        
        <div class="success" id="successMsg">✅ Code-barre ajouté avec succès!</div>
        <div class="error" id="errorMsg">❌ Erreur: <span id="errorText"></span></div>
        
        <form id="barcodeForm">
            <div class="input-group">
                <label for="code">Code-barre (Douchette USB):</label>
                <input type="text" id="code" name="code" autofocus required 
                       placeholder="Scanner ou collez le code ici">
            </div>
            
            <div class="input-group">
                <label for="type">Type de disque:</label>
                <input type="text" id="type" name="type" 
                       placeholder="ex: Blu-ray, DVD, CD">
            </div>
            
            <div class="input-group">
                <label for="quantite">Quantité:</label>
                <input type="number" id="quantite" name="quantite" value="1" min="1">
            </div>
            
            <div class="input-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Notes supplémentaires..."></textarea>
            </div>
            
            <button type="submit">✅ Enregistrer</button>
        </form>
        
        <div class="barcode-list" id="barcodeList"></div>
    </div>

    <script>
        // Charger les codes-barres
        function loadBarcodes() {
            fetch('api.php?action=list')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('barcodeList');
                    if (data.length === 0) {
                        list.innerHTML = '<p style="text-align:center;color:#999;">Aucun code-barre enregistré</p>';
                        return;
                    }
                    list.innerHTML = '<h2 style="margin-bottom:20px;">📦 Codes-barres enregistrés (' + data.length + ')</h2>';
                    data.forEach(item => {
                        const date = new Date(item.date_lecture).toLocaleString('fr-FR');
                        list.innerHTML += `
                            <div class="barcode-item">
                                <div class="barcode-info">
                                    <div class="barcode-code">${item.code_barre}</div>
                                    <div class="barcode-date">${date}</div>
                                </div>
                                <button class="delete-btn" onclick="deleteBarcode(${item.id})">Supprimer</button>
                            </div>
                        `;
                    });
                });
        }


        // Soumettre un code-barre
        document.getElementById('barcodeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('code', document.getElementById('code').value);
            formData.append('type', document.getElementById('type').value);
            formData.append('quantite', document.getElementById('quantite').value);
            formData.append('description', document.getElementById('description').value);
                                                                
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('successMsg').style.display = 'block';
                    setTimeout(() => document.getElementById('successMsg').style.display = 'none', 3000);
                    document.getElementById('barcodeForm').reset();
                    document.getElementById('code').focus();
                    loadBarcodes();
                } else {
                    showError(data.error || 'Erreur lors de l\'ajout');
                }
            } catch (error) {
                showError(error.message);
            }
        });

        function showError(msg) {
            const errorMsg = document.getElementById('errorMsg');
            const errorText = document.getElementById('errorText');
            errorText.textContent = msg;
            errorMsg.style.display = 'block';
            setTimeout(() => errorMsg.style.display = 'none', 15000);
        }

        function deleteBarcode(id) {
            if (confirm('Supprimer ce code-barre?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadBarcodes();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        }

        // Charger au démarrage
        loadBarcodes();
    </script>
</body>
</html>
