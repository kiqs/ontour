<?php

use Illuminate\Support\Facades\Validator;

class UserController extends BaseController {

    /**
     * Show registration page
     */

    public function getRegistrationPage() {
        return View::make('registration');
    }

    /**
     * Show login page
     */

    public function getLoginPage() {
        return View::make('login');
    }

    /**
     * Show change password page
     */

    public function getChangePassword() {
        return View::make('changepwd');
    }

    /**
     * Change password
     */

    public function postChangePassword() {
        $rules = [
            'password'  => 'required | alpha_num | between:6,12 | confirmed',
            'password_confirmation' => 'required | alpha_num | between:6,12'
        ];

        $validator = Validator::make(Input::all(), $rules);

        if ($validator->passes()) {

            User::find(Auth::user()->id)->update([
                'password' => Hash::make(Input::get('password'))
            ]);

            return Redirect::to('users/profile')->with('result', 'success');
        }

        return Redirect::to('users/change-password')->withErrors($validator);
    }

    /**
     * Show profile page
     */

    public function getProfile() {
        return View::make('profile', array('user' => User::find(Auth::user()->id)));
    }

    /**
     * Show forgot password page
     */

    public function postForgot() {
        return View::make('forgot');
    }

    /**
     * Send reset password link to email
     */

    public function postSendResetLink() {
        $user = User::where('email', Input::get('email'))->first();

        if ($user) {
            Password::remind(Input::only('email'), function($message)
            {
                $message->subject('Password Reminder');
            });

            return View::make('confirm')->with(['result' => 'Reset link have been sent!']);
        } else {
            return View::make('forgot')->withErrors(['email' => 'Wrong email!']);
        }
    }

    /**
     * Show reset password page
     */

    public function getReset($token = null) {
        if (is_null($token)) App::abort(404);

        return View::make('reset')->with(['token' => $token]);
    }

    /**
     * Reset password
     */

    public function postReset() {
        $credentials = Input::only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $response = Password::reset($credentials, function($user, $password)
        {
            $user->password = Hash::make($password);

            $user->save();
        });

        switch ($response)
        {
            case Password::INVALID_PASSWORD:
            case Password::INVALID_TOKEN:
            case Password::INVALID_USER:
                return Redirect::back()->withErrors(['result' => Lang::get($response)]);

            case Password::PASSWORD_RESET:
                return Redirect::to('/');
        }
    }

    /**
     * Confirm registration
     */

    public function getConfirm() {
        $user = User::where('confirmation', Input::get('link'))
                    ->where('confirmed', 0)->first();

        if ($user) {
            $user->confirmed = true;
            $user->save();

            return View::make('confirm')->with(['result' => 'User confirmed! Sign in!']);
        } else {
            return View::make('confirm')->with(['result' => 'Wrong confirmation link!']);
        }
    }

    /**
     * Register
     */

    public function postRegister() {
        $validator = Validator::make(Input::all(), User::$rules);

        $confirm_str = str_random(16);

        if ($validator->passes()) {
            User::create([
                'email'        => Input::get('email'),
                'password'     => Hash::make(Input::get('password')),
                'confirmation' => $confirm_str,
                'confirmed'    => false
            ]);

            $confirm_link = 'http://ontour/users/confirm?link='.$confirm_str;

            Mail::send('emails.auth.confirmation', ['link' => $confirm_link], function($message)
            {
                $message->to(Input::get('email'), 'Guest')->subject('Welcome to ontour!');
            });

            return View::make('regsuccess');
        } else {
            return Redirect::to('users/registration-page')->withErrors($validator);
        }
    }

    /**
     * Login
     */

    public function postLogin() {
        $userData = [
            'email'     => Input::get('email'),
            'password'  => Input::get('password'),
            'confirmed' => true
        ];

        if (Auth::attempt($userData)) {
            return Redirect::intended('/');
        } else {
            return Redirect::to('users/login-page')->withErrors(['result' => 'Wrong email or password!']);
        }
    }

    /**
     * Logout
     */

    public function getLogout() {
        Auth::logout();
        return Redirect::to('users/login-page');
    }

    /**
     * Edit profile
     */

    public function postEdit() {
        $validator = Validator::make(Input::all(), User::$editRules);

        if ($validator->passes()) {

            $file = Input::file('userfile');

            if ($file) {
                $file_name = $file->getClientOriginalName();
                $file->move(base_path().'/public/uploads/user_'.Auth::user()->id.'/', $file_name);
            } else {
                $file_name = '';
            }

            User::find(Auth::user()->id)->update([
                'login'      => Input::get('login'),
                'email'      => Input::get('email'),
                'first_name' => Input::get('first_name'),
                'last_name'  => Input::get('last_name'),
                'sex'        => Input::get('sex'),
                'location'   => Input::get('location'),
                'phone'      => Input::get('phone'),
                'photo'      => $file_name
            ]);

            return Redirect::to('users/profile')->with('result', 'success');
        }

        return Redirect::to('users/profile')->withErrors($validator);
    }

}