<?php
/**
 * Created by PhpStorm.
 * User: sohail
 * Date: 4/16/2017
 * Time: 12:13 PM
 */

namespace App\Http\ViewComposers;


use Illuminate\View\View;
use App\Repositories\InstrumentRepository;
use App\Repositories\DataBanksIntradayRepository;

class IntradayMarketCompositionBarPer
{

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {

        $viewdata= $view->getData();
        $base=$viewdata['base'];

        $height=700;
        if(isset($viewdata['height']))
            $height=$viewdata['height'];

        $instrumentListMain=InstrumentRepository::getInstrumentsScripOnly();
         $instrumentListMain->load('sector_list');
        $instrumentList=$instrumentListMain->groupBy('sector_list_id');

        $instrumentTradeData=DataBanksIntradayRepository::getLatestTradeDataAll();
        $instrumentTradeData=$instrumentTradeData->keyBy('instrument_id');

        $instrumentTradeDataPrev=DataBanksIntradayRepository::getMinuteAgoTradeDataAll();
        $instrumentTradeDataPrev=$instrumentTradeDataPrev->keyBy('instrument_id');


        $today=array();
        $prevDay=array();
        $category=array();

        $marketTotalToday=$instrumentTradeData->sum($base);
        $marketTotalPrev=$instrumentTradeDataPrev->sum($base);
        foreach($instrumentList as $sector_id=>$instrument_arr)
        {

            $sector_name=$instrument_arr->first()->sector_list->name;

            $sector_area_total=0;
            $sector_area_total_prev=0;

            foreach($instrument_arr as $instrument)
            {

                $instrument_id=$instrument->id;
                //dd($instrumentTradeDataPrev[$instrument_id]);
                if(isset($instrumentTradeData[$instrument_id]))
                    $sector_area_total+=$instrumentTradeData[$instrument_id]->$base;


                if(isset($instrumentTradeDataPrev[$instrument_id]))
                    $sector_area_total_prev+=$instrumentTradeDataPrev[$instrument_id]->$base;

            }


            $raw_value_today[$sector_name] = ($sector_area_total / $marketTotalToday) * 100;
            $raw_value_prev[$sector_name] = $marketTotalPrev ? ($sector_area_total_prev / $marketTotalPrev) * 100 : 0;


        }

        arsort($raw_value_today);
        foreach ($raw_value_today as $sector_name => $data) {
            $todayTemp = array();
            $prevTemp = array();

            $todayTemp['name'] = $sector_name;
            $todayTemp['color'] = '#1BA39C';
            $todayTemp['y'] = round($data, 2);

            $prevTemp['name'] = $sector_name;
            $prevTemp['color'] = '#EF4836';
            $prevTemp['y'] = round($raw_value_prev[$sector_name], 2);

            $today[] = $todayTemp;
            $prevDay[] = $prevTemp;
            $category[] = $sector_name;


        }

        // need to fix.. if today market don't have any data project throw a error.
        $todayDate=$instrumentTradeData->first()->lm_date_time;
        $prevDate=$instrumentTradeDataPrev->first()->lm_date_time;

        $view->with('today', collect($today)->toJson(JSON_NUMERIC_CHECK))
            ->with('prevDay', collect($prevDay)->toJson(JSON_NUMERIC_CHECK))
            ->with('category', collect($category)->toJson())
            ->with('todayDate', $todayDate)
            ->with('prevDate', $prevDate)
            ->with('renderTo', "intraday_market_composition_per_$base")
            ->with('height',$height)
            ->with('ylabel',$base);

    }
}