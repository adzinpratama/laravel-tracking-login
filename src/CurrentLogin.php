<?php

namespace Adzinpratama\TrackingLogin;

use Adzinpratama\TrackingLogin\Models\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CurrentLogin
{
    public ?Login $currentLogin = null;

    public function __construct()
    {
        $this->loadCurrentLogin();
    }

    public function loadCurrentLogin(): void
    {
        $user = Auth::user();
        if ($user && Logins::tracked($user) && ! $this->currentLogin) {
            if ($user->isAuthenticatedBySession()) {

                $this->currentLogin = $user->logins()
                    ->where('session_id', session()->getId())
                    ->first();
            } elseif (Config::get('logins.sanctum_token_tracking') && $user->isAuthenticatedBySanctumToken()) {

                $this->currentLogin = $this->logins()
                    ->where('personal_access_token_id', $user->currentAccessToken()->getKey())
                    ->first();
            }
        }
    }
}
