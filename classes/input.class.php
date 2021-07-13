<?php
declare(strict_types=1);

/**
 * Input class
 * 
 * An attempt to normalize and secure form inputs.
 */

class Input {

    /**
     * passphrase
     */
    function passphrase(
        string $Name = 'password',
        string $ID = 'password',
        string $Placeholder = 'Passphrase',
        bool $Advice = false) {
        $ENV = ENV::go();

        # Input validation
        if (!is_string($Name) || empty($Name)) {
            error("Expected non-empty string, got \$Name = $Name in Input::passphrase.");
        }

        if (!empty($Advice) && $Advice !== true || $Advice !== false) {
            error("Expected true|false, got \$Advice = $Advice in Input::passphrase.");
        }

        $Field = <<<HTML
        <input type="password" name="$Name" id="$ID" placeholder="$Placeholder"
          minlength="$ENV->PW_MIN" maxlength="$ENV->PW_MAX"
          class="inputtext" autocomplete="off" required="required" />
HTML;

if ($Advice) {
    return $Field . $ENV->PW_ADVICE;
} else {
    return $Field;
}

    }
}