@extends('layouts.metronic.default')
<!-- BEGIN GLOBAL MANDATORY STYLES -->

@section('content')
@include('admin_courses.menu')
<div class="row">
  <div class="col-md-12">
    <div class="portlet light portlet-fit portlet-datatable bordered">
      <div class="portlet-title">
          <div class="caption">
            <span class="caption-subject font-green sbold uppercase">Bathes List:</span>
          </div>
      </div>
      <div class="portlet-body">
        <div class="row">
          @include('admin_courses.message')
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="table-container">
                <table class="table table-striped table-bordered table-hover" id="table_filter">
                    <thead>
                      <th class="sorting_asc" tabindex="0" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 10px;">
                        #
                      </th>
                      <th class="sorting" tabindex="1" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 100px;">
                        Participant Name
                      </th>
                      <th class="sorting" tabindex="2" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 50px;">
                        Phone
                      </th>
                      <th class="sorting" tabindex="2" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 80px;">
                        Paid Status
                      </th>
                      <th class="sorting" tabindex="2" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 100px;">
                        Previous Batch
                      </th>
                      <th class="sorting" tabindex="2" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 100px;">
                        Next Batch
                      </th>
                      <th class="sorting" tabindex="2" aria-controls="sample_3" rowspan="1" colspan="1" style="width: 80px;">
                        Action
                      </th>
                    </thead>
                    <tbody id="">
                      @foreach($participants as $participant)

                          <tr>
                            <form action="{{ route('batch_transfer.update', $participant->id) }}" method="post">
                              {{ csrf_field() }}
                              {{ method_field('PUT') }}
                              <td>
                                {{ $loop->iteration }}
                              </td>
                              <td>
                                {{ $participant->p_name }}
                              </td>
                              <td>
                                {{ $participant->p_phone }}
                              </td>
                              <td>
                                {{ $participant->paid_status }}
                              </td>
                              <td>
                                {{ $participant->course_name }} {{ $participant->batch_name }}
                              </td>
                              <td>
                                <select id="course_batch_id" name="course_batch_id" class="form-control" data-live-search="true">
                          				@foreach($transfers as $transfer)
                                    <option value="{{$transfer->id}}">{{$transfer->course_name}} {{ $transfer->batch_name }}</option>
                          				@endforeach
                          			</select>

                              </td>
                              <td>
                                <input type="submit" class="btn btn-default" value="Transfer">
                              </td>
                            </form>
                          </tr>

                      @endforeach
                    </tbody>
                </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>



@endsection
@push('scripts')

  <script>
     $( document ).ready(function() {

       $('table').DataTable();

       $.ajaxSetup({

       });

        lots_of_stuff_already_done = false;

        $('.trashButton').click(function(event){
          //.event.preventDefault();
          if (confirm("Do you want delete item?")) {
            var form = $(this).parents('form:first');
            form.submit();

          }
          else {

          }

    //     alert('sadf');
    //     // $.ajax({
    //     //     url: '/user/4',
    //     //     type: 'DELETE',  // user.destroy
    //     //     success: function(result) {
    //     //         // Do something with the result
    //     //     }
    //     // });
       });
    });
  </script>
@endpush
