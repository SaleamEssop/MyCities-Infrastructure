<?php

namespace App\Http\Controllers;

use App\Http\Services\MeterService;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    public function __construct(MeterService $service)
    {
        $this->service = $service;
    }

    public function costEstimation($meterId, Request $request)
    {
        $response = $this->service->getCostEstimationByMeterId($meterId, $request->get('month', 0));
        if (isset($response['status']) && !$response['status']) {
            return response($response, $response['status_code']);
        }
        return response($response);
    }

    public function completeBill($accountId, Request $request)
    {
        $response = $this->service->getCompleteBillByAccount($accountId, $request->get('month', 0));
        if (isset($response['status']) && !$response['status']) {
            return response($response, $response['status_code']);
        }
        return response($response);
    }
}
