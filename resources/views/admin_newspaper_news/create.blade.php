@extends('layouts.metronic.default')
<!-- BEGIN GLOBAL MANDATORY STYLES -->

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light portlet-fit portlet-datatable bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject font-green sbold uppercase">Create News:</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    @include('admin_newspaper_news.message')
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <form action="{{route('news.store')}}" method="POST">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label for="title">News Title:</label>
                                <input type="text" class="form-control" id="course_name" name="title">
                            </div>
                            <div class="form-group">
                                <label for="details">News Details:</label>
                                <textarea class="form-control" name="details"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="published_date">Published Date:</label>
                                <div class="input-group input-large date date-picker" data-date="" data-date-format="dd-mm-yyyy" data-date-viewmode="published_date">
                                    <input type="text" class="form-control" name="published_date">
                                    <span class="input-group-btn">
                                        <button class="btn default" type="button">
                                            <i class="fa fa-calendar"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-default">Add</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>



@endsection
@push('scripts')
<script src="/vendor/unisharp/laravel-ckeditor/ckeditor.js"></script>
<script src="/vendor/unisharp/laravel-ckeditor/adapters/jquery.js"></script>
<script>
// CKEDITOR.replace( 'texarea' );
$('textarea').ckeditor();
</script>
@endpush
