<?php

declare(strict_types=1);


/**
 * NotificationsManager
 */

class NotificationsManager
{
    // Option types
    public const OPT_DISABLED = 0;
    public const OPT_POPUP = 1;
    public const OPT_TRADITIONAL = 2;

    // Importances
    public const IMPORTANT = 'information';
    public const CRITICAL = 'error';
    public const WARNING = 'warning';
    public const INFO = 'confirmation';

    public static $Importances = array(
        'important' => self::IMPORTANT,
        'critical' => self::CRITICAL,
        'warning' => self::WARNING,
        'info' => self::INFO
    );

    // Types. These names must correspond to column names in users_notifications_settings
    public const NEWS = 'News';
    public const BLOG = 'Blog';
    public const STAFFPM = 'StaffPM';
    public const INBOX = 'Inbox';
    public const QUOTES = 'Quotes';
    public const SUBSCRIPTIONS = 'Subscriptions';
    public const TORRENTS = 'Torrents';
    public const COLLAGES = 'Collages';
    public const SITEALERTS = 'SiteAlerts';
    public const FORUMALERTS = 'ForumAlerts';
    public const REQUESTALERTS = 'RequestAlerts';
    public const COLLAGEALERTS = 'CollageAlerts';
    public const TORRENTALERTS = 'TorrentAlerts';
    public const GLOBALNOTICE = 'Global';

    public static $Types = array(
        'News',
        'Blog',
        'StaffPM',
        'Inbox',
        'Quotes',
        'Subscriptions',
        'Torrents',
        'Collages',
        'SiteAlerts',
        'ForumAlerts',
        'RequestAlerts',
        'CollageAlerts',
        'TorrentAlerts'
    );

    private $UserID;
    private $Notifications;
    private $Settings;
    private $Skipped;


    /**
     * __construct
     */
    public function __construct($UserID, $Skip = [], $Load = true, $AutoSkip = true)
    {
        $this->UserID = $UserID;
        $this->Notifications = [];
        $this->Settings = self::get_settings($UserID);
        $this->Skipped = $Skip;

        if ($AutoSkip) {
            foreach ($this->Settings as $Key => $Value) {
                // Skip disabled and traditional settings
                if ($Value === self::OPT_DISABLED || $this->is_traditional($Key)) {
                    $this->Skipped[$Key] = true;
                }
            }
        }

        if ($Load) {
            $this->load_global_notification();
            if (!isset($this->Skipped[self::NEWS])) {
                $this->load_news();
            }

            if (!isset($this->Skipped[self::BLOG])) {
                $this->load_blog();
            }

            if (!isset($this->Skipped[self::STAFFPM])) {
                $this->load_staff_pms();
            }

            if (!isset($this->Skipped[self::INBOX])) {
                $this->load_inbox();
            }

            if (!isset($this->Skipped[self::TORRENTS])) {
                $this->load_torrent_notifications();
            }

            if (!isset($this->Skipped[self::COLLAGES])) {
                $this->load_collage_subscriptions();
            }

            if (!isset($this->Skipped[self::QUOTES])) {
                $this->load_quote_notifications();
            }

            if (!isset($this->Skipped[self::SUBSCRIPTIONS])) {
                $this->load_subscriptions();
            }

            // $this->load_one_reads(); // The code that sets these notices is commented out.
        }
    }


    /**
     * get_notifications
     */
    public function get_notifications()
    {
        return $this->Notifications;
    }


    /**
     * clear_notifications_array
     */
    public function clear_notifications_array()
    {
        unset($this->Notifications);
        $this->Notifications = [];
    }


    /**
     * create_notification
     */
    private function create_notification($Type, $ID, $Message, $URL, $Importance)
    {
        $this->Notifications[$Type] = array(
            'id' => (int) $ID,
            'message' => $Message,
            'url' => $URL,
            'importance' => $Importance
        );
    }


    /**
     * get_notification_enabled_users
     */
    public static function get_notification_enabled_users($Type, $UserID)
    {
        $app = \Gazelle\App::go();

        $Type = db_string($Type);
        $UserWhere = '';
        if (isset($UserID)) {
            $UserID = (int)$UserID;
            $UserWhere = " AND UserID = '$UserID'";
        }
        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT UserID
        FROM users_notifications_settings
          WHERE $Type != 0
        $UserWhere");
        $IDs = [];
        while (list($ID) = $app->dbOld->next_record()) {
            $IDs[] = $ID;
        }
        $app->dbOld->set_query_id($QueryID);
        return $IDs;
    }


    /**
     * load_one_reads
     */
    public function load_one_reads()
    {
        $app = \Gazelle\App::go();

        $OneReads = $app->cache->get('notifications_one_reads_' . $app->user->core["id"]);
        if (is_array($OneReads)) {
            $this->Notifications = $this->Notifications + $OneReads;
        }
    }


    /**
     * clear_one_read
     */
    public static function clear_one_read($ID)
    {
        $app = \Gazelle\App::go();

        $OneReads = $app->cache->get('notifications_one_reads_' . $app->user->core["id"]);
        if ($OneReads) {
            unset($OneReads[$ID]);
            if (count($OneReads) > 0) {
                $app->cache->set('notifications_one_reads_' . $app->user->core["id"], $OneReads, 0);
            } else {
                $app->cache->delete('notifications_one_reads_' . $app->user->core["id"]);
            }
        }
    }


    /**
     * load_global_notification
     */
    public function load_global_notification()
    {
        $app = \Gazelle\App::go();

        $GlobalNotification = $app->cache->get('global_notification');
        if ($GlobalNotification) {
            $Read = $app->cache->get('user_read_global_' . $app->user->core["id"]);
            if (!$Read) {
                $this->create_notification(self::GLOBALNOTICE, 0, $GlobalNotification['Message'], $GlobalNotification['URL'], $GlobalNotification['Importance']);
            }
        }
    }


    /**
     * get_global_notification
     */
    public static function get_global_notification()
    {
        $app = \Gazelle\App::go();

        return $app->cache->get('global_notification');
    }


    /**
     * set_global_notification
     */
    public static function set_global_notification($Message, $URL, $Importance, $Expiration)
    {
        $app = \Gazelle\App::go();

        if (empty($Message) || empty($Expiration)) {
            error('Error setting notification');
        }
        $app->cache->set('global_notification', array("Message" => $Message, "URL" => $URL, "Importance" => $Importance, "Expiration" => $Expiration), $Expiration);
    }


    /**
     * delete_global_notification
     */
    public static function delete_global_notification()
    {
        $app = \Gazelle\App::go();

        $app->cache->delete('global_notification');
    }


    /**
     * clear_global_notification
     */
    public static function clear_global_notification()
    {
        $app = \Gazelle\App::go();

        $GlobalNotification = $app->cache->get('global_notification');
        if ($GlobalNotification) {
            // This is some trickery
            // since we can't know which users have the read cache key set
            // we set the expiration time of their cache key to that of the length of the notification
            // this gaurantees that their cache key will expire after the notification expires
            $app->cache->set('user_read_global_' . $app->user->core["id"], true, $GlobalNotification['Expiration']);
        }
    }


    /**
     * load_news
     */
    public function load_news()
    {
        $app = \Gazelle\App::go();

        $MyNews = $app->user->extra['LastReadNews'];
        $CurrentNews = $app->cache->get('news_latest_id');
        $Title = $app->cache->get('news_latest_title');
        if ($CurrentNews === false || $Title === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query('
            SELECT ID, Title
            FROM news
              ORDER BY Time DESC
              LIMIT 1');
            if ($app->dbOld->has_results()) {
                list($CurrentNews, $Title) = $app->dbOld->next_record();
            } else {
                $CurrentNews = -1;
            }
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('news_latest_id', $CurrentNews, 0);
            $app->cache->set('news_latest_title', $Title, 0);
        }
        if ($MyNews < $CurrentNews) {
            $this->create_notification(self::NEWS, $CurrentNews, "Announcement: $Title", "index.php#news$CurrentNews", self::IMPORTANT);
        }
    }


    /**
     * load_blog
     */
    public function load_blog()
    {
        $app = \Gazelle\App::go();

        $MyBlog = $app->user->extra['LastReadBlog'];
        $CurrentBlog = $app->cache->get('blog_latest_id');
        $Title = $app->cache->get('blog_latest_title');
        if ($CurrentBlog === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query('
            SELECT ID, Title
            FROM blog
              WHERE Important = 1
              ORDER BY Time DESC
              LIMIT 1');
            if ($app->dbOld->has_results()) {
                list($CurrentBlog, $Title) = $app->dbOld->next_record();
            } else {
                $CurrentBlog = -1;
            }
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('blog_latest_id', $CurrentBlog, 0);
            $app->cache->set('blog_latest_title', $Title, 0);
        }
        if ($MyBlog < $CurrentBlog) {
            $this->create_notification(self::BLOG, $CurrentBlog, "Blog: $Title", "blog.php#blog$CurrentBlog", self::IMPORTANT);
        }
    }


    /**
     * load_staff_pms
     */
    public function load_staff_pms()
    {
        $app = \Gazelle\App::go();

        $NewStaffPMs = $app->cache->get('staff_pm_new_' . $app->user->core["id"]);
        if ($NewStaffPMs === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT COUNT(ID)
            FROM staff_pm_conversations
              WHERE UserID = '" . $app->user->core["id"] . "'
              AND Unread = '1'");
            list($NewStaffPMs) = $app->dbOld->next_record();
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('staff_pm_new_' . $app->user->core["id"], $NewStaffPMs, 0);
        }

        if ($NewStaffPMs > 0) {
            $Title = 'You have ' . ($NewStaffPMs === 1 ? 'a' : $NewStaffPMs) . ' new Staff PM' . ($NewStaffPMs > 1 ? 's' : '');
            $this->create_notification(self::STAFFPM, 0, $Title, 'staffpm.php', self::INFO);
        }
    }


    /**
     * load_inbox
     */
    public function load_inbox()
    {
        $app = \Gazelle\App::go();

        $NewMessages = $app->cache->get('inbox_new_' . $app->user->core["id"]);
        if ($NewMessages === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT COUNT(UnRead)
            FROM pm_conversations_users
              WHERE UserID = '" . $app->user->core["id"] . "'
              AND UnRead = '1'
              AND InInbox = '1'");
            list($NewMessages) = $app->dbOld->next_record();
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('inbox_new_' . $app->user->core["id"], $NewMessages, 0);
        }

        if ($NewMessages > 0) {
            $Title = 'You have ' . ($NewMessages === 1 ? 'a' : $NewMessages) . ' new message' . ($NewMessages > 1 ? 's' : '');
            $this->create_notification(self::INBOX, 0, $Title, Inbox::get_inbox_link(), self::INFO);
        }
    }


    /**
     * load_torrent_notifications
     */
    public function load_torrent_notifications()
    {
        $app = \Gazelle\App::go();

        if ($app->user->can(["notifications" => "read"])) {
            $NewNotifications = $app->cache->get('notifications_new_' . $app->user->core["id"]);
            if ($NewNotifications === false) {
                $QueryID = $app->dbOld->get_query_id();
                $app->dbOld->query("
                SELECT COUNT(UserID)
                FROM users_notify_torrents
                  WHERE UserID = ' " . $app->user->core["id"] . "'
                  AND UnRead = '1'");
                list($NewNotifications) = $app->dbOld->next_record();
                $app->dbOld->set_query_id($QueryID);
                $app->cache->set('notifications_new_' . $app->user->core["id"], $NewNotifications, 0);
            }
        }
        if (isset($NewNotifications) && $NewNotifications > 0) {
            $Title = 'You have ' . ($NewNotifications === 1 ? 'a' : $NewNotifications) . ' new torrent notification' . ($NewNotifications > 1 ? 's' : '');
            $this->create_notification(self::TORRENTS, 0, $Title, 'torrents.php?action=notify', self::INFO);
        }
    }


    /**
     * load_collage_subscriptions
     */
    public function load_collage_subscriptions()
    {
        $app = \Gazelle\App::go();

        if ($app->user->can(["subscriptions" => "create"])) {
            $NewCollages = $app->cache->get('collage_subs_user_new_' . $app->user->core["id"]);
            if ($NewCollages === false) {
                $QueryID = $app->dbOld->get_query_id();
                $app->dbOld->query("
                SELECT COUNT(DISTINCT s.CollageID)
                FROM users_collage_subs AS s
                  JOIN collages AS c ON s.CollageID = c.ID
                  JOIN collages_torrents AS ct ON ct.CollageID = c.ID
                WHERE s.UserID = " . $app->user->core["id"] . "
                  AND ct.AddedOn > s.LastVisit
                  AND c.Deleted = '0'");
                list($NewCollages) = $app->dbOld->next_record();
                $app->dbOld->set_query_id($QueryID);
                $app->cache->set('collage_subs_user_new_' . $app->user->core["id"], $NewCollages, 0);
            }
            if ($NewCollages > 0) {
                $Title = 'You have ' . ($NewCollages === 1 ? 'a' : $NewCollages) . ' new collage update' . ($NewCollages > 1 ? 's' : '');
                $this->create_notification(self::COLLAGES, 0, $Title, 'userhistory.php?action=subscribed_collages', self::INFO);
            }
        }
    }


    /**
     * load_quote_notifications
     */
    public function load_quote_notifications()
    {
        $app = \Gazelle\App::go();

        if (isset($app->user->extra['NotifyOnQuote']) && $app->user->extra['NotifyOnQuote']) {
            $QuoteNotificationsCount = Subscriptions::has_new_quote_notifications();
            if ($QuoteNotificationsCount > 0) {
                $Title = 'New quote' . ($QuoteNotificationsCount > 1 ? 's' : '');
                $this->create_notification(self::QUOTES, 0, $Title, 'userhistory.php?action=quote_notifications', self::INFO);
            }
        }
    }


    /**
     * load_subscriptions
     */
    public function load_subscriptions()
    {
        $SubscriptionsCount = Subscriptions::has_new_subscriptions();
        if ($SubscriptionsCount > 0) {
            $Title = 'New subscription' . ($SubscriptionsCount > 1 ? 's' : '');
            $this->create_notification(self::SUBSCRIPTIONS, 0, $Title, 'userhistory.php?action=subscriptions', self::INFO);
        }
    }


    /**
     * clear_news
     */
    public static function clear_news($News)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if (!$News) {
            if (!$News = $app->cache->get('news')) {
                $app->dbOld->query('
                SELECT
                  ID,
                  Title,
                  Body,
                  Time
                FROM news
                  ORDER BY Time DESC
                  LIMIT 1');
                $News = $app->dbOld->to_array(false, MYSQLI_NUM, false);
                $app->cache->set('news_latest_id', $News[0][0], 0);
            }
        }

        if ($app->user->extra['LastReadNews'] !== $News[0][0]) {
            /*
            $app->cacheOld->begin_transaction('user_info_heavy_' . $app->user->core["id"]);
            $app->cacheOld->update_row(false, array('LastReadNews' => $News[0][0]));
            $app->cacheOld->commit_transaction(0);
            */

            $app->dbOld->query("
            UPDATE users_info
            SET LastReadNews = '".$News[0][0]."'
              WHERE UserID = " . $app->user->core["id"]);
            $app->user->extra['LastReadNews'] = $News[0][0];
        }
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_blog
     */
    public static function clear_blog($Blog)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if (!isset($Blog) || !$Blog) {
            if (!$Blog = $app->cache->get('blog')) {
                $app->dbOld->query("
                SELECT
                  b.ID,
                  um.Username,
                  b.UserID,
                  b.Title,
                  b.Body,
                  b.Time,
                  b.ThreadID
                FROM blog AS b
                  LEFT JOIN users_main AS um ON b.UserID = um.ID
                  ORDER BY Time DESC
                  LIMIT 1");
                $Blog = $app->dbOld->to_array();
            }
        }
        if ($app->user->extra['LastReadBlog'] < $Blog[0][0]) {
            /*
            $app->cacheOld->begin_transaction('user_info_heavy_' . $app->user->core["id"]);
            $app->cacheOld->update_row(false, array('LastReadBlog' => $Blog[0][0]));
            $app->cacheOld->commit_transaction(0);
            */

            $app->dbOld->query("
            UPDATE users_info
            SET LastReadBlog = '". $Blog[0][0]."'
              WHERE UserID = " . $app->user->core["id"]);
            $app->user->extra['LastReadBlog'] = $Blog[0][0];
        }
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_staff_pms
     */
    public static function clear_staff_pms()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT ID
        FROM staff_pm_conversations
          WHERE Unread = true
          AND UserID = " . $app->user->core["id"]);
        $IDs = [];
        while (list($ID) = $app->dbOld->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            $app->dbOld->query("
            UPDATE staff_pm_conversations
            SET Unread = false
              WHERE ID IN ($IDs)");
        }
        $app->cache->delete('staff_pm_new_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_inbox
     */
    public static function clear_inbox()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT ConvID
        FROM pm_conversations_users
          WHERE Unread = '1'
          AND UserID = " . $app->user->core["id"]);
        $IDs = [];
        while (list($ID) = $app->dbOld->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            $app->dbOld->query("
            UPDATE pm_conversations_users
            SET Unread = '0'
              WHERE ConvID IN ($IDs)
              AND UserID = " . $app->user->core["id"]);
        }
        $app->cache->delete('inbox_new_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_torrents
     */
    public static function clear_torrents()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT TorrentID
        FROM users_notify_torrents
          WHERE UserID = ' " . $app->user->core["id"] . "'
          AND UnRead = '1'");
        $IDs = [];
        while (list($ID) = $app->dbOld->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            $app->dbOld->query("
            UPDATE users_notify_torrents
            SET Unread = '0'
              WHERE TorrentID IN ($IDs)
              AND UserID = " . $app->user->core["id"]);
        }
        $app->cache->delete('notifications_new_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_collages
     */
    public static function clear_collages()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        UPDATE users_collage_subs
        SET LastVisit = NOW()
          WHERE UserID = " . $app->user->core["id"]);
        $app->cache->delete('collage_subs_user_new_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_quotes
     */
    public static function clear_quotes()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        UPDATE users_notify_quoted
        SET UnRead = '0'
          WHERE UserID = " . $app->user->core["id"]);
        $app->cache->delete('notify_quoted_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * clear_subscriptions
     */
    public static function clear_subscriptions()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if (($UserSubscriptions = $app->cache->get('subscriptions_user_' . $app->user->core["id"])) === false) {
            $app->dbOld->query("
            SELECT TopicID
            FROM users_subscriptions
              WHERE UserID = " . $app->user->core["id"]);
            if ($UserSubscriptions = $app->dbOld->collect(0)) {
                $app->cache->set('subscriptions_user_' . $app->user->core["id"], $UserSubscriptions, 0);
            }
        }
        if (!empty($UserSubscriptions)) {
            $app->dbOld->query("
            INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
            SELECT '" . $app->user->core["id"] . "', ID, LastPostID
            FROM forums_topics
              WHERE ID IN (".implode(',', $UserSubscriptions).')
            ON DUPLICATE KEY UPDATE
              PostID = LastPostID');
        }
        $app->cache->delete('subscriptions_user_new_' . $app->user->core["id"]);
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * get_settings
     */
    public static function get_settings($UserID)
    {
        $app = \Gazelle\App::go();

        $Results = $app->cache->get("users_notifications_settings_$UserID");
        if (!$Results) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT *
            FROM users_notifications_settings
              WHERE UserID = ?", $UserID);
            $Results = $app->dbOld->next_record(MYSQLI_ASSOC, false);
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set("users_notifications_settings_$UserID", $Results, 0);
        }
        return $Results;
    }


    /**
     * save_settings
     */
    public static function save_settings($UserID, $Settings = false)
    {
        $app = \Gazelle\App::go();

        if (!is_array($Settings)) {
            // A little cheat technique, gets all keys in the $_POST array starting with 'notifications_'
            $Settings = array_intersect_key($_POST, array_flip(preg_grep('/^notifications_/', array_keys($_POST))));
        }
        $Update = [];
        foreach (self::$Types as $Type) {
            $Popup = array_key_exists("notifications_{$Type}_popup", $Settings);
            $Traditional = array_key_exists("notifications_{$Type}_traditional", $Settings);
            $Result = self::OPT_DISABLED;
            if ($Popup) {
                $Result = self::OPT_POPUP;
            } elseif ($Traditional) {
                $Result = self::OPT_TRADITIONAL;
            }
            $Update[] = "$Type = $Result";
        }
        $Update = implode(',', $Update);

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        UPDATE users_notifications_settings
        SET $Update
          WHERE UserID = ?", $UserID);

        $app->dbOld->set_query_id($QueryID);
        $app->cache->delete("users_notifications_settings_$UserID");
    }


    /**
     * is_traditional
     */
    public function is_traditional($Type)
    {
        return $this->Settings[$Type] === self::OPT_TRADITIONAL;
    }


    /**
     * is_skipped
     */
    public function is_skipped($Type)
    {
        return isset($this->Skipped[$Type]);
    }


    /**
     * in_array
     */
    public function use_noty()
    {
        return in_array(self::OPT_POPUP, $this->Settings);
    }
}
