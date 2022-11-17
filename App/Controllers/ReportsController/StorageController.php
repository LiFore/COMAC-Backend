<?php

namespace App\Controllers\ReportsController;

use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class StorageController
 * @package App\Controllers\ReportsController
 *
 * @path /reports/storage
 */
class StorageController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了通过批次号储存报告
     *
     * @route POST /store/batch/{batchId}
     *
     * @return array
     */
    public function storeReports($batchId,$content)
    {

        $this->db->insertInto("report_storage")
            ->values([
                "storageContent"=>$content,
                "storageTitle"=>$batchId,
                "storeTime" => TimeUtils::getNormalTime()])
            ->exec();
        return (new ResponseUtils())->getResponse('success');
    }

    /**
     * 此方法实现了通过批次号储存报告
     *
     * @route GET /list/batch/{batchId}
     *
     * @return array
     */
    public function listReports($batchId)
    {
        $storeageList = $this->db->select(["storeTime","storageId"])
            ->from("report_storage")
            ->where("storageTitle = '" . $batchId . "'")
            ->get();
        return (new ResponseUtils())->getResponse($storeageList);
    }

    /**
     * 此方法实现了通过批次号储存报告
     *
     * @route GET /get
     *
     * @return array
     */
    public function getReports($storageId)
    {
        $storeageList = $this->db->select("*")
            ->from("report_storage")
            ->where("storageId = '" . $storageId . "'")
            ->getFirst();
        return (new ResponseUtils())->getResponse($storeageList);
    }
}