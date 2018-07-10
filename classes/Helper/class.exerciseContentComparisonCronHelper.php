<?php

include_once("./Modules/Exercise/classes/class.ilExAssignment.php");

class exerciseContentComparisonCronHelper
{
    /**
     * @param ilCopyRightCronLog $a_log
     */
    public static function _comparison($a_log)
    {
        global $ilDB;
        include_once("./Modules/Exercise/classes/class.ilExAssignmentTeam.php");
        $sql = "SELECT * from cron_ecc_ass_list";

        $res = $ilDB->query($sql);
        try {
            while ($row = $ilDB->fetchAssoc($res)) {
                $assignment = new ilExAssignment($row["ass_id"]);
                $teams = ilExAssignmentTeam::getInstancesFromMap($assignment);
                $members = self::_getMembersList($assignment);
                $files = self::_getAllSubmittedFilesInAssignment($assignment, $members, $teams);

                self::_deleteExerciseContentComparison($assignment->getExerciseId(), $row["ass_id"]);
                self::_deleteExerciseContentComparisonError($assignment->getExerciseId(), $row["ass_id"]);
                $fileFingerPrintArray = [];
                $fileIndexArray = [];

                foreach ($files as $key => $file) {
                    $fileInfo = new SplFileInfo($file["filename"]);
                    if (!in_array($fileInfo->getExtension(), ["docx", "txt", "pdf", "doc"])) {
                        self::_insertExerciseContentComparisonError(
                            $assignment->getExerciseId(),
                            $row["ass_id"],
                            $file["returned_id"],
                            "unsupported_file_type"
                        );
                        unset($files[$key]);
                    } else {
                        if (!array_key_exists($file["filename"], $fileFingerPrintArray)) {
                            $fileContent = self::_getFileContent($file["filename"]);
                            list($fingerprintIndex, $fingerprintValue) = self::_getFingerprint(
                                $row["k_gram"],
                                $row["threshold"],
                                $fileContent
                            );

                            $fileFingerPrintArray[$file["filename"]] = $fingerprintValue;
                            $fileIndexArray[$file["filename"]] = $fingerprintIndex;

                            if ((sizeof($fileFingerPrintArray[$file["filename"]]) == 1 &&
                                    $fileFingerPrintArray[$file["filename"]][0] == -1) ||
                                !$fileContent
                            ) {
                                self::_insertExerciseContentComparisonError(
                                    $assignment->getExerciseId(),
                                    $row["ass_id"],
                                    $file["returned_id"],
                                    "unreadable_file"
                                );
                                unset($files[$key]);
                            }
                        }
                    }
                }

                foreach ($files as $file) {
                    foreach ($files as $compere_with_file) {
                        if ($file["user_id"] !== $compere_with_file["user_id"]) {
                            $numberOfMatched = self::_getNumberOfMatched(
                                $fileFingerPrintArray[$file["filename"]],
                                $fileIndexArray[$file["filename"]],
                                $fileFingerPrintArray[$compere_with_file["filename"]]
                            );

                            $matchPercent = ($numberOfMatched / sizeof($fileFingerPrintArray[$file["filename"]])) * 100;

                            self::_insertExerciseContentComparison(
                                $assignment->getExerciseId(),
                                $row["ass_id"],
                                $file["returned_id"],
                                $compere_with_file["returned_id"],
                                $row["k_gram"],
                                $row["threshold"],
                                $matchPercent
                            );
                        }
                    }
                }
                self::_deleteFromCornJob($assignment->getExerciseId(), $row["ass_id"]);
            }
        } catch (Exception $e) {
            $a_log->warn($e->getMessage());
        }
    }

    /**
     * Insert exercise content comparison data for compared files
     *
     * @param int $a_exercise_id
     * @param int $a_ass_id
     * @param int $a_returned_id
     * @param int $a_compared_with_returned_id
     * @param int $a_threshold
     * @param int $a_k_gram
     * @param float $match_percent
     */
    public static function _insertExerciseContentComparison($a_exercise_id, $a_ass_id, $a_returned_id, $a_compared_with_returned_id, $a_threshold, $a_k_gram, $match_percent)
    {
        global $ilDB;

        $values = [
            "exercise_id" => ["integer", $a_exercise_id],
            "ass_id" => ["integer", $a_ass_id],
            "returned_id" => ["integer", $a_returned_id],
            "compared_with_returned_id" => ["integer", $a_compared_with_returned_id],
            "threshold" => ["integer", $a_threshold],
            "k_gram" => ["integer", $a_k_gram],
            "match_percent" => ["float", $match_percent]];
        $ilDB->insert("comparison_data", $values);
    }

    /**
     * Delete exercise content comparison data for exercise and assignment
     *
     * @param int $a_exercise_id
     * @param int $a_ass_id
     */
    public static function _deleteExerciseContentComparison($a_exercise_id, $a_ass_id)
    {
        global $ilDB;

        $query = "DELETE FROM comparison_data WHERE exercise_id = " .
            $ilDB->quote($a_exercise_id, "integer") .
            " and ass_id = " . $ilDB->quote($a_ass_id, "integer");

        $ilDB->manipulate($query);
    }

    /**
     * @param int $a_exercise_id
     * @param int $a_ass_id
     */
    public static function _deleteFromCornJob($a_exercise_id, $a_ass_id)
    {
        global $ilDB;

        $query = "DELETE FROM cron_ecc_ass_list WHERE exercise_id = " .
            $ilDB->quote($a_exercise_id, "integer") .
            " and ass_id = " . $ilDB->quote($a_ass_id, "integer");

        $ilDB->manipulate($query);
    }

    /**
     * Insert exercise content comparison error
     *
     * @param int $a_exercise_id
     * @param int $a_ass_id
     * @param int $a_returned_id
     * @param string $a_error_text
     */
    public static function _insertExerciseContentComparisonError($a_exercise_id, $a_ass_id, $a_returned_id, $a_error_text)
    {
        global $ilDB;

        $values = [
            "exercise_id" => ["integer", $a_exercise_id],
            "ass_id" => ["integer", $a_ass_id],
            "returned_id" => ["integer", $a_returned_id],
            "error_text" => ["text", $a_error_text]];
        $ilDB->insert("comparison_error", $values);
    }

    /**
     * Delete exercise content comparison error for exercise and assignment
     *
     * @param int $a_exercise_id
     * @param int $a_ass_id
     */
    public static function _deleteExerciseContentComparisonError($a_exercise_id, $a_ass_id)
    {
        global $ilDB;

        $query = "DELETE FROM comparison_error WHERE exercise_id = " .
            $ilDB->quote($a_exercise_id, "integer") .
            " and ass_id = " . $ilDB->quote($a_ass_id, "integer");

        $ilDB->manipulate($query);
    }

    /**
     * Get members list in assignment
     *
     * @param ilExAssignment $a_assignment
     * @return array
     */
    public static function _getMembersList($a_assignment)
    {
        $members = $a_assignment->getMemberListData();

        if ($a_assignment->hasTeam()) {
            $team_map = ilExAssignmentTeam::getAssignmentTeamMap($a_assignment->getId());
            $tmp = array();

            foreach ($members as $item) {
                $team_id = $team_map[$item["usr_id"]];

                if (!$team_id) {
                    $team_id = "nty" . $item["usr_id"];
                }

                if (!isset($tmp[$team_id])) {
                    $tmp[$team_id] = $item;
                }

                $tmp[$team_id]["team"][$item["usr_id"]] = $item["name"];
                $tmp[$team_id]["team_id"] = $team_id;
            }

            $members = $tmp;
        }

        return $members;
    }

    /**
     * Get exercise content comparison result for assignment
     *
     * @param ilExAssignment $a_assignment
     * @return array
     */
    public static function _getExerciseContentComparisonResult($a_assignment)
    {
        global $ilDB;

        $sql = "SELECT * FROM comparison_data where ass_id = " . $ilDB->quote($a_assignment->getId(), "integer");

        $res = $ilDB->query($sql);
        $data = array();

        while ($row = $ilDB->fetchAssoc($res)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get exercise content comparison error for assignment
     *
     * @param ilExAssignment $a_assignment
     * @return array
     */
    public static function _getExerciseContentComparisonError($a_assignment)
    {
        global $ilDB;

        $sql = "SELECT * FROM comparison_error where ass_id = " . $ilDB->quote($a_assignment->getId(), "integer");

        $res = $ilDB->query($sql);
        $data = array();

        while ($row = $ilDB->fetchAssoc($res)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get all submitted files in assignment
     *
     * @param ilExAssignment $a_assignment
     * @param array $a_members
     * @param array $teams
     * @return array
     */
    public static function _getAllSubmittedFilesInAssignment($a_assignment, $a_members, $teams)
    {
        $all_files = array();
        foreach ($a_members as $member) {
            $submission = new ilExSubmission($a_assignment, $member["usr_id"], $teams[$member["team_id"]]);
            $files = $submission->getFiles();
            for ($i = 0; $i < sizeof($files); $i++) {
                $files[$i]["member_name"] = $member["name"];
                $files[$i]["team"] = $member["team"];
            }
            $all_files = array_merge($files, $all_files);
        }

        return $all_files;
    }

    public static function _getFileContent($a_file)
    {
        include_once("./Customizing/Global/plugins/Services/Cron/CronHook/ExerciseContentComparisonCron/classes/Helper/DocxConversion.php");
        include_once("./Services/PDFGeneration/classes/tcpdf/tcpdf_parser.php");

        $fileInfo = new SplFileInfo($a_file);
        $extension = ($fileInfo->getExtension());
        if ($extension == 'pdf') {
            spl_autoload_register(function ($class_name) {
                include "./Customizing/Global/plugins/Services/Cron/CronHook/ExerciseContentComparisonCron/classes/Helper/PdfParser/src/" . $class_name . '.php';
            });
            $pdfParser = new \Smalot\PdfParser\Parser();
            $parsedFile = $pdfParser->parseFile($a_file);
            $fileContent = preg_replace('/[^A-Za-z0-9ÄÜÖäüöß]/', '', $parsedFile->getText());
        } else if ($extension == 'docx' || $extension == 'doc') {
            $docxConversion = new DocxConversion($a_file);
            $fileContent = preg_replace('/[^A-Za-z0-9ÄÜÖäüöß]/', '', $docxConversion->convertToText());
        } else if ($extension == 'txt') {
            $handle = fopen($a_file, 'r');
            $fileContent = preg_replace('/[^A-Za-z0-9ÄÜÖäüöß]/', '', file_get_contents($a_file));
        }

        return $fileContent;
    }


    /**
     * @param $kGram
     * @param $threshold
     * @param $data
     * @return array
     */
    public static function _getFingerprint($kGram, $threshold, $data)
    {
        $kGramsHash = array();
        $prevHash = -1;
        $base = 73;
        $charrem = "";
        $counter = 0;
        $window = $threshold - $kGram + 1;
        $fingerprintValue = array();
        $fingerprintIndex = array();


        for ($i = 0; $i < strlen($data) - $kGram; $i++) {
            $temp = "";

            for ($j = $i; $j < $i + $kGram; $j++) {
                $temp = $temp . substr($data, $j, 1);
            }

            if ($prevHash == -1) {
                $hash = 0;
                $pci = 0;
                for ($ci = 0; $ci < $kGram; $ci++) {
                    $hash = $hash + ord(substr($temp, $ci, 1)) * pow($base, $pci);
                    $pci++;
                }
                $prevHash = $hash;
                $kGramsHash[$counter] = $prevHash;
                $charrem = substr($temp, 0, 1);
            } else {
                $prevHash = ($prevHash - ord($charrem)) / 101 + ord(substr($temp, $kGram - 1, 1)) * pow($base, $kGram - 1);
                $kGramsHash[$counter] = $prevHash;
                $charrem = substr($temp, 0, 1);
            }
            $counter++;
        }

        for ($i = 0; $i < sizeof($kGramsHash) - $window + 1; $i++) {
            $currentMinValue = -1;
            $currentMinIndex = 0;
            for ($j = $i; $j < $i + $window; $j++) {
                if ($currentMinValue === -1 || $kGramsHash[$j] <= $currentMinValue) {
                    $currentMinIndex = $j;
                    $currentMinValue = $kGramsHash[$j];
                }
            }
            if (!in_array($currentMinIndex, $fingerprintIndex)) {

                $fingerprintIndex[] = $currentMinIndex;
                $fingerprintValue[] = $currentMinValue;
            }
        }
//        var_dump($fingerprintValue);die();
        return array($fingerprintIndex, $fingerprintValue);
    }

    public static function _getNumberOfMatched($fingerprintValue1, $fingerprintIndex1, $fingerprintValue2)
    {
        $matched = array();
        for ($i = 0; $i < sizeof($fingerprintValue1); $i++) {
            for ($j = 0; $j < sizeof($fingerprintValue2); $j++) {
                if ($fingerprintValue1[$i] === $fingerprintValue2[$j]) {
                    if (!in_array($fingerprintIndex1[$i], $matched)) {
                        $matched[] = $fingerprintIndex1[$i];
                    }
                }
            }
        }
        return sizeof($matched);
    }
}