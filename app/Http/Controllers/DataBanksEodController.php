<?php

namespace App\Http\Controllers;

use App\DataBanksEod;
use App\Repositories\DataBanksIntradayRepository;
use App\Repositories\DataBankEodRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use View;
use App\Repositories\InstrumentRepository;
use App\Repositories\ChartRepository;
use App\Repositories\FundamentalRepository;
use DB;

class DataBanksEodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DataBanksEod  $dataBanksEod
     * @return \Illuminate\Http\Response
     */
    public function show(DataBanksEod $dataBanksEod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\DataBanksEod  $dataBanksEod
     * @return \Illuminate\Http\Response
     */
    public function edit(DataBanksEod $dataBanksEod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DataBanksEod  $dataBanksEod
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DataBanksEod $dataBanksEod)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DataBanksEod  $dataBanksEod
     * @return \Illuminate\Http\Response
     */
    public function destroy(DataBanksEod $dataBanksEod)
    {
        //
    }

   // public function chart_img_trac(Request $request,$reportrange = "", $instrumentCode = 'DSEX', $comparewith = 'ABBANK', $Indicators = "RSI,MACD", $configure = "VOLBAR", $charttype = "CandleStick", $overlay = "BB", $mov1 = "SMA",$avgPeriod1=20, $mov2 = "SMA",$avgPeriod2=30,$adj=1)
    public function  chart_img_trac($reportrange = "", $instrument = '10001', $comparewith = 'ABBANK', $Indicators = "RSI,MACD", $configure = "VOLBAR", $charttype = "CandleStick", $overlay = "BB", $mov1 = "SMA", $avgPeriod1 = 20, $mov2 = "SMA", $avgPeriod2 = 30, $adj = 1, $interval = 24*60*60)
    {

        $instrument_arr=explode('_',$instrument);  //example $instrument=sector_11

        $sector_id=0;
        $instrumentId=0;
        if(count($instrument_arr)==2) // it is sector
        {
            $sector_id=(int)$instrument_arr[1];
            $sector_info= DB::select("select * from sector_lists where id=$sector_id");
            $instrumentInfo=$sector_info[0];
            $instrumentCode = $instrumentInfo->name;

        }else // it is share
        {

            $instrumentId = (int)$instrument_arr[0];
            //if instrument code given
            if (!$instrumentId) {
                $instrumentInfo = InstrumentRepository::getInstrumentsByCode(array("$instrument"))->first();
                $instrumentId = $instrumentInfo->id;
                $instrumentCode = $instrumentInfo->name;
            } else {
                $instrumentInfo = InstrumentRepository::getInstrumentsById(array($instrumentId))->first();
                $instrumentCode = $instrumentInfo->name;

            }


        }




        $avgPeriod1 = (int)$avgPeriod1;
        $avgPeriod2 = (int)$avgPeriod2;
        $adj = (int)$adj;


        //$path=public_path('metronic_custom/chart_director/lib/FinanceChart.php');
        //File::requireOnce($path);
        include(app_path() . '/ChartDirector/FinanceChart.php');

        $width = request()->width?:1230;
        $mainHeight = request()->height?:320;
        $indicatorHeight = 90;
        $extraPoints = 21;

        if($mainHeight > 500)
        {
            $mainHeight = 500;
        }
        if ($reportrange == '') {
            $from = date('Y-m-d', strtotime(' -120 days'));
            $to = date('Y-m-d');
        } else {
            $datearr = explode('|', $reportrange);
            $from = $datearr[0];
            $to = $datearr[1];
        }


        $timeStamps = null;
        $volData = null;
        $highData = null;
        $lowData = null;
        $openData = null;
        $closeData = null;


        if ($avgPeriod1 > $extraPoints) {

            $extraPoints = $avgPeriod1;

        }

        if ($avgPeriod2 > $extraPoints) {

            $extraPoints = $avgPeriod2;

        }

        if($sector_id)
        {

            $ohlcData = ChartRepository::getDailySectorData($sector_id, $from, $to, $extraPoints);


        }else
        {
            if ($adj)
                $ohlcData = ChartRepository::getAdjustedDailyData($instrumentId, $from, $to, $extraPoints);

            else
                if($interval == 24*60*60)
                {
                        $ohlcData = ChartRepository::getDailyData($instrumentId, $from, $to, $extraPoints);
                }else{
                        $ohlcData = ChartRepository::getIntradayCandle($instrumentId, $from, $to, $interval);

                }

        }



        $ohlcData['realtimeStamps'] = array_reverse($ohlcData['realtimeStamps']);
        $timeStamps = array_reverse($ohlcData['date']);
        $closeData = array_reverse($ohlcData['close']);
        $openData = array_reverse($ohlcData['open']);
        $lowData = array_reverse($ohlcData['low']);
        $highData = array_reverse($ohlcData['high']);
        $volData = array_reverse($ohlcData['volume']);


        $index = count($ohlcData['realtimeStamps']);
        $lastday = $ohlcData['realtimeStamps'][$index - 1];
        $lastday = date("M d, Y", $lastday);
        $open = $openData[$index - 1];
        $high = $highData[$index - 1];
        $low = $lowData[$index - 1];
        $close = $closeData[$index - 1];
        $volume = $volData[$index - 1];

        $metaKey = array();
        $metaKey[] = 'market_lot';
        $metaKey[] = 'total_no_securities';
        $metaKey[] = 'net_asset_val_per_share';
        $metaKey[] = 'year_end';
        $metaKey[] = 'share_percentage_public';
        $metaKey[] = 'net_asset_val_per_share';

        $last_trade_data = DataBanksIntradayRepository::getAvailableLTP([$instrumentId]);
        $category = 'N/A';
        $ltp = 0;
        if (count($last_trade_data)) {
            $last_trade_data_arr = explode('-', $last_trade_data[0]->quote_bases);
            $category = $last_trade_data_arr[0];
            $ltp = $last_trade_data[0]->pub_last_traded_price;
        }


        //$fundamentalDataOrganized = $StockBangladesh->getFundamentalInfo($instrumentId,$metaKey);
        $fundamentalDataOrganized = FundamentalRepository::getFundamentalData($metaKey, array($instrumentId));
        $epsData = FundamentalRepository::getAnnualizedEPS(array($instrumentId));
        if (isset($epsData[$instrumentId])) {
            $epsData = $epsData[$instrumentId];
        } else {
            $epsData = 'N/A';
        }
        $annualized_eps = 'N/A';
        $eps_text = 'N/A';
        $eps_date = 'N/A';
        $pe = 'N/A';
        if (isset($epsData['annualized_eps'])) {
            $annualized_eps = $epsData['annualized_eps'];
            $pe = $annualized_eps ? $ltp / $annualized_eps : 0;
            $pe = round($pe, 2);
            $eps_text = $epsData['text'];
            if (strlen($epsData['meta_date']) > 5) {
                $eps_date = $epsData['meta_date']->format('d-m-Y');
            } else {
                $eps_date = 'N/A';
            }

        }


        $share_percentage_public = 0;
        $publicText = '';
        if (isset($fundamentalDataOrganized['share_percentage_public'][$instrumentId]['meta_value'])) {
            $publicText = $fundamentalDataOrganized['share_percentage_public'][$instrumentId]['meta_value'] . '%';
            $share_percentage_public = isset($fundamentalDataOrganized['total_no_securities']) ? ($fundamentalDataOrganized['total_no_securities'][$instrumentId]['meta_value'] * $fundamentalDataOrganized['share_percentage_public'][$instrumentId]['meta_value']) / 100 : "";
        }

        $topText = $instrumentInfo->name;
        $topText .= '<*font=arial.ttf,size=9*> CAT: ' . $category . ',';

        if (isset($fundamentalDataOrganized['market_lot'][$instrumentId]['meta_value']))
            $topText .= '<*font=arial.ttf,size=9*> LOT: ' . $fundamentalDataOrganized['market_lot'][$instrumentId]['meta_value'] . ',';

        if (isset($fundamentalDataOrganized['year_end'][$instrumentId]['meta_value']))
            $topText .= '<*font=arial.ttf,size=9*> YearEnd: ' . $fundamentalDataOrganized['year_end'][$instrumentId]['meta_value'] . ',';

        if (isset($fundamentalDataOrganized['net_asset_val_per_share'][$instrumentId]['meta_value']))
            $topText .= '<*font=arial.ttf,size=9*> P/E: ' . $pe . ',';

        $no_of_securities = 0;
        if (isset($fundamentalDataOrganized['total_no_securities'][$instrumentId]['meta_value']))
            $no_of_securities = $fundamentalDataOrganized['total_no_securities'][$instrumentId]['meta_value'];


        $chartData['timeStamps'] = $timeStamps;
        $chartData['closeData'] = $closeData;
        $chartData['openData'] = $openData;
        $chartData['lowData'] = $lowData;
        $chartData['highData'] = $highData;
        $chartData['volData'] = $volData;
        $chartData['lastday'] = $lastday;
        $chartData['open'] = $open;
        $chartData['high'] = $high;
        $chartData['low'] = $low;
        $chartData['close'] = $close;
        $chartData['volume'] = $volume;
        $chartData['publicText'] = $publicText;
        $chartData['topText'] = $topText;
        $chartData['share_percentage_public'] = $share_percentage_public;
        $chartData['extraPoints'] = $extraPoints;
        $chartData['fundamentalDataOrganized'] = $fundamentalDataOrganized;
        $chartData['annualized_eps'] = $annualized_eps;
        $chartData['eps_date'] = $eps_date;
        $chartData['eps_text'] = $eps_text;
        $chartData['mov1'] = $mov1;
        $chartData['mov2'] = $mov2;
        $chartData['avgPeriod1'] = $avgPeriod1;
        $chartData['avgPeriod2'] = $avgPeriod2;


        # Set the data into the chart object
        $m = new \FinanceChart($width);
        $m->setData($chartData['timeStamps'], $chartData['highData'], $chartData['lowData'], $chartData['openData'], $chartData['closeData'], $chartData['volData'], $extraPoints);
        $m->setLegendStyle("normal", 8, Transparent, Transparent);

        $indiArr = explode(",", $Indicators);
        ChartRepository::addIndicator($m, $indiArr[0], $indicatorHeight);
        unset($indiArr[0]);
        $m->addMainChart($mainHeight);

        // $m->addCandleStick(0x33ff33, 0xff3333);

        // $m->addVolBars(75, 0x99ff99, 0xff9999, 0x808080);


        foreach ($indiArr as $indi) {

            ChartRepository::addIndicator($m, $indi, $indicatorHeight);

        }


        $configureArr = explode(",", $configure);
        foreach ($configureArr as $config) {
            ChartRepository::addConfiguration($m, $config, $indicatorHeight);
        }


        // $m->addPlotAreaTitle(BottomLeft, sprintf("<*font=arial.ttf,size=8*>%s - Open: %s High: %s Low: %s Close: %s Volume: %s   NOS: %s Public( %s ): %s", $lastday, $open,$high,$low,$close,$volume,$no_of_securities,$publicText,$share_percentage_public));
       // @ $m->addPlotAreaTitle(BottomLeft, sprintf("<*font=arial.ttf,size=8*>%s - Open: %s High: %s Low: %s Close: %s Volume: %s   NOS: %s Public( %s ): %s", $chartData['lastday'], $chartData['open'],$chartData['high'],$chartData['low'],$chartData['close'],$chartData['volume'],$chartData['fundamentalDataOrganized']['total_no_securities']['meta_value'],$chartData['publicText'],$chartData['share_percentage_public']));
        @$m->addPlotAreaTitle(BottomLeft, sprintf("<*font=arial.ttf,size=8*>%s - Open: %s High: %s Low: %s Close: %s Volume: %s   NOS: %s Public( %s ): %s  NAV: %s  Annualized EPS: %s (%s published at %s)", $chartData['lastday'], $chartData['open'], $chartData['high'], $chartData['low'], $chartData['close'], $chartData['volume'], isset($chartData['fundamentalDataOrganized']['total_no_securities']) ? $chartData['fundamentalDataOrganized']['total_no_securities'][$instrumentId]['meta_value'] : 'N/A', $chartData['publicText'], $chartData['share_percentage_public'], isset($chartData['fundamentalDataOrganized']['net_asset_val_per_share']) ? $chartData['fundamentalDataOrganized']['net_asset_val_per_share'][$instrumentId]['meta_value'] : 'N/A', $chartData['annualized_eps'], $chartData['eps_text'], $chartData['eps_date']));

        ChartRepository::addMovingAvg($m, $mov1, $avgPeriod1, 0x663300);

        ChartRepository::addMovingAvg($m, $mov2, $avgPeriod2, 0x9900ff);

        ChartRepository::addChartType($m, $charttype);
        ChartRepository::addOverlay($m, $overlay);

        # A copyright message at the bottom right corner the title area
        $imageMap = $m->getHTMLImageMap("", "", "title='".$m->getToolTipDateFormat()." {value|G}'");
        if($width > 1000)
        {


            $m->addPlotAreaTitle(BottomRight,"<*font=arial.ttf,size=8*>(c) StockBangladesh Ltd.");
            
        }
            $m->addPlotAreaTitle(BottomRight,"<*font=arial.ttf,size=8*>(c) StockBangladesh Ltd.");
        $viewer = new \WebChartViewer("ta_chart");
        $textBoxObj = $m->addText(650, 270, "www.stockbangladesh.com", 'arial.ttf', 20, 0xc09090, '', 0);
        $textBoxObj->setAlignment(TopRight);
        $m->addPlotAreaTitle(TopLeft, $chartData['topText']);


        //$chartId = md5(String::uuid());
        $chartId = md5($instrumentCode . rand(999, 99999));

        # Create the WebChartViewer object

        # Output the chart
        $chartQuery = $m->makeSession($viewer->getId());

        # Set the chart URL to the viewer
        $viewer->setImageUrl("getchart/" . $chartQuery);
        $viewer->setImageMap($imageMap);

        # Output Javascript chart model to the browser to support tracking cursor
        // se modification
   
                $viewer->setChartModel($m->getJsChartModel());  // SHOULD BE DISABLE IN LIVE AS IT IS NOT WORKING COMPRESSION
        
        // se modification
        // $instrumentList=array_flip ($instrumentList);
// dd($m->getToolTipDateFormat());
        $imageMap = $m->getHTMLImageMap("", "", "title='" . $m->getToolTipDateFormat() . " {value|G}'");

        /*        header("Content-type: image/png");
                print($m->makeChart2(PNG));*/

        return View::make("ta_chart/chart_img")->with('viewer', $viewer)->with('imageMap', $imageMap);

    }

    public function panel(Request $request)
    {
        //$path=public_path('metronic_custom/chart_director/lib/FinanceChart.php');
        //File::requireOnce($path);
        include(app_path() . '/ChartDirector/FinanceChart.php');


        $instrumentCode = $request->input('instrumentCode','DSEX');
        $Indicators = $request->input('Indicators','RSI,MACD');
        $configure = $request->input('configure','VOLBAR');
        $charttype = $request->input('charttype','CandleStick');
        $overlay = $request->input('overlay','BB');
        $mov1 = $request->input('mov1','SMA');
        $avgPeriod1 = $request->input('avgPeriod1',20);
        $mov2 = $request->input('mov2','SMA');
        $avgPeriod2 = $request->input('avgPeriod2',30);
        $adj = $request->input('adj',1);


        $width = 1230; $mainHeight = 320; $indicatorHeight = 90; $extraPoints = 21;

        $instrumentCode = $request->input('instrumentCode','ABBANK');
        $cacheVar = "chartData$instrumentCode";

        $instrumentCode = $request->input('instrumentCode','ABBANK');
        $instrumentCodeArr[]=$instrumentCode;
        $instrumentInfo=InstrumentRepository::getInstrumentsByCode($instrumentCodeArr)->first();
        $instrumentId = $instrumentInfo->id;

        //disabling cache by setting time to 0
        $chartData = Cache::remember("$cacheVar", 0, function () use($request,$extraPoints,$instrumentInfo){

            $reportrange = $request->input('reportrange');
            if ($reportrange == '') {
                $from = date('Y-m-d', strtotime(' -120 days'));
                $to = date('Y-m-d');
            } else {
                $datearr = explode('|', $reportrange);
                $from = $datearr[0];
                $to = $datearr[1];
            }


            $Indicators = $request->input('Indicators','RSI,MACD');
            $configure = $request->input('configure','VOLBAR');
            $charttype = $request->input('charttype','CandleStick');
            $overlay = $request->input('overlay','BB');
            $mov1 = $request->input('mov1','SMA');
            $avgPeriod1 = $request->input('avgPeriod1',20);
            $mov2 = $request->input('mov2','SMA');
            $avgPeriod2 = $request->input('avgPeriod2',30);
            $adj = $request->input('adj',1);

            $instrumentId = $instrumentInfo->id;


            $timeStamps = null; $volData = null; $highData = null; $lowData = null; $openData = null; $closeData = null;

            $instrumentCodeArr=array();





            if ($avgPeriod1 > $extraPoints) {

                $extraPoints = $avgPeriod1;

            }

            if ($avgPeriod2 > $extraPoints) {

                $extraPoints = $avgPeriod2;

            }

            if ($adj)
                $ohlcData = ChartRepository::getAdjustedDailyData($instrumentId,$from,$to,$extraPoints);

            else
                $ohlcData = ChartRepository::getDailyData($instrumentId,$from,$to,$extraPoints);


            $ohlcData['realtimeStamps']=array_reverse($ohlcData['realtimeStamps']);
            $timeStamps = array_reverse($ohlcData['date']);
            $closeData = array_reverse($ohlcData['close']);
            $openData = array_reverse($ohlcData['open']);
            $lowData = array_reverse($ohlcData['low']);
            $highData = array_reverse($ohlcData['high']);
            $volData = array_reverse($ohlcData['volume']);


            $index = count($ohlcData['realtimeStamps']);
            $lastday = $ohlcData['realtimeStamps'][$index - 1];
            $lastday = date("M d, Y", $lastday);
            $open = $openData[$index - 1];
            $high = $highData[$index - 1];
            $low = $lowData[$index - 1];
            $close = $closeData[$index - 1];
            $volume = $volData[$index - 1];

            $metaKey = array();
            $metaKey[] = 'category';
            $metaKey[] = 'market_lot';
            $metaKey[] = 'total_no_securities';
            $metaKey[] = 'net_asset_val_per_share';
            $metaKey[] = 'year_end';
            $metaKey[] = 'share_percentage_public';
            $metaKey[] = 'net_asset_val_per_share';


            //$fundamentalDataOrganized = $StockBangladesh->getFundamentalInfo($instrumentId,$metaKey);
            $fundamentalDataOrganized=FundamentalRepository::getFundamentalData($metaKey,array($instrumentId));
            $epsData = FundamentalRepository::getAnnualizedEPS(array($instrumentId));
            $epsData=$epsData[$instrumentId];
            $annualized_eps='N/A';
            $eps_text='N/A';
            $eps_date='N/A';
            if(isset($epsData['annualized_eps']))
            {
                $annualized_eps = $epsData['annualized_eps'];
                $eps_text = $epsData['text'];
                $eps_date = $epsData['meta_date']->format('d-m-Y');

            }

            $publicText = $fundamentalDataOrganized['share_percentage_public'][$instrumentId]['meta_value'] . '%';

            $topText =$instrumentInfo->name;
            $topText .= '<*font=arial.ttf,size=9*> CAT:- ' . $fundamentalDataOrganized['category'][$instrumentId]['meta_value'] . ',';
            $topText .= '<*font=arial.ttf,size=9*> LOT:- ' . $fundamentalDataOrganized['market_lot'][$instrumentId]['meta_value'] . ',';
            $topText .= '<*font=arial.ttf,size=9*> YearEnd:- ' . $fundamentalDataOrganized['year_end'][$instrumentId]['meta_value'] . ',';
            $topText .= '<*font=arial.ttf,size=9*> NAV:- ' . $fundamentalDataOrganized['net_asset_val_per_share'][$instrumentId]['meta_value'] . ',';

            $share_percentage_public = ($fundamentalDataOrganized['total_no_securities'][$instrumentId]['meta_value'] * $fundamentalDataOrganized['share_percentage_public'][$instrumentId]['meta_value']) / 100;


            $chartData['timeStamps']=$timeStamps;
            $chartData['closeData']=$closeData;
            $chartData['openData']=$openData;
            $chartData['lowData']=$lowData;
            $chartData['highData']=$highData;
            $chartData['volData']=$volData;
            $chartData['lastday']=$lastday;
            $chartData['open']=$open;
            $chartData['high']=$high;
            $chartData['low']=$low;
            $chartData['close']=$close;
            $chartData['volume']=$volume;
            $chartData['publicText']=$publicText;
            $chartData['topText']=$topText;
            $chartData['share_percentage_public']=$share_percentage_public;
            $chartData['extraPoints']=$extraPoints;
            $chartData['fundamentalDataOrganized']=$fundamentalDataOrganized;
            $chartData['annualized_eps']= $annualized_eps;
            $chartData['eps_date']= $eps_date;
            $chartData['eps_text']= $eps_text;
            $chartData['mov1']=$mov1;
            $chartData['mov2']=$mov2;
            $chartData['avgPeriod1']=$avgPeriod1;
            $chartData['avgPeriod2']=$avgPeriod2;

            return $chartData;
        });


        # Set the data into the chart object
        $m = new \FinanceChart($width);
        $m->setData($chartData['timeStamps'], $chartData['highData'], $chartData['lowData'], $chartData['openData'], $chartData['closeData'], $chartData['volData'],$extraPoints);
        $m->setLegendStyle("normal", 8, Transparent, Transparent);
            
        $indiArr = explode(",", $Indicators);
        ChartRepository::addIndicator($m, $indiArr[0], $indicatorHeight);
        unset($indiArr[0]);

        $m->addMainChart($mainHeight);

        // $m->addCandleStick(0x33ff33, 0xff3333);

        // $m->addVolBars(75, 0x99ff99, 0xff9999, 0x808080);

            $i = 0;
        foreach ($indiArr as $indi) {
            ChartRepository::addIndicator($m, $indi, $indicatorHeight);

        }


        $configureArr = explode(",", $configure);
        foreach ($configureArr as $config) {
            ChartRepository::addConfiguration($m, $config, $indicatorHeight);
        }


        $m->addPlotAreaTitle(BottomLeft, sprintf("<*font=arial.ttf,size=8*>%s - Open: %s High: %s Low: %s Close: %s Volume: %s   NOS: %s Public( %s ): %s  NAV: %s  Annualized EPS: %s (%s published at %s)", $chartData['lastday'], $chartData['open'],$chartData['high'],$chartData['low'],$chartData['close'],$chartData['volume'],$chartData['fundamentalDataOrganized']['total_no_securities'][$instrumentId]['meta_value'],$chartData['publicText'],$chartData['share_percentage_public'], $chartData['fundamentalDataOrganized']['net_asset_val_per_share'][$instrumentId]['meta_value'], $chartData['annualized_eps'], $chartData['eps_text'], $chartData['eps_date']));

        ChartRepository::addMovingAvg($m, $mov1, $avgPeriod1, 0x663300);

        ChartRepository::addMovingAvg($m, $mov2, $avgPeriod2, 0x9900ff);

        ChartRepository::addChartType($m,$charttype);
        ChartRepository::addOverlay($m,$overlay);

        # A copyright message at the bottom right corner the title area
        if($width > 500)
        {
            $m->addPlotAreaTitle(BottomRight,"<*font=arial.ttf,size=8*>(c) StockBangladesh Ltd.");
            
        }
        $textBoxObj = $m->addText(650, 270, "www.stockbangladesh.com", 'arial.ttf', 20, 0xc09090, '', 0);
        $textBoxObj->setAlignment(TopRight);
        $m->addPlotAreaTitle(TopLeft, $chartData['topText']);


        //$chartId = md5(String::uuid());
        $chartId = md5($instrumentCode.rand(999,99999));

        # Create the WebChartViewer object
        
        $viewer = new \WebChartViewer("ta_chart");

        # Output the chart
        $chartQuery = $m->makeSession($viewer->getId());

        # Set the chart URL to the viewer
        $viewer->setImageUrl("getchart/" . $chartQuery);


        # Output Javascript chart model to the browser to support tracking cursor

        $viewer->setChartModel($m->getJsChartModel());  // SHOULD BE DISABLE IN LIVE AS IT IS NOT WORKING COMPRESSION
        // $instrumentList=array_flip ($instrumentList);

        // $imageMap = $m->getHTMLImageMap("", "", "title='".$m->getToolTipDateFormat()." {value|G}'");


        return View::make("ta_chart/panel")->with('viewer',$viewer)->with('instrumentInfo', $instrumentInfo);



    }

    public function java_chart()
    {


        $instrument_list=InstrumentRepository::getInstrumentsScripWithIndex();
        $instrumentString=NULL;

        foreach($instrument_list as $instrument)
        {
            $instrumentString .=$instrument->instrument_code.",";
        }

        return View::make("ta_chart/java_chart")->with('instrumentList',$instrumentString);
    }
    public function getchart($chartQuery)

    {
        session_cache_limiter("private_no_expire");
        if (!session_id()) {
            session_start();
        }

        $paramArr = explode('&', $chartQuery);
        $imgArr = explode('=', $paramArr[0]);

        $filename = null;
        $stype = null;
        $image = $_SESSION[$imgArr[1]];

        $contentType = "text/html; charset=utf-8";

        if (strlen($image) >= 3) {
            $c0 = ord($image[0]);
            $c1 = ord($image[1]);
            $c2 = ord($image[2]);
            if (($c0 == 0x47) && ($c1 == 0x49))
                $contentType = "image/gif";
            else if (($c1 == 0x50) && ($c2 == 0x4e))
                $contentType = "image/png";
            else if (($c0 == 0x42) && ($c1 == 0x4d))
                $contentType = "image/bmp";
            else if (($c0 == 0xff) && ($c1 == 0xd8))
                $contentType = "image/jpeg";
            else if (($c0 == 0) && ($c1 == 0))
                $contentType = "image/vnd.wap.wbmp";
            else if ($stype == ".svg")
                $contentType = "image/svg+xml";
            if (($c0 == 0x1f) && ($c1 == 0x8b))
                header("Content-Encoding: gzip");
        }

        header("Content-type: $contentType");
        if ($filename != null)
            header("Content-Disposition: inline; filename=$filename");
        print $image;
        exit;

    }

    public function tooltip_chart($instrumentId)
    {
        $instrumentId=(int) $instrumentId;
        include(app_path() . '/ChartDirector/FinanceChart.php');
        $from = date('Y-m-d', strtotime(' -120 days'));
        $to = date('Y-m-d');
        $extraDays=0;
        $ohlcData = ChartRepository::getAdjustedDailyData($instrumentId,$from,$to,$extraDays);

        $ohlcData['realtimeStamps']=array_reverse($ohlcData['realtimeStamps']);
        $timeStamps = array_reverse($ohlcData['date']);
        $closeData = array_reverse($ohlcData['close']);
        $openData = array_reverse($ohlcData['open']);
        $lowData = array_reverse($ohlcData['low']);
        $highData = array_reverse($ohlcData['high']);
        $volData = array_reverse($ohlcData['volume']);

        $c = new \FinanceChart(450);


# Add a title to the chart
        //$c->addTitle("Finance Chart Demonstration");

# Set the data into the finance chart object
        $c->setData($timeStamps, $highData, $lowData, $openData, $closeData, $volData, $extraDays);
        $c->setLegendStyle("normal", 8, Transparent, Transparent);
# Add the main chart with 240 pixels in height

        $c->addMainChart(240);

# Add candlestick symbols to the main chart, using green/red for up/down days
        $c->addCandleStick(0x26C281, 0xff0000);

        $c->addVolBars(75, 0x99ff99, 0xff9999, 0x808080);

        header("Content-type: image/png");
        print($c->makeChart2(PNG));

//        return View::make("ta_chart/panel")->with('viewer',$viewer)->with('imageMap',$imageMap);



    }

    public function price_scale_chart($high=50, $low=40, $close=45, $text="ABBANK")
    {
        include(app_path() . '/ChartDirector/phpchartdir.php');

        $scal = ($high - $low) / 3;
        // $scal=round($scal,0);
        //$value = 75.35;
# Create an LinearMeter object of size 250 x 75 pixels, using silver background with
# a 2 pixel black 3D depressed border.
        $m = new \LinearMeter(300, 90, 0xffffff, 0xffffff, 0);

# Set the scale region top-left corner at (15, 25), with size of 200 x 50 pixels. The
# scale labels are located on the top (implies horizontal meter)
        $m->setMeter(15, 25, 270, 30, Top);

# Set meter scale from 0 - 100, with a tick every 10 units
        $m->setScale($low, $high, $scal);

# Set 0 - 50 as green (99ff99) zone, 50 - 80 as yellow (ffff66) zone, and 80 - 100 as
# red (ffcccc) zone
        $colorgap = ($high - $low) / 3;


        $m->addZone($low, $low + $colorgap, 0xEDDE15);
        $m->addZone($low + $colorgap, $low + $colorgap + $colorgap, 0xF7BD00);
        $m->addZone($low + $colorgap + $colorgap, $high, 0xF79000);

# Add a blue (0000cc) pointer at the specified value
        $m->addPointer($close, 0x0000cc);

# Add a label at bottom-left (10, 68) using Arial Bold/8 pts/red (c00000)
        $m->addText(10, 75, "$text", 'arialbd.ttf', 8, 0xc00000, BottomLeft);

# Add a text box to show the value formatted to 2 decimal places at bottom right. Use
# white text on black background with a 1 pixel depressed 3D border.
        $textBoxObj = $m->addText(280, 80, $m->formatValue($close, "2"), 'arialbd.ttf', 8,
            0xffffff, BottomRight);
        $textBoxObj->setBackground(0, 0, -1);

# Output the chart
        header("Content-type: image/png");
        print($m->makeChart2(PNG));



    }

}
