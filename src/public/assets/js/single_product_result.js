/**
 * 単品分析結果画面専用JavaScript - 完全機能実装版
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
        console.log('クイック分析結果画面が読み込まれました');
        
        if (!this.initializeElements()) {
            return;
        }
        
        this.bindEvents();
        this.initializeExecutionTime();
        this.initializeUrlShareFeature();
        this.initializeCollapseFeature();
        this.initializeProductModal();
        
        console.log('単品分析結果画面初期化完了');
    }
    
    /**
     * 要素の初期化
     */
    initializeElements() {
        const elementIds = [
            'shareUrlBtn', 'urlShareSection', 'shareUrl', 'copyShareUrlBtn',
            'slipDetails', 'productModal'
        ];
        
        for (const id of elementIds) {
            this.elements[id] = document.getElementById(id);
        }
        
        // 必須要素のチェック（一部は存在しない場合もある）
        const requiredElements = ['shareUrlBtn', 'urlShareSection'];
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
        
        console.log('実行時間:', this.executionTime, '秒');
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
        
        console.log('Product modal opened');
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
        
        console.log('Product modal closed');
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