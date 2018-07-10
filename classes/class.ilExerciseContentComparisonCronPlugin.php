<?php
include_once("./Services/Cron/classes/class.ilCronHookPlugin.php");
require_once 'class.ilExerciseContentComparisonCron.php';

/**
 * Class ilExerciseContentComparisonCronPlugin
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 */
class ilExerciseContentComparisonCronPlugin extends ilCronHookPlugin
{

    /**
     * @var ilExerciseContentComparisonCronPlugin
     */
    protected static $instance;


    /**
     * @return ilExerciseContentComparisonCronPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    const PLUGIN_NAME = 'ExerciseContentComparisonCron';
    /**
     * @var  ilExerciseContentComparisonCron
     */
    protected static $cron_job_instance;


    /**
     * @return ilExerciseContentComparisonCron[]
     */
    public function getCronJobInstances()
    {
        $this->loadCronJobInstance();

        return array(self::$cron_job_instance);
    }


    /**
     * @param $a_job_id
     *
     * @return ilExerciseContentComparisonCron
     */
    public function getCronJobInstance($a_job_id)
    {
        if ($a_job_id == ilExerciseContentComparisonCron::ID) {
            $this->loadCronJobInstance();

            return self::$cron_job_instance;
        }

        return false;
    }


    /**
     * @return string
     */
    public function getPluginName()
    {
        return self::PLUGIN_NAME;
    }


    protected function loadCronJobInstance()
    {
        if (!isset(self::$cron_job_instance)) {
            self::$cron_job_instance = new ilExerciseContentComparisonCron();
        }
    }
}