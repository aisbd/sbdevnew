<link href="{{ URL::asset('metronic/assets/global/plugins/cubeportfolio/css/cubeportfolio.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('metronic/assets/pages/css/portfolio.min.css') }}" rel="stylesheet" type="text/css" />

<div class="portfolio-content portfolio-3">
    <div class="clearfix">

        <div id="js-filters-lightbox-gallery2" class="cbp-l-filters-button cbp-l-filters-left">
        <div data-filter="*" class="cbp-filter-item-active cbp-filter-item btn btn-xs blue btn-outline uppercase">All</div>
        @foreach($searchKeyArr as $row)
            <div data-filter=".{{$row['filter_key']}}" class="cbp-filter-item btn btn-xs blue btn-outline uppercase">{{$row['label']}}</div>
        @endforeach

        </div>
    </div>
    <div class="scroller" style="height:400px" data-rail-visible="1" data-rail-color="yellow" data-handle-color="#a1b2bd">
        <div id="js-grid-lightbox-gallery" class="cbp">

        @foreach($allNews as $oneInstrumentsNews)
            @foreach($oneInstrumentsNews as $news)


                <div class="cbp-item {{$news->filter_str}}">
                <div class="mt-element-ribbon bg-grey-steel">
                      <div class="ribbon ribbon-right ribbon-clip ribbon-shadow ribbon-round ribbon-border-dash-hor ribbon-color-info uppercase">
                              <div class="ribbon-sub ribbon-clip ribbon-right"></div> {{$news->post_date->format('d-m-Y')}} </div>
                              <div class="ribbon ribbon-border-dash ribbon-shadow ribbon-color-default uppercase">{{$news->prefix}}</div>
                          <p class="ribbon-content">{{$news->details}} </br>{{$news->filter_str}}</p>
                      </div>
                </div>
            @endforeach
        @endforeach
        </div>
    </div>

    {{--<div id="js-filters-lightbox-gallery1" class="cbp-l-filters-dropdown cbp-l-filters-dropdown-floated">
                <div class="cbp-l-filters-dropdownWrap border-grey-salsa">
                    <div class="cbp-l-filters-dropdownHeader uppercase">Sort News</div>
                    <div class="cbp-l-filters-dropdownList">
                        <div data-filter="*" class="cbp-filter-item-active cbp-filter-item uppercase"> All (
                            <div class="cbp-filter-counter"></div> items) </div>

                            @foreach($searchKeyArr as $row)
                             <div data-filter=".{{$row['filter_key']}}" class="cbp-filter-item uppercase"> {{$row['label']}} (
                            <div class="cbp-filter-counter"></div> items) </div>
                            @endforeach

                    </div>
                </div>
    </div>--}}

</div>



<script src="{{ URL::asset('metronic/assets/global/plugins/cubeportfolio/js/jquery.cubeportfolio.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('metronic/assets/pages/scripts/portfolio-3.min.js') }}" type="text/javascript"></script>
<script type="text/javascript">

</script>