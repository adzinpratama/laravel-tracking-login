<?php

namespace Adzinpratama\TrackingLogin\Listeners;

use Adzinpratama\TrackingLogin\CurrentLogin;
use Adzinpratama\TrackingLogin\Events\LoggedIn;
use Adzinpratama\TrackingLogin\Factories\LoginFactory;
use Adzinpratama\TrackingLogin\Logins;
use Adzinpratama\TrackingLogin\RequestContext;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class SanctumEventSubscriber
{
    /**
     * Handle personal access token creation event.
     *
     * @throws \Exception
     */
    public function handlePersonalAccessTokenAuthentication(TokenAuthenticated $event): void
    {
        if (Logins::tracked($event->token->tokenable)) {
            /**
             * @var CurrentLogin $login
             *  */
            $login = app(CurrentLogin::class);
            $login->auth = $event->token->tokenable;
            $login->token = $event->token;
            $login->loadCurrentLogin();

            Logins::updateLastActivity();
        }
    }

    /**
     * Handle personal access token creation event.
     *
     * @throws \Exception
     */
    public function handlePersonalAccessTokenCreation(PersonalAccessToken $personalAccessToken): void
    {
        // Get the authenticated model
        $model = $personalAccessToken->tokenable;

        if (Logins::tracked($model)) {

            $sanctumTokenNamePattern = Config::get('logins.sanctum_token_name_regex');

            if (
                ! empty($sanctumTokenNamePattern)
                && ! Str::of($personalAccessToken->name)->isMatch($sanctumTokenNamePattern)
            ) {
                return;
            }

            // Get as much information as possible about the request
            $context = new RequestContext($personalAccessToken->name);

            // Build a new login
            $login = LoginFactory::buildFromSanctumToken($context, $personalAccessToken);

            // Set the expiration date
            $login->expiresAt($personalAccessToken->expires_at);

            // Attach the login to the model and save it
            $model->logins()->save($login);

            // Dispatch event
            event(new LoggedIn($model, $context));
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            TokenAuthenticated::class,
            [SanctumEventSubscriber::class, 'handlePersonalAccessTokenAuthentication']
        );

        $events->listen(
            'eloquent.created: ' . Sanctum::personalAccessTokenModel(),
            [SanctumEventSubscriber::class, 'handlePersonalAccessTokenCreation']
        );
    }
}
