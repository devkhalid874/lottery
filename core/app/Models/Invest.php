<?php

namespace App\Models;

use App\Traits\ApiQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Invest extends Model
{
    use ApiQuery;

    protected $guarded = ['id'];

    protected $appends = ['diffDatePercent', 'diffInSeconds', 'isShowDiffInSeconds', 'isEligibleCapitalBack'];

    public function getDiffDatePercentAttribute(){

        if ($this->last_time) {
            $start = $this->last_time;
        } else {
            $start = $this->created_at;
        }

        return diffDatePercent($start, $this->next_time);
    }

    public function getDiffInSecondsAttribute(){
        return abs(Carbon::parse($this->next_time)->diffInSeconds());
    }

    public function getIsShowDiffInSecondsAttribute(){
        return Carbon::parse($this->next_time) > now() ? true : false;
    }

    public function getIsEligibleCapitalBackAttribute(){
        return $this->eligibleCapitalBack();
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id')->withDefault();
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withDefault();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeLastSevenDays()
    {
        return $this->where('created_at', '>=', now()->subDays(7));
    }

    public function scopeThisMonth()
    {
        return $this->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeThisYear()
    {
        return $this->where('created_at', '>=', now()->startOfYear());
    }

    public function eligibleCapitalBack()
    {
        if ($this->status == 0 && $this->capital_status == 1 && $this->capital_back == 0) {
            return true;
        }
        return false;
    }

}
