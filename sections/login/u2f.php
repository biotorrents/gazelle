<?
if (!empty($LoggedUser['ID'])) {
  header('Location: login.php');
  die();
}
if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($U2FRegs)) {
  header('Location: login.php');
  die();
}

$U2FReq = json_encode($U2F->getAuthenticateData($U2FRegs));

View::show_header('U2F Authentication'); ?>

<form id="u2f_sign_form" action="login.php" method="post">
  <input type="hidden" name="username" value="<?=htmlspecialchars($_POST['username'])?>">
  <input type="hidden" name="password" value="<?=htmlspecialchars($_POST['password'])?>">
  <input type="hidden" name="u2f-request" value='<?=$U2FReq?>'>
  <input type="hidden" name="u2f-response">
</form>

This account is protected by a Universal Two Factor token. To continue logging in, please insert your U2F token and press it if necessary.

<? View::show_footer(); ?>
