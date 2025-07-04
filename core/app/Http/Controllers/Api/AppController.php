<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Extension;
use App\Models\Frontend;
use App\Models\Invest;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\UserRanking;
use App\Models\Withdrawal;
use App\Traits\SupportTicketManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use stdClass;

class AppController extends Controller
{
    use SupportTicketManager;
    
    public function __construct()
    {
        $this->userType     = 'user';
        $this->column       = 'user_id';
        $this->user = auth()->user();
        $this->apiRequest = true;
    }

    public function generalSetting()
    {
        $notify[] = 'General setting data';

        $data = [
            'general_setting' => gs(),
            'social_login_redirect' => route('user.social.login.callback', ''),
        ];

        return responseSuccess('general_setting', $notify, $data);
    }

    public function logoFavicon()
    {
        $notify[] = 'Logo & Favicon';

        return response()->json([
            'remark'  => 'logo_favicon',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'logo'    => siteLogo(),
                'favicon' => siteFavicon(),
            ],
        ]);

    }

    public function getCountries()
    {
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $notify[]    = 'Country List';

        foreach ($countryData as $k => $country) {
            $countries[] = [
                'country' => $country->country,
                'dial_code' => $country->dial_code,
                'country_code' => $k,
            ];
        }

        return responseSuccess('country_data', $notify, [
            'countries' => $countries,
        ]);
    }

    public function getLanguage($code = null)
    {
        $languages     = Language::get();
        $languageCodes = $languages->pluck('code')->toArray();

        if (($code && !in_array($code, $languageCodes))) {
            $notify[] = 'Invalid code given';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if (!$code) {
            $code = Language::where('is_default', Status::YES)->first()?->code ?? 'en';
        }

        $jsonFile = file_get_contents(resource_path('lang/' . $code . '.json'));

        $notify[] = 'Language';
        return responseSuccess('language', $notify, [
            'languages' => $languages,
            'file' => json_decode($jsonFile) ?? [],
            'code' => $code,
            'image_path' => getFilePath('language')
        ]);
    }

    public function policies()
    {
        $policies = getContent('policy_pages.element', orderById: true);
        $notify[] = 'All policies';

        return responseSuccess('policy_data', $notify, [
            'policies' => $policies,
        ]);
    }

    public function policyContent($slug)
    {
        $policy = Frontend::where('slug', $slug)->where('data_keys', 'policy_pages.element')->first();
        if (!$policy) {
            $notify[] = 'Policy not found';
            return responseError('policy_not_found', $notify);
        }
        $seoContents = $policy->seo_content;
        $seoImage = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        $notify[] = 'Policy content';
        return responseSuccess('policy_content', $notify, [
            'policy' => $policy,
            'seo_content' => $seoContents,
            'seo_image' => $seoImage
        ]);
    }

    public function faq()
    {
        $faq      = getContent('faq.element', orderById: true);
        $notify[] = 'FAQ';
        
        return responseSuccess('faq', $notify, ['faq' => $faq]);
    }

    public function seo()
    {
        $notify[] = 'Global SEO data';
        $seo = Frontend::where('data_keys', 'seo.data')->first();
        return responseSuccess('seo', $notify, ['seo_content' => $seo]);
    }

    public function getExtension($act)
    {
        $notify[] = 'Extension Data';
        $extension = Extension::where('status', Status::ENABLE)->where('act', $act)->first()?->makeVisible('shortcode');
        return responseSuccess('extension', $notify, [
            'extension' => $extension,
            'custom_captcha' => $act == 'custom-captcha' ? loadCustomCaptcha() : null
        ]);
    }

    public function submitContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }
        if (!verifyCaptcha()) {
            $notify[] = 'Invalid captcha provided';
            return responseError('captcha_error', $notify);
        }
        $random = getNumber();
        $ticket = new SupportTicket();
        $ticket->user_id = 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;
        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = Status::TICKET_OPEN;
        $ticket->save();
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = 0;
        $adminNotification->title = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();
        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();
        $notify[] = 'Contact form submitted successfully';
        return responseSuccess('contact_form_submitted', $notify, ['ticket' => $ticket]);
    }

    public function cookie()
    {
        $cookie = Frontend::where('data_keys', 'cookie.data')->first();
        $notify[] = 'Cookie policy';
        return responseSuccess('cookie_data', $notify, [
            'cookie' => $cookie
        ]);
    }

    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
        $notify[] = 'Cookie accepted';
        return responseSuccess('cookie_accepted', $notify);
    }

    public function customPages()
    {
        $pages = Page::where('tempname', activeTemplate())
            ->where(function ($query) {
                $query->where('is_default', Status::NO)->orWhere('slug', '/'); // home page data went with default
            })
            ->get();
        $notify[] = 'Custom pages';
        return responseSuccess('custom_pages', $notify, [
            'pages' => $pages
        ]);
    }

    public function customPageData($slug)
    {
        if ($slug == 'home') $slug = '/';
        // default is home page, the where clause for default page is removed
        $page = Page::where('tempname', activeTemplate())->where('slug', $slug)->first();
        if (!$page) {
            $notify[] = 'Page not found';
            return responseError('page_not_found', $notify);
        }
        $seoContents = $page->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $notify[] = 'Custom page';
        return responseSuccess('custom_page', $notify, [
            'page' => $page,
            'seo_content' => $seoContents,
            'seo_image' => $seoImage
        ]);
    }

    public function sectionData($section, Request $request)
    {
        $content = Frontend::query();
        
        if($section == 'maintenance'){
            $content = $content->where('data_keys', "{$section}.data")->first();
        }else{
            $content = $content->where('data_keys', "{$section}.content")->where('tempname', 'bit_gold')->first();
        }

        $elements = Frontend::where('data_keys', "{$section}.element")->select('id', 'data_values');
        if($section != 'maintenance'){
            $elements = $elements->where('tempname', 'bit_gold');
        }

        if ($request->orderById) {
            $elements = $elements->orderBy('id');
        } else {
            $elements = $elements->orderBy('id', 'desc');
        }

        if($request->limit){
            $elements = $elements->limit($request->limit)->get();
        }else{
            $elements = $elements->get();
        }

        $dataContent = Frontend::where('data_keys', "{$section}.data");
        if($section == 'maintenance'){
            $dataContent = $dataContent->first();
        }else{
            $dataContent = $dataContent->where('tempname', 'bit_gold')->first();
        }

        $data = [
            'data' => @$dataContent->data_values,
            'content' => @$content->data_values,
            'elements' => $elements->map(function ($element) {
                $columns = new stdClass();
                $columns = $element->data_values;
                $columns->id = $element->id;
                return $columns;
            })
        ];
        $notify[] = 'Section data';
        return responseSuccess('section_data', $notify, $data);
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $subscribe = new Subscriber();
        $subscribe->email = $request->email;
        $subscribe->save();

        $notify = 'Thank you, we will notice you our latest news';
        return responseSuccess('subscribe', $notify);
    }

    public function userRankings()
    {
        $userRankings = UserRanking::active()->get();

        $firstPercent = 20;
        $lastPercent = 100;
        $perItem = ($lastPercent - $firstPercent) / ($userRankings->count() > 1 ? $userRankings->count() - 1 : $userRankings->count());

        $data = [
            'per_item' => $perItem,
            'first_percent' => $firstPercent,
            'user_rankings' => $userRankings,
            'content' => @getContent('ranking.content', true)['data_values']
        ];

        $notify = 'User Rankings';
        return responseSuccess('user_rankings', $notify, $data);
    }

    public function topInvestors()
    {
        $data = [
            'top_investors' => Invest::with('user')
            ->selectRaw('SUM(amount) as totalAmount, user_id')
            ->orderBy('totalAmount', 'desc')
            ->groupBy('user_id')
            ->limit(8)
            ->get(),
            'content' => @getContent('top_investor.content', true)['data_values'],
        ];

        $notify = 'Top Investors';
        return responseSuccess('top_investors', $notify, $data);
    }

    public function latestTransaction()
    {
        $latestDeposit = Deposit::with('user', 'gateway')->where('status', 1)->latest()->limit(10)->get();
        $fakeDeposit = Frontend::where('data_keys', 'transaction.element')->whereJsonContains('data_values->trx_type', 'deposit')->limit(10)->get();
        $deposits = $latestDeposit->merge($fakeDeposit)->sortByDesc('created_at')->take(10)->values();
        
        $latestWithdraw = Withdrawal::with('user', 'method')->where('status', 1)->latest()->limit(10)->get();
        $fakeWithdraw = Frontend::where('data_keys', 'transaction.element')->whereJsonContains('data_values->trx_type', 'withdraw')->limit(10)->get();
        $withdrawals = $latestWithdraw->merge($fakeWithdraw)->sortByDesc('created_at')->take(10)->values();
        
        $data = [
            'content' => @getContent('transaction.content', true)['data_values'],
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
        ];
        
        $notify = 'Latest Transaction';
        return responseSuccess('latest_transaction', $notify, $data);
    }

    public function plans(Request $request)
    { 
        $plans = Plan::with('timeSetting')->whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('status', Status::ENABLE);

        if($request->fromSection){
            $plans = $plans->where('featured', 1);
        }
        
        $plans = $plans->get();

        $modifiedPlans = [];
        $general       = gs();
        
        foreach ($plans as $plan) {
            if ($plan->lifetime == 0) {
                $totalReturn = 'Total ' . $plan->interest * $plan->repeat_time . ($plan->interest_type == 1 ? '%' : ' '.$general->cur_text);
        
                if($request->is_web == Status::YES){
                    $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + <span class="badge badge--success">Capital</span>' : $totalReturn;
                }else{
                    $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + Capital' : $totalReturn;
                }
        
                $repeatTime       = 'For ' . $plan->repeat_time . ' ' . $plan->timeSetting->name;
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours for ' . $plan->repeat_time . ' times';
            } else {
                $totalReturn      = 'Lifetime Earning'; 
                $repeatTime       = 'For Lifetime';
                $interestValidity = 'Per ' . $plan->timeSetting->time . ' hours for lifetime';
            }
        
            $modifiedPlans[] = [
                'id'                => $plan->id,
                'name'              => $plan->name,
                'minimum'           => $plan->minimum,
                'maximum'           => $plan->maximum,
                'fixed_amount'      => $plan->fixed_amount,
                'return'            => showAmount($plan->interest, currencyFormat: false) . ($plan->interest_type == 1 ? '%' : ' '.$general->cur_text),
                'interest_duration' => 'Every ' . $plan->timeSetting->name,
                'repeat_time'       => $repeatTime,
                'total_return'      => $totalReturn,
                'interest_validity' => $interestValidity,
                'hold_capital'      => $plan->hold_capital,
                'compound_interest' => $plan->compound_interest,
        
                'interest' => $plan->interest,
                'interest_type' => $plan->interest_type,
                'raw_interest_type' => $plan->repeat_time,
                'capital_back' => $plan->capital_back,
            ];
        }
        
        $data = [
            'plans' => $modifiedPlans
        ];
        
        $notify = 'Plans';
        return responseSuccess('plans', $notify, $data);
    }

    public function planCalculator(Request $request)
    {
        if ($request->planId == null) {
            return responseError('validation_error', ['Please Select a Plan!']);
        }

        $requestAmount = $request->investAmount;
        if ($requestAmount == null || 0 > $requestAmount) {
            return responseError('validation_error', ['Please Enter Invest Amount!']);
        }

        $plan = Plan::whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('id', $request->planId)->where('status', Status::ENABLE)->first();

        if (!$plan) {
            return responseError('validation_error', ['Invalid Plan!']);
        }

        if ($plan->fixed_amount == '0') {
            if ($requestAmount < $plan->minimum) {
                return responseError('validation_error', ['Minimum Invest ' . getAmount($plan->minimum) . ' ' . gs('cur_text')]);
            }
            if ($requestAmount > $plan->maximum) {
                return responseError('validation_error', ['Maximum Invest ' . getAmount($plan->maximum) . ' ' . gs('cur_text')]);
            }
        } else {
            if ($requestAmount != $plan->fixed_amount) {
                return responseError('validation_error', ['Fixed Invest amount ' . getAmount($plan->fixed_amount) . ' ' . gs('cur_text')]);
            }
        }

        if ($plan->interest_type == 1) {
            $interestAmount = ($requestAmount * $plan->interest) / 100;
        } else {
            $interestAmount = $plan->interest;
        }

        $timeName = $plan->timeSetting->name;

        if ($plan->lifetime == 0) {
            $ret        = $plan->repeat_time;
            $total      = ($interestAmount * $plan->repeat_time) . ' ' . gs('cur_text');
            $totalMoney = $interestAmount * $plan->repeat_time;

            if ($plan->capital_back == 1) {
                $total .= '+Capital';
                $totalMoney += $request->investAmount;
            }

            $result['description'] = 'Return ' . showAmount($interestAmount) . ' Every ' . $timeName . ' For ' . $ret . ' ' . $timeName . '. Total ' . $total;
            $result['totalMoney']  = $totalMoney;
            $result['netProfit']   = 'Net Profit '.showAmount($totalMoney - $request->investAmount);
        } else {
            $result['description'] = 'Return ' . showAmount($interestAmount) . ' Every ' . $timeName . ' For Lifetime';
        }

        $notify = 'Plan Calculator';
        return responseSuccess('plan_calculator', $notify, $result);
    }

    public function blogDetails($id)
    {
        $blog        = Frontend::where('id', $id)->where('tempname', activeTemplateName())->where('data_keys', 'blog.element')->first();

        if (!$blog) {
            $notify[] = 'Not found';
            $data = [
                'response' => 404,
            ];
            return responseError('not_found', $notify, $data);
        }

        $blogs       = Frontend::where('id', '!=', $blog->id)->where('tempname', activeTemplateName())->where('data_keys', 'blog.element')->latest()->limit(5)->get();
        $seoContents = $blog->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('blog', $seoContents->image, getFileSize('seo'), true) : null;
        
        $data = [
            'blog' => $blog,
            'blogs' => $blogs,
            'seoContents' => $seoContents,
            'seoImage' => $seoImage,
        ];
        
        $notify = 'Blog Details';
        return responseSuccess('blog_details', $notify, $data);
    }

    public function blogs()
    {
        $blogs     = Frontend::where('data_keys', 'blog.element')->where('tempname', activeTemplateName())->orderBy('id', 'desc')->paginate(getPaginate(9));

        $data = [
            'blogs' => $blogs
        ];

        $notify = 'Blogs';
        return responseSuccess('blogs', $notify, $data);
    }

}
