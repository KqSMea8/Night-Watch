<?php
/**
 * author:       joddiyzhang <joddiyzhang@gmail.com>
 * createTime:   11/03/2018 9:23 PM
 * fileName :    ApiController.php
 */

namespace app\controllers;

use app\components\Common;
use app\components\RestController;
use app\components\XMMitalk;
use app\components\XMPassport;
use app\models\AdminLog;
use app\models\AdminToken;
use app\models\AdminUser;
use app\components\XMCas;
use app\models\Company;
use app\models\GpuList;
use app\models\GpuLog;
use app\models\GpuPs;
use app\models\Graph;
use app\models\LbArchitecture;
use app\models\LbBem;
use app\models\News;
use app\models\OSISoft;
use Yii;
use yii\base\ErrorException;
use yii\db\Exception;


/**
 * Class ApiController
 * @package app\controllers
 */
class ApiController extends RestController
{

    /**
     * @return array
     */
    public function actionWatchGpu()
    {

        try {
            $params = Yii::$app->request->get();

            $log = json_decode($params['log'], true);
            $hostname = $log['hostname'];
            $current_time = date('Y-m-d H:i:s', strtotime($log['query_time']));
            $transaction = \Yii::$app->getDb()->beginTransaction();
            try {
                foreach ($log['gpus'] as $item) {
                    $gpu_order = $item['index'];
                    $gpu = GpuList::findOne(["cluster" => $hostname, "gpu_order" => $gpu_order]);
                    $new_gpu_log = new GpuLog();
                    $new_gpu_log->gpu_id = $gpu->gpu_id;
                    $new_gpu_log->temperature = $item['temperature.gpu'];
                    $new_gpu_log->utilization = $item['utilization.gpu'];
                    $new_gpu_log->power_draw = $item['power.draw'];
                    $new_gpu_log->power_max = $item['enforced.power.limit'];
                    $new_gpu_log->memory_used = $item['memory.used'];
                    $new_gpu_log->memory_total = $item['memory.total'];
                    $new_gpu_log->add_time = $current_time;
                    $new_gpu_log->insert();
                    foreach ($item['processes'] as $ps) {
                        $new_ps = new GpuPs();
                        $new_ps->log_id = $new_gpu_log->log_id;
                        $new_ps->username = $ps['username'];
                        $new_ps->command = $ps['command'];
                        $new_ps->gpu_memory_usage = $ps['gpu_memory_usage'];
                        $new_ps->pid = $ps['pid'];
                        $new_ps->add_time = $current_time;
                        $new_ps->insert();
                    }
                }
            } catch (\Throwable $e) {
                $transaction->rollBack();
                return $this->formatRestResult(self::FAILURE, $e->getMessage());
            }
            $transaction->commit();

            return $this->formatRestResult(self::SUCCESS, []);
        } catch (\Exception $e) {
            return $this->formatRestResult(self::FAILURE, $e->getMessage());
        }
    }
}