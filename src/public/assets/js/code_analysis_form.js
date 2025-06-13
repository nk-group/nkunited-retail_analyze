/**
 * コード分析フォーム専用JavaScript
 * (public/assets/js/code_analysis_form.js)
 */

class CodeAnalysisForm {
    constructor() {
        this.elements = {};
        this.currentRowCount = 1;
        this.validProductCodes = [];
        this.currentCodeType = 'jan_code';
        this.productData = new Map();
        this.duplicateCheckEnabled = true;
        this.debounceTimers = new Map();
        this.currentProductSearchPage = 1;
        this.productSearchKeyword = '';
        this.totalProductSearchPages = 1;
        this.selectedProductFromModal = null;
        this.currentSearchRowIndex = null;
        this.baseUrl = '';
        this.siteUrl = '';
        this.apiBase = '';
        
        this.init();
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
        this.initializeState();
        this.initializeProductSearchModal();
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
            'codeTypeJan', 'codeTypeSku', 'currentCodeType', 'addProductBtn',
            'productInputList', 'executeBtn', 'productCodesInput', 
            'analysisSummary', 'summaryText', 'urlCopySection',
            'quickAnalysisUrl', 'copyUrlBtn', 'productSearchModal'
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
     * イベントバインド
     */
    bindEvents() {
        // コード種類変更
        this.elements.codeTypeJan.addEventListener('change', () => this.handleCodeTypeChange());
        this.elements.codeTypeSku.addEventListener('change', () => this.handleCodeTypeChange());
        
        // 商品追加
        this.elements.addProductBtn.addEventListener('click', () => this.addProductRow());
        
        // URLコピー
        this.elements.copyUrlBtn.addEventListener('click', () => this.copyUrlToClipboard());
        
        // フォーム送信時の検証
        const form = document.getElementById('codeAnalysisForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
        
        // 初期行のイベント設定
        this.bindRowEvents(1);
        
        // キーボードショートカット
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }
    
    /**
     * 初期状態の設定
     */
    initializeState() {
        this.updateRemoveButtonsVisibility();
        this.updateAnalysisSummary();
        this.updateExecuteButton();
        this.updatePlaceholders();
    }
    
    /**
     * コード種類変更の処理
     */
    handleCodeTypeChange() {
        const selectedType = document.querySelector('input[name="code_type"]:checked').value;
        this.currentCodeType = selectedType;
        
        this.elements.currentCodeType.textContent = 
            selectedType === 'jan_code' ? 'JANコード' : 'SKUコード';
        
        // 既存入力のクリアと確認
        if (this.hasAnyInput()) {
            if (confirm('コード種類を変更すると、入力済みの内容がクリアされます。よろしいですか？')) {
                this.clearAllProductInputs();
            } else {
                // 変更をキャンセル
                this.revertCodeTypeSelection();
                return;
            }
        }
        
        this.updatePlaceholders();
        this.updateAnalysisSummary();
        this.updateExecuteButton();
    }
    
    /**
     * コード種類選択の復元
     */
    revertCodeTypeSelection() {
        if (this.currentCodeType === 'jan_code') {
            this.elements.codeTypeJan.checked = true;
        } else {
            this.elements.codeTypeSku.checked = true;
        }
    }
    
    /**
     * 入力があるかチェック
     */
    hasAnyInput() {
        const inputs = document.querySelectorAll('.product-code-input');
        return Array.from(inputs).some(input => input.value.trim() !== '');
    }
    
    /**
     * プレースホルダーの更新
     */
    updatePlaceholders() {
        const inputs = document.querySelectorAll('.product-code-input');
        const placeholder = this.currentCodeType === 'jan_code' 
            ? 'JANコードを入力してください' 
            : 'SKUコードを入力してください';
            
        inputs.forEach(input => {
            input.placeholder = placeholder;
        });
    }
    
    /**
     * 商品行の追加
     */
    addProductRow() {
        this.currentRowCount++;
        
        const newRow = this.createProductRow(this.currentRowCount);
        this.elements.productInputList.appendChild(newRow);
        
        this.bindRowEvents(this.currentRowCount);
        this.updateRemoveButtonsVisibility();
        
        // 新しい行にフォーカス
        const newInput = newRow.querySelector('.product-code-input');
        if (newInput) {
            newInput.focus();
        }
    }
    
    /**
     * 商品行の作成
     */
    createProductRow(index) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'product-input-row';
        rowDiv.setAttribute('data-index', index);
        
        const placeholder = this.currentCodeType === 'jan_code' 
            ? 'JANコードを入力してください' 
            : 'SKUコードを入力してください';
        
        rowDiv.innerHTML = this.getRowTemplate(index, placeholder);
        return rowDiv;
    }
    
    /**
     * 行テンプレートの取得
     */
    getRowTemplate(index, placeholder) {
        return `
            <div class="row-header">
                <span class="row-number">${index}</span>
                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            
            <div class="product-input-content">
                <div class="code-input-area">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control product-code-input" 
                               id="productCode${index}" 
                               name="product_code_${index}"
                               placeholder="${placeholder}" 
                               data-index="${index}"
                               autocomplete="off">
                        <button type="button" 
                                class="btn btn-outline-secondary product-search-btn" 
                                data-index="${index}"
                                data-bs-toggle="modal" 
                                data-bs-target="#productSearchModal">
                            <i class="bi bi-search me-2"></i>参照
                        </button>
                    </div>
                    <div class="code-validation-message" id="codeMessage${index}"></div>
                </div>
                
                <div class="product-info-display" id="productInfo${index}" style="display: none;">
                    <div class="product-info-grid">
                        <div class="info-item">
                            <label>商品名:</label>
                            <span class="info-value" data-field="product_name">-</span>
                        </div>
                        <div class="info-item">
                            <label>メーカー品番:</label>
                            <span class="info-value" data-field="product_number">-</span>
                        </div>
                        <div class="info-item">
                            <label>カラー:</label>
                            <span class="info-value" data-field="color_name">-</span>
                        </div>
                        <div class="info-item">
                            <label>サイズ:</label>
                            <span class="info-value" data-field="size_name">-</span>
                        </div>
                        <div class="info-item">
                            <label>売価:</label>
                            <span class="info-value" data-field="selling_price">-</span>
                        </div>
                        <div class="info-item">
                            <label>M単価:</label>
                            <span class="info-value" data-field="m_unit_price">-</span>
                        </div>
                        <div class="info-item">
                            <label>原価:</label>
                            <span class="info-value" data-field="cost_price">-</span>
                        </div>
                        <div class="info-item">
                            <label>メーカー:</label>
                            <span class="info-value" data-field="manufacturer">-</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * 行イベントのバインド
     */
    bindRowEvents(index) {
        const row = document.querySelector(`.product-input-row[data-index="${index}"]`);
        if (!row) return;
        
        const input = row.querySelector('.product-code-input');
        const removeBtn = row.querySelector('.remove-row-btn');
        const searchBtn = row.querySelector('.product-search-btn');
        
        if (input) {
            // デバウンス処理付きの入力イベント
            input.addEventListener('input', () => {
                this.clearProductInfo(index);
                this.updateRowState(index, 'input');
                
                // 既存のタイマーをクリア
                if (this.debounceTimers.has(index)) {
                    clearTimeout(this.debounceTimers.get(index));
                }
                
                // 新しいタイマーを設定
                const timer = setTimeout(() => {
                    this.handleProductCodeInput(input);
                }, 500);
                this.debounceTimers.set(index, timer);
            });
            
            input.addEventListener('blur', () => {
                this.validateProductCode(input);
            });
            
            // エンターキーで次の行に移動または新規追加
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.handleEnterKey(index);
                }
            });
            
            // ペースト処理（一括入力対応）
            input.addEventListener('paste', (e) => {
                setTimeout(() => {
                    this.handlePasteEvent(input);
                }, 10);
            });
        }
        
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.removeProductRow(index);
            });
        }
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.openProductSearchModal(index);
            });
        }
    }
    
    /**
     * 商品コード入力の処理
     */
    handleProductCodeInput(input) {
        const index = input.getAttribute('data-index');
        const code = input.value.trim();
        
        // 重複チェック
        if (code && this.duplicateCheckEnabled) {
            this.checkDuplicateCode(input, code);
        }
        
        this.updateValidProductCodes();
    }
    
    /**
     * 重複チェック
     */
    checkDuplicateCode(currentInput, code) {
        const index = currentInput.getAttribute('data-index');
        const inputs = document.querySelectorAll('.product-code-input');
        let duplicateFound = false;
        
        inputs.forEach(input => {
            if (input !== currentInput && input.value.trim() === code) {
                duplicateFound = true;
            }
        });
        
        if (duplicateFound) {
            this.showCodeValidationMessage(index, '重複するコードが入力されています', 'warning');
            this.updateRowState(index, 'warning');
        }
    }
    
    /**
     * ペーストイベントの処理（一括入力対応）
     */
    handlePasteEvent(input) {
        const pastedText = input.value;
        const codes = this.parsePastedCodes(pastedText);
        
        if (codes.length > 1) {
            if (confirm(`${codes.length}個のコードが検出されました。自動的に行を追加して入力しますか？`)) {
                this.fillMultipleCodes(input, codes);
            }
        }
    }
    
    /**
     * ペーストされたテキストからコードを抽出
     */
    parsePastedCodes(text) {
        // 改行、タブ、カンマ、セミコロンで分割
        const separators = /[\n\r\t,;]/;
        return text.split(separators)
                  .map(code => code.trim())
                  .filter(code => code.length > 0);
    }
    
    /**
     * 複数コードの一括入力
     */
    fillMultipleCodes(startInput, codes) {
        const startIndex = parseInt(startInput.getAttribute('data-index'));
        
        codes.forEach((code, i) => {
            const targetIndex = startIndex + i;
            
            // 必要に応じて行を追加
            while (this.currentRowCount < targetIndex) {
                this.addProductRow();
            }
            
            const targetInput = document.getElementById(`productCode${targetIndex}`);
            if (targetInput) {
                targetInput.value = code;
                this.handleProductCodeInput(targetInput);
                this.validateProductCode(targetInput);
            }
        });
    }
    
    /**
     * エンターキーの処理
     */
    handleEnterKey(currentIndex) {
        const nextIndex = currentIndex + 1;
        let nextInput = document.getElementById(`productCode${nextIndex}`);
        
        if (!nextInput) {
            // 次の行がない場合は追加
            this.addProductRow();
            nextInput = document.getElementById(`productCode${this.currentRowCount}`);
        }
        
        if (nextInput) {
            nextInput.focus();
        }
    }
    
    /**
     * 商品コードの検証（APIによるリアルタイム検証）
     */
    validateProductCode(input) {
        const code = input.value.trim();
        const index = input.getAttribute('data-index');
        
        if (!code) {
            this.clearProductInfo(index);
            this.updateRowState(index, 'empty');
            return;
        }
        
        // 基本的な形式チェック
        if (!this.isValidCodeFormat(code)) {
            this.showCodeValidationMessage(index, 'コード形式が正しくありません', 'error');
            this.updateRowState(index, 'error');
            return;
        }
        
        // API呼び出しによる商品情報取得
        this.fetchProductInfo(code, index);
    }
    
    /**
     * 商品情報API呼び出し
     */
    async fetchProductInfo(code, index) {
        try {
            this.showCodeValidationMessage(index, '商品情報を取得中...', 'info');
            
            const apiUrl = `${this.apiBase}/validate-product-code?code=${encodeURIComponent(code)}&code_type=${this.currentCodeType}`;
            
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.valid && data.product_info) {
                this.setProductInfo(index, data.product_info);
                this.showCodeValidationMessage(index, '商品情報を取得しました', 'success');
                this.updateRowState(index, 'success');
            } else {
                this.showCodeValidationMessage(index, data.message || '商品が見つかりません', 'error');
                this.updateRowState(index, 'error');
            }
            
        } catch (error) {
            console.error('商品情報取得エラー:', error);
            this.showCodeValidationMessage(index, 'エラーが発生しました', 'error');
            this.updateRowState(index, 'error');
        }
    }
    
    /**
     * コード形式の基本チェック
     */
    isValidCodeFormat(code) {
        if (this.currentCodeType === 'jan_code') {
            // JANコード: 8桁または13桁の数字
            return /^\d{8}$|^\d{13}$/.test(code);
        } else {
            // SKUコード: 英数字、ハイフン、アンダースコア（1-50文字）
            return /^[A-Za-z0-9\-_]{1,50}$/.test(code);
        }
    }
    
    /**
     * 商品行の削除
     */
    removeProductRow(index) {
        const row = document.querySelector(`.product-input-row[data-index="${index}"]`);
        if (row) {
            // アニメーション付きで削除
            row.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                row.remove();
                this.renumberRows();
                this.updateRemoveButtonsVisibility();
                this.updateValidProductCodes();
                this.updateAnalysisSummary();
                this.updateExecuteButton();
            }, 300);
            
            // キャッシュから削除
            const input = row.querySelector('.product-code-input');
            if (input) {
                const code = input.value.trim();
                if (code) {
                    this.productData.delete(code);
                }
            }
        }
    }
    
    /**
     * 行番号の振り直し
     */
    renumberRows() {
        const rows = document.querySelectorAll('.product-input-row');
        rows.forEach((row, index) => {
            const newIndex = index + 1;
            this.updateRowIndex(row, newIndex);
        });
        
        this.currentRowCount = rows.length;
    }
    
    /**
     * 行インデックスの更新
     */
    updateRowIndex(row, newIndex) {
        row.setAttribute('data-index', newIndex);
        
        const elements = {
            rowNumber: row.querySelector('.row-number'),
            input: row.querySelector('.product-code-input'),
            message: row.querySelector('.code-validation-message'),
            productInfo: row.querySelector('.product-info-display'),
            searchBtn: row.querySelector('.product-search-btn')
        };
        
        if (elements.rowNumber) elements.rowNumber.textContent = newIndex;
        if (elements.input) {
            elements.input.id = `productCode${newIndex}`;
            elements.input.name = `product_code_${newIndex}`;
            elements.input.setAttribute('data-index', newIndex);
        }
        if (elements.message) elements.message.id = `codeMessage${newIndex}`;
        if (elements.productInfo) elements.productInfo.id = `productInfo${newIndex}`;
        if (elements.searchBtn) elements.searchBtn.setAttribute('data-index', newIndex);
    }
    
    /**
     * 削除ボタンの表示制御
     */
    updateRemoveButtonsVisibility() {
        const removeButtons = document.querySelectorAll('.remove-row-btn');
        removeButtons.forEach(btn => {
            btn.style.display = this.currentRowCount > 1 ? 'inline-block' : 'none';
        });
    }
    
    /**
     * 商品情報のクリア
     */
    clearProductInfo(index) {
        const productInfo = document.getElementById(`productInfo${index}`);
        const codeMessage = document.getElementById(`codeMessage${index}`);
        
        if (productInfo) {
            productInfo.style.display = 'none';
        }
        
        if (codeMessage) {
            codeMessage.innerHTML = '';
        }
    }
    
    /**
     * 行の状態更新
     */
    updateRowState(index, state) {
        const row = document.querySelector(`.product-input-row[data-index="${index}"]`);
        if (!row) return;
        
        // 既存の状態クラスを削除
        row.classList.remove('has-product', 'has-error', 'has-warning', 'has-info');
        
        // 新しい状態クラスを追加
        switch (state) {
            case 'success':
                row.classList.add('has-product');
                break;
            case 'error':
                row.classList.add('has-error');
                break;
            case 'warning':
                row.classList.add('has-warning');
                break;
            case 'info':
                row.classList.add('has-info');
                break;
        }
    }
    
    /**
     * コード検証メッセージの表示
     */
    showCodeValidationMessage(index, message, type) {
        const messageElement = document.getElementById(`codeMessage${index}`);
        if (!messageElement) return;
        
        const classMap = {
            success: 'success-message',
            error: 'text-danger',
            warning: 'text-warning',
            info: 'text-info'
        };
        
        const iconMap = {
            success: 'bi-check-circle',
            error: 'bi-exclamation-triangle',
            warning: 'bi-exclamation-triangle',
            info: 'bi-info-circle'
        };
        
        const className = classMap[type] || 'text-muted';
        const icon = iconMap[type] || 'bi-info-circle';
        
        messageElement.innerHTML = `<div class="${className}"><i class="bi ${icon}"></i> ${message}</div>`;
    }
    
    /**
     * 有効な商品コードリストの更新
     */
    updateValidProductCodes() {
        this.validProductCodes = [];
        
        const inputs = document.querySelectorAll('.product-code-input');
        inputs.forEach(input => {
            const code = input.value.trim();
            if (code && this.isValidCodeFormat(code)) {
                this.validProductCodes.push(code);
            }
        });
        
        // 重複除去
        this.validProductCodes = [...new Set(this.validProductCodes)];
        
        // 隠しフィールドを更新
        this.elements.productCodesInput.value = JSON.stringify(this.validProductCodes);
    }
    
    /**
     * 分析サマリーの更新
     */
    updateAnalysisSummary() {
        if (this.validProductCodes.length === 0) {
            this.elements.analysisSummary.style.display = 'none';
            this.elements.summaryText.textContent = '商品コードを入力してください';
        } else {
            this.elements.analysisSummary.style.display = 'block';
            const codeTypeText = this.currentCodeType === 'jan_code' ? 'JANコード' : 'SKUコード';
            this.elements.summaryText.textContent = `${codeTypeText} ${this.validProductCodes.length}個を分析対象とします`;
        }
    }
    
    /**
     * 実行ボタンの更新
     */
    updateExecuteButton() {
        const hasValidCodes = this.validProductCodes.length > 0;
        this.elements.executeBtn.disabled = !hasValidCodes;
        
        if (hasValidCodes) {
            this.updateStepStatus(3, 'completed');
            this.showUrlCopySection();
        } else {
            this.updateStepStatus(3, 'disabled');
            this.hideUrlCopySection();
        }
    }
    
    /**
     * ステップ状態の更新
     */
    updateStepStatus(stepNumber, status) {
        const stepElement = document.getElementById(`step${stepNumber}-number`);
        if (stepElement) {
            stepElement.classList.remove('completed', 'disabled');
            
            if (status === 'completed') {
                stepElement.classList.add('completed');
            } else if (status === 'disabled') {
                stepElement.classList.add('disabled');
            }
        }
    }
    
    /**
     * URLコピーセクションの表示
     */
    showUrlCopySection() {
        if (this.validProductCodes.length > 0) {
            const resultUrl = `${this.siteUrl}/sales-analysis/single-product/result`;
            const codesParam = this.validProductCodes.join(',');
            
            let quickUrl;
            if (this.currentCodeType === 'jan_code') {
                quickUrl = `${resultUrl}?jan_codes=${encodeURIComponent(codesParam)}`;
            } else {
                quickUrl = `${resultUrl}?sku_codes=${encodeURIComponent(codesParam)}`;
            }
            
            this.elements.quickAnalysisUrl.value = quickUrl;
            this.elements.urlCopySection.style.display = 'block';
            this.elements.urlCopySection.classList.add('fade-in');
        }
    }
    
    /**
     * URLコピーセクションの非表示
     */
    hideUrlCopySection() {
        this.elements.urlCopySection.style.display = 'none';
        this.elements.quickAnalysisUrl.value = '';
    }
    
    /**
     * URLのクリップボードコピー
     */
    copyUrlToClipboard() {
        this.elements.quickAnalysisUrl.select();
        this.elements.quickAnalysisUrl.setSelectionRange(0, 99999);
        
        try {
            navigator.clipboard.writeText(this.elements.quickAnalysisUrl.value).then(() => {
                this.showCopySuccess();
            }).catch(() => {
                // フォールバック
                document.execCommand('copy');
                this.showCopySuccess();
            });
        } catch (err) {
            document.execCommand('copy');
            this.showCopySuccess();
        }
    }
    
    /**
     * コピー成功の表示
     */
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
     * 全商品入力のクリア
     */
    clearAllProductInputs() {
        const inputs = document.querySelectorAll('.product-code-input');
        inputs.forEach(input => {
            input.value = '';
            const index = input.getAttribute('data-index');
            this.clearProductInfo(index);
            this.updateRowState(index, 'empty');
        });
        
        this.validProductCodes = [];
        this.productData.clear();
        this.elements.productCodesInput.value = '[]';
        this.updateAnalysisSummary();
        this.updateExecuteButton();
    }
    
    /**
     * キーボードショートカットの処理
     */
    handleKeyboardShortcuts(e) {
        // Ctrl + Enter で分析実行
        if (e.ctrlKey && e.key === 'Enter') {
            if (!this.elements.executeBtn.disabled) {
                this.elements.executeBtn.click();
            }
        }
        
        // Ctrl + N で行追加
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            this.addProductRow();
        }
    }
    
    /**
     * フォーム送信時の処理
     */
    handleFormSubmit(e) {
        // 最終的な検証
        this.updateValidProductCodes();
        
        if (this.validProductCodes.length === 0) {
            e.preventDefault();
            alert('分析対象となる有効な商品コードを入力してください。');
            return false;
        }
        
        // 重複チェック
        const uniqueCodes = new Set(this.validProductCodes);
        if (uniqueCodes.size !== this.validProductCodes.length) {
            if (!confirm('重複するコードが含まれています。続行しますか？')) {
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 商品データの設定（API取得結果）
     */
    setProductInfo(index, productData) {
        const productInfo = document.getElementById(`productInfo${index}`);
        if (!productInfo) return;
        
        const fields = {
            'product_name': productData.product_name || '-',
            'product_number': productData.product_number || '-',
            'color_name': productData.color_name || '-',
            'size_name': productData.size_name || 'F',
            'selling_price': productData.selling_price ? `¥${productData.selling_price.toLocaleString()}` : '-',
            'm_unit_price': productData.m_unit_price ? `¥${productData.m_unit_price.toLocaleString()}` : '-',
            'cost_price': productData.effective_cost_price ? `¥${productData.effective_cost_price.toLocaleString()}` : '-',
            'manufacturer': productData.manufacturer || '-'
        };
        
        Object.entries(fields).forEach(([field, value]) => {
            const element = productInfo.querySelector(`[data-field="${field}"]`);
            if (element) {
                element.textContent = value;
            }
        });
        
        productInfo.style.display = 'block';
        
        // キャッシュに保存
        const code = document.getElementById(`productCode${index}`).value.trim();
        this.productData.set(code, productData);
        
        // 有効コードリストを更新
        this.updateValidProductCodes();
        this.updateAnalysisSummary();
        this.updateExecuteButton();
    }
    
    // ===== 商品検索モーダル関連 =====
    
    /**
     * 商品検索モーダルの初期化
     */
    initializeProductSearchModal() {
        const modal = this.elements.productSearchModal;
        if (!modal) return;
        
        // モーダル表示時の初期化
        modal.addEventListener('show.bs.modal', () => {
            this.initializeProductSearchModalContent();
        });
        
        // モーダル非表示時のクリーンアップ
        modal.addEventListener('hidden.bs.modal', () => {
            this.cleanupModalState();
        });
        
        // 検索イベント
        const searchBtn = document.getElementById('btn_product_search_modal');
        const searchInput = document.getElementById('product_search_modal_keyword');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                const keyword = searchInput.value.trim();
                this.searchProductsInModal(keyword, 1);
            });
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const keyword = searchInput.value.trim();
                    this.searchProductsInModal(keyword, 1);
                }
            });
        }
        
        // 追加ボタン（旧：選択ボタン）
        const addBtn = document.getElementById('btn_add_product_modal');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.addProductFromModal();
            });
        }
        
        // 閉じるボタン（旧：キャンセルボタン）
        const closeBtns = modal.querySelectorAll('[data-bs-dismiss="modal"]');
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeProductSearchModal();
            });
        });
        
        // ×ボタン
        const closeBtn = modal.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeProductSearchModal();
            });
        }
        
        // ページングボタン
        const prevBtn = document.getElementById('btn_product_search_prev_page');
        const nextBtn = document.getElementById('btn_product_search_next_page');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.currentProductSearchPage > 1) {
                    this.searchProductsInModal(this.productSearchKeyword, this.currentProductSearchPage - 1);
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (this.currentProductSearchPage < this.totalProductSearchPages) {
                    this.searchProductsInModal(this.productSearchKeyword, this.currentProductSearchPage + 1);
                }
            });
        }
    }

    /**
     * モーダル状態のクリーンアップ
     */
    cleanupModalState() {
        // 選択状態をリセット
        this.selectedProductFromModal = null;
        this.currentSearchRowIndex = null;
        
        // 検索状態をリセット
        this.productSearchKeyword = '';
        this.currentProductSearchPage = 1;
        this.totalProductSearchPages = 1;
        
        // 追加ボタンを無効化
        const addBtn = document.getElementById('btn_add_product_modal');
        if (addBtn) addBtn.disabled = true;
        
        // 検索結果をクリア
        const resultsContainer = document.getElementById('product_search_modal_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
        
        // 検索キーワードをクリア
        const searchInput = document.getElementById('product_search_modal_keyword');
        if (searchInput) searchInput.value = '';
        
        // 各種表示要素を非表示
        const elementsToHide = [
            'product_search_modal_results_info',
            'product_search_modal_pagination', 
            'product_search_modal_loading',
            'product_search_modal_no_results'
        ];
        
        elementsToHide.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        // バックドロップの強制削除
        setTimeout(() => {
            // 複数のバックドロップがある場合は削除
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // body要素からモーダル関連のクラスを削除
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // htmlタグからもクラスを削除
            document.documentElement.classList.remove('modal-open');
            
        }, 100);
    }

    /**
     * 商品検索モーダルの閉じる処理
     */
    closeProductSearchModal() {
        const modal = bootstrap.Modal.getInstance(this.elements.productSearchModal);
        if (modal) {
            modal.hide();
        }
        
        // 強制的にバックドロップとbodyの状態をリセット
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.documentElement.classList.remove('modal-open');
        }, 150);
    }
    
    /**
     * 商品検索モーダルを開く
     */
    openProductSearchModal(index) {
        this.currentSearchRowIndex = index;
        const modal = new bootstrap.Modal(this.elements.productSearchModal);
        modal.show();
    }
    
    /**
     * 商品検索モーダルコンテンツの初期化
     */
    initializeProductSearchModalContent() {
        const keywordInput = document.getElementById('product_search_modal_keyword');
        const selectBtn = document.getElementById('btn_select_product_modal');
        
        if (keywordInput) keywordInput.value = '';
        if (selectBtn) selectBtn.disabled = true;
        
        this.hideAllProductSearchModalElements();
        
        // 初期メッセージ表示
        this.showProductSearchNoResults();
        const noResultsElement = document.getElementById('product_search_modal_no_results');
        if (noResultsElement) {
            noResultsElement.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-search fs-1 text-muted mb-3"></i>
                    <h6 class="text-muted">商品を検索してください</h6>
                    <p class="text-muted small mb-0">
                        商品名、メーカー名、品番、JANコード、SKUコードで検索できます<br>
                        2文字以上のキーワードを入力してください
                    </p>
                </div>
            `;
        }
    }
    
    /**
     * モーダル要素の非表示
     */
    hideAllProductSearchModalElements() {
        const hideElements = [
            'product_search_modal_results_info', 'product_search_modal_pagination', 
            'product_search_modal_no_results', 'product_search_modal_loading'
        ];
        hideElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.style.display = 'none';
        });
        
        const resultsContainer = document.getElementById('product_search_modal_results');
        if (resultsContainer) resultsContainer.innerHTML = '';
    }
    
    /**
     * 商品検索実行（モーダル用）
     */
    async searchProductsInModal(keyword, page = 1) {
        // キーワードが空の場合の処理
        if (!keyword) {
            this.showProductSearchNoResults();
            const noResultsElement = document.getElementById('product_search_modal_no_results');
            if (noResultsElement) {
                noResultsElement.innerHTML = `
                    <i class="bi bi-search me-2"></i>
                    検索キーワードを入力して商品を検索してください。
                `;
            }
            return;
        }
        
        // キーワードが短すぎる場合
        if (keyword.length < 2) {
            this.showProductSearchNoResults();
            const noResultsElement = document.getElementById('product_search_modal_no_results');
            if (noResultsElement) {
                noResultsElement.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    検索キーワードは2文字以上で入力してください。
                `;
            }
            return;
        }
        
        this.showProductSearchModalLoading();
        this.productSearchKeyword = keyword;
        this.currentProductSearchPage = page;

        try {
            const searchUrl = `${this.apiBase}/search-all-products?keyword=${encodeURIComponent(keyword)}&page=${page}`;

            const response = await fetch(searchUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            this.hideProductSearchModalLoading();
            
            if (!response.ok) {
                // HTTPステータスエラーの場合
                const errorText = await response.text();
                console.error('HTTP Error:', response.status, errorText);
                throw new Error(`サーバーエラー: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success === false) {
                // APIがエラーを返した場合
                this.showProductSearchNoResults();
                const noResultsElement = document.getElementById('product_search_modal_no_results');
                if (noResultsElement) {
                    noResultsElement.innerHTML = `
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        ${data.error || 'エラーが発生しました'}
                    `;
                }
                return;
            }
            
            if (data.success && data.data && data.data.length > 0) {
                this.displayProductsInModal(data.data);
                this.updateProductSearchResultsInfo(data.pagination, data.keyword);
                this.updateProductSearchPagination(data.pagination);
            } else {
                this.showProductSearchNoResults();
                const noResultsElement = document.getElementById('product_search_modal_no_results');
                if (noResultsElement) {
                    noResultsElement.innerHTML = `
                        <i class="bi bi-search me-2"></i>
                        「${keyword}」に該当する商品が見つかりませんでした。
                    `;
                }
                
                // ページング情報があれば更新
                if (data.pagination && data.pagination.total_count === 0) {
                    this.updateProductSearchResultsInfo(data.pagination, data.keyword);
                }
            }
            
        } catch (error) {
            console.error('モーダル商品検索エラー:', error);
            this.hideProductSearchModalLoading();
            this.showProductSearchModalError(error.message);
        }
    }
    
    /**
     * モーダルローディング表示
     */
    showProductSearchModalLoading() {
        document.getElementById('product_search_modal_loading').style.display = 'block';
        document.getElementById('product_search_modal_no_results').style.display = 'none';
        document.getElementById('product_search_modal_results_info').style.display = 'none';
        document.getElementById('product_search_modal_pagination').style.display = 'none';
    }
    
    /**
     * モーダルローディング非表示
     */
    hideProductSearchModalLoading() {
        document.getElementById('product_search_modal_loading').style.display = 'none';
    }
    
    /**
     * 商品検索結果なし表示
     */
    showProductSearchNoResults() {
        document.getElementById('product_search_modal_no_results').style.display = 'block';
    }
    
    /**
     * 商品検索エラー表示
     */
    showProductSearchModalError(message) {
        const noResultsElement = document.getElementById('product_search_modal_no_results');
        if (noResultsElement) {
            noResultsElement.style.display = 'block';
            noResultsElement.innerHTML = `
                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                検索中にエラーが発生しました: ${this.escapeHtml(message)}
                <br><small class="text-muted mt-2">
                    しばらく時間をおいて再度お試しください。
                </small>
            `;
        }
    }
    
    /**
     * 商品一覧表示（モーダル用）
     */
    displayProductsInModal(products) {
        const resultsContainer = document.getElementById('product_search_modal_results');
        if (!resultsContainer) {
            console.error('検索結果コンテナが見つかりません');
            return;
        }
        
        resultsContainer.innerHTML = '';
        
        if (!products || products.length === 0) {
            this.showProductSearchNoResults();
            return;
        }
        
        products.forEach(product => {
            const row = document.createElement('tr');
            row.style.cursor = 'pointer';
            
            // 価格表示
            const priceDisplay = product.selling_price && product.selling_price > 0 
                ? `¥${product.selling_price.toLocaleString()}` 
                : '-';
            const mUnitPriceDisplay = product.m_unit_price && product.m_unit_price > 0 
                ? `¥${product.m_unit_price.toLocaleString()}` 
                : '-';
            
            // コード表示（JANまたはSKU）
            const displayCode = this.currentCodeType === 'jan_code' 
                ? (product.jan_code || '-') 
                : (product.sku_code || '-');
            
            row.innerHTML = `
                <td class="text-monospace">${this.escapeHtml(displayCode)}</td>
                <td>${this.escapeHtml(product.product_number || '-')}</td>
                <td>${this.escapeHtml(product.product_name || '-')}</td>
                <td>${this.escapeHtml(product.manufacturer_code || '-')}</td>
                <td>${this.escapeHtml(product.color_name || '-')}</td>
                <td>${this.escapeHtml(product.size_name || '-')}</td>
                <td class="text-end">${priceDisplay}</td>
                <td class="text-end">${mUnitPriceDisplay}</td>
            `;
            
            // 行クリックイベント
            row.addEventListener('click', () => {
                const targetCode = this.currentCodeType === 'jan_code' 
                    ? product.jan_code : product.sku_code;
                if (!targetCode || targetCode === '' || targetCode === null) {
                    alert(`この商品には${this.currentCodeType === 'jan_code' ? 'JANコード' : 'SKUコード'}が登録されていません。`);
                    return;
                }
                
                // 既存の選択を解除
                document.querySelectorAll('#product_search_modal_results tr').forEach(tr => {
                    tr.classList.remove('table-primary');
                });
                
                // 新しい選択を設定
                row.classList.add('table-primary');
                this.selectedProductFromModal = product;
                const addBtn = document.getElementById('btn_add_product_modal');
                if (addBtn) addBtn.disabled = false;
            });
            
            resultsContainer.appendChild(row);
        });
    }
    
    /**
     * 検索結果情報の更新（モーダル用）
     */
    updateProductSearchResultsInfo(pagination, keyword) {
        if (!pagination) return;
        
        let infoText = '';
        if (keyword) {
            infoText = `「${keyword}」の検索結果: `;
        } else {
            infoText = '商品一覧: ';
        }
        
        if (pagination.total_count === 0) {
            infoText += '該当なし';
        } else {
            infoText += `${pagination.total_count}件中 ${pagination.from}-${pagination.to}件目を表示`;
        }
        
        document.getElementById('product_search_modal_results_count_text').textContent = infoText;
        document.getElementById('product_search_modal_results_info').style.display = 'block';
    }
    
    /**
     * ページング情報の更新（モーダル用）
     */
    updateProductSearchPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            document.getElementById('product_search_modal_pagination').style.display = 'none';
            return;
        }
        
        this.currentProductSearchPage = pagination.current_page;
        this.totalProductSearchPages = pagination.total_pages;
        
        document.getElementById('product_search_modal_page_info').textContent = `${this.currentProductSearchPage} / ${this.totalProductSearchPages}`;
        document.getElementById('btn_product_search_prev_page').disabled = !pagination.has_prev_page;
        document.getElementById('btn_product_search_next_page').disabled = !pagination.has_next_page;
        
        document.getElementById('product_search_modal_pagination').style.display = 'flex';
    }
    
    /**
     * モーダルから商品を追加
     */
    addProductFromModal() {
        if (!this.selectedProductFromModal) {
            alert('商品が選択されていません。');
            return;
        }
        
        // 現在の行数を再確認
        const actualRows = document.querySelectorAll('.product-input-row');
        this.currentRowCount = actualRows.length;
        
        // currentSearchRowIndex が無効な場合、最初の空行または最後の行を使用
        let targetIndex = this.currentSearchRowIndex;
        if (!targetIndex || targetIndex < 1 || targetIndex > this.currentRowCount) {
            // 空の行を探す
            let emptyRowFound = false;
            for (let i = 1; i <= this.currentRowCount; i++) {
                const input = document.getElementById(`productCode${i}`);
                if (input && !input.value.trim()) {
                    targetIndex = i;
                    emptyRowFound = true;
                    break;
                }
            }
            
            // 空行がない場合は新しい行を追加
            if (!emptyRowFound) {
                this.addProductRow();
                targetIndex = this.currentRowCount;
            }
        }
        
        // 対象行の入力フィールドに商品コードを設定
        const targetInput = document.getElementById(`productCode${targetIndex}`);
        if (!targetInput) {
            console.error('対象入力フィールドが見つかりません:', `productCode${targetIndex}`);
            alert('対象行が見つかりません。ページを再読み込みしてください。');
            return;
        }
        
        const displayCode = this.currentCodeType === 'jan_code' 
            ? this.selectedProductFromModal.jan_code 
            : this.selectedProductFromModal.sku_code;
        
        if (displayCode) {
            targetInput.value = displayCode;
            
            // 商品情報を直接設定
            this.setProductInfo(targetIndex, {
                jan_code: this.selectedProductFromModal.jan_code,
                sku_code: this.selectedProductFromModal.sku_code,
                manufacturer_code: this.selectedProductFromModal.manufacturer_code,
                manufacturer_name: this.selectedProductFromModal.manufacturer_name,
                product_number: this.selectedProductFromModal.product_number,
                product_name: this.selectedProductFromModal.product_name,
                color_name: this.selectedProductFromModal.color_name,
                size_name: this.selectedProductFromModal.size_name,
                selling_price: this.selectedProductFromModal.selling_price,
                m_unit_price: this.selectedProductFromModal.m_unit_price,
                effective_cost_price: this.selectedProductFromModal.cost_price,
                manufacturer: `${this.selectedProductFromModal.manufacturer_code}:${this.selectedProductFromModal.manufacturer_name}`
            });
            
            this.showCodeValidationMessage(targetIndex, '商品を追加しました', 'success');
            
            // 新しい行を追加
            this.addProductRow();
            
            // 追加された新しい行を次の選択対象として設定
            this.currentSearchRowIndex = this.currentRowCount;
            
            // 選択状態をリセット（選択状態は保持しない仕様に変更）
            // 追加ボタンを無効化
            const addBtn = document.getElementById('btn_add_product_modal');
            if (addBtn) addBtn.disabled = true;
            
            // 行の選択表示をクリア
            document.querySelectorAll('#product_search_modal_results tr').forEach(tr => {
                tr.classList.remove('table-primary');
            });
            
            this.selectedProductFromModal = null;
        } else {
            alert(`この商品には${this.currentCodeType === 'jan_code' ? 'JANコード' : 'SKUコード'}が登録されていません。`);
        }
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

// slideOut アニメーション用CSS（動的追加）
if (!document.querySelector('#code-analysis-animations')) {
    const style = document.createElement('style');
    style.id = 'code-analysis-animations';
    style.textContent = `
        @keyframes slideOut {
            from { 
                opacity: 1; 
                transform: translateX(0); 
                max-height: 200px; 
            }
            to { 
                opacity: 0; 
                transform: translateX(-20px); 
                max-height: 0; 
                padding: 0; 
                margin: 0; 
            }
        }
        
        .product-input-row.has-warning {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .product-input-row.has-info {
            border-color: #17a2b8;
            background: #d1ecf1;
        }
    `;
    document.head.appendChild(style);
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', function() {
    window.codeAnalysisForm = new CodeAnalysisForm();
});