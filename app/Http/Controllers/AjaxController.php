<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\InstrumentRepository;
use App\Repositories\DataBanksIntradayRepository;
use App\Repositories\DataBankEodRepository;
use App\Repositories\FundamentalRepository;
use App\Repositories\SectorListRepository;
use App\Repositories\UserRepository;
use Log;
use Illuminate\Support\Facades\Cache;

class AjaxController extends Controller
{
    //
    public function monitor($inst_id, $period,$dayBefore=0)
    { 
        $minuteChartData = DataBanksIntradayRepository::getDataForMinuteChart($inst_id,1,$dayBefore);

        $instrumentIdArr=array();
        $instrumentIdArr[]=(int) $inst_id;

        $instrumentInfo=InstrumentRepository::getInstrumentsById($instrumentIdArr)->first();

        $returnData=array();
        $returnData['div'] ='mm_div_'.rand(1111,1111111); // required
        $returnData['height'] = 200; // required
        $returnData['title'] = 'name';
        $returnData['instrumentInfo'] = $instrumentInfo->instrument_code;

        $returnData['xcat'] =array_slice($minuteChartData['date_data'],(-1)*$period);
        $returnData['ydata']=array_slice($minuteChartData['volume_data'],(-1)*$period);
        $returnData['xdata']=array_slice($minuteChartData['close_data'],(-1)*$period);

        $returnData['price_chart_color'] = $minuteChartData['yday_close_price']<$minuteChartData['cp']?'#26C281':'#D91E18';
        $returnData['lm_date_time'] = $minuteChartData['lm_date_time'];
        $returnData['bullBear'] = array_reverse($minuteChartData['bullBear']);
        $returnData['day_total_volume'] = $minuteChartData['day_total_volume'];

        return response()
         ->json(collect($returnData))->setTtl(60);
    }

    public function market($inst_id)
    {
        $instrumentIdArr=array();
        $instrumentIdArr[]=(int) $inst_id;

        $instrumentInfo=InstrumentRepository::getInstrumentsById($instrumentIdArr)->first();
        $instrumentCode=$instrumentInfo->instrument_code;
    	$ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, "http://www.dsebd.org/bshis_new1_old.php?w=$instrumentCode&sid=0.3340593789410694");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        $dataArr = explode("\n", $data);
        $returnArr = array();
        $returnArr[] = $dataArr[9];
        
        $returnArr = array_merge($returnArr, array_slice($dataArr, 22, count($dataArr) - 25));
        //print_r($returnArr);
        $data = implode("\n", $returnArr);
        return $data;
    }

    public function yDay($inst_id, $period)
    {
    	# code...
    	date_default_timezone_set('UTC');
    	$inst_ids = array();
    	$inst_ids[] = $inst_id;
    	if ($period < 0) $period = 15;
    	$data = DataBanksIntradayRepository::getYdayMinuteData($inst_ids, $period);

    	$bearVolumeData = array();
    	$neutVolumeData = array();
    	$bullVolumeData = array();
    	$priceData = array();
    	$bull = $neutral = $bear = 0;
    	$lastprice = $data[$inst_id][0]->close_price;
    	foreach ($data[$inst_id] as $row) {    		
    		
			if(($lastprice - $row->close_price) < 0) {
				$bear += $row->total_volume_difference;
				
				$bearVolumeData[] = [$row->lm_date_time->timestamp*1000, $row->total_volume_difference];
			}
    		elseif(($lastprice - $row->close_price) == 0) {
    			$neutral += $row->total_volume_difference;
    			$neutVolumeData[] = [$row->lm_date_time->timestamp*1000, $row->total_volume_difference];
			}
    		elseif(($lastprice - $row->close_price) > 0) {
    			$bull += $row->total_volume_difference;
    			$bullVolumeData[] = [$row->lm_date_time->timestamp*1000, $row->total_volume_difference];
    		}
    		$priceData[] = [$row->lm_date_time->timestamp*1000, $row->close_price];

    		$lastprice = $row->close_price;
    	}
    	
    	$returnData = array();
    	$returnData['bearVolumeData'] = $bearVolumeData;
    	$returnData['bullVolumeData'] = $bullVolumeData;
    	$returnData['neutVolumeData'] = $neutVolumeData;
    	$returnData['priceData'] = $priceData;
    	$returnData['bull'] = $bull;
    	$returnData['bear'] = $bear;
    	$returnData['neutral'] = $neutral;
    	date_default_timezone_set('asia/dhaka');
    	return json_encode($returnData);
    }
    public function saveData(Request $request)
    {
    	$savedUserData = ['symbols'=>array(),'periods' => array()];
    	$savedUserData['symbols'] = explode(',', $request->input('symbols'));
    	$savedUserData['periods'] = explode(',', $request->input('periods'));
    	UserRepository::saveUserInfo(array('market_monitor_settings'),serialize($savedUserData));
    	return redirect()->back();
    }
    public function load_block($param)
    {
        $paramArr=explode(':',$param);
        $viewData=array();
        foreach($paramArr as $each_param)
        {
            $explodeArr=explode('=',$each_param);
            $param_name=$explodeArr[0];
            $param_value=$explodeArr[1];

            $viewData[$param_name]=$param_value;

        }
        $viewData=r_collect($viewData);

        return response()->view('load_block',
            [
                'viewData' => $viewData

            ]
        //);
        )->setTtl(60);
        //return view('load_block',['viewData' => $viewData,'insid' => 12]);
    }

    public function marketDepthData($inst_id)
    {
        $instrumentInfo=InstrumentRepository::getInstrumentsById(array((int) $inst_id))->first();
        $code=$instrumentInfo->instrument_code;
        $getText = getWebPage('http://www.dsebd.org/bshis_new1_old.php?w=' . $code);
        //dd($getText);
        $getText = preg_replace('/Please click on the button to refresh/', ' ', $getText);
        $getText = preg_replace('/<INPUT\b[^>]*>(.*?)[^>]/', ' ', $getText);
        $getText = preg_replace('/<META\b[^>]*>(.*?)[^>]/', ' ', $getText);
        $getText = preg_replace('/<script async\b[^>]*>(.*?)script>/', ' ', $getText);

        $sendData['dsePage']=$getText;
        $sendData['code']=$code;
        $data=json_encode($sendData, JSON_HEX_QUOT | JSON_HEX_TAG);
        return $data;
    }
    public function data_matrix()
    {

        $jsonresult = Cache::remember("data_matrix_data", 1, function () {

            $latestData=DataBanksIntradayRepository::getLatestTradeDataAll();
            $instrument_arr= $latestData->pluck('instrument_id');

            $metaKey=array("paid_up_capital","earning_per_share","net_asset_val_per_share","share_percentage_director","share_percentage_public","share_percentage_institute","share_percentage_foreign","share_percentage_govt");
            //Cache::forget('data_matrix_fundamental');
            $fundamentaInfo = Cache::remember("data_matrix_fundamental", 300 , function () use ($metaKey,$instrument_arr) {
                $fundamentaInfo = FundamentalRepository::getFundamentalData($metaKey, $instrument_arr);
                return $fundamentaInfo;
            });

            //cache for 1 day=1440 minutes
            //Cache::forget('annualized_eps_all_instruments');
            $epsData = Cache::remember("annualized_eps_all_instruments", 300 , function () use ($instrument_arr) {
                $epsData = FundamentalRepository::getAnnualizedEPS($instrument_arr);
                return $epsData;
            });




            $instrumentList=InstrumentRepository::getInstrumentsScripOnly();
            $sectorList=SectorListRepository::getSectorList();

            $maingrid=array();
            foreach($latestData as $arr)
            {
                $temp=array();
                $instrument_id=$arr->instrument_id;
                $quote_bases=explode('-',$arr->quote_bases);
                $category=$quote_bases[0];

                $sector_list_id=$instrumentList->where('id',$instrument_id)->first()->sector_list_id;
                $temp['id']=$arr->instrument_id;
                $temp['code']=$instrumentList->where('id',$instrument_id)->first()->instrument_code;
                $temp['sector']=$sectorList->where('id',$sector_list_id)->first()->name;

                $temp['category']=$category;
                if(isset($fundamentaInfo['net_asset_val_per_share'][$instrument_id]))
                {
                    $temp['nav'] = $fundamentaInfo['net_asset_val_per_share'][$instrument_id]['meta_value'];
                }
                else
                {
                    $temp['nav']=0;
                }


                $temp['lastprice']=$arr->close_price;
                $temp['open']=$arr->open_price;
                $temp['high']=$arr->high_price;
                $temp['low']=$arr->low_price;
                $temp['volume']=$arr->total_volume;
                $temp['value']=$arr->total_value;
                $temp['trade']=$arr->total_trades;
                $temp['ycp']=$arr->yday_close_price;
                $temp['pchange']=$arr->price_change_per;
                $temp['change']=$arr->price_change;

                if(isset($epsData[$instrument_id]))
                {
                    $temp['eps'] = $epsData[$instrument_id]['annualized_eps'];
                }else
                {
                    $temp['eps'] = 0;
                }
                if(isset($epsData[$instrument_id]))
                {
                    $temp['pe'] = $epsData[$instrument_id]['annualized_eps']?round($arr->close_price/$epsData[$instrument_id]['annualized_eps'],2):0;
                }else
                {
                    $temp['pe'] = 0;
                }

                if(isset($fundamentaInfo['earning_per_share'][$instrument_id]))
                {
                    $temp['aud_eps'] = floatval($fundamentaInfo['earning_per_share'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['aud_eps'] = 0;
                }
                if(isset($fundamentaInfo['earning_per_share'][$instrument_id]))
                {
                    $temp['aud_pe'] = floatval($fundamentaInfo['earning_per_share'][$instrument_id]['meta_value'])?round($arr->close_price/floatval($fundamentaInfo['earning_per_share'][$instrument_id]['meta_value']),2):0;
                }else
                {
                    $temp['aud_pe'] = 0;
                }

                if(isset($fundamentaInfo['share_percentage_director'][$instrument_id]))
                {
                    $temp['dir'] = floatval($fundamentaInfo['share_percentage_director'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['dir'] = 0;
                }

                if(isset($fundamentaInfo['share_percentage_public'][$instrument_id]))
                {
                    $temp['pub'] = floatval($fundamentaInfo['share_percentage_public'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['pub'] = 0;
                }

                if(isset($fundamentaInfo['share_percentage_institute'][$instrument_id]))
                {
                    $temp['inst'] = floatval($fundamentaInfo['share_percentage_institute'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['inst'] = 0;
                }

                if(isset($fundamentaInfo['share_percentage_foreign'][$instrument_id]))
                {
                    $temp['for'] = floatval($fundamentaInfo['share_percentage_foreign'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['for'] = 0;
                }

                if(isset($fundamentaInfo['share_percentage_govt'][$instrument_id]))
                {
                    $temp['gov'] = floatval($fundamentaInfo['share_percentage_govt'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['gov'] = 0;
                }

                if(isset($fundamentaInfo['paid_up_capital'][$instrument_id]))
                {
                    $temp['paid_up'] = floatval($fundamentaInfo['paid_up_capital'][$instrument_id]['meta_value']);
                }else
                {
                    $temp['paid_up'] = 0;
                }

                $maingrid[]=$temp;
            }


            $jsonArr=array();
            $firstgrid=array();
            $secondgrid=array();
            $thirdgrid=array();

            $jsonArr['maingrid'] = $maingrid;
            $jsonArr['firstgrid'] = $firstgrid;
            $jsonArr['secondgrid'] = $secondgrid;
            $jsonArr['thirdgrid'] = $thirdgrid;

            $jsonresult = json_encode($jsonArr,JSON_NUMERIC_CHECK);

            return $jsonresult;

        });





        return  $jsonresult;
    }
    public function price_matrix_data()
    {

        $returnData = Cache::remember("price_matrix_data_all", 1, function (){

            $from_date = date("Y-m-d", strtotime("-2 month"));
            $to_date = date("Y-m-d");
            $instrumentList = InstrumentRepository::getInstrumentsScripWithIndex();
            $sectorList = SectorListRepository::getSectorList()->keyBy('id');

            $eodData = DataBankEodRepository::getPriceChangeHistory($from_date, $to_date, array(2, 3, 4, 6, 15, 21, 30), array(), array('close', 'high'));

            $returnData = array();
            foreach ($eodData as $instrument_id => $data) {

                $instrumentInfo = $instrumentList->where('id', $instrument_id)->first();
                if (count($instrumentInfo)) {
                    $sector_list_id = $instrumentInfo->sector_list_id;
                } else {
                    // we dont want index data those are exist in $eodData
                    continue;
                }

                $sector_name = $sectorList->where('id', $sector_list_id)->first()->name;
                $temp = array();
                $temp['code'] = $data[2]['code'];
                $temp['lastprice'] = $data[2]['close'];
                $temp['sector'] = $sector_name;
                $temp['oneDay'] = $data[2]['price_change_per'];
                $temp['twoDay'] = $data[3]['price_change_per'];
                $temp['threeDay'] = $data[4]['price_change_per'];
                $temp['oneWeek'] = $data[6]['price_change_per'];
                $temp['twoWeek'] = $data[15]['price_change_per'];
                $temp['threeWeek'] = $data[21]['price_change_per'];
                $temp['oneMonth'] = $data[30]['price_change_per'];

                $returnData[] = $temp;
            }

            return $returnData;
        });


        return json_encode($returnData,JSON_NUMERIC_CHECK);

    }

    public function watch_matrix()
    {
        $firstGridCodeArr = array();
        if(isset($_REQUEST['firstgrid']))
        {
            $firstGridParam = $_REQUEST['firstgrid'];
            $firstGridParamArr = json_decode(stripslashes($firstGridParam));
            foreach ($firstGridParamArr as $row) {
                $code = trim(stripslashes($row[0]));
                $firstGridCodeArr[$code] = $code;
            }

        }



        $secondGridCodeArr = array();
        if(isset($_REQUEST['secondgrid']))
        {
            $secondGridParam = $_REQUEST['secondgrid'];
            $secondGridParamArr = json_decode(stripslashes($secondGridParam));
            foreach ($secondGridParamArr as $row) {
                $code = trim(stripslashes($row[0]));
                $secondGridCodeArr[$code] = $code;
            }
        }

        $thirdGridCodeArr = array();
        if(isset($_REQUEST['thirdgrid']))
        {
            $thirdGridParam = $_REQUEST['thirdgrid'];
            $thirdGridParamArr = json_decode(stripslashes($thirdGridParam));
            foreach ($thirdGridParamArr as $row) {
                $code = trim(stripslashes($row[0]));
                $thirdGridCodeArr[$code] = $code;
            }

        }


        $instrumentList=InstrumentRepository::getInstrumentsScripOnly();
        $instrumentList=$instrumentList->keyBy('id');
        $sectorList=SectorListRepository::getSectorList();
        $sectorList=$sectorList->keyBy('id');

        //$last_traded_price_all=DataBanksIntradayRepository::getAvailableLTP();

        $last_traded_price_all=DataBanksIntradayRepository::getLatestTradeDataAll();
        $instrument_arr= $last_traded_price_all->pluck('instrument_id');


        //$instrument_arr=collect($last_traded_price_all)->pluck('instrument_id');

        $metaKey=array("net_asset_val_per_share");
        $fundamentaInfo = FundamentalRepository::getFundamentalData($metaKey, $instrument_arr);

        $epsData = Cache::remember("annualized_eps_all_instruments", 300 , function () use ($instrument_arr) {
            $epsData = FundamentalRepository::getAnnualizedEPS($instrument_arr);
            return $epsData;
        });

        $arrall=array();
        $firstgrid=array();
        $secondgrid=array();
        $thirdgrid=array();

        foreach($last_traded_price_all as $instrument)
        {

     /*       if($instrument->sector_list_id==4 || $instrument->sector_list_id==5 || $instrument->sector_list_id==22 || $instrument->sector_list_id==23 || $instrument->sector_list_id==24)
                continue;*/

            $sector=$sectorList[$instrumentList[$instrument->instrument_id]->sector_list_id]->name;
            $category=category($instrument);
            $net_asset_val_per_share=isset($fundamentaInfo['net_asset_val_per_share'][$instrument->instrument_id])?floatval($fundamentaInfo['net_asset_val_per_share'][$instrument->instrument_id]['meta_value']):0;


            $instrument_code=$instrumentList[$instrument->instrument_id]->instrument_code;
            $temp=array();
            $temp['id']=$instrument->instrument_id;
            $temp['code']=$instrument_code;
            $temp['sector']=$sector;
            $temp['category']=$category;
            $temp['market_lot']=1;
            $temp['face_value']=10;
            $temp['nav']=$net_asset_val_per_share;
            $temp['lastprice']=$instrument->close_price;
            $temp['open']=$instrument->open_price;
            $temp['high']=$instrument->high_price;
            $temp['low']=$instrument->low_price;
            $temp['volume']=$instrument->total_volume;
            $temp['value']=$instrument->total_value;
            $temp['trade']=$instrument->total_trades;
            $temp['ycp']=$instrument->yday_close_price;
            $temp['pchange']=$instrument->yday_close_price?($instrument->close_price-$instrument->yday_close_price)/$instrument->yday_close_price*100:0;
            $temp['pchange']=round($temp['pchange'],2);
            $temp['change']=$instrument->close_price-$instrument->yday_close_price;
            $temp['change']=round($temp['change'],2);


            if(isset($epsData[$instrument->instrument_id]))
            {
                $temp['eps'] = $epsData[$instrument->instrument_id]['annualized_eps'];
            }else
            {
                $temp['eps'] = 0;
            }
            if(isset($epsData[$instrument->instrument_id]))
            {
                $temp['pe'] = $epsData[$instrument->instrument_id]['annualized_eps']?round($instrument->close_price/$epsData[$instrument->instrument_id]['annualized_eps'],2):0;
            }else
            {
                $temp['pe'] = 0;
            }

            $arrall [] = $temp;

            if (isset($firstGridCodeArr[$instrument_code])) {
                $firstgrid[] = $temp;
            }
            if (isset($secondGridCodeArr[$instrument_code])) {
                $secondgrid[] = $temp;
            }
            if (isset($thirdGridCodeArr[$instrument_code])) {
                $thirdgrid[] = $temp;
            }



        }



        $arr['maingrid'] = $arrall;
        $arr['firstgrid'] = $firstgrid;
        $arr['secondgrid'] = $secondgrid;
        $arr['thirdgrid'] = $thirdgrid;


        $jsonresult = json_encode($arr);

        // echo '({"total":"' . count ( $result ) . '","results":' . $jsonresult . '})';
        echo $jsonresult;

exit;

    }




}
