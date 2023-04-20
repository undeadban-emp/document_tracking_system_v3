@extends('layouts.app')
@section('page-title', $serviceName . '-' . $description)
@prepend('page-css')
<link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.1/css/all.min.css'>
<link rel="stylesheet" href="{{ asset('/assets/css/timeline.css') }}">
@endprepend
@section('content')
<div class="card">
     <div class="card-body">
          <div class="float-end mx-3">
               <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ route('document-qr-scanned', $transactionCode) }}" alt="">
                    <p style="color:black;font-size:14px;" class='text-center'>{{ $transactionCode }}
               </p>
          </div>
          <div class="clearfix"></div>
          <div class="container-fluid animated animated-done bootdey" data-animate="fadeIn" data-animate-delay="0.05" style="animation-delay: 0.05s;">
               <h5 class="text-center text-uppercase mt-0 text-dark">
                @foreach ($logs as $log)
                @if ($loop->first)
                    {{ $log->request_description }}
                @endif
                @endforeach
                <br>
                <small>({{ $serviceName }})</small>
               </h5>
               <hr class="hr-lg mt-0 mb-2 w-10 mx-auto hr-primary">
               <div class="timeline timeline-left mx-lg-10">
                    <div style="border-radius:5px;" class="timeline-breaker">User</div>
                    @foreach ($logs as $log)
                        @if($log->status == 'pending')
                            @if ($loop->first)
                                <div class="timeline-item mt-3 row">
                                    <div class=" font-weight-bold text-md-right">
                                        <li @class([ 'h6' , 'p-2' , 'bg-primary text-white'=> $log->stage === 'current',
                                        'text-dark'
                                        ])
                                        >
                                        <i class="fas fa-arrow-right"></i>
                                        {{ $serviceName }} {{ $log->request_description }} - <span class='text-uppercase font-size-15 fw-bold'>{{ Str::upper($log->status) }} </span>
                                        on
                                        {{ $log->updated_at->format('F d, Y h:i A') }}
                                    </li>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                    <!--Timeline item 1-->

                    @foreach($process as $key => $process)

                              <div style="border-radius:5px;" class="timeline-breaker text-center timeline-breaker-middle">
                                   <div>{{ $process->responsible }}</div>
                              </div>
                    <div class="timeline-item mt-3 row">
                         <div class=" font-weight-bold text-md-right">
                              {{ $previous = null; }}
                              <div class="d-none">{{ $first_array = true; }}</div>
                              @foreach($logs as $keys => $document_logs)
                              @if($document_logs->service_index === $process->index)
                              @if($first_array)
                                   @if(!empty($logs))
                                        <p style="font-weight:bold;" class="text-dark "> {{ secondsToTime($resultAll[$key]) }}</p>
                                   @endif
                                   {{ $first_array = false; }}
                              @endif
                              @if($document_logs->status != 'pending')

                              <li @class([ 'h6' , 'p-2' , 'bg-success text-white'=> $document_logs->stage === 'current',
                                'text-dark'
                                ])
                                >


                                <span class='fw-bold'>
                                     {{ $document_logs->stage === 'current' ? '(CURRENT)' : '' }}
                                </span>


                                <i class="fas fa-arrow-right"></i>

                                @if($document_logs->received_by_user?->fullname == null)
                                {{ $serviceName }} {{ $document_logs->request_description }} - <span class='text-uppercase font-size-15 fw-bold'>{{ Str::upper($document_logs->status) == 'LAST' ? 'Released By Admin' : 'Skip' }} </span>
                                @else
                                    {{ $serviceName }} {{ $document_logs->request_description }} - <span class='text-uppercase font-size-15 fw-bold'>{{ Str::upper($document_logs->status) == 'LAST' ? 'Released' : $document_logs->status }}  by {{ Str::upper($document_logs->received_by_user?->fullname) }}</span>
                                @endif


                                @if(Str::upper($document_logs->status) === 'RECEIVED')
                                @if($document_logs->received_by_user?->fullname == null)
                                    <span class=''>by <b>Admin</b></span>
                                @else
                                    <span class=''>by {{ Str::upper($document_logs->received_by_user?->fullname) }}</span>
                                @endif
                                @elseif(Str::upper($document_logs->status) === 'FORWARDED')
                                {{-- <span class=''>by {{ Str::upper($document_logs) }}</span> --}}
                                <span class=''>by {{ Str::upper($document_logs->forwarded_by_user?->fullname) }}</span>
                                @elseif(Str::upper($document_logs->status) === 'RETURNED')
                                <span class='fw-bold'>by {{ Str::upper($document_logs->returnee->fullname) }} <span class='fw-normal'>to</span>
                                     @if($document_logs->return_to->id == Auth::user()->id)
                                     (YOU)
                                     @else
                                     {{ Str::upper($document_logs->return_to->fullname) }}
                                     @endif
                                </span>
                                because of <span class='fw-bold'>{{ Str::upper($document_logs->reasons) }}</span>
                                @endif
                                on
                                   {{  $document_logs->updated_at->format('F d, Y h:i A') }}

                           </li>

                              @endif
                              @endif
                              @endforeach
                              <li style="margin-bottom:-20px;" class="text-center text-dark p-2 fw-bold">
                                <span class=''>{{ Str::upper($process->office?->description) }}</span>
                              </li>

                         </div>
                    </div>
                    @endforeach

                        <div class="timeline-breaker text-center timeline-breaker-middle">
                            <div>End</div>
                        </div>

               </>
          </div>
     </div>
</div>
<!-- end row -->
@push('page-scripts')
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script type="text/javascript">
     $.ajaxSetup({
          headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
     });

</script>
@endpush
@endsection
