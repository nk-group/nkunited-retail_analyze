<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ImportTaskModel; 
use Config\Services;
use CodeIgniter\I18n\Time; 

class TaskViewController extends BaseController
{
    protected $helpers = ['form', 'url', 'text'];

    /**
     * 取り込みタスク一覧表示
     */
    public function index()
    {
        $request = Services::request();

        $dateRange = $request->getGet('date_range'); 
        $startDate = null;
        $endDate = null;
        if (!empty($dateRange)) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) === 2) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dates[0]))) {
                    $startDate = trim($dates[0]);
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dates[1]))) {
                    $endDate = trim($dates[1]);
                }
            } elseif (count($dates) === 1 && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dates[0]))) {
                $startDate = trim($dates[0]);
                $endDate = trim($dates[0]);
            }
        }

        $sortColumn = $request->getGet('sort') ?? 'uploaded_at'; 
        $sortOrder  = $request->getGet('order') ?? 'DESC';      
        
        $validSortColumns = [
            'id', 'status', 'target_data_name', 'original_file_name', 
            'uploaded_at', 'uploaded_by', 'processing_started_at', 
            'processing_finished_at', 'result_message'
        ];
        if (!in_array($sortColumn, $validSortColumns)) {
            $sortColumn = 'uploaded_at'; 
        }
        if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
            $sortOrder = 'DESC'; 
        }

        $perPage = 50; 
        
        $taskModel = new ImportTaskModel(); 

        if ($startDate || $endDate) { 
            $taskModel->applyDateFilter($startDate, $endDate);
        }
        
        $tasks = $taskModel
            ->orderBy($sortColumn, $sortOrder)
            ->paginate($perPage);

        $formattedTasks = [];
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $task->uploaded_at = $task->uploaded_at ? Time::parse($task->uploaded_at)->toDateTimeString() : null;
                $task->processing_started_at = $task->processing_started_at ? Time::parse($task->processing_started_at)->toDateTimeString() : null;
                $task->processing_finished_at = $task->processing_finished_at ? Time::parse($task->processing_finished_at)->toDateTimeString() : null;
                $formattedTasks[] = $task;
            }
        }
            
        $data['tasks'] = $formattedTasks; 
        $data['pager'] = $taskModel->pager;
        $data['sortColumn'] = $sortColumn;
        $data['sortOrder'] = $sortOrder;
        $data['dateRange'] = $dateRange; 

        $data['pageTitle'] = '取込タスク一覧';

        return view('tasks/index', $data); 
    }
}