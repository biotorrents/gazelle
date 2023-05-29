<?php
#declare(strict_types = 1);

/**
 * JUST USE MANTICORE
 */

$app = \Gazelle\App::go();
$ENV = ENV::go();

if (!empty($_GET['search'])) {
    if (preg_match("/{$app->env->regexIp4}/", $_GET['search'])) {
        $_GET['ip'] = $_GET['search'];
    } elseif (preg_match("/{$app->env->regexUsername}/iD", $_GET['search'])) {
        $app->dbOld->query("
      SELECT ID
      FROM users_main
      WHERE Username = '".db_string($_GET['search'])."'");
        if (list($ID) = $app->dbOld->next_record()) {
            Http::redirect("user.php?id=$ID");
            error();
        }
        $_GET['username'] = $_GET['search'];
    } else {
        $_GET['comment'] = $_GET['search'];
    }
}

define('USERS_PER_PAGE', 30);

function wrap($String, $ForceMatch = '', $IPSearch = false)
{
    if (!$ForceMatch) {
        global $Match;
    } else {
        $Match = $ForceMatch;
    }
    if ($Match == ' REGEXP ') {
        if (strpos($String, '\'') !== false || preg_match('/^.*\\\\$/i', $String)) {
            error('Regex contains illegal characters.');
        }
    } else {
        $String = db_string($String);
    }
    if ($Match == ' LIKE ') {
        // Fuzzy search
        // Stick in wildcards at beginning and end of string unless string starts or ends with |
        if (($String[0] != '|') && !$IPSearch) {
            $String = "%$String";
        } elseif ($String[0] == '|') {
            $String = substr($String, 1, strlen($String));
        }

        if (substr($String, -1, 1) != '|') {
            $String = "$String%";
        } else {
            $String = substr($String, 0, -1);
        }
    }
    $String = "'$String'";
    return $String;
}

function date_compare($Field, $Operand, $Date1, $Date2 = '')
{
    $Date1 = db_string($Date1);
    $Date2 = db_string($Date2);
    $Return = [];

    switch ($Operand) {
        case 'on':
            $Return [] = " $Field >= '$Date1 00:00:00' ";
            $Return [] = " $Field <= '$Date1 23:59:59' ";
            break;
        case 'before':
            $Return [] = " $Field < '$Date1 00:00:00' ";
            break;
        case 'after':
            $Return [] = " $Field > '$Date1 23:59:59' ";
            break;
        case 'between':
            $Return [] = " $Field >= '$Date1 00:00:00' ";
            $Return [] = " $Field <= '$Date2 00:00:00' ";
            break;
    }

    return $Return;
}


function num_compare($Field, $Operand, $Num1, $Num2 = '')
{
    if ($Num1 != 0) {
        $Num1 = db_string($Num1);
    }
    if ($Num2 != 0) {
        $Num2 = db_string($Num2);
    }

    $Return = [];

    switch ($Operand) {
        case 'equal':
            $Return [] = " $Field = '$Num1' ";
            break;
        case 'above':
            $Return [] = " $Field > '$Num1' ";
            break;
        case 'below':
            $Return [] = " $Field < '$Num1' ";
            break;
        case 'between':
            $Return [] = " $Field > '$Num1' ";
            $Return [] = " $Field < '$Num2' ";
            break;
        default:
            print_r($Return);
            error();
    }
    return $Return;
}

// Arrays, regexes, and all that fun stuff we can use for validation, form generation, etc

$DateChoices = array('inarray'=>array('on', 'before', 'after', 'between'));
$SingleDateChoices = array('inarray'=>array('on', 'before', 'after'));
$NumberChoices = array('inarray'=>array('equal', 'above', 'below', 'between', 'buffer'));
$YesNo = array('inarray'=>array('any', 'yes', 'no'));
$OrderVals = array('inarray'=>array('Username', 'Ratio', 'IP', 'Email', 'Joined', 'Last Seen', 'Uploaded', 'Downloaded', 'Invites', 'Snatches'));
$WayVals = array('inarray'=>array('Ascending', 'Descending'));

if (count($_GET)) {
    $DateRegex = array('regex' => '/\d{4}-\d{2}-\d{2}/');

    $ClassIDs = [];
    $SecClassIDs = [];

    $Val->SetFields('comment', '0', 'string', 'Comment is too long.', array('maxlength' => 512));
    $Val->SetFields('disabled_invites', '0', 'inarray', 'Invalid disabled_invites field', $YesNo);


    $Val->SetFields('joined', '0', 'inarray', 'Invalid joined field', $DateChoices);
    $Val->SetFields('join1', '0', 'regex', 'Invalid join1 field', $DateRegex);
    $Val->SetFields('join2', '0', 'regex', 'Invalid join2 field', $DateRegex);

    $Val->SetFields('lastactive', '0', 'inarray', 'Invalid lastactive field', $DateChoices);
    $Val->SetFields('lastactive1', '0', 'regex', 'Invalid lastactive1 field', $DateRegex);
    $Val->SetFields('lastactive2', '0', 'regex', 'Invalid lastactive2 field', $DateRegex);

    $Val->SetFields('ratio', '0', 'inarray', 'Invalid ratio field', $NumberChoices);
    $Val->SetFields('uploaded', '0', 'inarray', 'Invalid uploaded field', $NumberChoices);
    $Val->SetFields('downloaded', '0', 'inarray', 'Invalid downloaded field', $NumberChoices);
    //$Val->SetFields('snatched', '0', 'inarray', 'Invalid snatched field', $NumberChoices);

    $Val->SetFields('matchtype', '0', 'inarray', 'Invalid matchtype field', array('inarray' => array('strict', 'fuzzy', 'regex')));

    $Val->SetFields('lockedaccount', '0', 'inarray', 'Invalid locked account field', array('inarray' => array('any', 'locked', 'unlocked')));

    $Val->SetFields('enabled', '0', 'inarray', 'Invalid enabled field', array('inarray' => array('', 0, 1, 2)));
    $Val->SetFields('class', '0', 'inarray', 'Invalid class', array('inarray' => $ClassIDs));
    $Val->SetFields('secclass', '0', 'inarray', 'Invalid class', array('inarray' => $SecClassIDs));
    $Val->SetFields('donor', '0', 'inarray', 'Invalid donor field', $YesNo);
    $Val->SetFields('warned', '0', 'inarray', 'Invalid warned field', $YesNo);
    $Val->SetFields('disabled_uploads', '0', 'inarray', 'Invalid disabled_uploads field', $YesNo);

    $Val->SetFields('order', '0', 'inarray', 'Invalid ordering', $OrderVals);
    $Val->SetFields('way', '0', 'inarray', 'Invalid way', $WayVals);

    $Val->SetFields('passkey', '0', 'string', 'Invalid passkey', array('maxlength' => 32));
    $Val->SetFields('avatar', '0', 'string', 'Avatar URL too long', array('maxlength' => 512));
    #$Val->SetFields('stylesheet', '0', 'inarray', 'Invalid stylesheet', array_unique(array_keys($Stylesheets)));
    $Val->SetFields('cc', '0', 'inarray', 'Invalid Country Code', array('maxlength' => 2));

    $Err = $Val->ValidateForm($_GET);

    if (!$Err) {
        // Passed validation. Let's rock.
        $RunQuery = false; // if we should run the search

        if (isset($_GET['matchtype']) && $_GET['matchtype'] == 'strict') {
            $Match = ' = ';
        } elseif (isset($_GET['matchtype']) && $_GET['matchtype'] == 'regex') {
            $Match = ' REGEXP ';
        } else {
            $Match = ' LIKE ';
        }

        $OrderTable = array(
        'Username' => 'um1.Username',
        'Joined' => 'ui1.JoinDate',
        'Email' => 'um1.Email',
        'IP' => 'um1.IP',
        'Last Seen' => 'um1.LastAccess',
        'Uploaded' => 'um1.Uploaded',
        'Downloaded' => 'um1.Downloaded',
        'Ratio' => '(um1.Uploaded / um1.Downloaded)',
        'Invites' => 'um1.Invites',
        'Snatches' => 'Snatches');

        $WayTable = array('Ascending'=>'ASC', 'Descending'=>'DESC');

        $Where = [];
        $Having = [];
        $Join = [];
        $Group = [];
        $Distinct = '';
        $Order = '';


        $SQL = '
        SQL_CALC_FOUND_ROWS
        um1.ID,
        um1.Username,
        um1.Uploaded,
        um1.Downloaded,';
        if ($_GET['snatched'] == 'off') {
            $SQL .= "'X' AS Snatches,";
        } else {
            $SQL .= "
        (
          SELECT COUNT(xs.uid)
          FROM xbt_snatched AS xs
          WHERE xs.uid = um1.ID
        ) AS Snatches,";
        }
        if ($_GET['invitees'] == 'off') {
            $SQL .= "'X' AS Invitees,";
        } else {
            $SQL .= "
      (
        SELECT COUNT(ui2.UserID)
        FROM users_info AS ui2
        WHERE um1.ID = ui2.Inviter
      ) AS Invitees,";
        }
        $SQL .= '
        um1.PermissionID,
        um1.Email,
        um1.Enabled,
        um1.IP,
        um1.Invites,
        ui1.DisableInvites,
        ui1.Warned,
        ui1.Donor,
        ui1.JoinDate,
        um1.LastAccess
      FROM users_main AS um1
        JOIN users_info AS ui1 ON ui1.UserID = um1.ID ';


        if (!empty($_GET['username'])) {
            $Where[] = 'um1.Username'.$Match.wrap($_GET['username']);
        }

        if (!empty($_GET['email'])) {
            $Join['the'] = ' JOIN users_emails_decrypted AS he ON he.ID = um1.ID ';
            $Where[] = ' he.Email '.$Match.wrap($_GET['email']);
        }

        if (!empty($_GET['ip'])) {
            $Join['tip'] = ' JOIN users_ips_decrypted AS tip ON tip.ID = um1.ID ';
            $Where[] = ' tip.IP '.$Match.wrap($_GET['ip'], '', true);
        }


        if ($_GET['lockedaccount'] != '' && $_GET['lockedaccount'] != 'any') {
            $Join['la'] = '';

            if ($_GET['lockedaccount'] == 'unlocked') {
                $Join['la'] .= ' LEFT';
                $Where[] = ' la.UserID IS NULL';
            }

            $Join['la'] .= ' JOIN locked_accounts AS la ON la.UserID = um1.ID ';
        }

        if (!empty($_GET['tracker_ip'])) {
            $Distinct = 'DISTINCT ';
            $Join['xfu'] = ' JOIN xbt_files_users AS xfu ON um1.ID = xfu.uid ';
            $Where[] = ' xfu.ip '.$Match.wrap($_GET['tracker_ip'], '', true);
        }

        if (!empty($_GET['comment'])) {
            $Where[] = 'ui1.AdminComment'.$Match.wrap($_GET['comment']);
        }

        if (strlen($_GET['invites1'])) {
            $Invites1 = round($_GET['invites1']);
            $Invites2 = round($_GET['invites2']);
            $Where[] = implode(' AND ', num_compare('Invites', $_GET['invites'], $Invites1, $Invites2));
        }

        if (strlen($_GET['invitees1']) && $_GET['invitees'] != 'off') {
            $Invitees1 = round($_GET['invitees1']);
            $Invitees2 = round($_GET['invitees2']);
            $Having[] = implode(' AND ', num_compare('Invitees', $_GET['invitees'], $Invitees1, $Invitees2));
        }

        if ($_GET['disabled_invites'] == 'yes') {
            $Where[] = 'ui1.DisableInvites = \'1\'';
        } elseif ($_GET['disabled_invites'] == 'no') {
            $Where[] = 'ui1.DisableInvites = \'0\'';
        }

        if ($_GET['disabled_uploads'] == 'yes') {
            $Where[] = 'ui1.DisableUpload = \'1\'';
        } elseif ($_GET['disabled_uploads'] == 'no') {
            $Where[] = 'ui1.DisableUpload = \'0\'';
        }

        if ($_GET['join1']) {
            $Where[] = implode(' AND ', date_compare('ui1.JoinDate', $_GET['joined'], $_GET['join1'], $_GET['join2']));
        }

        if ($_GET['lastactive1']) {
            $Where[] = implode(' AND ', date_compare('um1.LastAccess', $_GET['lastactive'], $_GET['lastactive1'], $_GET['lastactive2']));
        }

        if ($_GET['ratio1']) {
            $Decimals = strlen(array_pop(explode('.', $_GET['ratio1'])));
            if (!$Decimals) {
                $Decimals = 0;
            }
            $Where[] = implode(' AND ', num_compare("ROUND(Uploaded/Downloaded,$Decimals)", $_GET['ratio'], $_GET['ratio1'], $_GET['ratio2']));
        }

        if (strlen($_GET['uploaded1'])) {
            $Upload1 = round($_GET['uploaded1']);
            $Upload2 = round($_GET['uploaded2']);
            if ($_GET['uploaded'] != 'buffer') {
                $Where[] = implode(' AND ', num_compare('ROUND(Uploaded / 1024 / 1024 / 1024)', $_GET['uploaded'], $Upload1, $Upload2));
            } else {
                $Where[] = implode(' AND ', num_compare('ROUND((Uploaded / 1024 / 1024 / 1024) - (Downloaded / 1024 / 1024 / 1023))', 'between', $Upload1 * 0.9, $Upload1 * 1.1));
            }
        }

        if (strlen($_GET['downloaded1'])) {
            $Download1 = round($_GET['downloaded1']);
            $Download2 = round($_GET['downloaded2']);
            $Where[] = implode(' AND ', num_compare('ROUND(Downloaded / 1024 / 1024 / 1024)', $_GET['downloaded'], $Download1, $Download2));
        }

        if (strlen($_GET['snatched1'])) {
            $Snatched1 = round($_GET['snatched1']);
            $Snatched2 = round($_GET['snatched2']);
            $Having[] = implode(' AND ', num_compare('Snatches', $_GET['snatched'], $Snatched1, $Snatched2));
        }

        if ($_GET['enabled'] != '') {
            $Where[] = 'um1.Enabled = '.wrap($_GET['enabled'], '=');
        }

        if ($_GET['class'] != '') {
            $Where[] = 'um1.PermissionID = '.wrap($_GET['class'], '=');
        }

        if ($_GET['secclass'] != '') {
            $Join['ul'] = ' JOIN users_levels AS ul ON um1.ID = ul.UserID ';
            $Where[] = 'ul.PermissionID = '.wrap($_GET['secclass'], '=');
        }

        if ($_GET['donor'] == 'yes') {
            $Where[] = 'ui1.Donor = \'1\'';
        } elseif ($_GET['donor'] == 'no') {
            $Where[] = 'ui1.Donor = \'0\'';
        }

        if ($_GET['warned'] == 'yes') {
            $Where[] = 'ui1.Warned IS NOT NULL';
        } elseif ($_GET['warned'] == 'no') {
            $Where[] = 'ui1.Warned IS NULL';
        }

        if ($_GET['disabled_ip']) {
            $Distinct = 'DISTINCT ';
            $Join['um2'] = ' JOIN users_main AS um2 ON um2.IP = um1.IP AND um2.Enabled = \'2\' ';
        }

        if (!empty($_GET['passkey'])) {
            $Where[] = 'um1.torrent_pass'.$Match.wrap($_GET['passkey']);
        }

        if (!empty($_GET['avatar'])) {
            $Where[] = 'ui1.Avatar'.$Match.wrap($_GET['avatar']);
        }

        if ($_GET['stylesheet'] != '') {
            $Where[] = 'ui1.StyleID = '.wrap($_GET['stylesheet'], '=');
        }

        if ($OrderTable[$_GET['order']] && $WayTable[$_GET['way']]) {
            $Order = ' ORDER BY '.$OrderTable[$_GET['order']].' '.$WayTable[$_GET['way']].' ';
        }

        //---------- Finish generating the search string

        $SQL = 'SELECT '.$Distinct.$SQL;
        $SQL .= implode(' ', $Join);

        if (count($Where)) {
            $SQL .= ' WHERE '.implode(' AND ', $Where);
        }

        if (count($Group)) {
            $SQL .= " GROUP BY " . implode(' ,', $Group);
        }

        if (count($Having)) {
            $SQL .= ' HAVING '.implode(' AND ', $Having);
        }

        $SQL .= $Order;

        if (count($Where) > 0 || count($Join) > 0 || count($Having) > 0) {
            $RunQuery = true;
        }

        list($Page, $Limit) = Format::page_limit(USERS_PER_PAGE);
        $SQL .= " LIMIT $Limit";
    } else {
        error("Your search returned no results. For privacy and security reasons, user searches must result in an exact hit. Fuzzy matches aren't allowed.");
    }
}
View::header('User search');
?>
<div>
  <form class="search_form" name="users" action="user.php" method="get">
    <input type="hidden" name="action" value="search">
    <table class="layout">
      <tr>
        <td class="label nobr">Username:</td>
        <td width="24%">
          <input type="text" name="username" size="20"
            value="<?=\Gazelle\Text::esc($_GET['username'])?>" />
        </td>
        <td class="label nobr">Joined:</td>
        <td width="24%">
          <select name="joined">
            <option value="on" <?php if ($_GET['joined'] === 'on') {
                echo ' selected="selected"';
            } ?>>On
            </option>
            <option value="before" <?php if ($_GET['joined'] === 'before') {
                echo ' selected="selected"';
            } ?>>Before
            </option>
            <option value="after" <?php if ($_GET['joined'] === 'after') {
                echo ' selected="selected"';
            } ?>>After
            </option>
            <option value="between" <?php if ($_GET['joined']==='between') {
                echo ' selected="selected"' ;
            } ?>>Between
            </option>
          </select>
          <input type="text" name="join1" size="10"
            value="<?=\Gazelle\Text::esc($_GET['join1'])?>"
            placeholder="YYYY-MM-DD" />
          <input type="text" name="join2" size="10"
            value="<?=\Gazelle\Text::esc($_GET['join2'])?>"
            placeholder="YYYY-MM-DD" />
        </td>
        <td class="label nobr">Enabled:</td>
        <td>
          <select name="enabled">
            <option value="" <?php if ($_GET['enabled'] === '') {
                echo ' selected="selected"';
            } ?>>Any
            </option>
            <option value="0" <?php if ($_GET['enabled']==='0') {
                echo ' selected="selected"' ;
            } ?>>Unconfirmed
            </option>
            <option value="1" <?php if ($_GET['enabled']==='1') {
                echo ' selected="selected"' ;
            } ?>>Enabled
            </option>
            <option value="2" <?php if ($_GET['enabled']==='2') {
                echo ' selected="selected"' ;
            } ?>>Disabled
            </option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label nobr">Email address:</td>
        <td>
          <input type="text" name="email" size="20"
            value="<?=\Gazelle\Text::esc($_GET['email'])?>" />
        </td>
        <td class="label nobr">Last active:</td>
        <td width="30%">
          <select name="lastactive">
            <option value="on" <?php if ($_GET['lastactive'] === 'on') {
                echo ' selected="selected"';
            } ?>>On
            </option>
            <option value="before" <?php if ($_GET['lastactive'] === 'before') {
                echo ' selected="selected"';
            } ?>>Before
            </option>
            <option value="after" <?php if ($_GET['lastactive'] === 'after') {
                echo ' selected="selected"';
            } ?>>After
            </option>
            <option value="between" <?php if ($_GET['lastactive']==='between') {
                echo ' selected="selected"' ;
            } ?>
              >Between
            </option>
          </select>
          <input type="text" name="lastactive1" size="10"
            value="<?=\Gazelle\Text::esc($_GET['lastactive1'])?>"
            placeholder="YYYY-MM-DD" />
          <input type="text" name="lastactive2" size="10"
            value="<?=\Gazelle\Text::esc($_GET['lastactive2'])?>"
            placeholder="YYYY-MM-DD" />
        </td>
        <td class="label nobr">Primary class:</td>
        <td>
          <select name="class">
            <option value="" <?php if ($_GET['class']==='') {
                echo ' selected="selected"' ;
            } ?>>Any
            </option>
            <?php foreach ($ClassLevels as $Class) {
                if ($Class['Secondary']) {
                    continue;
                } ?>
            <option value="<?=$Class['ID'] ?>"
              <?php
                          if ($_GET['class']===$Class['ID']) {
                              echo ' selected="selected"' ;
                          } ?>><?=\Gazelle\Text::limit($Class['Name'], 10).' ('.$Class['Level'].')'?>
            </option>
            <?php
            } ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label tooltip nobr"
          title="To fuzzy search (default) for a block of addresses (e.g. 55.66.77.*), enter &quot;55.66.77.&quot; without the quotes">
          IP address:</td>
        <td>
          <input type="text" name="ip" size="20"
            value="<?=\Gazelle\Text::esc($_GET['ip'])?>" />
        </td>
        <td class="label nobr">Locked Account:</td>
        <td>
          <select name="lockedaccount">
            <option value="any" <?php if ($_GET['lockedaccount']=='any') {
                echo ' selected="selected"' ;
            } ?>>Any
            </option>
            <option value="locked" <?php if ($_GET['lockedaccount']=='locked') {
                echo ' selected="selected"' ;
            } ?>>Locked
            </option>
            <option value="unlocked" <?php if ($_GET['lockedaccount']=='unlocked') {
                echo ' selected="selected"' ;
            } ?>
              >Unlocked
            </option>
          </select>
        </td>
        <td class="label nobr">Secondary class:</td>
        <td>
          <select name="secclass">
            <option value="" <?php if ($_GET['secclass']==='') {
                echo ' selected="selected"' ;
            } ?>>Any
            </option>
            <?php $Secondaries = [];
// Neither level nor ID is particularly useful when searching secondary classes, so let's do some
// kung-fu to sort them alphabetically.
$fnc = function ($Class1, $Class2) {
    return strcmp($Class1['Name'], $Class2['Name']);
};
foreach ($ClassLevels as $Class) {
    if (!$Class['Secondary']) {
        continue;
    }
    $Secondaries[] = $Class;
}
usort($Secondaries, $fnc);
foreach ($Secondaries as $Class) {
    ?>
            <option value="<?=$Class['ID'] ?>"
              <?php
            if ($_GET['secclass']===$Class['ID']) {
                echo ' selected="selected"' ;
            } ?>><?=\Gazelle\Text::limit($Class['Name'], 20)?>
            </option>
            <?php
} ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label nobr">Ratio:</td>
        <td width="30%">
          <select name="ratio">
            <option value="equal" <?php if ($_GET['ratio'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equal
            </option>
            <option value="above" <?php if ($_GET['ratio'] === 'above') {
                echo ' selected="selected"';
            } ?>>Above
            </option>
            <option value="below" <?php if ($_GET['ratio'] === 'below') {
                echo ' selected="selected"';
            } ?>>Below
            </option>
            <option value="between" <?php if ($_GET['ratio']==='between') {
                echo ' selected="selected"' ;
            } ?>>Between
            </option>
          </select>
          <input type="text" name="ratio1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['ratio1'])?>" />
          <input type="text" name="ratio2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['ratio2'])?>" />
        </td>
        <td class="label nobr">Donor:</td>
        <td>
          <select name="donor">
            <option value="" <?php if ($_GET['donor'] === '') {
                echo ' selected="selected"';
            } ?>>Any
            </option>
            <option value="yes" <?php if ($_GET['donor']==='yes') {
                echo ' selected="selected"' ;
            } ?>>Yes
            </option>
            <option value="no" <?php if ($_GET['donor'] === 'no') {
                echo ' selected="selected"';
            } ?>>No
            </option>
          </select>
        </td>
      </tr>
      <tr>
        <?php if (check_perms('users_mod')) { ?>
        <td class="label nobr">Staff notes:</td>
        <td>
          <input type="text" name="comment" size="20"
            value="<?=\Gazelle\Text::esc($_GET['comment'])?>" />
        </td>
        <?php } else { ?>
        <td class="label nobr"></td>
        <td>
        </td>
        <?php } ?>
        <td class="label tooltip nobr" title="Units are in gibibytes (the base 2 sibling of gigabytes)">Uploaded:</td>
        <td width="30%">
          <select name="uploaded">
            <option value="equal" <?php if ($_GET['uploaded'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equal
            </option>
            <option value="above" <?php if ($_GET['uploaded'] === 'above') {
                echo ' selected="selected"';
            } ?>>Above
            </option>
            <option value="below" <?php if ($_GET['uploaded'] === 'below') {
                echo ' selected="selected"';
            } ?>>Below
            </option>
            <option value="between" <?php if ($_GET['uploaded']==='between') {
                echo ' selected="selected"' ;
            } ?>>Between
            </option>
            <option value="buffer" <?php if ($_GET['uploaded'] === 'buffer') {
                echo ' selected="selected"';
            } ?>>Buffer
            </option>
          </select>
          <input type="text" name="uploaded1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['uploaded1'])?>" />
          <input type="text" name="uploaded2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['uploaded2'])?>" />
        </td>
        <td class="label nobr">Warned:</td>
        <td>
          <select name="warned">
            <option value="" <?php if ($_GET['warned'] === '') {
                echo ' selected="selected"';
            } ?>>Any
            </option>
            <option value="yes" <?php if ($_GET['warned']==='yes') {
                echo ' selected="selected"' ;
            } ?>>Yes
            </option>
            <option value="no" <?php if ($_GET['warned'] === 'no') {
                echo ' selected="selected"';
            } ?>>No
            </option>
          </select>
        </td>
      </tr>

      <tr>
        <td class="label nobr"># of invites:</td>
        <td>
          <select name="invites">
            <option value="equal" <?php if ($_GET['invites'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equal
            </option>
            <option value="above" <?php if ($_GET['invites'] === 'above') {
                echo ' selected="selected"';
            } ?>>Above
            </option>
            <option value="below" <?php if ($_GET['invites'] === 'below') {
                echo ' selected="selected"';
            } ?>>Below
            </option>
            <option value="between" <?php if ($_GET['invites']==='between') {
                echo ' selected="selected"' ;
            } ?>>Between
            </option>
          </select>
          <input type="text" name="invites1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['invites1'])?>" />
          <input type="text" name="invites2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['invites2'])?>" />
        </td>
        <td class="label tooltip nobr" title="Units are in gibibytes (the base 2 sibling of gigabytes)">Downloaded:</td>
        <td width="30%">
          <select name="downloaded">
            <option value="equal" <?php if ($_GET['downloaded'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equal
            </option>
            <option value="above" <?php if ($_GET['downloaded'] === 'above') {
                echo ' selected="selected"';
            } ?>>Above
            </option>
            <option value="below" <?php if ($_GET['downloaded'] === 'below') {
                echo ' selected="selected"';
            } ?>>Below
            </option>
            <option value="between" <?php if ($_GET['downloaded']==='between') {
                echo ' selected="selected"' ;
            } ?>
              >Between
            </option>
          </select>
          <input type="text" name="downloaded1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['downloaded1'])?>" />
          <input type="text" name="downloaded2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['downloaded2'])?>" />
        </td>
        <td class="label tooltip nobr" title="Only display users that have a disabled account linked by IP address">
          <label for="disabled_ip">Disabled accounts<br>linked by IP:</label>
        </td>
        <td>
          <input type="checkbox" name="disabled_ip" id="disabled_ip" <?php if ($_GET['disabled_ip']) {
              echo ' checked="checked"' ;
          } ?> />
        </td>
      </tr>

      <tr>
        <td class="label nobr">Disabled invites:</td>
        <td>
          <select name="disabled_invites">
            <option value="" <?php if ($_GET['disabled_invites'] === '') {
                echo ' selected="selected"';
            } ?>>Any
            </option>
            <option value="yes" <?php if ($_GET['disabled_invites']==='yes') {
                echo ' selected="selected"' ;
            } ?>>Yes
            </option>
            <option value="no" <?php if ($_GET['disabled_invites'] === 'no') {
                echo ' selected="selected"';
            } ?>>No
            </option>
          </select>
        </td>
        <td class="label nobr">Snatched:</td>
        <td width="30%">
          <select name="snatched">
            <option value="equal" <?php if (isset($_GET['snatched']) && $_GET['snatched'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equal
            </option>
            <option value="above" <?php if (isset($_GET['snatched']) && $_GET['snatched'] === 'above') {
                echo ' selected="selected"';
            } ?>>Above
            </option>
            <option value="below" <?php if (isset($_GET['snatched']) && $_GET['snatched'] === 'below') {
                echo ' selected="selected"';
            } ?>>Below
            </option>
            <option value="between" <?php if (isset($_GET['snatched']) && $_GET['snatched']==='between') {
                echo ' selected="selected"' ;
            } ?>>Between
            </option>
            <option value="off" <?php if (!isset($_GET['snatched']) || $_GET['snatched'] === 'off') {
                echo ' selected="selected"';
            } ?>>Off
            </option>
          </select>
          <input type="text" name="snatched1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['snatched1'])?>" />
          <input type="text" name="snatched2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['snatched2'])?>" />
        </td>
        <td class="label nobr">Disabled uploads:</td>
        <td>
          <select name="disabled_uploads">
            <option value="" <?php if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads'] === '') {
                echo ' selected="selected"';
            } ?>>Any
            </option>
            <option value="yes" <?php if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads']==='yes') {
                echo ' selected="selected"' ;
            } ?>>Yes
            </option>
            <option value="no" <?php if (isset($_GET['disabled_uploads']) && $_GET['disabled_uploads'] === 'no') {
                echo ' selected="selected"';
            } ?>>No
            </option>
          </select>
        </td>
      </tr>
      <tr>
        <td width="30%" class="label nobr"># of invitees:</td>
        <td>
          <select name="invitees">
            <option value="equal" <?=isset($_GET['invitees']) && $_GET['invitees'] == 'equal' ? 'selected' : ''?>>Equal
            </option>
            <option value="above" <?=isset($_GET['invitees']) && $_GET['invitees'] == 'above' ? 'selected' : ''?>>Above
            </option>
            <option value="below" <?=isset($_GET['invitees']) && $_GET['invitees'] == 'below' ? 'selected' : ''?>>Below
            </option>
            <option value="between" <?=isset($_GET['invitees']) && $_GET['invitees'] == 'between' ? 'selected' : ''?>>Between
            </option>
            <option value="off" <?=!isset($_GET['invitees']) || $_GET['invitees'] == 'off' ? 'selected' : ''?>>Off
            </option>
          </select>
          <input type="text" name="invitees1" size="6"
            value="<?=\Gazelle\Text::esc($_GET['invitees1'])?>" />
          <input type="text" name="invitees2" size="6"
            value="<?=\Gazelle\Text::esc($_GET['invitees2'])?>" />
        </td>
        <td class="label nobr">Passkey:</td>
        <td>
          <input type="text" name="passkey" size="20"
            value="<?=\Gazelle\Text::esc($_GET['passkey'])?>" />
        </td>
        <td class="label nobr">Tracker IP:</td>
        <td>
          <input type="text" name="tracker_ip" size="20"
            value="<?=\Gazelle\Text::esc($_GET['tracker_ip'])?>" />
        </td>
      </tr>

      <tr>
        <td class="label tooltip nobr"
          title="Supports partial URL matching, e.g. entering &quot;&#124;https://whatimg.com&quot; will search for avatars hosted on https://whatimg.com">
          Avatar URL:</td>
        <td>
          <input type="text" name="avatar" size="20"
            value="<?=\Gazelle\Text::esc($_GET['avatar'])?>" />
        </td>
        <td class="label nobr">Stylesheet:</td>
        <td>
          <select name="stylesheet" id="stylesheet">
            <option value="">Any</option>
            <?php foreach ($Stylesheets as $Style) { ?>
            <option value="<?=$Style['ID']?>"
              <?Format::selected('stylesheet', $Style['ID'])?>><?=$Style['ProperName']?>
            </option>
            <?php } ?>
          </select>
        </td>
        <td class="label tooltip nobr" title="Two-letter codes as defined in ISO 3166-1 alpha-2">Country code:</td>
        <td width="30%">
          <select name="cc_op">
            <option value="equal" <?php if ($_GET['cc_op'] === 'equal') {
                echo ' selected="selected"';
            } ?>>Equals
            </option>
            <option value="not_equal" <?php if ($_GET['cc_op']==='not_equal') {
                echo ' selected="selected"' ;
            } ?>>Not
              equal
            </option>
          </select>
          <input type="text" name="cc" size="2"
            value="<?=\Gazelle\Text::esc($_GET['cc'])?>" />
        </td>
      </tr>

      <tr>
        <td class="label nobr">Search type:</td>
        <td>
          <ul class="options_list nobullet">
            <li>
              <input type="radio" name="matchtype" id="strict_match_type" value="strict" <?php if ($_GET['matchtype']=='strict' || !$_GET['matchtype']) {
                  echo ' checked="checked"' ;
              } ?> />
              <label class="tooltip"
                title="A &quot;strict&quot; search uses no wildcards in search fields, and it is analogous to &#96;grep -E &quot;&circ;SEARCHTERM&#36;&quot;&#96;"
                for="strict_match_type">Strict</label>
            </li>
            <li>
              <input type="radio" name="matchtype" id="fuzzy_match_type" value="fuzzy" <?php if ($_GET['matchtype']=='fuzzy' || !$_GET['matchtype']) {
                  echo ' checked="checked"' ;
              } ?> />
              <label class="tooltip"
                title="A &quot;fuzzy&quot; search automatically prepends and appends wildcards to search strings, except for IP address searches, unless the search string begins or ends with a &quot;&#124;&quot; (pipe). It is analogous to a vanilla grep search (except for the pipe stuff)."
                for="fuzzy_match_type">Fuzzy</label>
            </li>
            <li>
              <input type="radio" name="matchtype" id="regex_match_type" value="regex" <?php if ($_GET['matchtype']=='regex') {
                  echo ' checked="checked"' ;
              } ?> />
              <label class="tooltip" title="A &quot;regex&quot; search uses MySQL's regular expression syntax."
                for="regex_match_type">Regex</label>
            </li>
          </ul>
        </td>
        <td class="label nobr">Order:</td>
        <td class="nobr">
          <select name="order">
            <?php
                        foreach (array_shift($OrderVals) as $Cur) { ?>
            <option value="<?=$Cur?>" <?php if (isset($_GET['order']) &&
                          $_GET['order']==$Cur || (!isset($_GET['order']) && $Cur=='Joined')) {
                echo ' selected="selected"' ;
            } ?>
              ><?=$Cur?>
            </option>
            <?php } ?>
          </select>
          <select name="way">
            <?php foreach (array_shift($WayVals) as $Cur) { ?>
            <option value="<?=$Cur?>" <?php if (isset($_GET['way']) &&
              $_GET['way']==$Cur || (!isset($_GET['way']) && $Cur=='Descending')) {
                echo ' selected="selected"' ;
            } ?>
              ><?=$Cur?>
            </option>
            <?php } ?>
          </select>
        </td>
        <td class="label nobr"># of emails:</td>
        <td>
          <select name="emails_opt">
            <option value="equal" <?php if ($_GET['emails_opt']==='equal') {
                echo ' selected="selected"' ;
            } ?>>Equal
            </option>
            <option value="above" <?php if ($_GET['emails_opt']==='above') {
                echo ' selected="selected"' ;
            } ?>>Above
            </option>
            <option value="below" <?php if ($_GET['emails_opt']==='below') {
                echo ' selected="selected"' ;
            } ?>>Below
            </option>
          </select>
          <input type="text" name="email_cnt" size="6"
            value="<?=\Gazelle\Text::esc($_GET['email_cnt'])?>" />
        </td>
      </tr>
      <tr>
        <td colspan="6" class="center">
          <input type="submit" value="Search users">
        </td>
      </tr>
    </table>
  </form>
</div>
<?php
if ($RunQuery) {
    if (!empty($_GET['ip'])) {
        $app->dbOld->query("SELECT ID, IP FROM users_main");
        while (list($ID, $EncIP) = $app->dbOld->next_record()) {
            $IPs[] = $ID.", '".Crypto::decrypt($EncIP)."'";
        }
        $app->dbOld->query("CREATE TEMPORARY TABLE users_ips_decrypted (ID INT(10) UNSIGNED NOT NULL, IP VARCHAR(45) NOT NULL, PRIMARY KEY (ID,IP)) ENGINE=MEMORY");
        $app->dbOld->query("INSERT IGNORE INTO users_ips_decrypted (ID, IP) VALUES(".implode("),(", $IPs).")");
    }
    if (!empty($_GET['email'])) {
        $app->dbOld->query("SELECT ID, Email FROM users_main");
        while (list($ID, $EncEmail) = $app->dbOld->next_record()) {
            $Emails[] = $ID.", '".Crypto::decrypt($EncEmail)."'";
        }
        $app->dbOld->query("CREATE TEMPORARY TABLE users_emails_decrypted (ID INT(10) UNSIGNED NOT NULL, Email VARCHAR(255) NOT NULL, PRIMARY KEY (ID,Email)) ENGINE=MEMORY");
        $app->dbOld->query("INSERT IGNORE INTO users_emails_decrypted (ID, Email) VALUES(".implode("),(", $Emails).")");
    }
    $Results = $app->dbOld->query($SQL);
    $app->dbOld->query('SELECT FOUND_ROWS()');
    list($NumResults) = $app->dbOld->next_record();
    if (!empty($_GET['ip'])) {
        $app->dbOld->query("DROP TABLE users_ips_decrypted");
    }
    if (!empty($_GET['email'])) {
        $app->dbOld->query("DROP TABLE users_emails_decrypted");
    }
    $app->dbOld->set_query_id($Results);
} else {
    $app->dbOld->query('SET @nothing = 0');
    $NumResults = 0;
}
?>
<div class="linkbox">
  <?php
$Pages = Format::get_pages($Page, $NumResults, USERS_PER_PAGE, 11);
echo $Pages;
?>
</div>
<div class="box pad center">
  <h2><?=\Gazelle\Text::float($NumResults)?> results</h2>
  <table width="100%">
    <tr class="colhead">
      <td>Username</td>
      <td>Ratio</td>
      <td>IP address</td>
      <td>Email</td>
      <td>Joined</td>
      <td>Last seen</td>
      <td>Upload</td>
      <td>Download</td>
      <td>Downloads</td>
      <td>Snatched</td>
      <td>Invites</td>
      <?php if (isset($_GET['invitees']) && $_GET['invitees'] != 'off') { ?>
      <td>Invitees</td>
      <?php } ?>
    </tr>
    <?php
while (list($UserID, $Username, $Uploaded, $Downloaded, $Snatched, $Invitees, $Class, $Email, $Enabled, $IP, $Invites, $DisableInvites, $Warned, $Donor, $JoinDate, $LastAccess) = $app->dbOld->next_record()) {
    $IP = apcu_exists('DBKEY') ? Crypto::decrypt($IP) : '[Encrypted]';
    $Email = apcu_exists('DBKEY') ? Crypto::decrypt($Email) : '[Encrypted]'; ?>
    <tr>
      <td><?=User::format_username($UserID, true, true, true, true)?>
      </td>
      <td><?=Format::get_ratio_html($Uploaded, $Downloaded)?>
      </td>
      <td style="word-break: break-all;"><?=\Gazelle\Text::esc($IP)?>
      </td>
      <td><?=\Gazelle\Text::esc($Email)?>
      </td>
      <td><?=time_diff($JoinDate)?>
      </td>
      <td><?=time_diff($LastAccess)?>
      </td>
      <td><?=Format::get_size($Uploaded)?>
      </td>
      <td><?=Format::get_size($Downloaded)?>
      </td>
      <?php $app->dbOld->query("
        SELECT COUNT(ud.UserID)
        FROM users_downloads AS ud
          JOIN torrents AS t ON t.ID = ud.TorrentID
        WHERE ud.UserID = $UserID");
    list($Downloads) = $app->dbOld->next_record();
    $app->dbOld->set_query_id($Results); ?>
      <td><?=\Gazelle\Text::float((int)$Downloads)?>
      </td>
      <td><?=(is_numeric($Snatched) ? \Gazelle\Text::float($Snatched) : \Gazelle\Text::esc($Snatched))?>
      </td>
      <td>
        <?php if ($DisableInvites) {
            echo 'X';
        } else {
            echo \Gazelle\Text::float($Invites);
        } ?>
      </td>
      <?php if (isset($_GET['invitees']) && $_GET['invitees'] != 'off') { ?>
      <td><?=\Gazelle\Text::float($Invitees)?>
      </td>
      <?php } ?>
    </tr>
    <?php
}
?>
  </table>
</div>

<div class="linkbox">
  <?=$Pages?>
</div>

<?php View::footer();
