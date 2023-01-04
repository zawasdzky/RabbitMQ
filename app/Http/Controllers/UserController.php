<?php

namespace App\Http\Controllers;

use App\Models\{AgeGate};
use App\Jobs\RabbitMQJob;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Support\Facades\DB;

class AgeGateController extends Controller
{
    public function index()
    {
        RabbitMQJob::dispatch('hello');
    }

    public function age()
    {
        try{
            //$response = AgeGate::where('enabled',1)->first();
            //return response()->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
        return response()->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }  
    }

}
