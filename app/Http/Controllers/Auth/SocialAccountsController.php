<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Socialite;
use Auth;
use App\SocialAccount;
use App\User;



class SocialAccountsController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialiate::driver($provider)->user();
        } catch (Exception $e) {
            Session::flash('Something went wrong');
            return redirect('/login');
        }


        $authUser = $this->findOrCreateUser($user, $provider);

        Auth::login($authUser, true);

        return redirect('/home');
        
    }

    public function findOrCreateUser($socialUser, $provider)
    {
        $account = SocialAccount::where('provider_name', $provider)->where('provider_id', 
        $socialUser->getId())->first();

        if ($account){
            return $account->user;
        } else {
            // User exist but has not used this particular social account, link existing user with the new social account
            $user = User::where('email', $socialUser->getEmail())->first();

            // If user does not exist, create a new user
            if (! $user) {
                $user = User::create([
                    'email'=> $socialUser->getEmail(),
                    'name' => $socialUser->getName(),
                ]);
            }

            // Link unknown social account to user
            $user->accounts()->create([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            return $user;
        }
        
    }
}
