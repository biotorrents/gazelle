<?
if (!empty($LoggedUser['ID'])) {
  header('Location: login.php');
  die();
}

View::show_header('Authorize Location');

if (isset($_REQUEST['act'])) {
?>

Your location is now authorized to access this account.<br /><br />
Click <a href="login.php">here</a> to login again.

<? } else { ?>

This appears to be the first time you've logged in from this location.<br /><br />

As a security measure to ensure that you are really the owner of this account,<br />
an email has been sent to the address in your profile settings. Please<br />
click the link contained in that email to allow access from<br />
your location, and then log in again.

<? }
View::show_footer(); ?>
