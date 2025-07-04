<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Lib\HyipLab;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\DeviceToken;
use App\Models\Form;
use App\Models\Invest;
use App\Models\NotificationLog;
use App\Models\PromotionTool;
use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller {

    public function dashboard() {
        $user            = auth()->user();
        $totalInvest     = Invest::where('user_id', $user->id)->sum('amount');
        $totalDeposit    = Deposit::successful()->where('user_id', $user->id)->sum('amount');
        $totalWithdraw   = Withdrawal::approved()->where('user_id', $user->id)->sum('amount');
        $referralEarings = Transaction::where('user_id', $user->id)->where('remark', 'referral_commission')->sum('amount');
        $pendingDeposit  = Deposit::pending()->where('user_id', $user->id)->sum('amount');
        $pendingWithdraw = Withdrawal::pending()->where('user_id', $user->id)->sum('amount');
        
        $isShowFirstDepositAlert = $user->deposits->where('status', 1)->count() == 1 && !$user->invests->count();
        $pendingWithdrawals = Withdrawal::pending()->where('user_id', $user->id)->sum('amount');
        $pendingDeposits = Deposit::pending()->where('user_id', $user->id)->sum('amount');
        $isHoliday = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        $transactions = $user->transactions->sortByDesc('id')->take(8);

        $totalInvest = Invest::where('user_id', auth()->id())->sum('amount');
        $totalDeposit = Deposit::where('user_id', $user->id)->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
        $totalWithdraw = Withdrawal::where('user_id', $user->id)->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
        $referralEarnings = Transaction::where('remark', 'referral_commission')->where('user_id', auth()->id())->sum('amount');
        
        $nextWorkingDayRemSec = 0; 
        $data['isHoliday']      = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        $data['nextWorkingDay'] = now()->toDateString();
        if ($data['isHoliday']) {
            $data['nextWorkingDay'] = HyipLab::nextWorkingDay(24);
            $data['nextWorkingDay'] = Carbon::parse($data['nextWorkingDay'])->toDateString();
            $nextWorkingDayRemSec = abs(\Carbon\Carbon::parse($data['nextWorkingDay'])->diffInSeconds());
        }

        $notify[] = 'User Dashboard';

        return response()->json([
            'remark'  => 'dashboard',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user'              => $user,
                'total_invest'      => $totalInvest,
                'total_deposit'     => $totalDeposit,
                'total_withdrawal'  => $totalWithdraw,
                'referral_earnings' => $referralEarings,
                'pending_deposit'   => $pendingDeposit,
                'pending_withdraw'  => $pendingWithdraw,

                'is_show_first_deposit_alert'  => $isShowFirstDepositAlert,
                'pending_withdrawals'  => $pendingWithdrawals,
                'pending_deposits'  => $pendingDeposits,
                'is_holiday'  => $isHoliday,
                'transactions'  => $transactions,

                'total_invest'  => $totalInvest,
                'total_deposit'  => $totalDeposit,
                'total_withdraw'  => $totalWithdraw,
                'referral_earnings'  => $referralEarnings,

                'next_working_day_rem_sec' => $nextWorkingDayRemSec,
            ],
        ]);
    }

    public function userDataSubmit(Request $request) {
        $user = auth()->user();
        if ($user->profile_complete == Status::YES) {
            $notify[] = 'You\'ve already completed your profile';
            return responseError('already_completed', $notify);
        }

        $countryData  = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        $rule = [
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'username'     => 'required|unique:users|min:6',
            'mobile'       => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
        ];

        if (!$user->email) {
            $rule['firstname'] = 'required';
            $rule['lastname']  = 'required';
            $rule['email']     = 'required|email|unique:users';
        }

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            if ($validator->fails()) return responseError('validation_error', $validator->errors());
        }

        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = 'No special character, space or capital letters in username';
            return responseError('validation_error', $notify);
        }

        if (!$user->email) {
            $user->firstname = $request->firstname;
            $user->lastname  = $request->lastname;
            $user->email = $request->email;
            $user->ev    = gs('ev') ? Status::NO : Status::YES;
        }

        $user->country_code = $request->country_code;
        $user->mobile       = $request->mobile;
        $user->username     = $request->username;

        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip          = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code    = $request->mobile_code;

        $user->profile_complete = Status::YES;
        $user->save();

        $notify[] = 'Profile completed successfully';
        return responseSuccess('profile_completed', $notify, ['user' => $user]);
    }

    private function getReferralsFromReferral($referral)
    {
        $referralList = [];
        if($referral->relationLoaded('referrals')){

            foreach ($referral->referrals as $subReferral) {
                $referralList[] = [
                    'fullname' => $subReferral->fullname,
                    'username' => $subReferral->username,
                    'referrals' => $this->getReferralsFromReferral($subReferral),
                ];
            }
        }
    
        return $referralList;
    }

    public function myReferrals(Request $request) {
        
        $maxLevel = Referral::max('level');

        $relations = [];
        for ($label = 1; $label <= $maxLevel; $label++) {
            $relations[$label] = (@$relations[$label - 1] ? $relations[$label - 1] . '.allReferrals' : 'allReferrals');
        }

        $user = auth()->user();

        if($request->is_web == Status::YES){
            
            $dynamicRefRelation = '';

            if($maxLevel){
                for($i = 1; $i <= $maxLevel; $i++){
                    $dynamicRefRelation .= 'referrals';
                    if($i != $maxLevel){
                        $dynamicRefRelation .= '.';
                    }
                }
    
                $user = auth()->user()->load($dynamicRefRelation);
            }

            $referrals = [];
            $referrals[] = [
                'fullname' => $user->fullname,
                'username' => $user->username,
                'referrals' => $this->getReferralsFromReferral($user ?? []),
            ];

        }else{        
            $referrals = getReferees($user, $maxLevel);
        }

        $notify[] = 'My referrals list';

        return response()->json([
            'remark'  => 'referral_list',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'max_level' => $maxLevel,
                'is_show_treeview' => ($user->allReferrals->count() > 0 && $maxLevel > 0),
                'referrals' => $referrals,
                'referrer' => $user->referrer,
                'get_user' => $user
            ],
        ]);
    }

    public function balanceTransfer(Request $request) {
        $validator = Validator::make($request->all(), [
            'wallet'   => 'required|in:deposit_wallet,interest_wallet',
            'username' => 'required',
            'amount'   => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();
        if ($user->username == $request->username) {
            $notify[] = 'You cannot transfer balance to your own account';
            return response()->json([
                'remark'  => 'error_own_account',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);

        }

        $receiver = User::where('username', $request->username)->first();
        if (!$receiver) {
            $notify[] = 'Oops! Receiver not found';
            return response()->json([
                'remark'  => 'not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);

        }

        if ($user->ts) {
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = 'Wrong verification code';
                return response()->json([
                    'remark'  => 'wrong_code',
                    'status'  => 'error',
                    'message' => ['error' => $notify],
                ]);
            }
        }

        $general     = gs();
        $charge      = $general->f_charge + ($request->amount * $general->p_charge) / 100;
        $afterCharge = $request->amount + $charge;
        $wallet      = $request->wallet;

        if ($user->$wallet < $afterCharge) {
            $notify[] = 'You have no sufficient balance to this wallet';
            return response()->json([
                'remark'  => 'insufficient_balance',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $user->$wallet -= $afterCharge;
        $user->save();

        $trx1                      = getTrx();
        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = getAmount($afterCharge);
        $transaction->charge       = $charge;
        $transaction->trx_type     = '-';
        $transaction->trx          = $trx1;
        $transaction->wallet_type  = $wallet;
        $transaction->remark       = 'balance_transfer';
        $transaction->details      = 'Balance transfer to ' . $receiver->username;
        $transaction->post_balance = getAmount($user->$wallet);
        $transaction->save();

        $receiver->deposit_wallet += $request->amount;
        $receiver->save();

        $trx2                 = getTrx();
        $transaction          = new Transaction();
        $transaction->user_id = $receiver->id;
        $transaction->amount  = getAmount($request->amount);
        $transaction->charge  = 0;

        $transaction->trx_type     = '+';
        $transaction->trx          = $trx2;
        $transaction->wallet_type  = 'deposit_wallet';
        $transaction->remark       = 'balance_received';
        $transaction->details      = 'Balance received from ' . $user->username;
        $transaction->post_balance = getAmount($user->deposit_wallet);
        $transaction->save();

        notify($user, 'BALANCE_TRANSFER', [
            'amount'        => showAmount($request->amount),
            'charge'        => showAmount($charge),
            'wallet_type'   => keyToTitle($wallet),
            'post_balance'  => showAmount($user->$wallet),
            'user_fullname' => $receiver->fullname,
            'username'      => $receiver->username,
            'trx'           => $trx1,
        ]);

        notify($receiver, 'BALANCE_RECEIVE', [
            'wallet_type'  => 'Deposit wallet',
            'amount'       => showAmount($request->amount),
            'post_balance' => showAmount($receiver->deposit_wallet),
            'sender'       => $user->username,
            'trx'          => $trx2,
        ]);

        $notify[] = 'Balance transferred successfully';

        return response()->json([
            'remark'  => 'balance_transfer',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function kycForm() {
        if (auth()->user()->kv == Status::KYC_PENDING) {
            $notify[] = 'Your KYC is under review';
            return responseError('under_review', $notify);
        }
        if (auth()->user()->kv == Status::KYC_VERIFIED) {
            $notify[] = 'You are already KYC verified';
            return responseError('already_verified', $notify);
        }
        $form     = Form::where('act', 'kyc')->first();
        $notify[] = 'KYC field is below';
        return responseSuccess('kyc_form', $notify, ['form' => $form->form_data]);
    }

    public function kycSubmit(Request $request) {
        $form = Form::where('act', 'kyc')->first();
        if (!$form) {
            $notify[] = 'Invalid KYC request';
            return responseError('invalid_request', $notify);
        }
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }
        $user = auth()->user();
        foreach (@$user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
            }
        }
        $userData = $formProcessor->processFormData($request, $formData);

        $user->kyc_data             = $userData;
        $user->kyc_rejection_reason = null;
        $user->kv                   = Status::KYC_PENDING;
        $user->save();

        $notify[] = 'KYC data submitted successfully';
        return responseSuccess('kyc_submitted', $notify, ['kyc_data' => $user->kyc_data]);

    }

    public function kycData()
    {
        $user = auth()->user();
        $kycData = $user->kyc_data ?? [];
        $kycValues = [];
        foreach ($kycData as $kycInfo) {
            if (!$kycInfo->value) {
                continue;
            }
            if ($kycInfo->type == 'checkbox') {
                $value = implode(', ', $kycInfo->value);
            } elseif ($kycInfo->type == 'file') {
                $value = encrypt(getFilePath('verify') . '/' . $kycInfo->value);
            } else {
                $value = $kycInfo->value;
            }
            $kycValues[] = [
                'name' => $kycInfo->name,
                'type' => $kycInfo->type,
                'value' => $value
            ];
        }
        $notify[] = 'KYC data';
        return responseSuccess('kyc_data', $notify, ['kyc_data' => $kycValues]);
    }

    public function depositHistory() {
        $deposits = auth()->user()->deposits()->searchable(['trx']);
        $deposits = $deposits->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate());
        $notify[] = 'Deposit data';
        return responseSuccess('deposits', $notify, ['deposits' => $deposits]);
    }

    public function transactions(Request $request) {
        $remarks      = Transaction::distinct('remark')->get('remark');
        $transactions = Transaction::where('user_id', auth()->id());

        if ($request->search) {
            $transactions = $transactions->where('trx', $request->search);
        }

        if ($request->type) {
            $type         = $request->type == 'plus' ? '+' : '-';
            $transactions = $transactions->where('trx_type', $type);
        }

        if ($request->remark) {
            $transactions = $transactions->where('remark', $request->remark);
        }

        $transactions = $transactions->orderBy('id', 'desc')->paginate(getPaginate());
        $notify[]     = 'Transactions data';
        return responseSuccess('transactions', $notify, [
            'transactions' => $transactions,
            'remarks' => $remarks,
        ]);
    }

    public function submitProfile(Request $request) {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required'
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;

        $user->address = $request->address;
        $user->city    = $request->city;
        $user->state   = $request->state;
        $user->zip     = $request->zip;

        $user->save();

        $notify[] = 'Profile updated successfully';
        return responseSuccess('profile_updated', $notify);
    }

    public function submitPassword(Request $request) {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', $passwordValidation],
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password       = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = 'Password changed successfully';
            return responseSuccess('password_changed', $notify);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return responseError('validation_error', $notify);
        }
    }

    public function addDeviceToken(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            $notify[] = 'Token already exists';
            return responseError('token_exists', $notify);
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::YES;
        $deviceToken->save();

        $notify[] = 'Token saved successfully';
        return responseSuccess('token_saved', $notify);
    }

    public function show2faForm() {
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $notify[]  = '2FA Qr';
        return responseSuccess('2fa_qr', $notify, [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function create2fa(Request $request) {
        $validator = Validator::make($request->all(), [
            'secret' => 'required',
            'code'   => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code, $request->secret);
        if ($response) {
            $user->tsc = $request->secret;
            $user->ts  = Status::ENABLE;
            $user->save();

            $notify[] = 'Google authenticator activated successfully';
            return responseSuccess('2fa_qr', $notify);
        } else {
            $notify[] = 'Wrong verification code';
            return responseError('wrong_verification', $notify);
        }
    }

    public function disable2fa(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = Status::DISABLE;
            $user->save();
            $notify[] = 'Two factor authenticator deactivated successfully';
            return responseSuccess('2fa_qr', $notify);
        } else {
            $notify[] = 'Wrong verification code';
            return responseError('wrong_verification', $notify);
        }
    }

    public function pushNotifications()
    {
        $notifications = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->orderBy('id', 'desc')->paginate(getPaginate());
        $notify[]      = 'Push notifications';
        return responseSuccess('notifications', $notify, [
            'notifications' => $notifications,
        ]);
    }

    public function pushNotificationsRead($id)
    {
        $notification = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->find($id);

        if (!$notification) {
            $notify[] = 'Notification not found';
            return responseError('notification_not_found', $notify);
        }

        $notify[]                = 'Notification marked as read successfully';
        $notification->user_read = Status::YES;
        $notification->save();

        return responseSuccess('notification_read', $notify);
    }

    public function userInfo()
    {
        $notify[] = 'User information';
        return responseSuccess('user_info', $notify, ['user' => auth()->user()]);
    }

    public function accountDelete(Request $request) {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            $notify[] = 'Invalid Password';
            return responseError('invalid_password', $notify);
        }

        $user->is_deleted = Status::YES;
        $user->save();

        $user->tokens()->delete();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = $user->username . ' deleted his account.';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();

        $notify[] = 'Account deleted successfully';
        return responseSuccess('account_deleted', $notify);
    }

    public function downloadAttachment($fileHash)
    {
        try {
            $filePath = decrypt($fileHash);
        } catch (\Exception $e) {
            $notify[] = 'Invalid file';
            return responseError('invalid_failed', $notify);
        }
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title = slug(gs('site_name')) . '-attachments.' . $extension;
        try {
            $mimetype = mime_content_type($filePath);
        } catch (\Exception $e) {
            $notify[] = 'File downloaded failed';
            return responseError('download_failed', $notify);
        }
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET,');
            header('Access-Control-Allow-Headers: Content-Type');
        }
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function promotionalBanners(){
        $promotionCount = PromotionTool::count();
        $banners      = PromotionTool::orderBy('id', 'desc')->get();
        $notify[] = 'Promotional banners';
        
        return responseSuccess('promotional_tool_count', $notify, [
            'count' => $promotionCount,
            'banners' => $banners
        ]);  
    }

    public function investStatistics(){

        $user = auth()->user();
        $invests    = Invest::where('user_id', $user->id)->with('plan.timeSetting')->limit(10)->orderBy('id', 'desc')->get();
        $activePlan = Invest::where('user_id', $user->id)->where('status', Status::INVEST_RUNNING)->count();

        $investChart = Invest::where('user_id', $user->id)->with('plan')->groupBy('plan_id')->select('plan_id')->selectRaw("SUM(amount) as investAmount")->orderBy('investAmount', 'desc')->get();

        $notify[] = 'Invest Statistics';
        
        return responseSuccess('invest_statistics', $notify, [
            'active_plan' => $activePlan,
            'invest_sum' => $user->invests->sum('amount'),
            'interest_sum' => $user->transactions()->where('remark', 'interest')->sum('amount'),
            'invest_chart_sum' => $investChart->sum('investAmount'),
            'invest_chart' => $investChart,
            'invests' => $invests,
        ]);  
    }
}
