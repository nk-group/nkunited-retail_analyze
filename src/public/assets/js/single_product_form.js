/**
 * 単品分析フォーム専用JavaScript
 * (public/assets/js/single_product_form.js)
 */

class SingleProductForm {
    constructor() {
        this.elements = {};
        this.selectedMaker = null;
        this.selectedProduct = null;
        this.selectedProductFromModal = null;
        this.currentPage = 1;
        this.currentKeyword = '';
        this.totalPages = 1;
        this.currentManufacturerCode = '';
        this.currentProductNumber = '';
        this.currentJanCodes = [];
        this.currentProductPage = 1;
        this.currentProductKeyword = '';
        this.totalProductPages = 1;
        this.baseUrl = '';
        this.siteUrl = '';
        this.apiBase = '';
        
        this.init();
    }
    
    /**
     * 認証付きfetchラッパー
     * セッション切れを統一的に処理
     */
    async fetchWithAuth(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });
            
            // セッション切れの処理
            if (response.status === 401) {
                if (confirm('セッションが切れています。ログイン画面に移動しますか？')) {
                    // 現在の状態を保存してからログイン画面へ
                    this.saveFormState();
                    window.location.href = `${this.siteUrl}/login`;
                }
                throw new Error('セッションが切れています');
            }
            
            // その他のHTTPエラー
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response;
        } catch (error) {
            // ネットワークエラー等
            if (error.message === 'Failed to fetch') {
                throw new Error('ネットワークエラーが発生しました。接続を確認してください。');
            }
            throw error;
        }
    }
    
    /**
     * 初期化
     */
    init() {
        if (!this.initializeUrls()) {
            return;
        }
        
        if (!this.initializeElements()) {
            return;
        }
        
        this.bindEvents();
        this.initializeMakerModal();
        this.initializeProductModal();
        
        // ブラウザの戻るボタンで戻ってきた場合のみ復元
        if (window.performance && window.performance.navigation.type === 2) {
            // ブラウザの戻る/進むで来た場合
            this.restoreFormState();
        } else {
            // 通常のアクセスの場合はクリア
            this.clearFormState();
        }
    }
    
    /**
     * URL設定の初期化
     */
    initializeUrls() {
        const body = document.body;
        this.baseUrl = body.dataset.baseUrl || '';
        this.siteUrl = body.dataset.siteUrl || '';
        this.apiBase = body.dataset.apiBase || '';
        
        if (!this.baseUrl || !this.siteUrl || !this.apiBase) {
            console.error('URL設定が不正です:', {
                baseUrl: this.baseUrl,
                siteUrl: this.siteUrl,
                apiBase: this.apiBase
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * 要素の初期化
     */
    initializeElements() {
        const elementIds = [
            'manufacturerCode', 'manufacturerName', 'productNumber', 'productList',
            'targetProducts', 'executeBtn', 'makerReferenceModal', 'productReferenceModal',
            'urlCopySection', 'quickAnalysisUrl', 'copyUrlBtn'
        ];
        
        for (const id of elementIds) {
            this.elements[id] = document.getElementById(id);
            if (!this.elements[id]) {
                console.error(`必須要素が見つかりません: ${id}`);
                return false;
            }
        }
        
        return true;
    }

    /**
     * フォーム状態をsessionStorageに保存
     */
    saveFormState() {
        // 選択された商品情報も含めて保存
        const selectedProductData = this.selectedProduct ? {
            product_name: this.selectedProduct.product_name,
            product_number: this.selectedProduct.product_number,
            season_code: this.selectedProduct.season_code,
            selling_price: this.selectedProduct.selling_price,
            jan_count: this.selectedProduct.jan_count,
            min_price: this.selectedProduct.min_price,
            max_price: this.selectedProduct.max_price
        } : null;

        const formState = {
            manufacturerCode: this.currentManufacturerCode,
            manufacturerName: this.elements.manufacturerName.textContent,
            isManufacturerEmpty: this.elements.manufacturerName.classList.contains('empty'),
            productNumber: this.currentProductNumber,
            productName: document.getElementById('productName') ? document.getElementById('productName').value : '',
            selectedProduct: selectedProductData,
            selectedJanCodes: this.currentJanCodes,
            stepStatuses: {
                step1: document.getElementById('step1-number').className,
                step2: document.getElementById('step2-number').className,
                step3: document.getElementById('step3-number').className,
                step4: document.getElementById('step4-number').className
            },
            productNumberDisabled: this.elements.productNumber.disabled,
            productSearchBtnDisabled: document.getElementById('productSearchBtn').disabled,
            executeBtnDisabled: this.elements.executeBtn.disabled,
            productListVisible: this.elements.productList.style.display === 'block',
            targetProductsVisible: this.elements.targetProducts.style.display === 'block'
        };
        
        sessionStorage.setItem('singleProductFormState', JSON.stringify(formState));
    }

    /**
     * sessionStorageからフォーム状態を復元
     */
    restoreFormState() {
        const savedState = sessionStorage.getItem('singleProductFormState');
        if (!savedState) {
            return;
        }
        
        try {
            const formState = JSON.parse(savedState);
            
            // メーカーコードの復元
            if (formState.manufacturerCode) {
                this.elements.manufacturerCode.value = formState.manufacturerCode;
                this.currentManufacturerCode = formState.manufacturerCode;
                
                // メーカー名の復元
                if (formState.manufacturerName && formState.manufacturerName !== 'メーカーコードを入力してください') {
                    this.elements.manufacturerName.textContent = formState.manufacturerName;
                    if (!formState.isManufacturerEmpty) {
                        this.elements.manufacturerName.classList.remove('empty');
                    }
                    
                    // メーカーが確認されている場合、メッセージを表示
                    const messageElement = document.getElementById('manufacturer-message');
                    if (messageElement) {
                        messageElement.innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>メーカー情報が確認されました</div>';
                    }
                }
            }
            
            // 品番の復元
            if (formState.productNumber) {
                this.elements.productNumber.value = formState.productNumber;
                this.currentProductNumber = formState.productNumber;
            }
            
            // 品番名の復元
            if (formState.productName) {
                const productNameInput = document.getElementById('productName');
                if (productNameInput) {
                    productNameInput.value = formState.productName;
                }
                
                // 選択された商品名も復元
                const selectedProductName = document.getElementById('selectedProductName');
                if (selectedProductName) {
                    selectedProductName.textContent = formState.productName;
                }
            }
            
            // 選択された商品情報の復元
            if (formState.selectedProduct) {
                this.selectedProduct = formState.selectedProduct;
            }
            
            // JANコードの復元
            if (formState.selectedJanCodes && formState.selectedJanCodes.length > 0) {
                this.currentJanCodes = formState.selectedJanCodes;
            }
            
            // ステップ状態の復元
            if (formState.stepStatuses) {
                Object.keys(formState.stepStatuses).forEach(stepId => {
                    const element = document.getElementById(stepId + '-number');
                    if (element) {
                        element.className = formState.stepStatuses[stepId];
                    }
                });
            }
            
            // フィールドの有効/無効状態の復元
            this.elements.productNumber.disabled = formState.productNumberDisabled;
            const productSearchBtn = document.getElementById('productSearchBtn');
            if (productSearchBtn) {
                productSearchBtn.disabled = formState.productSearchBtnDisabled;
            }
            this.elements.executeBtn.disabled = formState.executeBtnDisabled;
            
            // 品番リストの表示状態を復元
            if (formState.productListVisible) {
                this.elements.productList.style.display = 'block';
            }
            
            // 対象商品エリアの表示状態を復元
            if (formState.targetProductsVisible) {
                this.elements.targetProducts.style.display = 'block';
            }
            
            // URLコピーセクションの表示
            if (formState.selectedJanCodes && formState.selectedJanCodes.length > 0) {
                this.showUrlCopySection();
            }
            
            // 品番リストの再取得と選択状態の復元
            if (formState.manufacturerCode && formState.productNumber) {
                // 少し遅延させて実行（DOMの準備を待つ）
                setTimeout(() => {
                    this.fetchProductList(formState.manufacturerCode, formState.productNumber);
                    
                    // 商品選択状態の復元
                    if (formState.selectedProduct && formState.productName) {
                        setTimeout(() => {
                            const productItems = document.querySelectorAll('.product-item');
                            productItems.forEach(item => {
                                const productNameElement = item.querySelector('.product-name');
                                if (productNameElement && productNameElement.textContent === formState.selectedProduct.product_name) {
                                    item.click();
                                }
                            });
                        }, 200);
                    }
                }, 100);
            }
            
        } catch (error) {
            console.error('フォーム状態の復元エラー:', error);
            sessionStorage.removeItem('singleProductFormState');
        }
    }

    /**
     * sessionStorageをクリア
     */
    clearFormState() {
        sessionStorage.removeItem('singleProductFormState');
    }
    
    /**
     * イベントバインド
     */
    bindEvents() {
        // Step 1: メーカーコード入力処理（数値のみ、7桁制限）
        this.elements.manufacturerCode.addEventListener('input', (e) => {
            let code = e.target.value;
            
            // 数値以外を除去
            code = code.replace(/[^0-9]/g, '');
            
            // 最大7桁に制限
            if (code.length > 7) {
                code = code.substring(0, 7);
            }
            
            // 入力フィールドに反映
            e.target.value = code;
            
            this.currentManufacturerCode = code;
            
            if (code) {
                // 入力中は検証を行わない
            } else {
                this.resetManufacturerDisplay();
                this.resetSteps([2, 3, 4]);
                this.hideUrlCopySection();
                this.saveFormState();
            }
        });

        // ENTERキー処理
        this.elements.manufacturerCode.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                
                const code = e.target.value;
                if (code) {
                    // 7桁になるよう前0埋め
                    const formattedCode = code.padStart(7, '0');
                    e.target.value = formattedCode;
                    this.currentManufacturerCode = formattedCode;
                    
                    // 検証実行
                    this.validateManufacturerCode(formattedCode);
                }
            }
        });

        // フォーカスアウト時の処理
        this.elements.manufacturerCode.addEventListener('blur', (e) => {
            const code = e.target.value;
            if (code) {
                // 7桁になるよう前0埋め
                const formattedCode = code.padStart(7, '0');
                e.target.value = formattedCode;
                this.currentManufacturerCode = formattedCode;
                
                // 検証実行
                this.validateManufacturerCode(formattedCode);
            }
        });

        // Step 2: 品番入力処理
        this.elements.productNumber.addEventListener('input', () => {
            const number = this.elements.productNumber.value.trim();
            this.currentProductNumber = number;
            
            if (number && this.currentManufacturerCode) {
                this.validateProductNumber(this.currentManufacturerCode, number);
            } else {
                this.resetProductDisplay();
                this.resetSteps([3, 4]);
                this.hideUrlCopySection();
                this.saveFormState();
            }
        });

        // 品番でEnterキーを押した時も保存
        this.elements.productNumber.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.saveFormState();
            }
        });

        // URLコピー機能
        this.elements.copyUrlBtn.addEventListener('click', () => {
            this.elements.quickAnalysisUrl.select();
            this.elements.quickAnalysisUrl.setSelectionRange(0, 99999);
            
            try {
                navigator.clipboard.writeText(this.elements.quickAnalysisUrl.value).then(() => {
                    this.showCopySuccess();
                }).catch(() => {
                    document.execCommand('copy');
                    this.showCopySuccess();
                });
            } catch (err) {
                document.execCommand('copy');
                this.showCopySuccess();
            }
        });

        // 実行ボタンのイベント（必要に応じて後で実装）
    }
    
    /**
     * メーカーコード検証
     */
    validateManufacturerCode(code) {
        const searchUrl = `${this.apiBase}/search-makers?keyword=${encodeURIComponent(code)}&exact=1`;
        
        this.fetchWithAuth(searchUrl, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                const maker = data.data[0];
                this.showManufacturerFound(maker);
                this.enableStep2();
                
                // 成功時は次の入力フィールドにフォーカス
                this.elements.productNumber.focus();
            } else {
                this.showManufacturerNotFound();
                this.resetSteps([2, 3, 4]);
                
                // エラー時は同じフィールドに留まる
                this.elements.manufacturerCode.focus();
                this.elements.manufacturerCode.select();
            }
        })
        .catch(error => {
            console.error('メーカーコード検証エラー:', error);
            this.showManufacturerMessage(`検証エラー: ${error.message}`, 'error');
            this.resetSteps([2, 3, 4]);
            
            // エラー時も同じフィールドに留まる
            this.elements.manufacturerCode.focus();
            this.elements.manufacturerCode.select();
        });
    }

    /**
     * 品番存在確認（エラーハンドリング改善版）
     */
    validateProductNumber(manufacturerCode, productNumber) {
        const validateUrl = `${this.apiBase}/validate-product-number?manufacturer_code=${encodeURIComponent(manufacturerCode)}&product_number=${encodeURIComponent(productNumber)}`;
        
        this.fetchWithAuth(validateUrl, {
            method: 'GET'
        })
        .then(response => response.text())
        .then(text => {
            if (!text) {
                console.error('Empty response from validate-product-number API');
                throw new Error('サーバーからの応答が空です');
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('サーバーからの応答が不正です');
            }
            
            if (data.success && data.exists) {
                this.fetchProductList(manufacturerCode, productNumber);
            } else {
                this.showProductMessage('該当する品番が見つかりません', 'error');
                this.resetSteps([3, 4]);
                this.hideUrlCopySection();
                this.saveFormState();
            }
        })
        .catch(error => {
            console.error('品番検証エラー:', error);
            this.showProductMessage(`品番検証でエラーが発生しました: ${error.message}`, 'error');
            this.resetSteps([3, 4]);
            this.hideUrlCopySection();
            this.saveFormState();
        });
    }

    /**
     * 品番リスト取得（エラーハンドリング改善版）
     */
    fetchProductList(manufacturerCode, productNumber) {
        const searchUrl = `${this.apiBase}/search-products?manufacturer_code=${encodeURIComponent(manufacturerCode)}&keyword=${encodeURIComponent(productNumber)}`;
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            if (!text) {
                console.error('Empty response from search-products API');
                throw new Error('サーバーからの応答が空です');
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('サーバーからの応答が不正です');
            }
            
            if (data.success && data.data.length > 0) {
                this.showProductList(data.data);
                this.showProductMessage(`品番が確認されました（${data.data.length}件の商品グループが見つかりました）`, 'success');
                this.updateStepStatus(2, 'completed');
                this.updateStepStatus(3, '');
            } else {
                this.showProductMessage('該当する品番が見つかりません', 'error');
                this.resetSteps([3, 4]);
                this.hideUrlCopySection();
                this.saveFormState();
            }
        })
        .catch(error => {
            console.error('品番リスト取得エラー:', error);
            this.showProductMessage(`品番検索でエラーが発生しました: ${error.message}`, 'error');
            this.resetSteps([3, 4]);
            this.hideUrlCopySection();
            this.saveFormState();
        });
    }

    /**
     * メーカー表示関数群
     */
    showManufacturerFound(maker) {
        this.elements.manufacturerName.textContent = maker.manufacturer_name;
        this.elements.manufacturerName.classList.remove('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '<div class="success-message"><i class="bi bi-check-circle"></i>メーカー情報が確認されました</div>';
        
        this.updateStepStatus(1, 'completed');
        
        // 状態を保存
        this.saveFormState();
    }

    showManufacturerNotFound() {
        this.elements.manufacturerName.textContent = 'メーカーが見つかりません';
        this.elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>該当するメーカーが見つかりません</div>';
        
        this.updateStepStatus(1, 'disabled');
    }

    showManufacturerMessage(message, type) {
        const messageElement = document.getElementById('manufacturer-message');
        const className = type === 'success' ? 'success-message' : 'text-danger';
        const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
        
        messageElement.innerHTML = `<div class="${className}"><i class="bi ${icon}"></i> ${message}</div>`;
    }

    showManufacturerError(errorMessage) {
        this.elements.manufacturerName.textContent = 'エラーが発生しました';
        this.elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = `<div class="text-danger"><i class="bi bi-exclamation-triangle"></i>エラー: ${errorMessage}</div>`;
        
        this.updateStepStatus(1, 'disabled');
    }

    resetManufacturerDisplay() {
        this.elements.manufacturerName.textContent = 'メーカーコードを入力してください';
        this.elements.manufacturerName.classList.add('empty');
        
        const messageElement = document.getElementById('manufacturer-message');
        messageElement.innerHTML = '';
        
        this.updateStepStatus(1, 'disabled');
    }

    /**
     * 商品表示関数群
     */
    showProductList(products) {
        this.elements.productList.innerHTML = '';
        
        products.forEach(product => {
            const item = document.createElement('div');
            item.className = 'product-item';
            
            let priceDisplay = '';
            if (product.min_price === product.max_price) {
                priceDisplay = `¥${product.selling_price.toLocaleString()}`;
            } else {
                priceDisplay = `¥${product.min_price.toLocaleString()} - ¥${product.max_price.toLocaleString()}`;
            }
            
            item.innerHTML = `
                <div class="product-info">
                    <div class="product-name">${this.escapeHtml(product.product_name)}</div>
                    <div class="product-details">
                        シーズン: ${this.escapeHtml(product.season_code || '-')} | 
                        価格: ${priceDisplay} | 
                        SKU数: ${product.jan_count}個
                    </div>
                </div>
                <div class="jan-count">${product.jan_count} SKU</div>
            `;
            
            item.addEventListener('click', () => {
                this.selectProduct(item, product);
            });
            
            this.elements.productList.appendChild(item);
        });
        
        this.elements.productList.style.display = 'block';
        
        // 状態を保存
        this.saveFormState();
    }

    /**
     * 商品選択処理
     */
    selectProduct(element, product) {
        document.querySelectorAll('.product-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        element.classList.add('selected');
        this.selectedProduct = product;
        
        document.getElementById('productName').value = product.product_name;
        document.getElementById('selectedProductName').textContent = product.product_name;
        
        this.updateStepStatus(3, 'completed');
        this.updateStepStatus(4, '');
        this.elements.executeBtn.disabled = false;
        
        this.fetchTargetProducts(product);
        this.elements.targetProducts.style.display = 'block';
        
        // 状態を保存
        this.saveFormState();
    }

    /**
     * 集計対象商品（JANコード）取得
     */
    fetchTargetProducts(product) {
        const searchUrl = `${this.apiBase}/get-target-products?manufacturer_code=${encodeURIComponent(this.currentManufacturerCode)}&product_number=${encodeURIComponent(this.currentProductNumber)}&product_name=${encodeURIComponent(product.product_name)}`;
        
        this.fetchWithAuth(searchUrl, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                this.currentJanCodes = data.data.map(item => item.jan_code);
                
                const janList = document.getElementById('janList');
                janList.innerHTML = data.data.map(item => {
                    let displayText = item.jan_code;
                    if (item.size_name && item.size_name !== 'F') {
                        displayText += ` (${item.size_name})`;
                    }
                    if (item.color_name && item.color_name !== '-') {
                        displayText += ` [${item.color_name}]`;
                    }
                    return `<div class="jan-item" data-jan-code="${this.escapeHtml(item.jan_code)}">${this.escapeHtml(displayText)}</div>`;
                }).join('');
                
                this.showUrlCopySection();
                
                // 状態を保存
                this.saveFormState();
            } else {
                const janList = document.getElementById('janList');
                janList.innerHTML = '<div class="text-muted">JANコードが見つかりません</div>';
                this.hideUrlCopySection();
            }
        })
        .catch(error => {
            console.error('JANコード取得エラー:', error);
            const janList = document.getElementById('janList');
            janList.innerHTML = '<div class="text-danger">JANコード取得エラー</div>';
            this.hideUrlCopySection();
        });
    }

    showProductMessage(message, type) {
        const messageElement = document.getElementById('product-message');
        const className = type === 'success' ? 'success-message' : 'text-danger';
        const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
        
        messageElement.innerHTML = `<div class="${className}"><i class="bi ${icon}"></i> ${message}</div>`;
    }

    resetProductDisplay() {
        document.getElementById('product-message').innerHTML = '';
        this.elements.productList.style.display = 'none';
        this.elements.targetProducts.style.display = 'none';
        this.updateStepStatus(2, 'disabled');
        this.hideUrlCopySection();
    }

    /**
     * URLコピー機能
     */
    showUrlCopySection() {
        if (this.currentJanCodes.length > 0) {
            const resultUrl = `${this.siteUrl}/sales-analysis/single-product/result`;
            const janCodesParam = this.currentJanCodes.join(',');
            const quickUrl = `${resultUrl}?jan_codes=${encodeURIComponent(janCodesParam)}`;
            
            this.elements.quickAnalysisUrl.value = quickUrl;
            this.elements.urlCopySection.style.display = 'block';
            this.elements.urlCopySection.classList.add('fade-in');
        }
    }

    hideUrlCopySection() {
        this.elements.urlCopySection.style.display = 'none';
        this.elements.quickAnalysisUrl.value = '';
    }

    showCopySuccess() {
        const originalText = this.elements.copyUrlBtn.innerHTML;
        this.elements.copyUrlBtn.innerHTML = '<i class="bi bi-check me-2"></i>コピー完了';
        this.elements.copyUrlBtn.classList.remove('btn-outline-primary');
        this.elements.copyUrlBtn.classList.add('btn-success');
        
        setTimeout(() => {
            this.elements.copyUrlBtn.innerHTML = originalText;
            this.elements.copyUrlBtn.classList.remove('btn-success');
            this.elements.copyUrlBtn.classList.add('btn-outline-primary');
        }, 2000);
    }

    /**
     * Step管理関数
     */
    enableStep2() {
        this.updateStepStatus(2, '');
        this.elements.productNumber.disabled = false;
        document.getElementById('productSearchBtn').disabled = false;
    }

    updateStepStatus(stepNumber, status) {
        const stepElement = document.getElementById(`step${stepNumber}-number`);
        stepElement.classList.remove('completed', 'disabled');
        
        if (status === 'completed') {
            stepElement.classList.add('completed');
        } else if (status === 'disabled') {
            stepElement.classList.add('disabled');
        }
    }

    resetSteps(steps) {
        steps.forEach(step => {
            if (step === 2) {
                this.updateStepStatus(2, 'disabled');
                this.elements.productNumber.disabled = true;
                this.elements.productNumber.value = '';
                document.getElementById('productSearchBtn').disabled = true;
                document.getElementById('product-message').innerHTML = '';
            } else if (step === 3) {
                this.updateStepStatus(3, 'disabled');
                this.elements.productList.style.display = 'none';
                this.elements.targetProducts.style.display = 'none';
                document.getElementById('productName').value = '';
                this.selectedProduct = null;
                this.currentJanCodes = [];
            } else if (step === 4) {
                this.updateStepStatus(4, 'disabled');
                this.elements.executeBtn.disabled = true;
            }
        });
    }

    /**
     * メーカー参照モーダル処理
     */
    initializeMakerModal() {
        if (this.elements.makerReferenceModal) {
            this.elements.makerReferenceModal.addEventListener('show.bs.modal', () => {
                this.initializeMakerModalContent();
            });
        }

        // 検索イベント
        const modalSearchBtn = document.getElementById('btn_modal_search');
        const modalSearchInput = document.getElementById('modal_search_keyword');
        
        if (modalSearchBtn) {
            modalSearchBtn.addEventListener('click', () => {
                const keyword = modalSearchInput.value.trim();
                this.searchMakersInModal(keyword, 1);
            });
        }
        
        if (modalSearchInput) {
            modalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const keyword = modalSearchInput.value.trim();
                    this.searchMakersInModal(keyword, 1);
                }
            });
        }

        // ページングボタン
        const prevBtn = document.getElementById('btn_prev_page');
        const nextBtn = document.getElementById('btn_next_page');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.searchMakersInModal(this.currentKeyword, this.currentPage - 1);
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (this.currentPage < this.totalPages) {
                    this.searchMakersInModal(this.currentKeyword, this.currentPage + 1);
                }
            });
        }

        // メーカー選択ボタン
        const selectMakerBtn = document.getElementById('btn_select_maker');
        if (selectMakerBtn) {
            selectMakerBtn.addEventListener('click', () => {
                if (this.selectedMaker) {
                    this.elements.manufacturerCode.value = this.selectedMaker.manufacturer_code;
                    this.currentManufacturerCode = this.selectedMaker.manufacturer_code;
                    
                    this.showManufacturerFound(this.selectedMaker);
                    this.enableStep2();
                    
                    const modal = bootstrap.Modal.getInstance(this.elements.makerReferenceModal);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // モーダルが完全に閉じてからフォーカスを移動
                    this.elements.makerReferenceModal.addEventListener('hidden.bs.modal', () => {
                        this.elements.productNumber.focus();
                    }, { once: true });
                    
                    this.selectedMaker = null;
                }
            });
        }

        // キャンセルボタンのイベント処理
        const cancelBtn = this.elements.makerReferenceModal.querySelector('.modal-footer .btn-secondary');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                // モーダルが完全に閉じてからフォーカスを戻す
                this.elements.makerReferenceModal.addEventListener('hidden.bs.modal', () => {
                    this.elements.manufacturerCode.focus();
                }, { once: true });
            });
        }
    }

    initializeMakerModalContent() {
        document.getElementById('modal_search_keyword').value = '';
        document.getElementById('btn_select_maker').disabled = true;
        this.clearSelectedMakerInfo();
        this.hideAllModalElements();
        this.searchMakersInModal('', 1);
    }

    hideAllModalElements() {
        const hideElements = [
            'search_results_info', 'modal_pagination', 
            'modal_no_results', 'modal_loading'
        ];
        hideElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const resultsContainer = document.getElementById('maker_search_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
    }

    /**
     * メーカー検索実行（モーダル用）
     */
    searchMakersInModal(keyword, page = 1) {
        this.showModalLoading();
        this.currentKeyword = keyword;
        this.currentPage = page;

        // 除外範囲をパラメータに追加
        const params = new URLSearchParams({
            keyword: keyword,
            page: page,
            exclude_range_start: '0100000',
            exclude_range_end: '0199999'
        });

        const searchUrl = `${this.apiBase}/search-makers?${params}`;

        this.fetchWithAuth(searchUrl, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            this.hideModalLoading();
            
            if (data.success && data.data.length > 0) {
                this.displayMakersInModal(data.data);
                this.updateResultsInfo(data.pagination, data.keyword);
                this.updatePagination(data.pagination);
            } else {
                this.showNoResults();
                if (data.pagination && data.pagination.total_count === 0) {
                    this.updateResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            console.error('モーダルメーカー検索エラー:', error);
            this.hideModalLoading();
            this.showModalError(error.message);
        });
    }

    showModalLoading() {
        document.getElementById('modal_loading').style.display = 'block';
        document.getElementById('modal_no_results').style.display = 'none';
        document.getElementById('search_results_info').style.display = 'none';
        document.getElementById('modal_pagination').style.display = 'none';
    }

    hideModalLoading() {
        document.getElementById('modal_loading').style.display = 'none';
    }

    showNoResults() {
        document.getElementById('modal_no_results').style.display = 'block';
    }

    showModalError(message) {
        const noResultsElement = document.getElementById('modal_no_results');
        noResultsElement.style.display = 'block';
        noResultsElement.innerHTML = `
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            検索中にエラーが発生しました: ${message}
        `;
    }

    /**
     * メーカー一覧表示（モーダル用）
     */
    displayMakersInModal(makers) {
        const resultsContainer = document.getElementById('maker_search_results');
        resultsContainer.innerHTML = '';
        
        makers.forEach(maker => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            row.innerHTML = `
                <td>${this.escapeHtml(maker.manufacturer_code)}</td>
                <td>${this.escapeHtml(maker.manufacturer_name)}</td>
            `;
            
            // クリックイベント
            row.addEventListener('click', () => {
                document.querySelectorAll('#maker_search_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                
                row.classList.add('table-primary');
                this.selectedMaker = maker;
                document.getElementById('btn_select_maker').disabled = false;
            });
            
            // ダブルクリックイベント
            row.addEventListener('dblclick', () => {
                // 選択状態にする
                document.querySelectorAll('#maker_search_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                row.classList.add('table-primary');
                this.selectedMaker = maker;
                
                // 選択処理を実行
                this.elements.manufacturerCode.value = this.selectedMaker.manufacturer_code;
                this.currentManufacturerCode = this.selectedMaker.manufacturer_code;
                
                this.showManufacturerFound(this.selectedMaker);
                this.enableStep2();
                
                const modal = bootstrap.Modal.getInstance(this.elements.makerReferenceModal);
                if (modal) {
                    modal.hide();
                }
                
                // モーダルが完全に閉じてからフォーカスを移動
                this.elements.makerReferenceModal.addEventListener('hidden.bs.modal', () => {
                    this.elements.productNumber.focus();
                }, { once: true });
                
                this.selectedMaker = null;
            });
            
            resultsContainer.appendChild(row);
        });
    }

    updateSelectedMakerInfo(maker) {
        // 選択中のメーカー表示機能を削除
    }

    clearSelectedMakerInfo() {
        // 選択中のメーカー表示機能を削除
    }

    updateResultsInfo(pagination, keyword) {
        if (!pagination) return;
        
        let infoText = '';
        if (keyword) {
            infoText = `「${keyword}」の検索結果: `;
        } else {
            infoText = '検索結果: ';
        }
        
        if (pagination.total_count === 0) {
            infoText += '該当なし';
        } else {
            infoText += `${pagination.total_count}件中 ${pagination.from}-${pagination.to}件目を表示`;
        }
        
        document.getElementById('results_count_text').textContent = infoText;
        document.getElementById('search_results_info').style.display = 'block';
    }

    updatePagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            document.getElementById('modal_pagination').style.display = 'none';
            return;
        }
        
        this.currentPage = pagination.current_page;
        this.totalPages = pagination.total_pages;
        
        document.getElementById('page_info').textContent = `${this.currentPage} / ${this.totalPages}`;
        document.getElementById('btn_prev_page').disabled = !pagination.has_prev_page;
        document.getElementById('btn_next_page').disabled = !pagination.has_next_page;
        
        document.getElementById('modal_pagination').style.display = 'flex';
    }

    /**
     * 品番参照モーダル処理
     */
    initializeProductModal() {
        const productModal = this.elements.productReferenceModal;

        if (productModal) {
            productModal.addEventListener('show.bs.modal', () => {
                this.initializeProductModalContent();
            });
        }

        // 品番検索イベント
        const productSearchBtn = document.getElementById('btn_product_search');
        const productSearchInput = document.getElementById('product_search_keyword');
        
        if (productSearchBtn) {
            productSearchBtn.addEventListener('click', () => {
                const keyword = productSearchInput.value.trim();
                this.searchProductsInModal(keyword, 1);
            });
        }
        
        if (productSearchInput) {
            productSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const keyword = productSearchInput.value.trim();
                    this.searchProductsInModal(keyword, 1);
                }
            });
        }

        // 品番ページングボタン
        const productPrevBtn = document.getElementById('btn_product_prev_page');
        const productNextBtn = document.getElementById('btn_product_next_page');
        
        if (productPrevBtn) {
            productPrevBtn.addEventListener('click', () => {
                if (this.currentProductPage > 1) {
                    this.searchProductsInModal(this.currentProductKeyword, this.currentProductPage - 1);
                }
            });
        }
        
        if (productNextBtn) {
            productNextBtn.addEventListener('click', () => {
                if (this.currentProductPage < this.totalProductPages) {
                    this.searchProductsInModal(this.currentProductKeyword, this.currentProductPage + 1);
                }
            });
        }

        // 品番選択ボタン
        const selectProductBtn = document.getElementById('btn_select_product');
        if (selectProductBtn) {
            selectProductBtn.addEventListener('click', () => {
                if (this.selectedProductFromModal) {
                    this.elements.productNumber.value = this.selectedProductFromModal.product_number;
                    this.currentProductNumber = this.selectedProductFromModal.product_number;
                    
                    this.showProductList([this.selectedProductFromModal]);
                    this.showProductMessage(`品番が確認されました（選択: ${this.selectedProductFromModal.product_name}）`, 'success');
                    this.updateStepStatus(2, 'completed');
                    this.updateStepStatus(3, '');
                    
                    setTimeout(() => {
                        const productItems = document.querySelectorAll('.product-item');
                        if (productItems.length > 0) {
                            productItems[0].click();
                        }
                    }, 100);
                    
                    const modal = bootstrap.Modal.getInstance(productModal);
                    if (modal) {
                        modal.hide();
                    }
                    
                    this.selectedProductFromModal = null;
                }
            });
        }
    }

    initializeProductModalContent() {
        document.getElementById('current_maker_code').textContent = this.currentManufacturerCode || '-';
        const makerName = this.elements.manufacturerName.textContent;
        document.getElementById('current_maker_name').textContent = makerName === 'メーカーコードを入力してください' ? '-' : makerName;
        
        document.getElementById('product_search_keyword').value = '';
        document.getElementById('btn_select_product').disabled = true;
        this.hideAllProductModalElements();
        
        if (this.currentManufacturerCode) {
            this.searchProductsInModal('', 1);
        }
    }

    hideAllProductModalElements() {
        const hideElements = [
            'product_search_results_info', 'product_modal_pagination', 
            'product_modal_no_results', 'product_modal_loading'
        ];
        hideElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const resultsContainer = document.getElementById('product_search_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
    }

    /**
     * 品番検索実行（モーダル用・エラーハンドリング改善版）
     */
    searchProductsInModal(keyword, page = 1) {
        if (!this.currentManufacturerCode) {
            this.showProductModalError('メーカーが選択されていません');
            return;
        }
        
        this.showProductModalLoading();
        this.currentProductKeyword = keyword;
        this.currentProductPage = page;

        const searchUrl = `${this.apiBase}/search-products?manufacturer_code=${encodeURIComponent(this.currentManufacturerCode)}&keyword=${encodeURIComponent(keyword)}&page=${page}`;

        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            if (!text) {
                console.error('Empty response from search-products API');
                throw new Error('サーバーからの応答が空です');
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('サーバーからの応答が不正です');
            }
            
            this.hideProductModalLoading();
            
            if (data.success && data.data.length > 0) {
                this.displayProductsInModal(data.data);
                this.updateProductResultsInfo(data.pagination, data.keyword);
                this.updateProductPagination(data.pagination);
            } else {
                this.showProductNoResults();
                if (data.pagination && data.pagination.total_count === 0) {
                    this.updateProductResultsInfo(data.pagination, data.keyword);
                }
            }
        })
        .catch(error => {
            console.error('モーダル品番検索エラー:', error);
            this.hideProductModalLoading();
            this.showProductModalError(error.message);
        });
    }

    showProductModalLoading() {
        document.getElementById('product_modal_loading').style.display = 'block';
        document.getElementById('product_modal_no_results').style.display = 'none';
        document.getElementById('product_search_results_info').style.display = 'none';
        document.getElementById('product_modal_pagination').style.display = 'none';
    }

    hideProductModalLoading() {
        document.getElementById('product_modal_loading').style.display = 'none';
    }

    showProductNoResults() {
        document.getElementById('product_modal_no_results').style.display = 'block';
    }

    showProductModalError(message) {
        const noResultsElement = document.getElementById('product_modal_no_results');
        noResultsElement.style.display = 'block';
        noResultsElement.innerHTML = `
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            検索中にエラーが発生しました: ${message}
        `;
    }

    /**
     * 品番一覧表示（モーダル用）
     */
    displayProductsInModal(products) {
        const resultsContainer = document.getElementById('product_search_results');
        resultsContainer.innerHTML = '';
        
        products.forEach(product => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            
            let priceDisplay = '';
            if (product.min_price === product.max_price) {
                priceDisplay = `¥${product.selling_price.toLocaleString()}`;
            } else {
                priceDisplay = `¥${product.min_price.toLocaleString()} - ¥${product.max_price.toLocaleString()}`;
            }
            
            let deletionDate = '-';
            if (product.earliest_deletion_date) {
                const date = new Date(product.earliest_deletion_date);
                deletionDate = date.toLocaleDateString('ja-JP');
            }
            
            row.innerHTML = `
                <td>${this.escapeHtml(product.product_number)}</td>
                <td>${this.escapeHtml(product.product_name)}</td>
                <td>${this.escapeHtml(product.season_code || '-')}</td>
                <td>${priceDisplay}</td>
                <td>${product.jan_count}個</td>
                <td>${deletionDate}</td>
            `;
            
            row.addEventListener('click', () => {
                document.querySelectorAll('#product_search_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                
                row.classList.add('table-primary');
                this.selectedProductFromModal = product;
                document.getElementById('btn_select_product').disabled = false;
            });
            
            resultsContainer.appendChild(row);
        });
    }


    updateProductResultsInfo(pagination, keyword) {
        if (!pagination) return;
        
        let infoText = '';
        if (keyword) {
            infoText = `「${keyword}」の検索結果: `;
        } else {
            infoText = '検索結果: ';
        }
        
        if (pagination.total_count === 0) {
            infoText += '該当なし';
        } else {
            infoText += `${pagination.total_count}件中 ${pagination.from}-${pagination.to}件目を表示`;
        }
        
        document.getElementById('product_results_count_text').textContent = infoText;
        document.getElementById('product_search_results_info').style.display = 'block';
    }

    updateProductPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            document.getElementById('product_modal_pagination').style.display = 'none';
            return;
        }
        
        this.currentProductPage = pagination.current_page;
        this.totalProductPages = pagination.total_pages;
        
        document.getElementById('product_page_info').textContent = `${this.currentProductPage} / ${this.totalProductPages}`;
        document.getElementById('btn_product_prev_page').disabled = !pagination.has_prev_page;
        document.getElementById('btn_product_next_page').disabled = !pagination.has_next_page;
        
        document.getElementById('product_modal_pagination').style.display = 'flex';
    }

    /**
     * HTMLエスケープ関数
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', function() {
    window.singleProductForm = new SingleProductForm();
});