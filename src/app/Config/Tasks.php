<?php

namespace Config;

use CodeIgniter\Tasks\Scheduler;

class Tasks extends \CodeIgniter\Tasks\Config\Tasks
{
    /**
     * Register any tasks within this method.
     *
     * @param Scheduler $schedule
     */
    public function init(Scheduler $schedule)
    {
        // 例: 毎分 tasks:process_imports コマンドを実行
        // $schedule->command('tasks:process_imports')->everyMinute();

        // 例: 5分ごとに実行
        $schedule->command('tasks:process_imports')->cron('*/1 * * * *');

        // ログ出力先を指定する場合 (任意)
        // $schedule->command('tasks:process_imports')->everyMinute()->named('ProcessImports')->appendOutputTo(WRITEPATH . 'logs/tasks.log');
    }
}