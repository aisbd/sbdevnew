@extends('layouts.metronic.default')
@section('content')
@section('title')
  IPO Results
@endsection
<div class="row">
<div class="col-md-12">
    <div class="portlet box green">
        <div class="portlet-title">
            <div class="caption">
              IPO Results
           </div>
            <div class="tools">
                <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
            </div>
        </div>
        <div class="portlet-body">
                                <div class="center form-group" >    
                        <select class="form-control" id="year" onchange="window.location.href='?year='+this.value">
                            <option>Select a Year</option>
                            {!!yearsAsOption()!!}
                         </select>
                        </div>
            @if(count($ipos) == 0)
            Currently there is no IPO. Please check again later.
            @endif
            @foreach( $ipos as $ipo )
            <div class="panel-group accordion" id="ipo-accordion_{{$ipo->id}}">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion1" href="#collapse_{{$ipo->id}}" aria-expanded="false"> <span><h3>{{$ipo->ipo_name}}</h3></span> <br>
                                
                                <span style="text-transform: uppercase;">SUBSCRIPTION PERIOD: {{\Carbon\Carbon::parse($ipo->subscription_open)->format('l, M d, Y')}} -- {{\Carbon\Carbon::parse($ipo->subscription_close)->format('l, M d, Y')}}  </span>
                             </a>
                        </h4>
                    </div>
                    <div id="collapse_{{$ipo->id}}" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                        <div class="panel-body">
                            <div class="col-md-4">
                            </div>
                            <div class="col-md-8">
                                <ul>
                                    @php
                                    $i = 0;
                                    $ipo =  $ipo->toArray(); 
                                    @endphp
                                    @foreach($ipo as $key => $attachment)
                                    @php
                                    $attachment = json_decode($attachment);
                                    if(!is_array($attachment) || $key == 'logo' || !isset($attachment[0]->download_link))
                                    {
                                        continue;
                                    }
                                    $attachment = $attachment[0];
                                    @endphp
                                    <li><a href="{{$attachment->download_link?asset('/storage/'.$attachment->download_link):'javascript:'}}">{{$attachment->original_name}}</a></li>
                                    @php
                                    $i++;
                                    @endphp
                                    @endforeach
                                   </ul>
                                   @if(!$i)
                                   No data found
                                   @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
</div>
@endsection
