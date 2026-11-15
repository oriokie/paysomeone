<?php

/**
 * Staff office behaviour that is not part of the legacy safety switches.
 *
 * OFFICE_2FA_REQUIRED stays true on a real office. Set it false only when a
 * preview should open after the password, without an authenticator code.
 * The security page stays available so someone can still enrol.
 */
return [

    'two_factor_required' => filter_var(env('OFFICE_2FA_REQUIRED', true), FILTER_VALIDATE_BOOL),

];
