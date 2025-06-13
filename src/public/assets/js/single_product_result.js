/**
 * 単品分析結果画面専用JavaScript
 * (public/assets/js/single_product_result.js)
 */

class SingleProductResult {
    constructor() {
        this.elements = {};
        this.executionTime = 0;
        
        this.init();
    }
    
    /**
     * 初期化
     */
    init() {
        if (!this.initializeElements()) {
            return;
        }
        
        this.bindEvents();
        this.initializeExecutionTime();
        this.initializeUrlShareFeature();
        this.initializeCollapseFeature();
        this.initializeProductModal();
    }
    
    /**
     * 要素の初期化
     */
    initializeElements() {
        const elementIds = [
            'shareUrlBtn', 'urlShareSection', 'shareUrl', 'copyShareUrlBtn',
            'slipDetails', 'productModal', 'generateAiDataBtn'
        ];
        
        for (const id of elementIds) {
            this.elements[id] = document.getElementById(id);
        }
        
        // 必須要素のチェック（一部は存在しない場合もある）
        const requiredElements = ['shareUrlBtn', 'urlShareSection', 'generateAiDataBtn'];
        for (const id of requiredElements) {
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
        // URL共有ボタン
        if (this.elements.shareUrlBtn) {
            this.elements.shareUrlBtn.addEventListener('click', () => {
                this.toggleUrlShareSection();
            });
        }
        
        // URLコピーボタン
        if (this.elements.copyShareUrlBtn) {
            this.elements.copyShareUrlBtn.addEventListener('click', () => {
                this.copyShareUrl();
            });
        }

        // AI分析データ生成ボタン - この部分を追加
        if (this.elements.generateAiDataBtn) {
            this.elements.generateAiDataBtn.addEventListener('click', () => {
                this.generateAndShowAiData();
            });
        }

        // 対象商品モーダル表示ボタン（動的に生成される可能性があるため、イベント委譲を使用）
        document.addEventListener('click', (e) => {
            if (e.target.closest('.clickable[onclick*="showProductModal"]')) {
                e.preventDefault();
                this.showProductModal();
            }
        });
        
        // モーダル外クリックで閉じる
        if (this.elements.productModal) {
            this.elements.productModal.addEventListener('click', (e) => {
                if (e.target === this.elements.productModal) {
                    this.hideProductModal();
                }
            });
        }
        
        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideProductModal();
            }
        });
        
        // モーダル閉じるボタン
        const modalCloseBtn = document.querySelector('#productModal .modal-close');
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', () => {
                this.hideProductModal();
            });
        }
    }
    
    /**
     * 実行時間の初期化
     */
    initializeExecutionTime() {
        // PHPから渡された実行時間を取得（グローバル変数または data属性から）
        const executionTimeElement = document.querySelector('[data-execution-time]');
        if (executionTimeElement) {
            this.executionTime = parseFloat(executionTimeElement.dataset.executionTime) || 0;
        }
    }
    
    /**
     * URL共有機能の初期化
     */
    initializeUrlShareFeature() {
        if (!this.elements.shareUrl) {
            return;
        }
        
        // 現在のURLを設定
        const currentUrl = window.location.href;
        this.elements.shareUrl.value = currentUrl;
    }
    
    /**
     * URL共有セクションの表示/非表示切り替え
     */
    toggleUrlShareSection() {
        if (!this.elements.urlShareSection) {
            return;
        }
        
        if (this.elements.urlShareSection.style.display === 'none') {
            this.elements.urlShareSection.style.display = 'block';
            this.elements.shareUrlBtn.innerHTML = '<i class="bi bi-eye-slash me-2"></i>URL非表示';
        } else {
            this.elements.urlShareSection.style.display = 'none';
            this.elements.shareUrlBtn.innerHTML = '<i class="bi bi-share me-2"></i>URL共有';
        }
    }
    
    /**
     * URLをクリップボードにコピー
     */
    copyShareUrl() {
        if (!this.elements.shareUrl) {
            return;
        }
        
        this.elements.shareUrl.select();
        this.elements.shareUrl.setSelectionRange(0, 99999); // モバイル対応
        
        try {
            navigator.clipboard.writeText(this.elements.shareUrl.value).then(() => {
                this.showCopySuccess();
            }).catch(() => {
                // フォールバック: execCommand使用
                document.execCommand('copy');
                this.showCopySuccess();
            });
        } catch (err) {
            // フォールバック: execCommand使用
            document.execCommand('copy');
            this.showCopySuccess();
        }
    }
    
    /**
     * コピー成功の表示
     */
    showCopySuccess() {
        if (!this.elements.copyShareUrlBtn) {
            return;
        }
        
        const originalText = this.elements.copyShareUrlBtn.innerHTML;
        this.elements.copyShareUrlBtn.innerHTML = '<i class="bi bi-check me-2"></i>コピー完了';
        this.elements.copyShareUrlBtn.classList.remove('btn-outline-primary');
        this.elements.copyShareUrlBtn.classList.add('btn-success');
        
        setTimeout(() => {
            this.elements.copyShareUrlBtn.innerHTML = originalText;
            this.elements.copyShareUrlBtn.classList.remove('btn-success');
            this.elements.copyShareUrlBtn.classList.add('btn-outline-primary');
        }, 2000);
    }
    
    /**
     * 折りたたみ機能の初期化
     */
    initializeCollapseFeature() {
        const collapseElement = this.elements.slipDetails;
        const chevronIcon = document.querySelector('[data-bs-target="#slipDetails"] .bi-chevron-down');
        
        if (collapseElement && chevronIcon) {
            collapseElement.addEventListener('show.bs.collapse', () => {
                chevronIcon.style.transform = 'rotate(180deg)';
            });
            
            collapseElement.addEventListener('hide.bs.collapse', () => {
                chevronIcon.style.transform = 'rotate(0deg)';
            });
        }
    }
    
    /**
     * 対象商品モーダルの初期化
     */
    initializeProductModal() {
        // 特に初期化処理は不要（静的コンテンツ）
    }
    
    /**
     * 対象商品モーダル表示
     */
    showProductModal() {
        if (!this.elements.productModal) {
            console.error('Product modal element not found');
            return;
        }
        
        this.elements.productModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * 対象商品モーダル非表示
     */
    hideProductModal() {
        if (!this.elements.productModal) {
            return;
        }
        
        this.elements.productModal.classList.remove('show');
        document.body.style.overflow = '';
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

    /**
     * AI分析用データ生成・表示
     */
    generateAndShowAiData() {
        const baseUrl = document.body.dataset.siteUrl || '';
        const inputJanCodes = this.getInputJanCodes();
        
        fetch(`${baseUrl}/sales-analysis/generate-ai-data`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                jan_codes: inputJanCodes
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showAiDataModal(data.ai_text);
            } else {
                alert('データ生成エラー: ' + (data.error || '不明なエラー'));
            }
        })
        .catch(error => {
            console.error('AI分析データ生成エラー:', error);
            alert('AI分析データの生成中にエラーが発生しました');
        });
    }
    
    /**
     * 入力JANコードを取得
     */
    getInputJanCodes() {
        // URLパラメータからJANコードを取得
        const urlParams = new URLSearchParams(window.location.search);
        const janCodes = urlParams.get('jan_codes');
        
        if (janCodes) {
            return janCodes.split(',').filter(code => code.trim());
        }
        
        return [];
    }

    /**
     * AI分析データモーダル表示
     */
    showAiDataModal(aiText) {
        // 既存のモーダルがあれば削除
        const existingModal = document.getElementById('aiDataModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = `
            <div class="modal fade" id="aiDataModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-robot me-2"></i>AI分析用データ（アパレル・雑貨特化）
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>使用方法:</strong> 以下のデータをChatGPT、Claude等のAIにコピペして分析を依頼してください。
                                アパレル・雑貨業界に特化した分析項目が含まれています。
                            </div>
                            <div class="mb-3">
                                <label for="aiDataText" class="form-label">
                                    <strong>生成データ</strong> 
                                    <small class="text-muted">(${aiText.length.toLocaleString()}文字)</small>
                                </label>
                                <textarea class="form-control" id="aiDataText" rows="25" readonly 
                                        style="font-family: 'Courier New', monospace; font-size: 12px;">${this.escapeHtml(aiText)}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" id="aiCopyBtn">
                                <i class="bi bi-clipboard me-2"></i>クリップボードにコピー
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="aiSelectBtn">
                                <i class="bi bi-check-all me-2"></i>全選択
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>閉じる
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modal);
        const bootstrapModal = new bootstrap.Modal(document.getElementById('aiDataModal'));
        bootstrapModal.show();
        
        // モーダル表示後にイベントリスナーを設定
        const copyBtn = document.getElementById('aiCopyBtn');
        const selectBtn = document.getElementById('aiSelectBtn');
        
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                this.copyAiDataToClipboard();
            });
        }
        
        if (selectBtn) {
            selectBtn.addEventListener('click', () => {
                this.selectAllAiData();
            });
        }
        
        // モーダルが閉じられたらDOM要素を削除
        document.getElementById('aiDataModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    /**
     * AI分析データのクリップボードコピー（既存のcopyShareUrl()と同じ手法）
     */
    copyAiDataToClipboard() {
        const textarea = document.getElementById('aiDataText');
        if (!textarea) {
            alert('コピー対象のテキストが見つかりません');
            return;
        }
        
        textarea.select();
        textarea.setSelectionRange(0, 99999); // モバイル対応
        
        try {
            navigator.clipboard.writeText(textarea.value).then(() => {
                this.showAiCopySuccess();
            }).catch(() => {
                // フォールバック: execCommand使用
                document.execCommand('copy');
                this.showAiCopySuccess();
            });
        } catch (err) {
            // フォールバック: execCommand使用
            document.execCommand('copy');
            this.showAiCopySuccess();
        }
    }

    /**
     * AI分析データコピー成功の表示（既存のshowCopySuccess()と同じ手法）
     */
    showAiCopySuccess() {
        const copyBtn = document.getElementById('aiCopyBtn');
        if (!copyBtn) return;
        
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="bi bi-check me-2"></i>コピー完了';
        copyBtn.classList.remove('btn-success');
        copyBtn.classList.add('btn-info');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('btn-info');
            copyBtn.classList.add('btn-success');
        }, 2000);
    }

    /**
     * AI分析データの全選択
     */
    selectAllAiData() {
        const textarea = document.getElementById('aiDataText');
        if (!textarea) {
            alert('選択対象のテキストが見つかりません');
            return;
        }
        
        textarea.focus();
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);
    }

}

// グローバル関数（PHPテンプレートから呼び出される）
window.showProductModal = function() {
    if (window.singleProductResult) {
        window.singleProductResult.showProductModal();
    }
};

window.hideProductModal = function() {
    if (window.singleProductResult) {
        window.singleProductResult.hideProductModal();
    }
};

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', function() {
    window.singleProductResult = new SingleProductResult();
});