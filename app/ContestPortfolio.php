<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContestPortfolio extends Model
{
    /**
     * Get the user that join the contest.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() 
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the contest that join the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contest() 
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    /**
     * Get share for the contest portfolio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function share()
    {
        return $this->hasOne(ContestPortfolioShare::class);
    }

    /**
     * Get shares for the contest portfolio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shares()
    {
        return $this->hasMany(ContestPortfolioShare::class)->latest('buying_date');

        // return $this->hasMany(ContestPortfolioShare::class)->groupBy('instrument_id')->latest('buying_date');
    }

    public function pShares()
    {
        return $this->hasMany(ContestPortfolioShare::class)->groupBy('instrument_id')->where('id', null)->latest('buying_date');
    }
    /**
     * 
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function portfolioShares()
    {
        return $this->belongsToMany(ContestPortfolioShare::class, 'contest_portfolio_shares', 'contest_portfolio_id', 'instrument_id')
                    ->withPivot('no_of_shares', 'buying_price', 'buying_date', 'commission')
                    ->withTimestamps();
    }
}
