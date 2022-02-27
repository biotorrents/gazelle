<?php
declare(strict_types = 1);

class NotificationsManager
{
    // Option types
    const OPT_DISABLED = 0;
    const OPT_POPUP = 1;
    const OPT_TRADITIONAL = 2;

    // Importances
    const IMPORTANT = 'information';
    const CRITICAL = 'error';
    const WARNING = 'warning';
    const INFO = 'confirmation';

    public static $Importances = array(
        'important' => self::IMPORTANT,
        'critical' => self::CRITICAL,
        'warning' => self::WARNING,
        'info' => self::INFO
    );

    // Types. These names must correspond to column names in users_notifications_settings
    const NEWS = 'News';
    const BLOG = 'Blog';
    const STAFFPM = 'StaffPM';
    const INBOX = 'Inbox';
    const QUOTES = 'Quotes';
    const SUBSCRIPTIONS = 'Subscriptions';
    const TORRENTS = 'Torrents';
    const COLLAGES = 'Collages';
    const SITEALERTS = 'SiteAlerts';
    const FORUMALERTS = 'ForumAlerts';
    const REQUESTALERTS = 'RequestAlerts';
    const COLLAGEALERTS = 'CollageAlerts';
    const TORRENTALERTS = 'TorrentAlerts';
    const GLOBALNOTICE = 'Global';

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

    public function get_notifications()
    {
        return $this->Notifications;
    }

    public function clear_notifications_array()
    {
        unset($this->Notifications);
        $this->Notifications = [];
    }

    private function create_notification($Type, $ID, $Message, $URL, $Importance)
    {
        $this->Notifications[$Type] = array(
            'id' => (int) $ID,
            'message' => $Message,
            'url' => $URL,
            'importance' => $Importance
        );
    }

    public static function get_notification_enabled_users($Type, $UserID)
    {
        $Type = db_string($Type);
        $UserWhere = '';
        if (isset($UserID)) {
            $UserID = (int)$UserID;
            $UserWhere = " AND UserID = '$UserID'";
        }
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT UserID
        FROM users_notifications_settings
          WHERE $Type != 0
        $UserWhere");
        $IDs = [];
        while (list($ID) = G::$db->next_record()) {
            $IDs[] = $ID;
        }
        G::$db->set_query_id($QueryID);
        return $IDs;
    }

    public function load_one_reads()
    {
        $OneReads = G::$cache->get_value('notifications_one_reads_' . G::$user['ID']);
        if (is_array($OneReads)) {
            $this->Notifications = $this->Notifications + $OneReads;
        }
    }

    public static function clear_one_read($ID)
    {
        $OneReads = G::$cache->get_value('notifications_one_reads_' . G::$user['ID']);
        if ($OneReads) {
            unset($OneReads[$ID]);
            if (count($OneReads) > 0) {
                G::$cache->cache_value('notifications_one_reads_' . G::$user['ID'], $OneReads, 0);
            } else {
                G::$cache->delete_value('notifications_one_reads_' . G::$user['ID']);
            }
        }
    }

    public function load_global_notification()
    {
        $GlobalNotification = G::$cache->get_value('global_notification');
        if ($GlobalNotification) {
            $Read = G::$cache->get_value('user_read_global_' . G::$user['ID']);
            if (!$Read) {
                $this->create_notification(self::GLOBALNOTICE, 0, $GlobalNotification['Message'], $GlobalNotification['URL'], $GlobalNotification['Importance']);
            }
        }
    }

    public static function get_global_notification()
    {
        return G::$cache->get_value('global_notification');
    }

    public static function set_global_notification($Message, $URL, $Importance, $Expiration)
    {
        if (empty($Message) || empty($Expiration)) {
            error('Error setting notification');
        }
        G::$cache->cache_value('global_notification', array("Message" => $Message, "URL" => $URL, "Importance" => $Importance, "Expiration" => $Expiration), $Expiration);
    }

    public static function delete_global_notification()
    {
        G::$cache->delete_value('global_notification');
    }

    public static function clear_global_notification()
    {
        $GlobalNotification = G::$cache->get_value('global_notification');
        if ($GlobalNotification) {
            // This is some trickery
            // since we can't know which users have the read cache key set
            // we set the expiration time of their cache key to that of the length of the notification
            // this gaurantees that their cache key will expire after the notification expires
            G::$cache->cache_value('user_read_global_' . G::$user['ID'], true, $GlobalNotification['Expiration']);
        }
    }

    public function load_news()
    {
        $MyNews = G::$user['LastReadNews'];
        $CurrentNews = G::$cache->get_value('news_latest_id');
        $Title = G::$cache->get_value('news_latest_title');
        if ($CurrentNews === false || $Title === false) {
            $QueryID = G::$db->get_query_id();
            G::$db->query('
            SELECT ID, Title
            FROM news
              ORDER BY Time DESC
              LIMIT 1');
            if (G::$db->has_results()) {
                list($CurrentNews, $Title) = G::$db->next_record();
            } else {
                $CurrentNews = -1;
            }
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('news_latest_id', $CurrentNews, 0);
            G::$cache->cache_value('news_latest_title', $Title, 0);
        }
        if ($MyNews < $CurrentNews) {
            $this->create_notification(self::NEWS, $CurrentNews, "Announcement: $Title", "index.php#news$CurrentNews", self::IMPORTANT);
        }
    }

    public function load_blog()
    {
        $MyBlog = G::$user['LastReadBlog'];
        $CurrentBlog = G::$cache->get_value('blog_latest_id');
        $Title = G::$cache->get_value('blog_latest_title');
        if ($CurrentBlog === false) {
            $QueryID = G::$db->get_query_id();
            G::$db->query('
            SELECT ID, Title
            FROM blog
              WHERE Important = 1
              ORDER BY Time DESC
              LIMIT 1');
            if (G::$db->has_results()) {
                list($CurrentBlog, $Title) = G::$db->next_record();
            } else {
                $CurrentBlog = -1;
            }
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('blog_latest_id', $CurrentBlog, 0);
            G::$cache->cache_value('blog_latest_title', $Title, 0);
        }
        if ($MyBlog < $CurrentBlog) {
            $this->create_notification(self::BLOG, $CurrentBlog, "Blog: $Title", "blog.php#blog$CurrentBlog", self::IMPORTANT);
        }
    }

    public function load_staff_pms()
    {
        $NewStaffPMs = G::$cache->get_value('staff_pm_new_' . G::$user['ID']);
        if ($NewStaffPMs === false) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT COUNT(ID)
            FROM staff_pm_conversations
              WHERE UserID = '" . G::$user['ID'] . "'
              AND Unread = '1'");
            list($NewStaffPMs) = G::$db->next_record();
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('staff_pm_new_' . G::$user['ID'], $NewStaffPMs, 0);
        }

        if ($NewStaffPMs > 0) {
            $Title = 'You have ' . ($NewStaffPMs === 1 ? 'a' : $NewStaffPMs) . ' new Staff PM' . ($NewStaffPMs > 1 ? 's' : '');
            $this->create_notification(self::STAFFPM, 0, $Title, 'staffpm.php', self::INFO);
        }
    }

    public function load_inbox()
    {
        $NewMessages = G::$cache->get_value('inbox_new_' . G::$user['ID']);
        if ($NewMessages === false) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT COUNT(UnRead)
            FROM pm_conversations_users
              WHERE UserID = '" . G::$user['ID'] . "'
              AND UnRead = '1'
              AND InInbox = '1'");
            list($NewMessages) = G::$db->next_record();
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('inbox_new_' . G::$user['ID'], $NewMessages, 0);
        }

        if ($NewMessages > 0) {
            $Title = 'You have ' . ($NewMessages === 1 ? 'a' : $NewMessages) . ' new message' . ($NewMessages > 1 ? 's' : '');
            $this->create_notification(self::INBOX, 0, $Title, Inbox::get_inbox_link(), self::INFO);
        }
    }

    public function load_torrent_notifications()
    {
        if (check_perms('site_torrents_notify')) {
            $NewNotifications = G::$cache->get_value('notifications_new_' . G::$user['ID']);
            if ($NewNotifications === false) {
                $QueryID = G::$db->get_query_id();
                G::$db->query("
                SELECT COUNT(UserID)
                FROM users_notify_torrents
                  WHERE UserID = ' " . G::$user['ID'] . "'
                  AND UnRead = '1'");
                list($NewNotifications) = G::$db->next_record();
                G::$db->set_query_id($QueryID);
                G::$cache->cache_value('notifications_new_' . G::$user['ID'], $NewNotifications, 0);
            }
        }
        if (isset($NewNotifications) && $NewNotifications > 0) {
            $Title = 'You have ' . ($NewNotifications === 1 ? 'a' : $NewNotifications) . ' new torrent notification' . ($NewNotifications > 1 ? 's' : '');
            $this->create_notification(self::TORRENTS, 0, $Title, 'torrents.php?action=notify', self::INFO);
        }
    }

    public function load_collage_subscriptions()
    {
        if (check_perms('site_collages_subscribe')) {
            $NewCollages = G::$cache->get_value('collage_subs_user_new_' . G::$user['ID']);
            if ($NewCollages === false) {
                $QueryID = G::$db->get_query_id();
                G::$db->query("
                SELECT COUNT(DISTINCT s.CollageID)
                FROM users_collage_subs AS s
                  JOIN collages AS c ON s.CollageID = c.ID
                  JOIN collages_torrents AS ct ON ct.CollageID = c.ID
                WHERE s.UserID = " . G::$user['ID'] . "
                  AND ct.AddedOn > s.LastVisit
                  AND c.Deleted = '0'");
                list($NewCollages) = G::$db->next_record();
                G::$db->set_query_id($QueryID);
                G::$cache->cache_value('collage_subs_user_new_' . G::$user['ID'], $NewCollages, 0);
            }
            if ($NewCollages > 0) {
                $Title = 'You have ' . ($NewCollages === 1 ? 'a' : $NewCollages) . ' new collage update' . ($NewCollages > 1 ? 's' : '');
                $this->create_notification(self::COLLAGES, 0, $Title, 'userhistory.php?action=subscribed_collages', self::INFO);
            }
        }
    }

    public function load_quote_notifications()
    {
        if (isset(G::$user['NotifyOnQuote']) && G::$user['NotifyOnQuote']) {
            $QuoteNotificationsCount = Subscriptions::has_new_quote_notifications();
            if ($QuoteNotificationsCount > 0) {
                $Title = 'New quote' . ($QuoteNotificationsCount > 1 ? 's' : '');
                $this->create_notification(self::QUOTES, 0, $Title, 'userhistory.php?action=quote_notifications', self::INFO);
            }
        }
    }

    public function load_subscriptions()
    {
        $SubscriptionsCount = Subscriptions::has_new_subscriptions();
        if ($SubscriptionsCount > 0) {
            $Title = 'New subscription' . ($SubscriptionsCount > 1 ? 's' : '');
            $this->create_notification(self::SUBSCRIPTIONS, 0, $Title, 'userhistory.php?action=subscriptions', self::INFO);
        }
    }

    public static function clear_news($News)
    {
        $QueryID = G::$db->get_query_id();
        if (!$News) {
            if (!$News = G::$cache->get_value('news')) {
                G::$db->query('
                SELECT
                  ID,
                  Title,
                  Body,
                  Time
                FROM news
                  ORDER BY Time DESC
                  LIMIT 1');
                $News = G::$db->to_array(false, MYSQLI_NUM, false);
                G::$cache->cache_value('news_latest_id', $News[0][0], 0);
            }
        }

        if (G::$user['LastReadNews'] !== $News[0][0]) {
            G::$cache->begin_transaction('user_info_heavy_' . G::$user['ID']);
            G::$cache->update_row(false, array('LastReadNews' => $News[0][0]));
            G::$cache->commit_transaction(0);
            G::$db->query("
            UPDATE users_info
            SET LastReadNews = '".$News[0][0]."'
              WHERE UserID = " . G::$user['ID']);
            G::$user['LastReadNews'] = $News[0][0];
        }
        G::$db->set_query_id($QueryID);
    }

    public static function clear_blog($Blog)
    {
        $QueryID = G::$db->get_query_id();
        if (!isset($Blog) || !$Blog) {
            if (!$Blog = G::$cache->get_value('blog')) {
                G::$db->query("
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
                $Blog = G::$db->to_array();
            }
        }
        if (G::$user['LastReadBlog'] < $Blog[0][0]) {
            G::$cache->begin_transaction('user_info_heavy_' . G::$user['ID']);
            G::$cache->update_row(false, array('LastReadBlog' => $Blog[0][0]));
            G::$cache->commit_transaction(0);
            G::$db->query("
            UPDATE users_info
            SET LastReadBlog = '". $Blog[0][0]."'
              WHERE UserID = " . G::$user['ID']);
            G::$user['LastReadBlog'] = $Blog[0][0];
        }
        G::$db->set_query_id($QueryID);
    }

    public static function clear_staff_pms()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT ID
        FROM staff_pm_conversations
          WHERE Unread = true
          AND UserID = " . G::$user['ID']);
        $IDs = [];
        while (list($ID) = G::$db->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            G::$db->query("
            UPDATE staff_pm_conversations
            SET Unread = false
              WHERE ID IN ($IDs)");
        }
        G::$cache->delete_value('staff_pm_new_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function clear_inbox()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT ConvID
        FROM pm_conversations_users
          WHERE Unread = '1'
          AND UserID = " . G::$user['ID']);
        $IDs = [];
        while (list($ID) = G::$db->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            G::$db->query("
            UPDATE pm_conversations_users
            SET Unread = '0'
              WHERE ConvID IN ($IDs)
              AND UserID = " . G::$user['ID']);
        }
        G::$cache->delete_value('inbox_new_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function clear_torrents()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        SELECT TorrentID
        FROM users_notify_torrents
          WHERE UserID = ' " . G::$user['ID'] . "'
          AND UnRead = '1'");
        $IDs = [];
        while (list($ID) = G::$db->next_record()) {
            $IDs[] = $ID;
        }
        $IDs = implode(',', $IDs);
        if (!empty($IDs)) {
            G::$db->query("
            UPDATE users_notify_torrents
            SET Unread = '0'
              WHERE TorrentID IN ($IDs)
              AND UserID = " . G::$user['ID']);
        }
        G::$cache->delete_value('notifications_new_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function clear_collages()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        UPDATE users_collage_subs
        SET LastVisit = NOW()
          WHERE UserID = " . G::$user['ID']);
        G::$cache->delete_value('collage_subs_user_new_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function clear_quotes()
    {
        $QueryID = G::$db->get_query_id();
        G::$db->query("
        UPDATE users_notify_quoted
        SET UnRead = '0'
          WHERE UserID = " . G::$user['ID']);
        G::$cache->delete_value('notify_quoted_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function clear_subscriptions()
    {
        $QueryID = G::$db->get_query_id();
        if (($UserSubscriptions = G::$cache->get_value('subscriptions_user_' . G::$user['ID'])) === false) {
            G::$db->query("
            SELECT TopicID
            FROM users_subscriptions
              WHERE UserID = " . G::$user['ID']);
            if ($UserSubscriptions = G::$db->collect(0)) {
                G::$cache->cache_value('subscriptions_user_' . G::$user['ID'], $UserSubscriptions, 0);
            }
        }
        if (!empty($UserSubscriptions)) {
            G::$db->query("
            INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
            SELECT '" . G::$user['ID'] . "', ID, LastPostID
            FROM forums_topics
              WHERE ID IN (".implode(',', $UserSubscriptions).')
            ON DUPLICATE KEY UPDATE
              PostID = LastPostID');
        }
        G::$cache->delete_value('subscriptions_user_new_' . G::$user['ID']);
        G::$db->set_query_id($QueryID);
    }

    public static function get_settings($UserID)
    {
        $Results = G::$cache->get_value("users_notifications_settings_$UserID");
        if (!$Results) {
            $QueryID = G::$db->get_query_id();
            G::$db->query("
            SELECT *
            FROM users_notifications_settings
              WHERE UserID = ?", $UserID);
            $Results = G::$db->next_record(MYSQLI_ASSOC, false);
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value("users_notifications_settings_$UserID", $Results, 0);
        }
        return $Results;
    }

    public static function save_settings($UserID, $Settings = false)
    {
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

        $QueryID = G::$db->get_query_id();
        G::$db->query("
        UPDATE users_notifications_settings
        SET $Update
          WHERE UserID = ?", $UserID);

        G::$db->set_query_id($QueryID);
        G::$cache->delete_value("users_notifications_settings_$UserID");
    }

    public function is_traditional($Type)
    {
        return $this->Settings[$Type] === self::OPT_TRADITIONAL;
    }

    public function is_skipped($Type)
    {
        return isset($this->Skipped[$Type]);
    }

    public function use_noty()
    {
        return in_array(self::OPT_POPUP, $this->Settings);
    }
}
