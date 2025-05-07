<?php

namespace app\commands;

use yii\console\ExitCode;
use yii\console\controllers\MigrateController as BaseMigrateController;

class MigrateController extends BaseMigrateController
{
    public function init()
    {
        parent::init();
        
        // Get base migrations directory
        $basePath = \Yii::getAlias('@app/migrations');
        
        // Find all subdirectories
        $paths = ['@app/migrations'];
        if (is_dir($basePath)) {
            $iterator = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
            
            foreach ($iterator as $path => $dir) {
                if ($dir->isDir()) {
                    // Skip backup folders
                    if (strpos($path, 'backup') !== false) {
                        continue;
                    }
                    $relativePath = str_replace('\\', '/', substr($path, strlen($basePath)));
                    $paths[] = '@app/migrations' . $relativePath;
                }
            }
        }
        
        $this->migrationPath = $paths;
    }

    public function beforeAction($action)
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));

        if ($params['migrateFresh'] === false && $action->id === 'fresh') {
            echo "Skipping the migrate/fresh command in non-dev environment.\n";
            return ExitCode::OK;
        }

        return parent::beforeAction($action);
    }

    public function actionFresh()
    {
        if ($this->confirm('Are you sure you want to drop all tables and re-run all migrations? This will erase all data.')) {
            $this->actionDown('all');
            return $this->actionUp();
        }
        return ExitCode::OK;
    }
}