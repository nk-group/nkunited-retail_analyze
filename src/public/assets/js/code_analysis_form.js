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
        this.productData = new Map(); // 商品情報キャッシュ
        this.duplicateCheckEnabled = true;
        
        this.init();
    }
    
    /**
     * 初期化
     */
    init() {
        console.log('=== CodeAnalysisForm 初期化開始 ===');
        
        if (!this.initializeElements()) {
            return;
        }
        
        this.bindEvents();
        this.initializeState();
        
        console.log('=== CodeAnalysisForm 初期化完了 ===');
    }
    
    /**
     * 要素の初期化
     */
    initializeElements() {
        const elementIds = [
            'codeTypeJan', 'codeTypeSku', 'currentCodeType', 'addProductBtn',
            'productInputList', 'executeBtn', 'productCodesInput', 
            'analysisSummary', 'summaryText', 'urlCopySection',
            'quickAnalysisUrl', 'copyUrlBtn'
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
        
        console.log('コード種類変更:', selectedType);
        
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
        
        console.log('商品行追加: 行数=', this.currentRowCount);
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
        
        if (input) {
            // デバウンス処理付きの入力イベント
            let inputTimer;
            input.addEventListener('input', () => {
                clearTimeout(inputTimer);
                inputTimer = setTimeout(() => {
                    this.handleProductCodeInput(input);
                }, 300);
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
    }
    
    /**
     * 商品コード入力の処理
     */
    handleProductCodeInput(input) {
        const index = input.getAttribute('data-index');
        const code = input.value.trim();
        
        // 入力中は商品情報をクリア
        this.clearProductInfo(index);
        this.updateRowState(index, 'input');
        
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
     * 商品コードの検証（Phase 3で拡張予定）
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
        
        // Phase 3 で実装予定: API呼び出しによる商品情報取得
        this.showCodeValidationMessage(index, 'Phase 3で商品情報取得機能を実装予定', 'info');
        this.updateRowState(index, 'info');
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
            
            console.log('商品行削除: index=', index);
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
            productInfo: row.querySelector('.product-info-display')
        };
        
        if (elements.rowNumber) elements.rowNumber.textContent = newIndex;
        if (elements.input) {
            elements.input.id = `productCode${newIndex}`;
            elements.input.name = `product_code_${newIndex}`;
            elements.input.setAttribute('data-index', newIndex);
        }
        if (elements.message) elements.message.id = `codeMessage${newIndex}`;
        if (elements.productInfo) elements.productInfo.id = `productInfo${newIndex}`;
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
        
        console.log('有効な商品コード更新:', this.validProductCodes);
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
            const baseUrl = window.location.origin + '/sales-analysis/single-product/result';
            const codesParam = this.validProductCodes.join(',');
            
            let quickUrl;
            if (this.currentCodeType === 'jan_code') {
                quickUrl = `${baseUrl}?jan_codes=${encodeURIComponent(codesParam)}`;
            } else {
                quickUrl = `${baseUrl}?sku_codes=${encodeURIComponent(codesParam)}`;
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
        
        console.log('フォーム送信: 有効コード数=', this.validProductCodes.length);
        return true;
    }
    
    /**
     * 商品データの設定（Phase 3で使用予定）
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
            'cost_price': productData.cost_price ? `¥${productData.cost_price.toLocaleString()}` : '-',
            'manufacturer': productData.manufacturer_code && productData.manufacturer_name 
                ? `${productData.manufacturer_code}:${productData.manufacturer_name}` : '-'
        };
        
        Object.entries(fields).forEach(([field, value]) => {
            const element = productInfo.querySelector(`[data-field="${field}"]`);
            if (element) {
                element.textContent = value;
            }
        });
        
        productInfo.style.display = 'block';
        this.updateRowState(index, 'success');
        
        // キャッシュに保存
        const code = document.getElementById(`productCode${index}`).value.trim();
        this.productData.set(code, productData);
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