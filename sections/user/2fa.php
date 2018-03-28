<?
require_once(SERVER_ROOT.'/classes/twofa.class.php');
require_once(SERVER_ROOT.'/classes/u2f.class.php');

$TwoFA = new TwoFactorAuth(SITE_NAME);
$U2F = new u2f\U2F('https://'.SITE_DOMAIN);
if ($Type = $_POST['type'] ?? false) {
  if ($Type == 'PGP') {
    if (!empty($_POST['publickey']) && (strpos($_POST['publickey'], 'BEGIN PGP PUBLIC KEY BLOCK') === false || strpos($_POST['publickey'], 'END PGP PUBLIC KEY BLOCK') === false)) {
      $Error = "Invalid PGP public key";
    } else {
      $DB->query("
        UPDATE users_main
        SET PublicKey = '".db_string($_POST['publickey'])."'
        WHERE ID = $UserID");
      $Message = 'Public key '.(empty($_POST['publickey']) ? 'removed' : 'updated') ;
    }
  }
  if ($Type == '2FA-E') {
    if ($TwoFA->verifyCode($_POST['twofasecret'], $_POST['twofa'])) {
      $DB->query("
        UPDATE users_main
        SET TwoFactor='".db_string($_POST['twofasecret'])."'
        WHERE ID = $UserID");
      $Message = "Two Factor Authentication enabled";
    } else {
      $Error = "Invalid 2FA verification code";
    }
  }
  if ($Type == '2FA-D') {
    $DB->query("
      UPDATE users_main
      SET TwoFactor = NULL
      WHERE ID = $UserID");
    $Message = "Two Factor Authentication disabled";
  }
  if ($Type == 'U2F-E') {
    try {
      $U2FReg = $U2F->doRegister(json_decode($_POST['u2f-request']), json_decode($_POST['u2f-response']));
      $DB->query("
      INSERT INTO u2f
      (UserID, KeyHandle, PublicKey, Certificate, Counter, Valid)
      Values ($UserID, '".db_string($U2FReg->keyHandle)."', '".db_string($U2FReg->publicKey)."', '".db_string($U2FReg->certificate)."', '".db_string($U2FReg->counter)."', '1')");
      $Message = "U2F token registered";
    } catch(Exception $e) {
      $Error = "Failed to register U2F token";
    }
  }
  if ($Type == 'U2F-D') {
    $DB->query("
      DELETE FROM u2f
      WHERE UserID = $UserID");
    $Message = 'U2F tokens deregistered';
  }
}

$U2FRegs = [];
$DB->query("
  SELECT KeyHandle, PublicKey, Certificate, Counter
  FROM u2f
  WHERE UserID = $UserID");
// Needs to be an array of objects, so we can't use to_array()
while (list($KeyHandle, $PublicKey, $Certificate, $Counter) = $DB->next_record()) {
  $U2FRegs[] = (object)['keyHandle'=>$KeyHandle, 'publicKey'=>$PublicKey, 'certificate'=>$Certificate, 'counter'=>$Counter];
}

$DB->query("
  SELECT PublicKey, TwoFactor
  FROM users_main
  WHERE ID = $UserID");
list($PublicKey, $TwoFactor) = $DB->next_record();

list($U2FRequest, $U2FSigs) = $U2F->getRegisterData($U2FRegs);

View::show_header("Two-factor Authentication Settings", 'u2f');
?>
<h2>Additional Account Security Options</h2>
<div class="thin">
<? if (isset($Message)) { ?>
    <div class="alertbar"><?=$Message?></div>
<? }
   if (isset($Error)) { ?>
    <div class="alertbar error"><?=$Error?></div>
<? } ?>
    <div class="box">
      <div class="head">
        <strong>PGP Public Key</strong>
      </div>
      <div class="pad">
  <? if (empty($PublicKey)) {
       if (!empty($TwoFactor) || sizeof($U2FRegs) > 0) { ?>
        <strong class="important_text">You have a form of 2FA enabled but no PGP key associated with your account. If you lose access to your 2FA device, you will permanently lose access to your account.</strong>
  <?   } ?>
        <p>When setting up any form of second factor authentication, it is strongly recommended that you add your PGP public key as a form of secure recovery in the event that you lose access to your second factor device.</p>
        <p>After adding a PGP public key to your account, you will be able to disable your account's second factor protection by solving a challenge that only someone with your private key could solve.</p>
        <p>Additionally, being able to solve such a challenge when given manually by staff will suffice to provide proof of ownership of your account, provided no revocation certificate has been published for your key.</p>
        <p>Before adding your PGP public key, please make sure that you have taken the necessary precautions to protect it from loss (backup) or theft (revocation certificate).</p>
  <? } else { ?>
        <p>The PGP public key associated with your account is shown below.</p>
        <p>This key can be used to create challenges that are only solvable by the holder of the related private key. Successfully solving these challenges is necessary for disabling any form of second factor authentication or proving ownership of this account to staff when you are unable to login.</p>
  <? } ?>
        <form method="post">
          <input type="hidden" name="type" value="PGP">
          Public Key:
          <br>
          <textarea name="publickey" id="publickey" spellcheck="false" cols="64" rows="8"><?=display_str($PublicKey)?></textarea>
          <br>
          <button type="submit" name="type" value="PGP">Update Public Key</button>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="head">
        <strong>Two-Factor Authentication (2FA-TOTP)</strong>
      </div>
      <div class="pad">
<?    $TwoFASecret = empty($TwoFactor) ? $TwoFA->createSecret() : $TwoFactor;
      if (empty($TwoFactor)) {
        if (sizeof($U2FRegs) == 0) { ?>
          <p>Two Factor Authentication is not currently enabled for this account.</p>
          <p>To enable it, add the secret key below to your 2FA client either manually or by scanning the QR code, then enter a verification code generated by your 2FA client and click the "Enable 2FA" button.</p>
          <form method="post">
            <input type="text" size="60" name="twofasecret" id="twofasecret" value="<?=$TwoFASecret?>" readonly><br>
            <img src="<?=$TwoFA->getQRCodeImageAsDataUri(SITE_NAME.':'.$LoggedUser['Username'], $TwoFASecret)?>"><br>
            <input type="text" size="20" maxlength="6" pattern="[0-9]{0,6}" name="twofa" id="twofa" placeholder="Verification Code" autocomplete="off"><br><br>
            <button type="submit" name="type" value="2FA-E">Enable 2FA</button>
          </form>
<?      } else { ?>
          <p>Two Factor Authentication is not currently enabled for this account.</p>
          <p>To enable 2FA, you must first disable U2F below.</p>
<?      }
      } else {?>
        <form method="post">
          <input type="hidden" name="type" value="2FA-D">
          <p>2FA is enabled for this account with the following secret:</p>
          <input type="text" size="20" name="twofasecret" id="twofasecret" value="<?=$TwoFASecret?>" readonly><br>
          <img src="<?=$TwoFA->getQRCodeImageAsDataUri(SITE_NAME.':'.$LoggedUser['Username'], $TwoFASecret)?>"><br><br>
          <p>To disable 2FA, click the button below.</p>
          <button type="submit" name="type" value="2FA-D">Disable 2FA</button>
        </form>
<?    } ?>
      </div>
    </div>
    <div class="box">
      <div class="head">
        <strong>Universal Two Factor (FIDO U2F)</strong>
      </div>
      <div class="pad">
<?    if (sizeof($U2FRegs) == 0) { ?>
<?      if (empty($TwoFactor)) { ?>
          <form method="post" id="u2f_register_form">
            <input type="hidden" name="u2f-request" value='<?=json_encode($U2FRequest)?>'>
            <input type="hidden" name="u2f-sigs" value='<?=json_encode($U2FSigs)?>'>
            <input type="hidden" name="u2f-response">
            <input type="hidden" value="U2F-E">
          </form>
          <p>Universal Two Factor is not currently enabled for this account.</p>
          <p>To enable Universal Two Factor, plug in your U2F token and press the button on it.</p>
<?      } else { ?>
          <p>Universal Two Factor is not currently enabled for this account.</p>
          <p>To enable Universal Two Factor, you must first disable normal 2FA above.</p>
<?      } ?>
<?    } else { ?>
        <form method="post" id="u2f_register_form">
          <input type="hidden" name="u2f-request" value='<?=json_encode($U2FRequest)?>'>
          <input type="hidden" name="u2f-sigs" value='<?=json_encode($U2FSigs)?>'>
          <input type="hidden" name="u2f-response">
          <input type="hidden" value="U2F-E">
          <p>Universal Two Factor is enabled.</p>
          <p>To add an additional U2F token, plug it in and press the button on it</p>
          <p>To disable U2F completely and deregister all tokens, press the button below</p>
          <button type="submit" name="type" value="U2F-D">Disable U2F</button>
        </form>
<?    } ?>
      </div>
    </div>
</div>
<?
View::show_footer();
?>
