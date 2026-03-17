<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Rules\ReCaptcha;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function showLoginForm()
    {
        $username = old('username');
        $password = null;
        $demo_types = [];

        if (config('app.env') === 'demo') {
            $password = '123456';
            $demo_types = [
                'all_in_one' => [
                    'username' => 'admin',
                    'label' => 'All In One',
                    'description' => 'Showcases all features available in the application.',
                ],
                'super_market' => [
                    'username' => 'admin',
                    'label' => 'Super Market',
                    'description' => 'Super market and similar retail shops.',
                ],
                'pharmacy' => [
                    'username' => 'admin-pharmacy',
                    'label' => 'Pharmacy',
                    'description' => 'Products with expiry-date tracking.',
                ],
                'electronics' => [
                    'username' => 'admin-electronics',
                    'label' => 'Electronics',
                    'description' => 'Products with IMEI or serial number tracking.',
                ],
                'services' => [
                    'username' => 'admin-services',
                    'label' => 'Multi-Service Center',
                    'description' => 'Service businesses such as salons, repairs, and agencies.',
                ],
                'restaurant' => [
                    'username' => 'admin-restaurant',
                    'label' => 'Restaurant',
                    'description' => 'Restaurants, cafes, and similar businesses.',
                ],
                'superadmin' => [
                    'username' => 'superadmin',
                    'label' => 'SaaS / Superadmin',
                    'description' => 'Superadmin subscription and SaaS demo.',
                ],
                'woocommerce' => [
                    'username' => 'woocommerce_user',
                    'label' => 'WooCommerce',
                    'description' => 'WooCommerce integration demo user.',
                ],
                'essentials' => [
                    'username' => 'admin-essentials',
                    'label' => 'Essentials & HRM',
                    'description' => 'Essentials and HRM module demo.',
                ],
                'manufacturing' => [
                    'username' => 'manufacturer-demo',
                    'label' => 'Manufacturing',
                    'description' => 'Manufacturing module demo.',
                ],
            ];

            $requestedDemoType = request()->query('demo_type');
            if (!empty($requestedDemoType) && array_key_exists($requestedDemoType, $demo_types)) {
                $username = $demo_types[$requestedDemoType]['username'];
            } elseif (empty($username)) {
                $username = 'admin';
            }
        }

        return view('auth.login', compact('demo_types', 'password', 'username'));
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        $this->businessUtil->activityLog(auth()->user(), 'logout');

        request()->session()->flush();
        \Auth::logout();

        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $this->businessUtil->activityLog($user, 'login', null, [], false, $user->business_id);

        if (! $user->business->is_active) {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        } elseif ($user->status != 'active') {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        } elseif (! $user->allow_login) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]
                );
        } elseif (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]
                );
        }
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    public function validateLogin(Request $request)
    {
        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        }
       
    }

}
