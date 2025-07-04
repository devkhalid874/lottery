<?php

namespace App\Models;

use App\Traits\ApiQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StakingInvest extends Model
{
    use ApiQuery;

    protected $appends = ['diffDatePercent', 'isCompleted', 'diffInSeconds'];

    public function getDiffDatePercentAttribute(){
        return diffDatePercent($this->created_at, $this->end_at);
    }

    public function getDiffInSecondsAttribute(){
        return abs(Carbon::parse($this->end_at)->diffInSeconds());
    }

    public function getIsCompletedAttribute(){
        if($this->end_at > now()){
            return false;
        }

        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
