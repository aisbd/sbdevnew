<?php/** * Created by PhpStorm. * User: sohail * Date: 4/16/2017 * Time: 12:13 PM */namespace App\Http\ViewComposers;use App\Market;use Illuminate\View\View;use App\Repositories\DataBanksIntradayRepository;use App\Repositories\SectorListRepository;use App\Repositories\InstrumentRepository;use DB;class TradeStats{    /**     * Bind data to the view.     *     * @param  View  $view     * @return void     */    public function compose(View $view)    {        $viewdata = $view->getData();        $instrument_id=13;        if(isset($viewdata['instrument_id']))            $instrument_id=(int)$viewdata['instrument_id'];        $market = Market::getActiveDates();        $market_id = $market[0]->id;        $latest_trade_data_all_instruments=DataBanksIntradayRepository::getLatestTradeDataAll()->keyBy('instrument_id');        $prev_data = DB::table('data_banks_intradays')->where('instrument_id', $instrument_id)->where('market_id', '<', $market_id)->orderByDesc('trade_date')->limit(1)->get()->keyBy('instrument_id');        $position_arr=array();        $value_data=array();        $volume_data=array();        $trade_data=array();        $trade_nature=array();        if(isset($latest_trade_data_all_instruments[$instrument_id]))        {            //////////////////////////  position_arr \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\            $thresold=30;            $position_arr['price_change']['text']="Not Traded";            $position_arr['price_change']['val']= $thresold;            $position_arr['price_change_per']['text']="Not Traded";            $position_arr['price_change_per']['val']= $thresold;            $position_arr['total_volume']['text']="Not Traded";            $position_arr['total_volume']['val'] = $thresold;            $position_arr['total_trades']['text']= "Not Traded";            $position_arr['total_trades']['val'] = $thresold;            $position_arr['total_value']['text']="Not Traded";            $position_arr['total_value']['val'] = $thresold;            $total_shares_traded=count($latest_trade_data_all_instruments);            $position_arr['total_shares_traded'] =$total_shares_traded;/*            $sorted = $latest_trade_data_all_instruments->sortBy('price_change')->toArray();            $position_arr['price_change'] = array_search($instrument_id, array_keys($sorted));*/            $sorted = $latest_trade_data_all_instruments->sortByDesc('price_change_per')->toArray();            $position_arr['price_change_per']['text'] = addOrdinalNumberSuffix(array_search($instrument_id, array_keys($sorted))+1);            $position_arr['price_change_per']['val'] = $thresold-(array_search($instrument_id, array_keys($sorted)) + 1);            $sorted = $latest_trade_data_all_instruments->sortByDesc('total_volume')->toArray();            $position_arr['total_volume']['text'] = addOrdinalNumberSuffix(array_search($instrument_id, array_keys($sorted)) + 1);            $position_arr['total_volume']['val'] = $thresold - (array_search($instrument_id, array_keys($sorted)) + 1);            $sorted = $latest_trade_data_all_instruments->sortByDesc('total_trades')->toArray();            $position_arr['total_trades']['text'] = addOrdinalNumberSuffix(array_search($instrument_id, array_keys($sorted)) + 1);            $position_arr['total_trades']['val'] = $thresold - (array_search($instrument_id, array_keys($sorted)) + 1);            $sorted = $latest_trade_data_all_instruments->sortByDesc('total_value')->toArray();            $position_arr['total_value']['text'] = addOrdinalNumberSuffix(array_search($instrument_id, array_keys($sorted)) + 1);            $position_arr['total_value']['val'] = $thresold - (array_search($instrument_id, array_keys($sorted)) + 1);            //////////////////////////  Value data \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\            $value_data['total_value']=$latest_trade_data_all_instruments[$instrument_id]->total_value;            $value_data['prev_value']=$prev_data[$instrument_id]->total_value;            $value_data['value_change']=$latest_trade_data_all_instruments[$instrument_id]->total_value-$prev_data[$instrument_id]->total_value;            $value_data['value_change_per']=$latest_trade_data_all_instruments[$instrument_id]->total_value?$value_data['value_change']/$latest_trade_data_all_instruments[$instrument_id]->total_value*100:0;            $value_data['value_change_per']=round($value_data['value_change_per'],2);            //////////////////////////  Volume data \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\            $volume_data['total_volume']=$latest_trade_data_all_instruments[$instrument_id]->total_volume;            $volume_data['prev_volume']=$prev_data[$instrument_id]->total_volume;            $volume_data['volume_change']=$latest_trade_data_all_instruments[$instrument_id]->total_volume-$prev_data[$instrument_id]->total_volume;            $volume_data['volume_change_per']= $latest_trade_data_all_instruments[$instrument_id]->total_volume ?$volume_data['volume_change']/$latest_trade_data_all_instruments[$instrument_id]->total_volume*100:0;            $volume_data['volume_change_per']=round($volume_data['volume_change_per'],2);            //////////////////////////  Trade data \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\            $trade_data['total_trade']=$latest_trade_data_all_instruments[$instrument_id]->total_trades;            $trade_data['prev_trade']=$prev_data[$instrument_id]->total_trades;            $trade_data['trade_change']=$latest_trade_data_all_instruments[$instrument_id]->total_trades-$prev_data[$instrument_id]->total_trades;            $trade_data['trade_change_per']= $latest_trade_data_all_instruments[$instrument_id]->total_trades ?$trade_data['trade_change']/$latest_trade_data_all_instruments[$instrument_id]->total_trades*100:0;            $trade_data['trade_change_per']=round($trade_data['trade_change_per'],2);            //////////////////////////  Trade Nature \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\            $trade_nature['value_per_trade']= $trade_data['total_trade']?round($value_data['total_value']/$trade_data['total_trade']*1000000,2):0;            $trade_nature['prev_value_per_trade']= $trade_data['prev_trade']?round($value_data['prev_value']/$trade_data['prev_trade']*1000000,2):0;            $trade_nature['value_per_trade_change']=$trade_nature['value_per_trade']-$trade_nature['prev_value_per_trade'];            $trade_nature['value_per_trade_change_per']= $trade_nature['prev_value_per_trade']?$trade_nature['value_per_trade_change']/$trade_nature['prev_value_per_trade']*100:0;            $trade_nature['value_per_trade_change_per']=round($trade_nature['value_per_trade_change_per'],2);        }        //        $instrument_info=InstrumentRepository::getInstrumentsById($instrument_id)->first();        $sector_pe_data=SectorListRepository::getSectorPE([$instrument_info->sector_list_id]);        $sector_data['sector']=$sector_pe_data['sector_pe_arr'][$instrument_info->sector_list_id]['sector'];        $sector_data['sector_earning']=$sector_pe_data['sector_pe_arr'][$instrument_info->sector_list_id]['sector_earning'];        $sector_data['sector_cap']=$sector_pe_data['sector_pe_arr'][$instrument_info->sector_list_id]['sector_cap'];        $sector_data['pe']=$sector_pe_data['sector_pe_arr'][$instrument_info->sector_list_id]['pe'];        $view->with('position_arr',$position_arr)             ->with('thresold', $thresold)             ->with('value_data',$value_data)             ->with('volume_data',$volume_data)             ->with('trade_data',$trade_data)             ->with('trade_nature',$trade_nature)             ->with('sector_data',$sector_data);    }}