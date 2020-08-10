<?php

class PeepSoNotificationsQueue {

    private static $_instance = NULL;

    public $limit = 100;

    public static function get_instance()
    {
        if (NULL === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    private function __construct() {

    }

    public function debug() {

        echo "<style type=text/css>.xdebug-var-dump {padding:10px;margin-bottom:20px;background:#f6f6eb;}</style>";

        global $wpdb;

        var_dump($wpdb->get_results("SELECT NOW() as current_datetime"));

        var_dump($this->get_batches(20));

        echo "<hr><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>";
    }


    public function get_batches($limit=NULL) {

        if(!$limit) {
            $limit = $this->limit;
        }

        global $wpdb;
        $pf = $wpdb->prefix;

        $batches = array();

        // Get all possible notification levels

        $levels_in = array();
        $levels = PeepSoNotificationsIntensity::email_notifications_intensity_levels();

        unset($levels[999999]);
        unset($levels[0]);
        foreach($levels as $level_id=>$level) {
            $levels_in[]=$level_id;
        }


        // Base SQL: users who have the digest enabled and have unread notifications
        $base_sql = "SELECT u.ID as user_id, umeta.meta_value as email_intensity 
        FROM {$pf}users as u, {$pf}usermeta as umeta 
        WHERE (u.ID=umeta.user_id and umeta.meta_key='peepso_email_intensity' 
        AND umeta.meta_value IN (".implode(',',$levels_in)."))
        AND EXISTS (SELECT id FROM {$pf}peepso_notifications WHERE not_user_id=u.ID AND not_read=0) ";

        // Users without a log entry
        $sql = $base_sql . " AND NOT EXISTS (SELECT id FROM {$pf}peepso_notifications_queue_log WHERE user_id=u.ID) LIMIT $limit";
        $batches['no_log_entry'] = $wpdb->get_results($sql, ARRAY_A);


        // Schedule based
        foreach($levels_in as $level) {
            $sql = $base_sql. " AND umeta.meta_value=$level AND EXISTS (SELECT id FROM {$pf}peepso_notifications_queue_log WHERE user_id=u.ID AND sent<DATE_SUB(NOW(), INTERVAL $level MINUTE)) LIMIT $limit";
            $batches['interval_'.$level.'_minutes'] = $wpdb->get_results($sql, ARRAY_A);
        }

        foreach($batches as $interval => $users) {
            if(!count($users)) {
                unset($batches[$interval]);
            }
        }

        return $batches;
    }

    public function cron($limit = NULL) {
        if(!$limit) {
            $limit = $this->limit;
        }

    }


}