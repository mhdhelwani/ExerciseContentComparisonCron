<#1>
<?php
if (!$ilDB->tableExists('cron_ecc_ass_list')) {
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'exercise_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'ass_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'threshold' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        ),
        'k_gram' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        )
    );
    $ilDB->createTable("cron_ecc_ass_list", $fields);
    $ilDB->addUniqueConstraint("cron_ecc_ass_list", array("id"));
    $ilDB->createSequence("cron_ecc_ass_list");
}
?>