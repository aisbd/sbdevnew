<?php 
function sb_sma($time, $real)
{
	return trader_sma($real, $time);
}
function sb_kama($time, $real)
{
	return trader_kama($real, $time);
}
function sb_ema($time, $real)
{
	return trader_ema($real, $time);
}
function sb_dema($time, $real)
{
	return trader_dema($real, $time);
}
function sb_midpoint($time, $real)
{
	return trader_midpoint($real, $time);
}
function sb_wma($time, $real)
{
	return trader_wma($real, $time);
}
function sb_tema($time, $real)
{
	return trader_tema($real, $time);
}
function sb_trima($time, $real)
{
	return trader_trima($real, $time);
}

function sb_mama($fast, $slow, $real, $type)
{
	$data = trader_mama($real, $fast, $slow);
	if(!is_array($data))
	{
		return $data;
	}
	switch ($type) {
		case 'U':
		return $data[0];
			break;
		case 'L':
		return $data[1];
			break;
	}
}
function sb_t3($time, $vfactor, $real)
{
	return trader_t3($real, $time, $vfactor);
}

function sb_httrendline($real)
{
	return trader_ht_trendline($real);
}

function sb_rsi($time, $real)
{
	return trader_rsi($real, $time);
}
function sb_macd($slow, $fast, $signal,  $real, $type)
{
	// need to cache for duplicate query 
	$data =  trader_macd($real, $fast, $slow, $signal);
	switch (trim($type)) {
		case 'MACD':
			return $data[0];
			break;
		case 'SIGNAL':
			return $data[1];
			break;
		case 'DIVERGENCE':
			return $data[2];
			break;
	}
	return false;
}
function sb_trix($time,  $real)
{
	return trader_trix($real, $time);
}
function sb_aroonosc($time, $high, $low)
{
	return trader_aroonosc($high, $low, $time);
}
function sb_aroon($time, $high, $low, $type)
{
	$data =  trader_aroon($high, $low, $time);
	switch (trim($type)) {
		case 'UP':
		return $data[0];
			break;
		case 'DOWN':
		return $data[1];
			break;
	}
}
function sb_stochrsi ($time, $fastK, $fastD, $maType, $real, $type)
{
	$data = trader_stochrsi ($real, $time, $fastK, $fastD, constant("TRADER_MA_TYPE_".trim($maType)));
	switch (trim($type)) {
		case 'STOCHRSIK':
		return $data[0];
			break;
		case 'STOCHRSID':
		return $data[1];
			break;
	}
}
function sb_stochf ($high, $low, $close, $fastK, $fastD, $maType, $type)
{
	$data = trader_stochf ($high, $low, $close, $fastK, $fastD, constant("TRADER_MA_TYPE_".trim($maType)));
	switch (trim($type)) {
		case 'STOCHFK':
		return $data[0];
			break;
		case 'STOCHFD':
		return $data[1];
			break;
	}
}

function sb_ad($high, $low, $close, $volume)
{
	return trader_ad($high, $low, $close, $volume);
}
function sb_adx($time, $high, $low, $close)
{
	return trader_adx($high, $low, $close, $time);
}
function sb_atr($time, $high, $low, $close)
{
	return trader_atr($high, $low, $close, $time);
}

function sb_cci($time, $high, $low, $close)
{
	return trader_cci($high, $low, $close, $time);
}
function sb_mom($time, $real)
{
	return trader_mom($real, $time);
}

function sb_mfi($time, $high, $low, $close, $volume)
{
	return trader_mfi($high, $low, $close, $volume, $time);
}
function sb_obv ($real, $volume)
{
	return trader_obv ($real, $volume);
}
function sb_roc($time, $real)
{
	return trader_roc($real, $time);
}
function sb_ultosc($high, $low, $close, $t1, $t2, $t3)
{
	return trader_ultosc($high, $low, $close, $t1, $t2, $t3);
}
function sb_willr($t1, $high, $low, $close)
{
	return trader_willr($high, $low, $close, $t1);
}
 function sb_candlepatternlist($candle, $open, $high, $low, $close)
{
	$candle = "trader_".$candle;
	$ar = $candle($open, $high, $low, $close);
	if($ar)
	{
		foreach ($ar as $key => $value) {
		$ar[$key] = str_replace("100", "Yes", $value);
	}
	return $ar;
	}
	return $ar;
}
function sb_percentchange($time, $real)
{
	$result = [];
	$prev = 0;
	$current = $prev+$time;
	while (isset($real[$current])) {
		@$result[$current] = round((($real[$current] - $real[$prev])/$real[$prev])*100, 2);
		$prev++;
		$current++;
	}
	return  $result;
}
function sb_min($time, $real)
{
	// unset($real[count($real)-1]);
	return trader_min($real, $time);
}
function sb_max($time, $real)
{
	// unset($real[count($real)-1]);
	return trader_max($real, $time);
}
function sb_pe($id)
{
	$data = \App\Fundamental::allTogetherPE();
	if(!isset($data[$id])){return "not found"; }
	return  (float) @$data[$id];	
}
function sb_eps($year, $month, $id)
{
	$data = \App\Fundamental::allTogetherEps(trim($year), trim($month));
	if(!isset($data[$id])){return "not found"; }
	return  (float) @$data[$id];
}
function sb_category($id)
{
	return  @\App\Fundamental::allTogetherCategory()[$id];
}
function sb_sector($id)
{
	return  @strtoupper(\App\Fundamental::allTogetherSector()[$id]);
}
function sb_shareholding($type, $year,  $month, $id)
{
	return  @ (float) \App\Fundamental::allTogetherShareHolding($type, trim($year), trim($month))[$id];
}
function sb_nav($year, $id)
{
	$data = \App\Fundamental::allTogetherNav(trim($year));
	if(!isset($data[$id])){return "not found"; }
	return  (float) @$data[$id];	
}

function sb_paidup($id)
{
	return  @(float) \App\Fundamental::allTogetherPaidUp()[$id];
}

function sb_dividend($type, $year, $id)
{
	return  @(float) \App\Fundamental::allTogetherDividend(trim($type), trim($year))[$id];
}

function sb_yearend($month, $id)
{
	switch ($month) {
		case 'JAN':
		$month = "01";
			break;
		case 'MAR':
		$month = "03";
			break;
		case 'DEC':
		$month = "12";
			break;
		case 'SEP':
		$month = "09";
			break;
		case 'JUN':
		$month = "06";
			break;
	}
	// dd(\App\Fundamental::allTogetherYearEnd());
	@$yearend =  \App\Fundamental::allTogetherYearEnd()[$id];
	// dd(\App\Fundamental::allTogetherYearEnd());
	$m = $yearend[5].$yearend[6];
	if( (int) $m == (int) $month)
	{
		return true;
	}
	return false;
}
