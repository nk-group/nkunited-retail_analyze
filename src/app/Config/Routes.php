<?php
use CodeIgniter\Router\RouteCollection;


//$routes->setAutoRoute(true);

/**
 * @var RouteCollection $routes
 */

// アプリケーションのルートアクセスの処理:
// アプリケーションのルート、ログインしていなければログインページへ
$routes->get('/', function() {
    if (session()->get('isLoggedIn')) {
        return redirect()->to(site_url('menu'));
    }
    return redirect()->to(site_url('login'));
});

// ログイン関連のルート
$routes->get('/login', 'Login::index');
$routes->post('/login/attempt', 'Login::attempt'); // POSTリクエストを Login::attempt メソッドへ
$routes->get('/logout', 'Login::logout');

// メニューページ (AuthFilter で保護)
$routes->get('/menu', 'Menu::index', ['filter' => 'auth']); // 'auth' フィルターを適用

$routes->get('/', 'Home::index');



// === マスタデータ取込関連 ===
// マスタ取込画面表示 (認証フィルターで保護)
// 複数のマスタ取込フォームを1つのページに表示する想定
$routes->group('masters', ['filter' => 'auth'], static function ($routes) {
    $routes->get('import', 'MasterImportController::index', ['as' => 'master_import_form']);

    // 各マスタのファイルアップロード処理用エンドポイント
    // メソッド名は process[TargetName]Import に統一
    
    // 商品マスタ (Product Master)
    $routes->post('import/product', 'MasterImportController::processProductImport', ['as' => 'product_master_import_process']);
    
    // 部門マスタ (Department Master) - 将来の拡張用プレースホルダー
    // $routes->post('import/department', 'MasterImportController::processDepartmentImport', ['as' => 'department_master_import_process']);
    
    // メーカーマスタ (Manufacturer Master)
    $routes->post('import/manufacturer', 'MasterImportController::processManufacturerImport', ['as' => 'manufacturer_master_import_process']);
    
    // 仕入先マスタ (Supplier Master) - 将来の拡張用プレースホルダー
    // $routes->post('import/supplier', 'MasterImportController::processSupplierImport', ['as' => 'supplier_master_import_process']);
});



// Routes.php に追加する部分
// === 伝票データ取込関連 ===（既存の部分を更新）
$routes->group('slips', ['filter' => 'auth'], static function ($routes) {
    $routes->get('import', 'SlipImportController::uploadForm', ['as' => 'slip_import_form']);
    
    // 仕入伝票 (Purchase Slip)
    $routes->post('import/purchase', 'SlipImportController::processPurchaseSlipImport', ['as' => 'purchase_slip_import_process']);
    
    // 売上伝票 (Sales Slip)
    $routes->post('import/sales', 'SlipImportController::processSalesSlipImport', ['as' => 'sales_slip_import_process']);
    
    // 移動伝票 (Transfer Slip)
    $routes->post('import/transfer', 'SlipImportController::processTransferSlipImport', ['as' => 'transfer_slip_import_process']);
    
    // 調整伝票 (Adjustment Slip)
    $routes->post('import/adjustment', 'SlipImportController::processAdjustmentSlipImport', ['as' => 'adjustment_slip_import_process']);    
});


/**
 * 販売分析関連のルート設定（更新版）
 */
$routes->group('sales-analysis', ['filter' => 'auth'], function($routes) {
    // メイン画面（分析メニュー）
    $routes->get('/', 'SalesAnalysisController::index');
    
    // 単品分析
    $routes->group('single-product', function($routes) {
        // 集計指示画面
        $routes->get('/', 'SalesAnalysisController::singleProduct');
        
        // 集計実行
        $routes->post('execute', 'SalesAnalysisController::executeSingleProduct');
        
        // 結果画面
        $routes->get('result', 'SalesAnalysisController::singleProductResult');
    });
    
    // Ajax API
    $routes->get('search-makers', 'SalesAnalysisController::searchMakers');
    $routes->get('search-products', 'SalesAnalysisController::searchProducts');
    $routes->get('get-target-products', 'SalesAnalysisController::getTargetProducts');
    $routes->get('validate-product-number', 'SalesAnalysisController::validateProductNumber'); // 追加
    
    // 従来のルート（互換性のため残す）
    $routes->post('execute', 'SalesAnalysisController::execute');
    $routes->get('result', 'SalesAnalysisController::result');
});


// === タスク一覧表示関連 (認証フィルターで保護) ===
// マスタや伝票のグループ化パターンに合わせて 'tasks' グループを作成
// $routes->group('tasks', ['filter' => 'auth'], static function ($routes) {
//     $routes->get('list', 'TaskViewController::index', ['as' => 'task_list']);
//     // POST も許可する場合は以下のように変更
//     // $routes->match(['get', 'post'], 'list', 'TaskViewController::index', ['as' => 'task_list']);
// });

// === タスク一覧表示関連 (認証フィルターで保護) ===
// グループ 'admin' を削除し、直接ルートを定義
$routes->get('tasks', 'TaskViewController::index', [
    'filter' => 'auth', 
    'as' => 'task_list'
]);


//$routes->get('/dbtest', 'App\Controllers\DbTest::index');
$routes->get('/dbtest', 'DbTest::index'); 
