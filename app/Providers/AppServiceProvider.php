<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\Position;
use App\Models\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->app->bind('path.public', function() {
        //   return '../../public_html';
        // });

        view()->composer(
            [
                'auth.register',
                'admin.user.create',
                'admin.user.edit',
                'admin.service.create',
                'admin.service.edit',
                'admin.service.process.create',
                'user.account-settings',
            ],
            function ($view) {
                $view->with('positions', Position::get());
                $view->with('offices', Office::get());
            }
        );

        view()->composer([
            'layouts.app', 'user.documents.index'
        ],  function ($view) {
            $documents = UserService::where('user_id', Auth::user()->id)->where('status', '!=', 'disapproved')->where('stage', '!=', 'passed')->get();
            $incoming = UserService::where('forward_to', Auth::user()->id)->where('stage', 'current')->get();
            $outgoing = UserService::where('received_by', Auth::user()->id)->where('status', 'received')->where('stage', 'current')->get();
            $returned = UserService::where('returned_to', Auth::user()->id)->where('status', 'disapproved')->where('stage', 'current')->get();

        //     $filter = UserService::with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->get();
            $filter2 = UserService::with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->first();

        //     foreach($filter as $filters){
        //         if($filters->status == 'received' && $filters->stage == 'incoming'){
        //             $userID = Auth::user()->id;
        //                 $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
        //                 ->whereHas('manager_users', function($q) use ($userID) {
        //                     $q->where('user_id', $userID);
        //                 })
        //                 ->where('received_by', null)
        //                 ->where('stage', 'incoming')
        //                 ->where('status', 'received')
        //                 ->get();
        //         } else {
        //             $userID = Auth::user()->id;
        //             $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
        //                 ->whereHas('manager_users', function($q) use ($userID) {
        //                     $q->where('user_id', $userID);
        //                 })
        //                 ->where('received_by', null)
        //                 ->where('stage', 'current')
        //                 ->where('status', '!=','disapproved')
        //                 ->orWhere('stage', 'pending')
        //                 ->where('status', 'forwarded')
        //                 ->get();
        //         }
        // }

            $userID = Auth::user()->id;
            $filter = UserService::where('stage', 'current')->with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->get();
            $checker = UserService::with('manager_users')->whereHas('manager_users', function($q) {$q->where('user_id', Auth::user()->id);})->where('stage', 'current')->get();
            if($checker == '[]'){
                $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
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
                $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                ->whereHas('manager_users', function($q) use ($userID) {
                    $q->where('user_id', $userID);
                })
                ->where('stage', 'current')
                ->get();
                // foreach($filter as $filters){
                //     if($filters->status == 'received' && $filters->stage == 'current'){
                //         $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                //         ->whereHas('manager_users', function($q) use ($userID) {
                //             $q->where('user_id', $userID);
                //         })
                //         // ->where('received_by', null)
                //         // ->where('service_index', '!=', $filters->service_index)
                //         // ->orWhere('service_index', 1)
                //         ->where('stage', 'current')
                //         ->where('status', 'received')
                //         ->get();
                //     } else {
                //         $manageable = UserService::with('manager_users','information', 'information.process', 'information.requirements')
                //             ->whereHas('manager_users', function($q) use ($userID) {
                //                 $q->where('user_id', $userID);
                //             })
                //             ->where('received_by', null)
                //             ->where('stage', 'current')
                //             ->where('status', '!=','disapproved')
                //             ->orWhere('stage', 'pending')
                //             ->where('status', 'forwarded')
                //             ->get();
                //     }
                // }
            }



            //check id for user role
            // $userID = Auth::user()->id;
            $userRole = DB::table('users')->where('id', $userID)->first();

            $forRelease = UserService::with(['information', 'information.process'])->where('received_by', Auth::user()->id)->where('stage', 'current')->where('status', 'received')->get()->filter(function ($record) {
                return $record->service_index == array_values($record->information->process->pluck('index')->reverse()->toArray())[0];
            });


            $view->with('processing_documents', $documents->unique('tracking_number')->count());
            $view->with('incoming_documents', $incoming->unique('tracking_number')->count());
            $view->with('outgoing_documents', $outgoing->count());
            $view->with('returned_documents', $returned->unique('tracking_number')->count());
            if($filter2 == null){
                $view->with('manage_documents', 0);
            }else{
                $view->with('manage_documents', $manageable->unique('tracking_number')->count());
            }
            $view->with('for_release_documents', $forRelease->count());
            $view->with('userRole', $userRole);
        });
    }
}