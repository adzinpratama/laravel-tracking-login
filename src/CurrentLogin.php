<?php

namespace Adzinpratama\TrackingLogin;

use Adzinpratama\TrackingLogin\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

class CurrentLogin
{
    public ?Login $currentLogin = null;

    public ?Authenticatable $auth = null;
    public ?PersonalAccessToken $token = null;

    public function __construct()
    {
        $this->loadCurrentLogin();
    }

    public function loadCurrentLogin(): void
    {
        $user = $this->auth ?? Auth::user();

        if ($user && Logins::tracked($user) && ! $this->currentLogin) {
            if ($user->isAuthenticatedBySession()) {

                $this->currentLogin = $user->logins()
                    ->where('session_id', session()->getId())
                    ->first();
            } elseif (Config::get('logins.sanctum_token_tracking') && $user->isAuthenticatedBySanctumToken()) {

                $token = $this->token ?? $user->currentAccessToken();

                $this->currentLogin = $user->logins()
                    ->where('personal_access_token_id', $token->getKey())
                    ->first();
            }
        }
    }
}
