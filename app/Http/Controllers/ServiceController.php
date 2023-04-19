<?php

namespace App\Http\Controllers;

use DataTables;
use Carbon\Carbon;
use App\Models\User;
use Hashids\Hashids;
use App\Models\Office;
use App\Models\Upload;
use App\Models\Service;
use App\Jobs\SendEmailJob;
use App\Models\Requirement;
use App\Models\UserService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\NotifyMessage;
use App\Models\ServiceProcess;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Notifications\SendEmailNotification;
use Symfony\Component\HttpClient\CurlHttpClient;

class ServiceController extends Controller
{
     /**
      * Display a listing of the resource.
      *
      * @return \Illuminate\Http\Response
      */
     public function index()
     {
        $offices = Office::has('services')
        ->withCount('services')
        ->with(['services', 'services.requirements', 'services.process'])
        ->get();
          return view('services.index', [
               'officesWithService' => $offices,
          ]);
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
      * @param  \App\Models\Service  $service
      * @return \Illuminate\Http\Response
      */
        public function show($id)
        {
            $service = Service::with(['process', 'process.office'])
            ->find($id);
            $userID = Auth::user()->id;
            $hashids = new Hashids();
            $userID = $hashids->encode($userID);
            $firstLand = $hashids->encode(1);
            return view('services.show', compact('service', 'userID', 'firstLand'));
        }

     /**
      * Show the form for editing the specified resource.
      *
      * @param  \App\Models\Service  $service
      * @return \Illuminate\Http\Response
      */
     public function edit(Service $service)
     {
          //
     }

     /**
      * Update the specified resource in storage.
      *
      * @param  \Illuminate\Http\Request  $request
      * @param  \App\Models\Service  $service
      * @return \Illuminate\Http\Response
      */
     public function update(Request $request, Service $service)
     {
          //
     }

     /**
      * Remove the specified resource from storage.
      *
      * @param  \App\Models\Service  $service
      * @return \Illuminate\Http\Response
      */
     public function destroy(Service $service)
     {
          //
     }

        //  open receive file
        public function received(Request $request, $transactionCode)
        {
            //   $service = UserService::with(['information', 'information.process', 'information.process.user'])->where('tracking_number', $transactionCode)->where('stage', 'current')->first();

            $service2 = UserService::with(['manager_users', 'information', 'information.process', 'information.requirements'])->where('tracking_number', $transactionCode)->where('stage', 'current')->first();

            $incoming = UserService::with(['avail_by', 'information', 'forwarded_by_user'])->where('tracking_number', $transactionCode)->where('forward_to', Auth::user()->id)->where('stage', 'current')->first();

            $outgoing = UserService::where('tracking_number', $transactionCode)->where('received_by', Auth::user()->id)->where('stage', 'current')->where('status', 'received')->get()->filter(function ($record) {
                return $record->service_index != array_values($record->information->process->pluck('index')->reverse()->toArray())[0];
            });

            $userID = Auth::user()->id;
            $filter = UserService::where('tracking_number', $transactionCode)->where('stage', 'current')->with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->first();
            $checker = UserService::where('tracking_number', $transactionCode)->with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->where('stage', 'current')->first();

            if($checker == null){
                $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                ->whereHas('manager_users', function($q) use ($userID) {
                    $q->where('user_id', $userID);
                })
                ->where('tracking_number', $transactionCode)
                ->where('received_by', null)
                ->where('stage', 'current')
                ->where('status', '!=','disapproved')
                ->orWhere('stage', 'pending')
                ->where('status', 'forwarded')
                ->first();
            }else{
                    if($filter->status == 'received' && $filter->stage == 'current'){
                        $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                        ->whereHas('manager_users', function($q) use ($userID) {
                            $q->where('user_id', $userID);
                        })
                        // ->where('received_by', null)
                        // ->where('service_index', '!=', $filter->service_index)
                        // ->orWhere('service_index', 1)
                        ->where('tracking_number', $transactionCode)
                        ->where('stage', 'current')
                        ->where('status', 'received')
                        ->first();
                    } else {
                        $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                            ->whereHas('manager_users', function($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })
                            ->where('tracking_number', $transactionCode)
                            ->where('received_by', null)
                            ->where('stage', 'current')
                            ->where('status', '!=','disapproved')
                            ->orWhere('stage', 'pending')
                            ->where('status', 'forwarded')
                            ->first();
                    }
                // }
            }

            $forRelease = UserService::where('received_by', Auth::user()->id)->where('stage', 'current')->where('status', 'received')->get()->filter(function ($record) {
                return $record->service_index == array_values($record->information->process->pluck('index')->reverse()->toArray())[0];
            });

            // dd($manage);
            if (is_null($service2)) {
                return redirect()->to(route('service.incoming'));
            }
            $incomingChecker = 0;
            $outgoingChecker = 0;
            $manageChecker = 0;
            $forReleaseChecker = 0;

            if($incoming == null){
                $incomingChecker = 0;
            }else{
                $incomingChecker += 1;
            }

            if($outgoing == '[]'){
                $outgoingChecker = 0;
            }else{
                $outgoingChecker += 1;
            }

            if($manage == null){
                $manageChecker = 0;
            }else{
                $manageChecker += 1;
            }

            if($forRelease == '[]'){
                $forReleaseChecker = 0;
            }else{
                $forReleaseChecker += 1;
            }

            // dd($incomingChecker);
            // dd($outgoingChecker);
            // dd($manageChecker);
            // dd($forReleaseChecker);

            if ($incomingChecker == 0 && $outgoingChecker == 0 && $manageChecker == 0 && $forReleaseChecker == 0) {
                abort(404);
            }


            // if($service2->received_by == null || $service2->forwarded_to == Auth::user()->id || $service2->manager == Auth::user()->id ){

            //     $checker = UserService::with('manager_users','information', 'information.process', 'information.requirements')
            //     ->whereHas('manager_users', function($q) {
            //         $q->where('user_id', Auth::user()->id);
            //     })
            //     ->where('tracking_number', $transactionCode)
            //     ->where('received_by', null)
            //     ->where('stage', 'incoming')
            //     ->where('status', 'received')
            //     ->get();

            //     $service = UserService::with('manager_users','information', 'information.process', 'information.requirements')
            //     ->whereHas('manager_users', function($q) {
            //         $q->where('user_id', Auth::user()->id);
            //     })
            //     ->where('tracking_number', $transactionCode)
            //     ->where('received_by', null)
            //     ->where('stage', 'incoming')
            //     ->where('status', 'received')
            //     ->get();

            //     if (is_null($service)) {
            //         return redirect()->to(route('home'));
            //     }
            //     $isAbort1 = 0;
            //     $isAbort2 = 0;

            //     if($checker != '[]'){
            //         foreach($service as $servicess){
            //             foreach($servicess->manager_users as $services){
            //                 dd($services);
            //                 if ($services->user_id == Auth::user()->id) {
            //                         $isAbort1 += 1;
            //                 }else{
            //                         $isAbort1 = 0;
            //                 }
            //             }
            //         }

            //     }else{
            //         $isAbort1 = 0;
            //         $isAbort2 = 0;
            //     }

            //     if (is_null($service)) {
            //         return redirect()->to(route('home'));
            //     }

            //     if ($service2->forward_to == Auth::user()->id) {
            //         $isAbort2 += 1;
            //     } else {
            //         $isAbort2 = 0;
            //     }
            //     if ($isAbort1 == 0 && $isAbort2 == 0) {
            //         abort(404);
            //     }
            // }else if($service2->received_by != null && $service2->forward_to == Auth::user()->id){
            //     $isAbort1 = 0;
            //     $isAbort2 = 0;
            //     $service2 = UserService::with(['manager_users', 'information', 'information.process', 'information.requirements'])->where('tracking_number', $transactionCode)->where('stage', 'current')->first();
            //     if($service2->received_by == Auth::user()->id){
            //         $isAbort2 += 1;
            //     }else{
            //         $isAbort2 = 0;
            //     }
            //     if ($isAbort2 == 0) {
            //         abort(404);
            //     }
            // }





            $dateApplied = $service2->created_at;

            $trackingNumber = $transactionCode;

            $responsibles = $service2->information->process->where('index', '<', $service2->service_index);

            $attachedRequirements = Upload::where('transaction_code', $trackingNumber)->get();
            return view('services.received', compact('service2', 'dateApplied', 'responsibles', 'attachedRequirements', 'trackingNumber'));

        }

    // function of receive forward release
     public function documentReceived(Request $request, $trackingNumber)
     {
        $action = strtolower($request->action);
        if(Str::upper($action) === 'DISAPPROVE'){

            $this->validate($request, [
                'returnTo' => 'required',
                'reasons' => 'required',
            ]);
            list($returnedTo, $forwardedBy, $serviceIndex) = explode("|", $request->returnTo);

            $service = UserService::where('tracking_number', $request->tracking_number)->where('stage', 'current')->first();
            DB::table('user_service')->where('tracking_number', $request->tracking_number)->where('stage', 'current')
            ->update(['stage'=> 'passed']);
            // Check if Avail by is equal to returned to
            if ($service->user_id == $returnedTo) {
                DB::table('user_service')->insert([
                    'tracking_number' => $trackingNumber,
                    'phone_number' => $service->phone_number,
                    'user_id' => $service->user_id,
                    'service_index' => $service->service_index,
                    'received_by' => Auth::user()->id,
                    'service_id' => $service->service_id,
                    'forward_to' => $service->forward_to,
                    'returned_by' => Auth::user()->id,
                    'returned_to' => $returnedTo,
                    'reasons' => $request->reasons,
                    'request_description' => $service->request_description,
                    'status' => 'disapproved',
                    'stage' => 'current',
                    'manager_id' => $service->manager_id,
                    'created_at' => $service->created_at,
                    'updated_at' => Carbon::now(),
                ]);
                $availBy = User::find($service->user_id);
                if($availBy->phone_number != null){
                    $phoneNumber = $availBy->phone_number;
                }else{
                    $phoneNumber = $service->phone_number;
                }
                $client = new CurlHttpClient(['verify_peer' => false, 'verify_host' => false]);
                $response = $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&t=N8hyd6w');
                $res = ltrim($response->getContent(), '0');
                $res = json_decode($res, true);
                $clientID = $res['sid'];
                $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, ['body' => '40']);
                $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID);
                $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, [
                    'body' => '42["returned", "contact_number='.$phoneNumber.'&trackingNumber=' .$request->tracking_number.'&userIncharge=' . Auth::user()->fullname . '"]'
                ]);

                Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Returned the Transaction with Tracking No. ' . $request->tracking_number);
                return redirect()->to(route('service.outgoing'))->with('success', 'Successfully returned the document');
            }
        }else{
            $currentRecord = UserService::where('tracking_number', $trackingNumber)->where('stage', 'current')->first();
            $trackings = UserService::where('tracking_number', $trackingNumber)->orderBy('id', 'ASC')->get();
            $index_checker = UserService::where('tracking_number', $trackingNumber)->where('status', 'last')->first();
            if ($currentRecord->status === 'pending') {
                // Look for the next received update the current to passed then change the status of next record to current.
                DB::transaction(function () use ($trackings, $currentRecord) {
                    $currentRecord->updated_at;
                    $nextRecord = $trackings->where('service_index', '=', $currentRecord->service_index)->where('status', 'received')->where('stage', 'incoming')->where('stage', 'incoming')->first();
                    $nextRecord->stage = 'current';
                    $nextRecord->forwarded_by = $currentRecord->forwarded_by;
                    $currentRecord->stage = 'passed';
                    $currentRecord->timestamps = false;
                    $currentRecord->save();
                    $nextRecord->received_by = Auth::user()->id;
                    $nextRecord->save(['timestamps' => false]);
                });

                if($currentRecord->avail_by->phone_number != null){
                    $phoneNumber = $currentRecord->avail_by->phone_number;
                }else{
                    $phoneNumber = $currentRecord->phone_number;
                }

                // receive docs with status of pending
                $client = new CurlHttpClient(['verify_peer' => false, 'verify_host' => false]);
                $response = $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&t=N8hyd6w');
                $res = ltrim($response->getContent(), '0');
                $res = json_decode($res, true);
                $clientID = $res['sid'];

                $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, ['body' => '40']);
                $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID);
                $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, [
                    'body' => '42["docRecPending", "contact_number='.$phoneNumber.'&trackingNumber=' .$request->tracking_number.'&userIncharge=' . Auth::user()->fullname . '"]'
                ]);

                Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Received the Transaction with Tracking No. ' . $request->tracking_number);
                return redirect()->to(route('service.incoming'))->with('success', 'Successfully received the document');

            } else if ($currentRecord->status === 'received') {
                // if((int)$index_checker->forwarded_by != $currentRecord->service_index){
                if($index_checker->service_index != $currentRecord->service_index){
                    // check service index if same
                    DB::transaction(function () use ($trackings, $currentRecord, $request) {
                        $currentRecord->updated_at;
                        $nextRecord = $trackings->where('service_index', '=', ($currentRecord->service_index + 1))->where('status', 'received')->where('stage', 'incoming')->first();
                        $nextRecord->stage = 'current';
                        $nextRecord->forwarded_by = $currentRecord->forwarded_by;
                        $currentRecord->stage = 'passed';
                        $currentRecord->timestamps = false;
                        $currentRecord->save();
                        $nextRecord->received_by = Auth::user()->id;
                        $nextRecord->save(['timestamps' => false]);

                        if($currentRecord->avail_by->phone_number != null){
                            $phoneNumber = $currentRecord->avail_by->phone_number;
                        }else{
                            $phoneNumber = $currentRecord->phone_number;
                        }

                        // receive docs with status of forwarded
                        $client = new CurlHttpClient(['verify_peer' => false, 'verify_host' => false]);
                        $response = $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&t=N8hyd6w');
                        $res = ltrim($response->getContent(), '0');
                        $res = json_decode($res, true);
                        $clientID = $res['sid'];

                        $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, ['body' => '40']);
                        $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID);
                        $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, [
                            'body' => '42["docRecForwarded", "contact_number='.$phoneNumber.'&trackingNumber=' .$request->tracking_number.'&userIncharge=' . Auth::user()->fullname . '"]'
                        ]);
                    });
                    Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Received the Transaction with Tracking No. ' . $request->tracking_number);
                    return redirect()->to(route('service.incoming'))->with('success', 'Successfully received the document');
                }else{
                    // same service index
                    DB::transaction(function () use ($trackings, $currentRecord, $request) {
                        $currentRecord->updated_at;
                        $nextRecord = $trackings->where('service_index', '=', $currentRecord->service_index)->where('status', 'last')->where('stage', 'incoming')->first();
                        $nextRecord->stage = 'passed';
                        $nextRecord->forwarded_by = $currentRecord->forwarded_by;
                        $currentRecord->stage = 'passed';
                        $currentRecord->timestamps = false;
                        $currentRecord->save();
                        $nextRecord->received_by = Auth::user()->id;
                        $nextRecord->save(['timestamps' => false]);

                        if($currentRecord->avail_by->phone_number != null){
                            $phoneNumber = $currentRecord->avail_by->phone_number;
                        }else{
                            $phoneNumber = $currentRecord->phone_number;
                        }

                        // receive docs with status of forwarded
                        $client = new CurlHttpClient(['verify_peer' => false, 'verify_host' => false]);
                        $response = $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&t=N8hyd6w');
                        $res = ltrim($response->getContent(), '0');
                        $res = json_decode($res, true);
                        $clientID = $res['sid'];

                        $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, ['body' => '40']);
                        $client->request('GET', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID);
                        $client->request('POST', 'https://surigaodelsur.ph:3030/socket.io/?EIO=4&transport=polling&sid='.$clientID, [
                            'body' => '42["released", "contact_number='.$phoneNumber.'&trackingNumber=' .$request->tracking_number.'&userIncharge=' . Auth::user()->fullname . '"]'
                        ]);
                    });
                    Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Received the Transaction with Tracking No. ' . $request->tracking_number);
                    return redirect()->to(route('service.for-release'))->with('success', 'Successfully Released the document');
                }
            }

            // return redirect()->to(route('service.incoming'))->with('success', 'Successfully received the document');
        }
    }


     public function apply(Request $request, $id)
     {
        $request->validate([
            'request_description' => 'required',
        ]);

          $userID = Auth::user()->id;

          $service = Service::with(['process', 'requirements' => function ($query) {
               $query->where('is_required', 1);
          }])->find($id);



          $attachedRequirements = Requirement::whereIn('id', array_keys($request->file('attachments') ?? []))->sum('is_required');

          $requiredRequirements = array_sum($service->requirements->pluck('is_required')->toArray());


          // Condition for checking if the user submit all the required requirements.
          if ($requiredRequirements > $attachedRequirements) {
               return back()->withErrors([
                    'message' => 'Please comply all the required requirements to proceed.'
               ]);
          }

          $trackingNumber = $service->service_process_id .  date('m') . date('d') . date('Y') . $userID . UserService::get()->groupBy('tracking_number')->count() + 1;

          $filenames = [];

          // attach the files here...
          if (!is_null($request->attachments)) {
               foreach ($request->attachments as $attachment) {
                    $filename = time() . '|' . $attachment->getClientOriginalName();

                    $filenames[] = $filename;
                    $attachment->move(base_path() . '/storage/app/files/', $attachment->getClientOriginalName());
               }
          }

          $user = User::find($userID);
          foreach ($service->process as $index => $process) {
               if ($index == 0) {
                    $user->documents()->attach($service, [
                        'tracking_number' => $trackingNumber,
                        'phone_number' => $request->phone_number,
                        'service_index' => $process->index,
                        'status' => 'pending',
                        'stage' => 'current',
                        'forward_to' => $process->responsible_user,
                        'forwarded_by' => Auth::user()->id,
                        'manager_id' => $process->manager_id,
                        'request_description'   =>  $request->request_description
                    ]);
               }

               $user->documents()->attach($service, [
                    'tracking_number' => $trackingNumber,
                    'phone_number' => $request->phone_number,
                    'service_index' => $process->index,
                    'status' => 'received',
                    'stage' => 'incoming',
                    'forwarded_by' => ($index == 0) ? $process->responsible_user : $service->process[$index - 1]->responsible_user,
                    'forward_to' => ($index + 1)  === $service->process->count() ? null : $service->process[$index + 1]->responsible_user,
                    'manager_id' => ($index + 1) === $service->process->count() ? $process->manager_id : $service->process[$index + 1]->manager_id,
                    // 'manager_id' => $process->manager_id,
                    'request_description'   =>  $request->request_description
               ]);

            if ($service->process->count() == ($index + 1)) {
                $user->documents()->attach($service, [
                    'tracking_number' => $trackingNumber,
                    'phone_number' => $request->phone_number,
                    'service_index' => $process->index,
                    'status' => 'last',
                    'stage' => 'incoming',
                    'forwarded_by' => $service->process[$index]->responsible_user,
                    'forward_to' => ($index + 1)  === $service->process->count() ? null : $service->process[$index + 1]->responsible_user,
                    'manager_id' => 0,
                    // 'manager_id' => ($index + 1) === $service->process->count() ? $process->manager_id : $service->process[$index + 1]->manager_id,
                    'request_description'   =>  $request->request_description
                ]);
            }
          }

          foreach ($filenames as $filename) {
               Upload::updateOrCreate([
                    'transaction_code' => $trackingNumber,
                    'file' => $filename,
               ]);
          }

        Session::put('tracking-number', $trackingNumber);
        Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Create New Transaction' . ' with Tracking No. ' . $trackingNumber);
        return redirect()->route('user.documents')->with('success', 'Successfully Created ' . $service->name . ' with tracking number : ' . $trackingNumber);
     }

    public function reapply(Request $request, string $trackingNumber)
     {
          $previousDocumentLanded = UserService::where('tracking_number', $trackingNumber)
               ->where('status', 'disapproved')
                ->where('stage', 'current')
               ->first();

          $filenames = [];

          // attach the files here...
          if (!is_null($request->attachments)) {
               foreach ($request->attachments as $attachment) {
                    $filename = time() . '|' . $attachment->getClientOriginalName();

                    $filenames[] = $filename;
                    $attachment->move(base_path() . '/storage/app/files/', $attachment->getClientOriginalName());
               }
          }

          foreach ($filenames as $filename) {
               Upload::updateOrCreate([
                    'transaction_code' => $trackingNumber,
                    'file' => $filename,
               ]);
          }


                   DB::table('user_service')->insert([
                         'tracking_number' => $trackingNumber,
                         'phone_number' => $previousDocumentLanded->phone_number,
                         'user_id' => $previousDocumentLanded->user_id,
                         'service_id' => $previousDocumentLanded->service_id,
                         'service_index' => $previousDocumentLanded->service_index,
                         'forwarded_by' => $previousDocumentLanded->returned_to,
                         'forward_to' => $previousDocumentLanded->returned_by,
                         'status' => 'pending',
                         'stage' => 'current',
                         'created_at' => $previousDocumentLanded->created_at,
                         'updated_at' => Carbon::now(),
                    ]);

                    DB::table('user_service')->insert([
                         'tracking_number' => $trackingNumber,
                         'phone_number' => $previousDocumentLanded->phone_number,
                         'user_id' => $previousDocumentLanded->user_id,
                         'service_id' => $previousDocumentLanded->service_id,
                         'service_index' => $previousDocumentLanded->service_index,
                         'forwarded_by' => $previousDocumentLanded->returned_to,
                         'forward_to' => $previousDocumentLanded->forward_to,
                         'manager_id' => $previousDocumentLanded->manager_id,
                         'status' => 'received',
                         'stage' => 'incoming',
                         'created_at' => $previousDocumentLanded->created_at,
                         'updated_at' => Carbon::now(),
                    ]);

                   $previousDocumentLanded->stage = 'passed';
                   $previousDocumentLanded->timestamps = false;
                   $previousDocumentLanded->save();
                   Log::info(Auth::user()->fullname . '('. Auth::user()->userOffice->description .')' . ' Reapply the Transaction with Tracking No. ' . $trackingNumber);
          return redirect()->route('user.documents')->with('success', 'Successfully forward or continue the process of your document with tracking number ' . $trackingNumber);
     }

     public function incoming()
     {
          $hash = new Hashids();

          return view('user.documents.incoming', [
               'pageTitle' => 'Incoming Documents',
               'hash' => $hash,
          ]);
     }

     public function incomingData()
     {
         if (request()->ajax()) {
            $incomingData = UserService::with(['avail_by', 'information', 'forwarded_by_user'])->where('forward_to', Auth::user()->id)->where('stage', 'current')->get();
            return Datatables::of($incomingData)
            ->addColumn('tracking_number', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->addColumn('name', function($row){
                $data = $row->information->name;
                    return $data;
            })
            ->addColumn('office', function($row){
                $office = $row->avail_by->userOffice->description;
                        return $office;
            })
            ->addColumn('description', function($row){
                $data = $row->request_description;
                    return $data;
            })->addColumn('forwarded_by', function($row){
                $data = $row->forwarded_by_user->fullname;
                    return $data;
            })
            ->addColumn('avail_by', function($row){
                $data = $row->avail_by->fullname;
                    return $data;
            })
            ->addColumn('action', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->make(true);
        }
     }

     public function outgoing()
     {
          $hash = new Hashids();

          return view('user.documents.outgoing', [
               'pageTitle' => 'Out-going Documents',
               'hash' => $hash,
          ]);
     }

     public function outgoingData()
     {
         if (request()->ajax()) {
            $outgoingData = UserService::where('received_by', Auth::user()->id)->where('stage', 'current')->where('status', 'received')->get()->filter(function ($record) {
                return $record->service_index != array_values($record->information->process->pluck('index')->reverse()->toArray())[0];
            });
            // $outgoingData = UserService::where('received_by', Auth::user()->id)->where('status', 'received')->where('stage', 'current')->get();
            return Datatables::of($outgoingData)
            ->addColumn('tracking_number', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->addColumn('name', function($row){
                $data = $row->information->name;
                    return $data;
            })
            ->addColumn('description', function($row){
                $data = $row->request_description;
                    return $data;
            })->addColumn('forwarded_by', function($row){
                $data = $row->forwarded_by_user->fullname;
                    return $data;
            })
            ->addColumn('avail_by', function($row){
                $data = $row->avail_by->fullname;
                    return $data;
            })
            ->addColumn('action', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->make(true);
        }
     }

     public function forRelease()
     {
          $hash = new Hashids();

          return view('user.documents.for-release', [
               'pageTitle' => 'For Release Documents',
            //    'user' => $outgoing,
               'hash' => $hash,
          ]);
     }
     public function forReleaseData()
     {
         if (request()->ajax()) {
            // $outgoingData = UserService::where('received_by', Auth::user()->id)->where('status', 'received')->where('stage', 'current')->get();
            $outgoingData = UserService::where('received_by', Auth::user()->id)->where('stage', 'current')->where('status', 'received')->get()->filter(function ($record) {
                return $record->service_index == array_values($record->information->process->pluck('index')->reverse()->toArray())[0];
            });
            return Datatables::of($outgoingData)
            ->addColumn('tracking_number', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->addColumn('name', function($row){
                $data = $row->information->name;
                    return $data;
            })
            ->addColumn('description', function($row){
                $data = $row->request_description;
                    return $data;
            })->addColumn('forwarded_by', function($row){
                $data = $row->forwarded_by_user->fullname;
                    return $data;
            })
            ->addColumn('avail_by', function($row){
                $data = $row->avail_by->fullname;
                    return $data;
            })
            ->addColumn('action', function($row){
                $data = $row->tracking_number;
                    return $data;
            })
            ->make(true);
        }
     }



     public function return()
     {
          $userID = Auth::user()->id;

          $returned = UserService::with(['information', 'information.process', 'information.requirements'])->where('returned_to', $userID)->where('stage', 'current')->get();

          $hash = new Hashids();

          return view('user.documents.returned', [
               'pageTitle' => 'Incoming Documents',
               'user' => $returned,
               'hash' => $hash,
          ]);
     }

     public function manage()
     {
            $userID = Auth::user()->id;
            $filter = UserService::where('stage', 'current')->with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->get();
            $checker = UserService::with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->where('stage', 'current')->get();
            if($checker == '[]'){
                $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                ->whereHas('manager_users', function($q) use ($userID) {
                    $q->where('user_id', $userID);
                })
                ->where('received_by', null)
                ->where('stage', 'current')
                ->where('status', '!=','disapproved')
                ->orWhere('stage', 'pending')
                ->where('status', 'forwarded')
                ->get();
            }else{
                foreach($filter as $filters){
                    if($filters->status == 'received' && $filters->stage == 'current'){
                        $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                        ->whereHas('manager_users', function($q) use ($userID) {
                            $q->where('user_id', $userID);
                        })
                        // ->where('received_by', null)
                        ->where('stage', 'current')
                        ->where('status', 'received')
                        ->get();
                    } else {
                        $manage = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                            ->whereHas('manager_users', function($q) use ($userID) {
                                $q->where('user_id', $userID);
                            })
                            ->where('received_by', null)
                            ->where('stage', 'current')
                            ->where('status', '!=','disapproved')
                            ->orWhere('stage', 'pending')
                            ->where('status', 'forwarded')
                            ->get();
                    }
                }
            }
            return view('user.documents.manage', [
                'pageTitle' => 'Manage Documents',
                'user' => $manage,
            ]);
     }

     public function returned()
     {
          return view('user.documents.returned', [
               'pageTitle' => 'Returned Documents',
          ]);
     }



     public function trackMyDocument(Request $request){
        $ongoing = DB::table('user_service')->where('user_id', Auth::user()->id)
        ->select('id', 'tracking_number', 'user_id', 'status', 'stage', 'created_at', 'updated_at')
        ->where('status', '!=', 'last')
        ->where('stage', '!=','passed')
        ->get()
        ->groupBy('tracking_number')->count();
        $returned = UserService::with(['information', 'information.process', 'information.requirements'])
        ->where('returned_to', Auth::user()->id)
        ->where('stage', 'current')
        ->get()->count();
        $completed = DB::table('user_service')->where('user_id', Auth::user()->id)
        ->select('id', 'tracking_number', 'user_id', 'status', 'stage', 'created_at', 'updated_at')
        ->where('status', 'last')
        ->where('stage', 'passed')
        ->get()
        ->groupBy('tracking_number')->count();

         if ($request->has('tracking_id')) {
                $trackingID = $request->tracking_id;

                $userService = UserService::select('service_id')
                    ->where('tracking_number', $trackingID)->first();

                if (!is_null($userService)) {
                    $documentID = $userService->service_id;
                    $service = Service::with(['process'])->find($documentID);
                    $logs = UserService::with(['receiver', 'receiver.user', 'forwarded_by_user', 'forwarded_to_user'])->where('tracking_number', $trackingID)->where('stage', 'passed')->orWhere('stage', 'current')->orderBy('id', 'ASC')->get()->filter(function ($row) use ($trackingID) {
                        return $row->tracking_number == $trackingID;
                    });;
                } else {
                    $service = 'no-result';
                    $logs = [];
                }

            return view('user.documents.track-my-document', compact('completed', 'ongoing', 'returned'))->with(['service' => $service, 'logs' => $logs]);
        }
        return view('user.documents.track-my-document', compact('completed', 'ongoing', 'returned'));
     }
}