<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Game;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Winner;
use App\Models\Frontend;
use App\Models\Language;
use App\Constants\Status;
use App\Models\Subscriber;
use App\Models\Leaderboard;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\GatewayCurrency;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index()
    {
        $reference = @$_GET['reference'];
        if ($reference) {
            session()->put('reference', $reference);
        }

        $pageTitle   = 'Home';
        $sections    = Page::where('tempname', activeTemplate())->where('slug', '/')->first();
        $seoContents = @$sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;


        // Get latest active announcement
        $announcements = Announcement::where('is_active', true)->latest()->get();

        $leaderboardGroups = Winner::with(['user', 'game'])
            ->latest()
            ->get()
            ->groupBy('game_id'); // 🧠 group by game

           // Authenticated user winner check
        $isWinner = null;
        if (auth()->check()) {
        $isWinner = Winner::where('user_id', auth()->id())->latest()->first();
        }


        // ✅ Fetch active games
        if (auth()->check() && auth()->user()->selected_game_id) {
            $games = Game::where('id', auth()->user()->selected_game_id)->where('status', 1)->get();
        } else {
            $games = Game::where('status', 1)->where('featured', 1)->latest()->get();
        }
        return view('Template::home', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'games', 'announcements', 'leaderboardGroups','isWinner'));
    }

    public function pages($slug)
    {
        $page        = Page::where('tempname', activeTemplate())->where('slug', $slug)->firstOrFail();
        $pageTitle   = $page->name;
        $sections    = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
    }

    public function contact()
    {

        $pageTitle   = "Contact Us";
        $user        = auth()->user();
        $sections    = Page::where('tempname', activeTemplate())->where('slug', 'contact')->first();
        $seoContents = @$sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::contact', compact('pageTitle', 'user', 'sections', 'seoContents', 'seoImage'));
    }

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name'    => 'required',
            'email'   => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $random = getNumber();

        $ticket           = new SupportTicket();
        $ticket->user_id  = auth()->id() ?? 0;
        $ticket->name     = $request->name;
        $ticket->email    = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;

        $ticket->ticket     = $random;
        $ticket->subject    = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status     = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title     = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message                    = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message           = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug)
    {
        $policy      = Frontend::where('slug', $slug)->where('tempname', activeTemplateName())->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle   = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) {
            $lang = 'en';
        }

        session()->put('lang', $lang);
        return back();
    }

    public function blogs()
    {
        if (activeTemplateName() == 'invester') {
            abort(404);
        }
        $blogs     = Frontend::where('data_keys', 'blog.element')->where('tempname', activeTemplateName())->orderBy('id', 'desc')->paginate(getPaginate(9));
        $pageTitle = 'Blogs';
        $page      = Page::where('tempname', activeTemplateName())->where('slug', 'blogs')->first();
        $sections  = @$page->secs;
        return view('Template::blogs', compact('blogs', 'pageTitle', 'sections'));
    }

    public function blogDetails($slug)
    {
        if (activeTemplateName() == 'invester') {
            abort(404);
        }

        $blog        = Frontend::where('slug', $slug)->where('tempname', activeTemplateName())->where('data_keys', 'blog.element')->firstOrFail();

        $blogs       = Frontend::where('id', '!=', $blog->id)->where('tempname', activeTemplateName())->where('data_keys', 'blog.element')->latest()->limit(5)->get();
        $pageTitle   = $blog->data_values->title;
        $seoContents = $blog->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('blog', $seoContents->image, getFileSize('seo'), true) : null;

        return view('Template::blog_details', compact('pageTitle', 'blog', 'blogs', 'seoContents', 'seoImage'));
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code'    => 200,
                'status'  => 'error',
                'message' => $validator->errors()->all(),
            ]);
        }

        $subscribe        = new Subscriber();
        $subscribe->email = $request->email;
        $subscribe->save();

        $notify = 'Thank you, we will notice you our latest news';

        return response()->json([
            'code'    => 200,
            'status'  => 'success',
            'message' => $notify,
        ]);
    }

    public function plan()
    {
        $pageTitle = "Investment Plan";
        $plans     = Plan::with('timeSetting')->whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('status', Status::ENABLE)->get();

        $sections        = Page::where('tempname', activeTemplateName())->where('slug', 'plans')->first();
        $layout          = 'frontend';
        $gatewayCurrency = null;
        if (auth()->check()) {
            $layout          = 'master';
            $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
                $gate->where('status', Status::ENABLE);
            })->with('method')->orderby('name')->get();
        }
        return view('Template::plan', compact('pageTitle', 'plans', 'sections', 'layout', 'gatewayCurrency'));
    }

    public function planCalculator(Request $request)
    {
        if ($request->planId == null) {
            return response(['errors' => 'Please Select a Plan!']);
        }
        $requestAmount = $request->investAmount;
        if ($requestAmount == null || 0 > $requestAmount) {
            return response(['errors' => 'Please Enter Invest Amount!']);
        }

        $plan = Plan::whereHas('timeSetting', function ($time) {
            $time->where('status', Status::ENABLE);
        })->where('id', $request->planId)->where('status', Status::ENABLE)->first();

        if (!$plan) {
            return response(['errors' => 'Invalid Plan!']);
        }

        if ($plan->fixed_amount == '0') {
            if ($requestAmount < $plan->minimum) {
                return response(['errors' => 'Minimum Invest ' . getAmount($plan->minimum) . ' ' . gs('cur_text')]);
            }
            if ($requestAmount > $plan->maximum) {
                return response(['errors' => 'Maximum Invest ' . getAmount($plan->maximum) . ' ' . gs('cur_text')]);
            }
        } else {
            if ($requestAmount != $plan->fixed_amount) {
                return response(['errors' => 'Fixed Invest amount ' . getAmount($plan->fixed_amount) . ' ' . gs('cur_text')]);
            }
        }

        //start
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
            $result['netProfit']   = showAmount($totalMoney - $request->investAmount);
        } else {
            $result['description'] = 'Return ' . showAmount($interestAmount) . ' Every ' . $timeName . ' For Lifetime';
        }

        return response($result);
    }

    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy()
    {
        $cookieContent = Frontend::where('data_keys', 'cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE, 404);
        $pageTitle = 'Cookie Policy';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->first();
        return view('Template::cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth  = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text      = $imgWidth . '×' . $imgHeight;
        $fontFile  = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize  = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox    = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view('Template::maintenance', compact('pageTitle', 'maintenance'));
    }
}
