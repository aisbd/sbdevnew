<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Market;
use App\DataBanksEod;
use App\Repositories\InstrumentRepository;
use App\Repositories\SectorListRepository;
use App\Repositories\DataBanksIntradayRepository;
use App\Repositories\DataBankEodRepository;
Use App\DataBanksIntraday;
use App\Repositories\CorporateActionRepository;
use App\Repositories\FundamentalRepository;
use App\Repositories\MarketStatRepository;
use App\Repositories\IndexRepository;



class EodIntradayTradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dse:EodIntradayTrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserting data into === data_bank_eods == data_banks_intradays == trades == table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */



// live server command   /opt/cpanel/ea-php70/root/usr/bin/php /home/hostingmonitors/artisan dse:EodIntradayTrade
// source update_eods_and_intraday_data cron of old site
    public function handle()
    {


        $querystr = "select SUM(MKISTAT_TOTAL_TRADES) as total_trades,SUM(MKISTAT_TOTAL_VALUE) as total_value,SUM(MKISTAT_TOTAL_VOLUME) as total_volume,MAX(MKISTAT_LM_DATE_TIME) as MKISTAT_LM_DATE_TIME from MKISTAT ORDER BY MKISTAT_LM_DATE_TIME";
        $from_dse = DB::connection('dse')->select($querystr);
        $batch_total_trades_from_dse = $from_dse[0]->total_trades;


        $querystr = "select * from TRD";
        $tradeDataFromDseServer = DB::connection('dse')->select($querystr);
        $trade_date_time = $tradeDataFromDseServer[0]->TRD_LM_DATE_TIME;
        $convertedTimestamp_trade = strtotime($trade_date_time);
        $trade_date = date('Y-m-d', $convertedTimestamp_trade);





        $querystr = "select * from MKISTAT ORDER BY MKISTAT_LM_DATE_TIME DESC LIMIT 0 , 600";
        $dataFromDseServer = DB::connection('dse')->select($querystr);

        $this->info(count($dataFromDseServer) . ' row fetched from DSE server');

        if(!Market::isMarketOpen())
        {
            $this->info('market is not open');

        }
        else
        {
            $date_time = $dataFromDseServer[0]->MKISTAT_LM_DATE_TIME;
            $convertedTimestamp = strtotime($date_time);
            $trade_date = date('Y-m-d', $convertedTimestamp);


            // Market is open. Now we will check if dse server returning today's data. Sometimes dse return previous day data
            if($activeTradeDates=Market::validateTradeDate($trade_date))
            {
                // its returning today data. So we will proceed here
                $market_id=$activeTradeDates->id;

                //////////////////////////// BATCH DEALINGS \\\\\\\\\\\\\\\\\\\\\\\\

                // let us deal with the batch. we will first see which batch is currently on record in markets table for the active trade date
                $data_bank_intraday_batch = $activeTradeDates->data_bank_intraday_batch;
                $batch_total_trades = $activeTradeDates->batch_total_trades;



                // if $data_bank_intraday_batch=0, go for fresh query in the market tables
                if($data_bank_intraday_batch)
                {
                    // it will be 0 for the very first entry of a day. So we have to take max(data_bank_intraday_batch) frosftm the market table


                    $mdata = DB::table('markets')
                        ->select(DB::raw('MAX(data_bank_intraday_batch) AS data_bank_intraday_batch'))
                        ->get();
                    $data_bank_intraday_batch= $mdata[0]->data_bank_intraday_batch;
                }

                // incrementing
                $data_bank_intraday_batch++;
                $dataToSaveInTradesTable = array();

                // Check if it is same row of previous minute
                if ($batch_total_trades != $batch_total_trades_from_dse) {


                    // saving trade data in TRD table.  -- STARTS

                    foreach ($tradeDataFromDseServer as $data) {
                        $data->TRD_LM_DATE_TIME = str_replace('at', '', $data->TRD_LM_DATE_TIME);

                        $temp = array();
                        $temp['market_id'] = $market_id;
                        $temp['TRD_SNO'] = $data->TRD_SNO;
                        $temp['TRD_TOTAL_TRADES'] = $data->TRD_TOTAL_TRADES;
                        $temp['TRD_TOTAL_VOLUME'] = $data->TRD_TOTAL_VOLUME;
                        $temp['TRD_TOTAL_VALUE'] = $data->TRD_TOTAL_VALUE;
                        $temp['TRD_LM_DATE_TIME'] = date('Y-m-d H:i:s', strtotime($data->TRD_LM_DATE_TIME));
                        $temp['trade_time'] = date('H:i', strtotime($data->TRD_LM_DATE_TIME));
                        $temp['trade_date'] = date('Y-m-d', strtotime($data->TRD_LM_DATE_TIME));
                        $temp['batch'] = $data_bank_intraday_batch;
                        $dataToSaveInTradesTable[] = $temp;


                    }


                    // saving trade data in TRD table. This is alternative solution as TradeDataCommand corn missing data. -- ENDS


                    $instrumentList = InstrumentRepository::getInstrumentsScripOnly();
                    $instrumentList=$instrumentList->keyBy('instrument_code');


                    $dataToSave = array();

                    $instrument_ids = [];

                    foreach ($dataFromDseServer as $data) {


                        //$instrument_info = $instrumentList->where('instrument_code', trim($data->MKISTAT_INSTRUMENT_CODE))->first();
                        $instrument_info=array();
                        if(isset($instrumentList[trim($data->MKISTAT_INSTRUMENT_CODE)]))
                        {
                            $instrument_info = $instrumentList[trim($data->MKISTAT_INSTRUMENT_CODE)];
                        }

                        if (count($instrument_info)) {
                            $instrument_id = $instrument_info->id;
                            $ltp = $data->MKISTAT_CLOSE_PRICE != 0 ? $data->MKISTAT_CLOSE_PRICE : ($data->MKISTAT_PUB_LAST_TRADED_PRICE != 0 ? $data->MKISTAT_PUB_LAST_TRADED_PRICE : $data->MKISTAT_SPOT_LAST_TRADED_PRICE);


                            /////////////////// WE WILL PROCESS EOD DATA FIRST \\\\\\\\\\\\\\\\\\\\\\\
                            if ($data->MKISTAT_PUBLIC_TOTAL_VOLUME + $data->MKISTAT_SPOT_TOTAL_VOLUME > 0) {
                                $eod = DataBanksEod::updateOrCreate(
                                    ['market_id' => $market_id, 'instrument_id' => $instrument_id],
                                    [
                                        'open' => $data->MKISTAT_OPEN_PRICE,
                                        'high' => $data->MKISTAT_HIGH_PRICE,
                                        'low' => $data->MKISTAT_LOW_PRICE,
                                        'close' => $ltp,
                                        'volume' => $data->MKISTAT_TOTAL_VOLUME,
                                        'trade' => $data->MKISTAT_TOTAL_TRADES,
                                        'tradevalues' => $data->MKISTAT_TOTAL_VALUE,
                                        'updated' => date('Y-m-d H:i:s'),
                                        'date' => $trade_date
                                    ]
                                );


                                /////////////////// WE WILL PROCESS INTRADAY DATA NOW \\\\\\\\\\\\\\\\\\\\\\\


                                $temp = array();
                                $temp['market_id'] = $market_id;
                                $temp['instrument_id'] = $instrument_id;
                                $temp['quote_bases'] = $data->MKISTAT_QUOTE_BASES;
                                $temp['open_price'] = $data->MKISTAT_OPEN_PRICE;
                                $temp['pub_last_traded_price'] = $data->MKISTAT_PUB_LAST_TRADED_PRICE;
                                $temp['spot_last_traded_price'] = $data->MKISTAT_SPOT_LAST_TRADED_PRICE;
                                $temp['high_price'] = $data->MKISTAT_HIGH_PRICE;
                                $temp['low_price'] = $data->MKISTAT_LOW_PRICE;
                                //$temp['close_price'] = $data->MKISTAT_CLOSE_PRICE;
                                $temp['close_price'] = $ltp;
                                $temp['yday_close_price'] = $data->MKISTAT_YDAY_CLOSE_PRICE;
                                $temp['total_trades'] = $data->MKISTAT_TOTAL_TRADES;
                                $temp['total_volume'] = $data->MKISTAT_TOTAL_VOLUME;
                                $temp['total_value'] = $data->MKISTAT_TOTAL_VALUE;
                                $temp['public_total_trades'] = $data->MKISTAT_PUBLIC_TOTAL_TRADES;
                                $temp['public_total_volume'] = $data->MKISTAT_PUBLIC_TOTAL_VOLUME;
                                $temp['public_total_value'] = $data->MKISTAT_PUBLIC_TOTAL_VALUE;
                                $temp['spot_total_trades'] = $data->MKISTAT_SPOT_TOTAL_TRADES;
                                $temp['spot_total_volume'] = $data->MKISTAT_SPOT_TOTAL_VOLUME;
                                $temp['spot_total_value'] = $data->MKISTAT_SPOT_TOTAL_VALUE;
                                $temp['lm_date_time'] = date('Y-m-d H:i:s', strtotime($data->MKISTAT_LM_DATE_TIME));
                                $temp['trade_time'] = date('H:i', strtotime($data->MKISTAT_LM_DATE_TIME));
                                $temp['trade_date'] = date('Y-m-d', strtotime($data->MKISTAT_LM_DATE_TIME));
                                $temp['batch'] = $data_bank_intraday_batch;

                                //dd($temp);
                                 if($data->MKISTAT_PUBLIC_TOTAL_VOLUME + $data->MKISTAT_SPOT_TOTAL_VOLUME > 0)
                                 {
                                     $instrument_ids[] = $instrument_id;
                                 }


                                $dataToSave[] = $temp;
                            }


                        }


                    }

                    if (!empty($dataToSaveInTradesTable)) {

                        DB::table('trades')->insert($dataToSaveInTradesTable);

                        $this->info(count($dataToSaveInTradesTable) . ' row inserted into trades');

                    }


                    if (!empty($dataToSave)) {
                        /*set last updated batch_id in instruments table start*/
                        DB::table('instruments')->whereIn('id', $instrument_ids)->update(['batch_id' => $data_bank_intraday_batch ]);
                        /*set last updated batch_id in instruments table end*/

                        DB::table('data_banks_intradays')->insert($dataToSave);



                        //recording new value of data_bank_intraday_batch and batch_total_trades of markets table

                        DB::table('markets')
                            ->where('id', $market_id)
                            ->update(['data_bank_intraday_batch' => $data_bank_intraday_batch, 'batch_total_trades' => $batch_total_trades_from_dse]);

                        $this->info(count($dataToSave) . ' row inserted into data_banks_intradays');
                        $this->info("Batch  ===" . $data_bank_intraday_batch);
                        $this->info("Batch Total trades===" . $batch_total_trades_from_dse);


                    }


                }



            }
            else
            {
                // Its not returning today data. We will just send a message in console
                $this->info('Dse returning previous data');
            }


        }

    }
}
