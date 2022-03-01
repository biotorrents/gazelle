<?php
/**
 * Generate a table row for a staff member on staff.php
 *
 * @param $Row used for alternating row colors
 * @param $ID the user ID of the staff member
 * @param $Paranoia the user's paranoia
 * @param $Class the user class
 * @param $LastAccess datetime the user last browsed the site
 * @param $Remark the "Staff remark" or FLS' "Support for" text
 * @param $HiddenBy the text that is displayed when a staff member's
 *                  paranoia hides their LastAccess time
 * @return string $Row
 */
function make_staff_row($ID, $Paranoia, $Class, $LastAccess, $Remark = '', $HiddenBy = 'Hidden by user') {
  echo "\t\t\t<tr class=\"row\">
        <td class=\"nobr\">
          " . Users::format_username($ID, false, false, false) . "
        </td>
        <td class=\"nobr\">
          "; //used for proper indentation of HTML
          if (check_paranoia('lastseen', $Paranoia, $Class)) {
            echo time_diff($LastAccess);
          } else {
            echo "$HiddenBy";
          }
  echo "\n\t\t\t\t</td>
        <td class=\"nobr\">"
          . Text::parse($Remark) .
        "</td>
      </tr>\n"; // the "\n" is needed for pretty HTML
  // the foreach loop that calls this function needs to know the new value of $Row
}

function get_fls() {
  global $cache, $db;
  static $FLS;
  if (is_array($FLS)) {
    return $FLS;
  }
  if (($FLS = $cache->get_value('fls')) === false) {
    $db->query('
      SELECT
        m.ID,
        p.Level,
        m.Username,
        m.Paranoia,
        m.LastAccess,
        i.SupportFor
      FROM users_info AS i
        JOIN users_main AS m ON m.ID = i.UserID
        JOIN permissions AS p ON p.ID = m.PermissionID
        JOIN users_levels AS l ON l.UserID = i.UserID
      WHERE l.PermissionID = ' . FLS_TEAM . '
      ORDER BY m.Username');
    $FLS = $db->to_array(false, MYSQLI_BOTH, array(3, 'Paranoia'));
    $cache->cache_value('fls', $FLS, 180);
  }
  return $FLS;
}

/*
 * Build the SQL query that will be used for displaying staff members
 *
 * @param $StaffLevel a string for selecting the type of staff being queried
 * @return string the text of the generated SQL query
 */
function generate_staff_query($StaffLevel) {
  global $Classes;
  if ($StaffLevel == 'forum_staff') {
    $PName = ''; // only needed for full staff
    $PLevel = 'p.Level < ' . $Classes[MOD]['Level'];
  } elseif ($StaffLevel == 'staff') {
    $PName = 'p.Name,';
    $PLevel = 'p.Level >= ' . $Classes[MOD]['Level'];
  }

  $SQL = "
    SELECT
      m.ID,
      p.Level,
      $PName
      m.Username,
      m.Paranoia,
      m.LastAccess,
      i.SupportFor
    FROM users_main AS m
      JOIN users_info AS i ON m.ID = i.UserID
      JOIN permissions AS p ON p.ID = m.PermissionID
    WHERE p.DisplayStaff = '1'
      AND $PLevel
    ORDER BY p.Level";
  if (check_perms('users_mod')) {
    $SQL .= ', m.LastAccess ASC';
  } else {
    $SQL .= ', m.Username';
  }
  return $SQL;
}

function get_forum_staff() {
  global $cache, $db;
  static $ForumStaff;
  if (is_array($ForumStaff)) {
    return $ForumStaff;
  }

  // sort the lists differently if the viewer is a staff member
  if (!check_perms('users_mod')) {
    if (($ForumStaff = $cache->get_value('forum_staff')) === false) {
      $db->query(generate_staff_query('forum_staff'));
      $ForumStaff = $db->to_array(false, MYSQLI_BOTH, array(3, 'Paranoia'));
      $cache->cache_value('forum_staff', $ForumStaff, 180);
    }
  } else {
    if (($ForumStaff = $cache->get_value('forum_staff_mod_view')) === false) {
      $db->query(generate_staff_query('forum_staff'));
      $ForumStaff = $db->to_array(false, MYSQLI_BOTH, array(3, 'Paranoia'));
      $cache->cache_value('forum_staff_mod_view', $ForumStaff, 180);
    }
  }
  return $ForumStaff;
}

function get_staff() {
  global $cache, $db;
  static $Staff;
  if (is_array($Staff)) {
    return $Staff;
  }

  // sort the lists differently if the viewer is a staff member
  if (!check_perms('users_mod')) {
    if (($Staff = $cache->get_value('staff')) === false) {
      $db->query(generate_staff_query('staff'));
      $Staff = $db->to_array(false, MYSQLI_BOTH, array(4, 'Paranoia'));
      $cache->cache_value('staff', $Staff, 180);
    }
  } else {
    if (($Staff = $cache->get_value('staff_mod_view')) === false) {
      $db->query(generate_staff_query('staff'));
      $Staff = $db->to_array(false, MYSQLI_BOTH, array(4, 'Paranoia'));
      $cache->cache_value('staff_mod_view', $Staff, 180);
    }
  }
  return $Staff;
}

function get_support() {
  return array(
    get_fls(),
    get_forum_staff(),
    get_staff(),
    'fls' => get_fls(),
    'forum_staff' => get_forum_staff(),
    'staff' => get_staff()
  );
}

function printSectionDiv($ClassName) {
?>
    </div><br />
    <div class='box pad'>
    <h2 style='text-align: left;'><?=$ClassName?></h2>
<?php
}
