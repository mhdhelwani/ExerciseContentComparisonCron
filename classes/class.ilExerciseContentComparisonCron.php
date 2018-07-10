<?php

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once('class.ilExerciseContentComparisonCronPlugin.php');

/**
 * Class ilExerciseContentComparisonCron
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 */
class ilExerciseContentComparisonCron extends ilCronJob
{
    /**
     * @var ilExerciseContentComparisonCronLog
     */
    protected $log;

    const ID = 'ess_cron';
    /**
     * @var  ilExerciseContentComparisonCronPlugin
     */
    protected $pl;
    /**
     * @var  ilDB
     */
    protected $db;
    /**
     * @var  ilLog
     */
    protected $ilLog;


    public function __construct()
    {
        global $ilDB, $ilLog;
        $this->db = $ilDB;
        $this->pl = ilExerciseContentComparisonCronPlugin::getInstance();
        $this->log = $ilLog;
    }


    /**
     * @return string
     */
    public function getId()
    {
        return self::ID;
    }


    /**
     * @return bool
     */
    public function hasAutoActivation()
    {
        return false;
    }


    /**
     * @return bool
     */
    public function hasFlexibleSchedule()
    {
        return true;
    }


    /**
     * @return int
     */
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_DAILY;
    }


    /**
     * @return array|int
     */
    public function getDefaultScheduleValue()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCustomSettings()
    {
        return false;
    }

    /**
     * @return ilCronJobResult
     */
    public function run()
    {
        require_once "log/class.ilExerciseContentComparisonCronLog.php";
        require_once "Helper/class.exerciseContentComparisonCronHelper.php";

        $this->log = ilExerciseContentComparisonCronLog::getInstance();
        $this->log->info('Starting ExerciseContentComparison Cronjob...');
        $result = new ilCronJobResult();
        $result->setMessage('Finished ExerciseContentComparisonCron job task successfully');
        $result->setStatus(ilCronJobResult::STATUS_OK);

        exerciseContentComparisonCronHelper::_comparison($this->log);

        $this->log->info('...ExerciseContentComparisonCron job finished.');

        return $result;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return ilExerciseContentComparisonCronPlugin::getInstance()->txt('ecc_cron_title');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return ilExerciseContentComparisonCronPlugin::getInstance()->txt('ecc_cron_desc');
    }
}
