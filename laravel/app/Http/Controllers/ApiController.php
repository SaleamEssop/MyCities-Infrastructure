<?php

namespace App\Http\Controllers;

use App\Models\Ads;
use App\Models\Site;
use App\Models\User;
use App\Models\Meter;
use PHPUnit\Exception;
use App\Models\Account;
use App\Models\Regions;
use App\Models\Settings;
use App\Models\FixedCost;
use App\Models\MeterType;
use App\Models\AdsCategory;
use App\Models\RegionAlarms;
use Illuminate\Http\Request;
use App\Models\MeterReadings;
use App\Models\AccountFixedCost;
use App\Models\RegionCosts;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Models\RegionsAccountTypeCost;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public function addSite(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['user_id', 'title', 'lat', 'lng', 'address'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $siteArr = array(
            'user_id' => $postData['user_id'],
            'title' => $postData['title'],
            'lat' => $postData['lat'],
            'lng' => $postData['lng'],
            'address' => $postData['address']
        );
        if (!empty($postData['email']))
            $siteArr['email'] = $postData['email'];

        $res = Site::create($siteArr);
        if ($res)
            return response()->json(['status' => true, 'code' => 200, 'data' => $res, 'msg' => 'Location added successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function updateSite(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['site_id', 'user_id', 'title', 'lat', 'lng', 'address'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $site = Site::find($postData['site_id']);
        if (empty($site))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, wrong site_id provided!']);

        $site->user_id = $postData['user_id'];
        $site->title = $postData['title'];
        $site->lat = $postData['lat'];
        $site->lng = $postData['lng'];
        $site->address = $postData['address'];

        if (!empty($postData['email']))
            $site->email = $postData['email'];

        if ($site->save())
            return response()->json(['status' => true, 'code' => 200, 'data' => $site, 'msg' => 'Location updated successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getSite(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['user_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, user_id is required!']);

        $sites = Site::where('user_id', $postData['user_id'])->get();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Location retrieved successfully!', 'data' => $sites]);
    }

    public function deleteSite(Request $request)
    {
        $postData = $request->post();
        if (empty($postData['location_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, location_id is required!']);

        DB::beginTransaction();
        try {
            $result = Site::where('id', $postData['location_id'])->delete();
            $result1 = Account::where('site_id', $postData['location_id'])->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
        }

        if ($result)
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Location removed successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function addAccount(Request $request)
    {
        $postData = $request->post();
        DB::beginTransaction();
        if (empty($postData['site_id'])) {
            // No site_id has passed, so this must be the new site case
            // Check for required params in case of adding new site
            $requiredFields = ['user_id', 'address'];
            $validated = validateData($requiredFields, $postData);
            if (!$validated['status'])
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Site creation error: ' . $validated['error']]);

            $siteArr = array(
                'user_id' => $postData['user_id'],
                'title' => $postData['title'] ?? "",
                'lat' => $postData['lat'] ?? "",
                'lng' => $postData['lng'] ?? "",
                'address' => $postData['address'],
            );

            $exists = Site::where($siteArr)->first();
            if (!empty($exists)) {
                DB::rollBack();
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, site with same information already exists!']);
            }

            if (!empty($postData['email']))
                $siteArr['email'] = $postData['email'];

            $site = Site::create($siteArr);
            if ($site)
                $postData['site_id'] = $site->id;
            else
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong while creating new site!']);
        }

        $requiredFields = ['user_id', 'site_id', 'account_name', 'account_number', 'optional_information'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $accArr = array(
            'site_id' => $postData['site_id'],
            'account_name' => $postData['account_name'],
            'account_number' => $postData['account_number'],
        );

        $exists = Account::where($accArr)->first();
        if (!empty($exists)) {
            DB::rollBack();
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account with same information already exists!']);
        }

        // New simplified architecture: use tariff_template_id instead of region_id + account_type_id
        $accArr['tariff_template_id'] = $postData['tariff_template_id'] ?? null;
        $accArr['water_email'] = $postData['water_email'] ?? null;
        $accArr['electricity_email'] = $postData['electricity_email'] ?? null;
        $accArr['billing_date'] = $postData['billing_date'] ?? null;
        $accArr['optional_information'] = $postData['optional_information'] ?? null;
        $accArr['bill_day'] = $postData['bill_day'] ?? null;
        $accArr['read_day'] = $postData['read_day'] ?? null;
        $accArr['bill_read_day_active'] = $postData['bill_read_day_active'] ?? null;

        $res = Account::create($accArr);
        if ($res) {
            // Check if user has provided fixed-costs or not
            // if (!empty($postData['fixed_cost'])) {
            //     $fixedCostArr = [];
            //     $n = 0;
            //     foreach ($postData['fixed_cost'] as $fixedCost) {
            //         $fixedCostArr[$n]['account_id'] = $res->id;
            //         $fixedCostArr[$n]['title'] = $fixedCost['name'];
            //         $fixedCostArr[$n]['value'] = $fixedCost['value'];
            //         $fixedCostArr[$n]['added_by'] = $postData['user_id'];
            //         $fixedCostArr[$n]['created_at'] = date("Y-m-d H:i:s");
            //         if (isset($fixedCost['is_active']))
            //             $fixedCostArr[$n]['is_active'] = $postData['is_active'];
            //         $n++;
            //     }
            //     FixedCost::insert($fixedCostArr);
            // }
            // Get additional costs from tariff template if exists
            $tariff_template = $res->tariffTemplate;
            if(isset($tariff_template->additional) && !empty($tariff_template->additional)){
                $addtional = json_decode($tariff_template->additional);
                $fixedCostArr = [];
                $n = 0;
                foreach ($addtional as $fixedCost) {
                    $fixedCostArr[$n]['account_id'] = $res->id;
                    $fixedCostArr[$n]['title'] = $fixedCost->name;
                    $fixedCostArr[$n]['value'] = $fixedCost->cost;
                    $fixedCostArr[$n]['added_by'] = $postData['user_id'];
                    $fixedCostArr[$n]['created_at'] = date("Y-m-d H:i:s");
                    $fixedCostArr[$n]['is_active'] = 1;
                    $n++;
                }
                FixedCost::insert($fixedCostArr);
            }

            // Check if there are any default fixed costs
            // if (!empty($postData['default_fixed_cost'])) {
            //     $d = 0;
            //     $defaultCostArr = [];
            //     foreach ($postData['default_fixed_cost'] as $defaultCost) {
            //         $defaultCostArr[$d]['account_id'] = $res->id;
            //         $defaultCostArr[$d]['fixed_cost_id'] = $defaultCost['id'];
            //         $defaultCostArr[$d]['value'] = $defaultCost['value'];
            //         $defaultCostArr[$d]['created_at'] = date("Y-m-d H:i:s");
            //         if (isset($defaultCost['is_active']))
            //             $defaultCostArr[$d]['is_active'] = $defaultCost['is_active'];

            //         $d++;
            //     }
            //     AccountFixedCost::insert($defaultCostArr);
            // }

            // $defaultFixedCosts = Account::join('account_fixed_costs as afc', 'afc.account_id', 'accounts.id')
            //     ->join('fixed_costs as fc', 'fc.id', 'afc.fixed_cost_id')
            //     ->where('accounts.id', $res->id)->get();
            $data = Account::with(['fixedCosts', 'defaultFixedCosts.fixedCost'])->find($res->id);
            // $data->defaultFixedCosts = $defaultFixedCosts;

            DB::commit();
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Account added successfully!', 'data' => $data]);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function updateAccount(Request $request)
    {

        $postData = $request->post();
        DB::beginTransaction();
        if (empty($postData['site_id'])) {
            // No site_id has passed, so this must be the new site case
            // Check for required params in case of adding new site
            $requiredFields = ['user_id', 'title', 'lat', 'lng', 'address'];
            $validated = validateData($requiredFields, $postData);
            if (!$validated['status'])
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Site creation error: ' . $validated['error']]);

            $siteArr = array(
                'user_id' => $postData['user_id'],
                'title' => $postData['title'],
                'lat' => $postData['lat'],
                'lng' => $postData['lng'],
                'address' => $postData['address']
            );
            if (!empty($postData['email']))
                $siteArr['email'] = $postData['email'];

            $exists = Site::where($siteArr)->first();
            if (!empty($exists)) {
                DB::rollBack();
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, site with same information already exists!']);
            }

            $site = Site::create($siteArr);
            if ($site)
                $postData['site_id'] = $site->id;
            else
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong while creating new site!']);
        }

        $requiredFields = ['account_id', 'user_id', 'site_id', 'account_name', 'account_number', 'optional_information'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $account = Account::find($postData['account_id']);
        if (empty($account))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, wrong account_id is provided!']);

        $where = [
            'site_id' => $postData['site_id'],
            'account_name' => $postData['account_name'],
            'account_number' => $postData['account_number']
        ];

        $exists = Account::where($where)->where('id', '<>', $postData['account_id'])->first();
        if ($exists) {
            DB::rollBack();
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account with this information already exists!']);
        }

        $account->site_id = $postData['site_id'];
        $account->account_name = $postData['account_name'];
        $account->account_number = $postData['account_number'];
        $account->optional_information = $postData['optional_information'];
        // New simplified architecture: use tariff_template_id instead of region_id + account_type_id
        $account->tariff_template_id = $postData['tariff_template_id'] ?? null;
        $account->water_email = $postData['water_email'] ?? null;
        $account->electricity_email = $postData['electricity_email'] ?? null;
        $account->bill_day = $postData['bill_day'] ?? null;
        $account->read_day = $postData['read_day'] ?? null;
        $account->bill_read_day_active = $postData['bill_read_day_active'] ?? null;
        if (!empty($postData['billing_date']))
            $account->billing_date = $postData['billing_date'];

        if ($account->save()) {
            // Check if user has deleted any fixed costs or not
            if (!empty($postData['removed_fixed_costs_ids'])) {
                $removedCostIDs = $postData['removed_fixed_costs_ids'];
                foreach ($removedCostIDs as $removedCostID)
                    FixedCost::where('id', $removedCostID)->delete();
            }

            // Check if user has provided fixed-costs or not
            if (!empty($postData['fixed_cost'])) {
                FixedCost::where('account_id', $account->id)->delete();
                foreach ($postData['fixed_cost'] as $fixedCost) {
                    $fixedCostArr = array(
                        'account_id' => $account->id,
                        'title' => $fixedCost['name'],
                        'value' => $fixedCost['cost'],
                        // 'added_by' => $postData['user_id'],
                        'created_at' => date("Y-m-d H:i:s")
                    );
                    if (isset($fixedCost['isApplicable']) && $fixedCost['isApplicable'] == true) {
                        $fixedCostArr['is_active'] = 1;
                    } else {
                        $fixedCostArr['is_active'] = 0;
                    }
                    // if (isset($fixedCost['is_active']))
                    //     $fixedCostArr['is_active'] = $fixedCost['is_active'];

                    if (!empty($fixedCost['id']))
                        FixedCost::where('id', $fixedCost['id'])->update($fixedCostArr);
                    else
                        FixedCost::create($fixedCostArr);
                }
                // exit();
            }
            // Check if there are any default fixed costs
            // if (!empty($postData['default_fixed_cost'])) {
            //     foreach ($postData['default_fixed_cost'] as $defaultCost) {
            //         if (empty($defaultCost['id']))
            //             continue;

            //         $upd = array(
            //             'value' => $defaultCost['value']
            //         );

            //         if (isset($defaultCost['is_active']))
            //             $upd['is_active'] = $defaultCost['is_active'];

            //         AccountFixedCost::where('id', $defaultCost['id'])->update($upd);
            //     }
            // }

            $data = Account::with(['fixedCosts', 'defaultFixedCosts.fixedCost'])->find($account->id);

            DB::commit();
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Account updated successfully!', 'data' => $data]);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getAccounts(Request $request)
    {

        $accID = $request->get('account_id');
        if (empty($accID))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);

        // $data = Account::with('site')->where('site_id', $siteID)->get();
        $data = Account::where('id', $accID)->get();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Account retrieved successfully!', 'data' => $data]);
    }

    public function deleteAccount(Request $request)
    {
        

        $postData = $request->post();
        if (empty($postData['account_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);

        DB::beginTransaction();
        try {
            // get Site id
            $get_site_id = Account::where('id', $postData['account_id'])->first();
            
            $site_id = isset($get_site_id->site_id) ? $get_site_id->site_id : null;
            
            if(!empty($site_id)){
                Site::where('id',$site_id)->delete();
            }
            $result = Account::where('id', $postData['account_id'])->first()->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back();
        }

        if ($result)
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Account removed successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getAllData(Request $request)
    {

        // Get sites, accounts, fixedCosts
        if (empty($request->get('user_id')))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, user_id is required!']);

        $userID = $request->get('user_id');
        $defaultCosts = FixedCost::where('is_default', 1)->get();

        $data = Site::with(['account.fixedCosts', 'account.defaultFixedCosts.fixedCost'])->where('user_id', $userID)->get();
        foreach ($data as $site) {
            foreach ($site->account as $account) {
                $accountDefaultAndOtherCosts = [];
                foreach ($account->defaultFixedCosts as $accountCosts) {
                    $accountDefaultAndOtherCosts[] = $accountCosts->fixed_cost_id;
                }

                foreach ($defaultCosts as $defaultCost) {
                    // Check if this default cost is assigned to this account or not
                    if (in_array($defaultCost->id, $accountDefaultAndOtherCosts)) // Continue as it exists already
                        continue;

                    // Now add this default cost in this account as well.
                    $arr = array(
                        'account_id' => $account->id,
                        'fixed_cost_id' => $defaultCost->id,
                        'is_active' => 1,
                    );
                    AccountFixedCost::create($arr);
                    $account->refresh();
                }

                /*if (count($account->defaultFixedCosts) == 0 && count($defaultCosts) > 0) {
                    // Looks like account default cost relationship has not been set yet
                    $arr = [];
                    foreach ($defaultCosts as $defaultCost) {
                        $arr = array(
                            'account_id' => $account->id,
                            'fixed_cost_id' => $defaultCost->id,
                            'is_active' => 1,

                        );
                        AccountFixedCost::create($arr);
                    }
                    $account->refresh();
                }*/
            }
        }

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Data retrieved successfully!', 'data' => $data]);
    }

    public function addMeter(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['account_id', 'meter_type_id', 'meter_title', 'meter_number', 'meter_reading_date', 'meter_reading'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $meterArr = array(
            'account_id' => $postData['account_id'],
            'meter_type_id' => $postData['meter_type_id'],
            'meter_title' => $postData['meter_title'],
            'meter_number' => $postData['meter_number']
        );

        $exists = Meter::where($meterArr)->first();
        if (!empty($exists))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, meter with same information already exists.']);

        $res = Meter::create($meterArr);
        if ($res) {
            // Add meter reading in system
            $meterReading = array(
                'meter_id' => $res->id,
                //'reading_date' => date('Y-m-d', strtotime($postData['meter_reading_date'])),
                'reading_date' => $postData['meter_reading_date'],
                'reading_value' => $postData['meter_reading']
            );
            MeterReadings::create($meterReading);
            $data = Meter::with('readings')->find($res->id);

            return response()->json(['status' => true, 'code' => 200, 'data' => $data, 'msg' => 'Meter added successfully!']);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function addFixedCost(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['account_id', 'user_id'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        if (!empty($postData['fixed_cost'])) {
            foreach ($postData['fixed_cost'] as $fixedCost) {

                if (empty($fixedCost['title']) || empty($fixedCost['value']))
                    continue;

                $costArr['account_id'] = $postData['account_id'];
                $costArr['title'] = $fixedCost['title'];
                $costArr['value'] = $fixedCost['value'];
                $costArr['added_by'] = $postData['user_id'];
                if (isset($fixedCost['is_active']))
                    $costArr['is_active'] = $fixedCost['is_active'];

                if (empty($fixedCost['id']))
                    FixedCost::create($costArr);
                else
                    FixedCost::where('id', $fixedCost['id'])->update($costArr);
            }
        }

        // Check if user has deleted any fixed costs or not
        if (!empty($postData['removed_fixed_costs_ids'])) {
            $removedCostIDs = $postData['removed_fixed_costs_ids'];
            foreach ($removedCostIDs as $removedCostID)
                FixedCost::where('id', $removedCostID)->delete();
        }

        // Get all fixed costs for the account
        $res = Account::with('fixedCosts')->where('id', $postData['account_id'])->get();
        if ($res)
            return response()->json(['status' => true, 'code' => 200, 'data' => $res, 'msg' => 'Fixed cost added successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function addDefaultCost(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['account_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);

        if (!empty($postData['default_fixed_cost'])) {
            foreach ($postData['default_fixed_cost'] as $defaultCost) {
                if (empty($defaultCost['id']))
                    continue;

                $upd = [];
                if (!empty($defaultCost['value']))
                    $upd['value'] = $defaultCost['value'];

                if (isset($defaultCost['is_active']))
                    $upd['is_active'] = $defaultCost['is_active'];

                if (!empty($upd))
                    AccountFixedCost::where('id', $defaultCost['id'])->update($upd);
            }
        }

        // Get all fixed costs for the account
        $res = AccountFixedCost::where('account_id', $postData['account_id'])->get();
        if ($res)
            return response()->json(['status' => true, 'code' => 200, 'data' => $res, 'msg' => 'Default cost added successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function updateMeter(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['meter_id', 'meter_reading_id', 'account_id', 'meter_type_id', 'meter_title', 'meter_number', 'meter_reading_date', 'meter_reading'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $meter = Meter::find($postData['meter_id']);
        $meterReading = MeterReadings::find($postData['meter_reading_id']);
        if (empty($meter) || empty($meterReading))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, wrong meter_id or meter_reading_id provided!']);

        $meter->account_id = $postData['account_id'];
        $meter->meter_type_id = $postData['meter_type_id'];
        $meter->meter_title = $postData['meter_title'];
        $meter->meter_number = $postData['meter_number'];

        if ($meter->save()) {

            //$meterReading->reading_date = date('Y-m-d', strtotime($postData['meter_reading_date']));
            $meterReading->reading_date = $postData['meter_reading_date'];
            $meterReading->reading_value = $postData['meter_reading'];
            $meterReading->save();

            $data = Meter::with('readings')->find($meterReading->id);

            return response()->json(['status' => true, 'code' => 200, 'data' => $data, 'msg' => 'Meter updated successfully!']);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getMeter(Request $request)
    {

        $accountID = $request->get('account_id');
        if (empty($accountID))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);

        $meters = Meter::with('readings')->where('account_id', $accountID)->get();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meters retrieved successfully!', 'data' => $meters]);
    }

    public function getMeterReadings(Request $request)
    {

        $meterID = $request->get('meter_id');
        if (empty($meterID))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, meter_id is required!']);

        $meters = Meter::with('readings')->where('id', $meterID)->get();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meters retrieved successfully!', 'data' => $meters]);
    }

    public function addMeterReadings(Request $request)
    {
        $postData = $request->post();
        $requiredFields = ['meter_id', 'meter_reading_date', 'meter_reading'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $readingImg = null;
        // Check if meter reading image is provided
        if ($request->hasFile('reading_image'))
            $readingImg = $request->file('reading_image')->store('public/readings');

        $siteArr = array(
            'meter_id' => $postData['meter_id'],
            'reading_date' => $postData['meter_reading_date'],
            'reading_value' => $postData['meter_reading'],
            'reading_image' => $readingImg
        );

        $res = MeterReadings::create($siteArr);
        if ($res)
            return response()->json(['status' => true, 'code' => 200, 'data' => $res, 'msg' => 'Meter readings added successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function deleteMeter(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['meter_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, location_id is required!']);

        DB::beginTransaction();
        try {
            $result = Meter::where('id', $postData['meter_id'])->first()->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
        }

        if ($result)
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meter removed successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function deleteMeterReading(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['reading_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, location_id is required!']);

        $result = MeterReadings::where('id', $postData['reading_id'])->delete();
        if ($result)
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meter reading removed successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getMeterTypes(Request $request)
    {

        $meters = MeterType::all();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meters retrieved successfully!', 'data' => $meters]);
    }

    public function verifyEmail(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['email']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, email field is required!']);

        // Now check if this email exists in our system or not
        $result = User::where('email', $postData['email'])->get();
        if (count($result) == 0)
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'No user exists under this email in our system!']);

        // Now that email exists in our system, make a reset code
        $digits = 5;
        $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

        // Add this code into the database column
        $upd = User::where('email', $postData['email'])->update(['password_reset_code' => $code]);
        if (!$upd)
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);

        // Send the code to user email
        require base_path("vendor/autoload.php");

        $mail = new PHPMailer(env('MAILER_DEBUG', false));     // Passing `true` enables exceptions

        $mailerHost = env('MAIL_HOST');
        $mailerUsername = env('MAIL_USERNAME');
        $mailerPassword = env('MAIL_PASSWORD');
        $mailerFrom = env('MAIL_FROM_ADDRESS');
        if (empty($mailerHost) || empty($mailerUsername) || empty($mailerPassword) || empty($mailerFrom))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, mailer setting is missing.']);

        try {
            // Email server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = $mailerHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailerUsername;
            $mail->Password = $mailerPassword;
            $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
            $mail->Port = 587;

            $mail->setFrom($mailerUsername, 'LightsAndWater');
            $mail->addAddress($postData['email']);

            $mail->addReplyTo($mailerUsername, 'LightsAndWater');

            $mail->isHTML(true);

            $mail->Subject = "Password Reset Code";
            $mail->Body    = "Hi, we have received you password reset request. Please use the following code: " . $code;

            if (!$mail->send())
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong while sending code via email.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, an exception occurred!']);
        }

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'A code has been sent to your email.!']);
    }

    public function verifyCode(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['code']) || empty($postData['email']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, code and email fields are required!']);

        $user = User::where(['email' => $postData['email'], 'password_reset_code' => $postData['code']])->get();
        if (count($user) == 1) {
            User::where(['email' => $postData['email'], 'password_reset_code' => $postData['code']])->update(['password_reset_code' => null]);
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Code matched. Please proceed!']);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Code did not matched!']);
    }

    public function resetPassword(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['password']) || empty($postData['email']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, password and email fields are required!']);

        $user = User::where(['email' => $postData['email']])->get();
        if (count($user) == 1) {
            User::where(['email' => $postData['email']])->update(['password' => bcrypt($postData['password'])]);
            return response()->json(['status' => true, 'code' => 200, 'msg' => 'Code matched. Please proceed!']);
        } else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Wrong email provided!']);
    }

    public function updateMeterReadings(Request $request)
    {

        $postData = $request->post();
        $requiredFields = ['meter_reading_id', 'meter_id', 'meter_reading_date', 'meter_reading'];
        $validated = validateData($requiredFields, $postData);
        if (!$validated['status'])
            return response()->json(['status' => false, 'code' => 400, 'msg' => $validated['error']]);

        $reading = MeterReadings::find($postData['meter_reading_id']);
        if (empty($reading))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, wrong meter_reading_id provided!']);

        $reading->meter_id = $postData['meter_id'];
        $reading->reading_date = $postData['meter_reading_date'];
        $reading->reading_value = $postData['meter_reading'];

        if ($reading->save())
            return response()->json(['status' => true, 'code' => 200, 'data' => $reading, 'msg' => 'Meter readings updated successfully!']);
        else
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, something went wrong!']);
    }

    public function getTC()
    {

        $settings = Settings::first();
        $data = ['id' => $settings->id, 'terms_condition' => $settings->terms_condition];
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Terms and conditions retrieved successfully!', 'data' => $data]);
    }

    public function getAds()
    {

        $ads = Ads::with('category')->get();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Ads retrieved  successfully!', 'data' => $ads]);
    }

    public function getFixedCosts(Request $request)
    {

        $postData = $request->post();
        if (empty($postData['account_id']))
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);

        $fixedCosts = FixedCost::with('account')->where('account_id', $postData['account_id'])
            ->where('is_default', 0)->get();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Fixed costs retrieved  successfully!', 'data' => $fixedCosts]);
    }

    public function getDefaultCosts()
    {

        $defaultCosts = FixedCost::select('id', 'account_id', 'title', 'value', 'is_default', 'created_at')->where('is_default', 1)->get();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Default costs retrieved  successfully!', 'data' => $defaultCosts]);
    }

    public function getAdsCategories(Request $request)
    {

        $postData = $request->post();
        if (!empty($postData['category_id']))
            $categories = AdsCategory::with(['ads', 'childs'])->where('id', $postData['category_id'])->WhereNull('parent_id')->get();
        else
            $categories = AdsCategory::with(['ads', 'childs'])->get();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Ads categories retrieved successfully!', 'data' => $categories]);
    }

    public function getAlarms(Request $request)
    {

        $postData = $request->post();
        $whereArr = [];
        if (!empty($postData['date']))
            $whereArr['date'] = date("Y-m-d", strtotime($postData['date']));

        if (!empty($postData['region_id']))
            $whereArr['region_id'] = $postData['region_id'];

        if (!empty($whereArr))
            $alarms = RegionAlarms::where($whereArr)->get();
        else
            $alarms = RegionAlarms::all();

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Alarms retrieved successfully!', 'data' => $alarms]);
    }

    public function getRegions()
    {

        $regions = Regions::select('id', 'name')->get();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Regions retrieved  successfully!', 'data' => $regions]);
    }

    /**
     * Get tariff templates by region.
     * New simplified architecture endpoint.
     */
    public function getTariffTemplatesByRegion($regionId)
    {
        $tariffTemplates = RegionsAccountTypeCost::where('region_id', $regionId)
            ->where('is_active', 1)
            ->select('id', 'template_name', 'start_date', 'end_date')
            ->get();
        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Tariff Templates retrieved successfully!', 'data' => $tariffTemplates]);
    }
    public function getRegionEmails($id)
    {
        if (!empty($id)) {
            $regions = Regions::select('water_email', 'electricity_email')->where('id', $id)->first();
            return $regions;
        }
        return false;
    }
    public function getDefaultCostAccount($uid)
    {
        $fixedCosts = Account::with(['defaultFixedCosts.fixedCost'])->find($uid);
        //echo "<pre>";print_r($fixedCosts);exit()
        $fixedCostArr = [];
        $user_additional_cost = [];
        $rates_rebate = 0;
        $rates = 0;
        if (isset($fixedCosts['defaultFixedCosts']) && !empty($fixedCosts['defaultFixedCosts'])) {
            foreach ($fixedCosts['defaultFixedCosts'] as $key => $value) {
                if ($value->is_active == 1) {
                    if(isset($value['fixedCost']) && !empty($value['fixedCost'])){
                        if ($value['fixedCost']['title'] == "Water Loss Levy" || $value['fixedCost']['title'] == "Refuse Collection") {
                            $user_additional_cost[] = array(
                                'title' => $value['fixedCost']['title'],
                                'total' => $value['value']
                            );
                        } elseif ($value['fixedCost']['title'] == "Rates Rebate") {
                            $rates_rebate  = $value['value'];
                        } elseif ($value['fixedCost']['title'] == "Rates") {
                            $rates  = $value['value'];
                        } else {
                            $fixedCostArr[] = array(
                                'title' => $value['fixedCost']['title'],
                                'total' => $value['value']
                            );
                        }
                    }
                    
                }
            }
        }
        return ['fixedCost' => $fixedCostArr, 'user_additional_cost' => $user_additional_cost, 'rate_rebate' => $rates_rebate, 'rates' => $rates];
    }
    public function getEastimateCost(Request $request)
    {

        $postData = $request->post();

        $water_fullbill = [];
        $electricity_fullbill = [];

        if (!isset($postData['account_id']) && empty($postData['account_id'])) {
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);
        }

        // if (!isset($postData['meter_id']) && empty($postData['meter_id'])) {
        //     return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, meter_id is required!']);
        // }
        $account = Account::with('tariffTemplate')->where('id', $postData['account_id'])->first();
        // New simplified architecture: get tariff template directly from account
        $region_cost_full_bill = $account->tariffTemplate;

        if (empty($region_cost_full_bill)) {

            $response = [
                'status' => false,
                'code' => 400,
                'msg' => 'Please select a tariff template for this account',
                'data' => []
            ];
            return $response;
            //return response()->json(['status' => false, 'code' => 400, 'msg' => 'In Account Please select region and account type', 'data' => []]);
        }
        if (isset($postData['meter_id']) && !empty($postData['meter_id'])) {
            $meters = Meter::with('readings')->where('id', $postData['meter_id'])->get();
        } else {
            $meters = Meter::with('readings')->where('account_id', $postData['account_id'])->get();
        }

        // echo "<pre>";
        // print_r(count($meters));
        // exit();
        if (count($meters) > 0) {
            foreach ($meters as $meter) {
                $response[] = $this->getReadings($postData['account_id'], $meter, $postData['type']);
            }
            if ($postData['type'] == "fullbill") {
                if (isset($response) && !empty($response)) {
                    $account = Account::with('tariffTemplate')->where('id', $postData['account_id'])->first();

                    // New simplified architecture: get tariff template directly from account
                    $region_cost_full_bill = $account->tariffTemplate;
                    $ele_total  = 0;
                    $water_total  = 0;
                    $additional_total  = 0;
                    $subtotal_all_cost = 0;
                    $grand_total = 0;
                    $default_fixed_cost = $this->getDefaultCostAccount($postData['account_id']);
                    $user_rate_rebate = 0;
                    $user_rates = 0;

                    $user_rate_rebate = isset($default_fixed_cost['rate_rebate']) ? $default_fixed_cost['rate_rebate'] : 0;
                    $user_rates = isset($default_fixed_cost['rates']) ? $default_fixed_cost['rates'] : 0;
                    // echo $user_rate_rebate;
                    // echo $user_rates;

                    $user_additional_cost = isset($default_fixed_cost['user_additional_cost']) ? $default_fixed_cost['user_additional_cost'] : [];
                    //echo "<pre>";print_r($response);exit();
                    foreach ($response as $key => $value) {
                        $water_in_project = [];
                        $water_out_project = [];
                        $vat = [];
                        // echo "<pre>";printf($value->type);
                        if (isset($value->type) && $value->type == 1) {
                            $water_fullbill[$value->meter_number]['type'] = $value->type;
                            $water_fullbill[$value->meter_number]['usage'] = $value->usage;
                            $water_fullbill[$value->meter_number]['meter_number'] = $value->meter_number;
                            $water_fullbill[$value->meter_number]['meter_title'] = $value->meter_title;
                            $water_fullbill[$value->meter_number]['water_email'] = $value->water_email;

                            $water_in_project[] = array(
                                'title' => 'water In',
                                'use' => $value->usage,
                                'total' => isset($value->water_in_total) ? $value->water_in_total : 0
                            );
                            $water_out_project[] = array(
                                'title' => 'water Out',
                                'total' => isset($value->water_out_total) ? $value->water_out_total : 0
                            );
                            $waterin_additional = isset($value->waterin_additional) ? $value->waterin_additional : [];
                            $waterout_additional = isset($value->waterout_additional) ? $value->waterout_additional : [];

                            $water_fullbill[$value->meter_number]['projection'] = array_merge($water_in_project, $water_out_project, $waterin_additional, $waterout_additional, $vat);
                            $water_total += array_sum(array_column($water_fullbill[$value->meter_number]['projection'], 'total'));
                            //$water_fullbill['water_total'] = $water_total;
                        } elseif (isset($value->type) && $value->type == 2) {
                            // electricity
                            $electricity_fullbill[$value->meter_number]['type'] = $value->type;
                            $electricity_fullbill[$value->meter_number]['usage'] = $value->usage;
                            $electricity_fullbill[$value->meter_number]['meter_number'] = $value->meter_number;
                            $electricity_fullbill[$value->meter_number]['meter_title'] = $value->meter_title;
                            $electricity_fullbill[$value->meter_number]['electricity_email'] = $value->electricity_email;
                            $ele[] = array(
                                'title' => 'electricity',
                                'use' => $value->usage,
                                'total' => $value->electricity_total,
                            );
                            $electricity_additional = isset($value->electricity_additional) ? $value->electricity_additional : [];

                            $electricity_fullbill[$value->meter_number]['projection'] = array_merge($ele, $electricity_additional);
                            $ele_total += array_sum(array_column($electricity_fullbill[$value->meter_number]['projection'], 'total'));
                        }
                    }
                    $user_additional_cost = FixedCost::select('title', 'value as total')->where('is_active',1)->where('account_id', $postData['account_id'])->get()->toArray();
                    if (isset($user_additional_cost) && !empty($user_additional_cost)) {
                        $use_plus_admin_additional_cost = $user_additional_cost;
                    } else {
                        $use_plus_admin_additional_cost = array_merge($user_additional_cost, $value->common_additional);
                    }
                    // $use_plus_admin_additional_cost = $user_additional_cost;//array_merge($user_additional_cost, $value->common_additional);
                    //echo "<pre>";print_r($use_plus_admin_additional_cost);exit();
                    $additional_total += array_sum(array_column($use_plus_admin_additional_cost, 'total'));
                    //  echo $additional_total;exit();

                    $response_fullbill['water'] = $water_fullbill;
                    $response_fullbill['electricity'] = $electricity_fullbill;
                    $response_fullbill['additional'] = isset($use_plus_admin_additional_cost) ? $use_plus_admin_additional_cost : [];
                    $subtotal_all_cost = number_format($water_total + $ele_total + $additional_total, 2, '.', '');
                    $vat = $subtotal_all_cost * $region_cost_full_bill->vat_percentage / 100;
                    $sub_total_vat  = number_format($vat, 2, '.', '');
                    $grand_total = ($subtotal_all_cost + $sub_total_vat + $user_rates) - $user_rate_rebate;
                    $response_fullbill['final_total'] = array(
                        'subtotal_of_all_cost' => $subtotal_all_cost,
                        'vat' => $sub_total_vat,
                        'total_including_vat' => number_format($subtotal_all_cost + $sub_total_vat, 2, '.', ''),
                        'rates' => number_format($user_rates, 2, '.', ''), // get from customer input,
                        'rebate' => number_format($user_rate_rebate, 2, '.', ''), // get from customer input
                        'grand_total' => number_format($grand_total, 2, '.', '')
                    );
                    if (isset($response_fullbill) && !empty($response_fullbill)) {
                        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Full bill get successfully!', 'data' => $response_fullbill]);
                    } else {
                        return response()->json(['status' => false, 'code' => 400, 'msg' => 'Cost Not Found in Admin Side', 'data' => []]);
                    }
                }
            }
            if ((isset($response) && !empty($response)) && !empty($postData['meter_id'])) {
                return response()->json($response);
            } else {
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Cost Not Found in Admin Side', 'data' => []]);
            }

            if (isset($response) && !empty($response)) {
                return response()->json(['status' => true, 'code' => 200, 'msg' => 'Meters retrieved successfully!', 'data' => $response]);
            } else {
                return response()->json(['status' => false, 'code' => 400, 'msg' => 'Cost Not Found in Admin Side', 'data' => []]);
            }
        } else {
            $response = [
                'status' => false,
                'code' => 400,
                'msg' => 'Please add meter in this account',
                'data' => []
            ];
            return $response;
        }
    }
    public function getReadings($accountID, $meter, $bill_type)
    {

        if ($meter) {
            // water
            // $metersReading = MeterReadings::where('meter_id', 255)->get();
            $metersReading = MeterReadings::where('meter_id', $meter->id)->get();

            if (isset($metersReading) && !empty($metersReading)) {
                foreach ($metersReading as $key => $value) {
                    $reading_dates[] = array('date' => $value->reading_date, 'reading_value' => $value->reading_value);;
                }
                if (count($reading_dates) >= 2) {
                    $reading_arr = array_slice($reading_dates, -2, 2, true);
                    usort($reading_arr, function ($a, $b) {
                        return strtotime($a['date']) - strtotime($b['date']);
                    });

                    $firstReadingDate = date('Y-m-d', strtotime($reading_arr[0]['date'])) ?? null;
                    $firstReading = $reading_arr[0]['reading_value'] ?? null;
                    $endReadingDate = date('Y-m-d', strtotime($reading_arr[1]['date'])) ?? null;
                    $endReading = $reading_arr[1]['reading_value'] ?? null;

                    if (!empty($firstReadingDate) && !empty($firstReading) && !empty($endReadingDate) && !empty($endReading)) {
                        $time1 = strtotime($firstReadingDate);
                        $time2 = strtotime($endReadingDate);

                        $daydiff = floor(($time2 - $time1) / 86400);
                        $final_reading = $endReading - $firstReading;
                        if ($meter->meter_type_id == 1) {
                            // water
                            $final_reading = substr($final_reading, 0, -4);
                        } else {
                            // electricity
                            $final_reading = substr($final_reading, 0, -1);
                        }

                        // previous reading and last reading same then error
                        if (isset($final_reading) && !empty($final_reading)) {
                            $response = $this->getWaterBill($accountID, $final_reading, $meter, $daydiff, $bill_type);
                            // echo "<pre>";print_r($response);exit();
                            if (isset($response) && !empty($response)) {
                                $account = Account::where('id', $accountID)->first();
                                if(isset($account->bill_day) && !empty($account->bill_day)){
                                    $bill_day = $account->bill_day;
                                    $current_month = date($bill_day. ' F, Y');
                                    $current_month = date("d F Y",strtotime($current_month));
                                    $previous_month = date('d F Y', strtotime('-1 months', strtotime($current_month))); 
                                    $response->firstReadingDate = $previous_month;
                                    $response->endReadingDate = $current_month;
                                  
                                }
                               //$response->firstReadingDate = date('d F Y', strtotime($firstReadingDate)) ?? null;
                                //$response->endReadingDate = date('d F Y', strtotime($endReadingDate)) ?? null;
                                if ($meter->meter_type_id == 1) {
                                    // water
                                    $response->firstReading = substr($firstReading, 0, -4) ?? 0;
                                    $response->endReading = substr($endReading, 0, -4) ?? 0;
                                } else {
                                    // electricity
                                    $response->firstReading = substr($firstReading, 0, -1) ?? 0;
                                    $response->endReading = substr($endReading, 0, -1) ?? 0;
                                }
                                return $response;
                            }
                        } else {
                            return [
                                'status' => false,
                                'code' => 400,
                                'msg' => 'Your current reading should be greater than the previous reading.',
                                'data' => []
                            ];
                        }
                    } else {
                        return [
                            'status' => false,
                            'code' => 400,
                            'msg' => 'You have invalid meter reading.',
                            'data' => []
                        ];
                    }
                } else {
                    return [
                        'status' => false,
                        'code' => 400,
                        'msg' => 'Please enter Previous and Current reading',
                        'data' => []
                    ];
                }
            }
        }
    }
    public function getWaterBill($accountID, $reading, $meters, $daydiff, $bill_type)
    {
        //echo "<pre>";print_r($daydiff);exit;
        // if ($reading > 0 && $daydiff > 0) {
        //     $reading = number_format($reading / $daydiff * 31, 2, '.', '');
        // } else {
        //     $reading = $reading;
        // }
        $reading = $reading;
        $type_id = $meters->meter_type_id; // meter type = 1 - water, 2 - electricity
        $meter_id = $meters->id;
        $meter_number = $meters->meter_number;
        $meter_title = $meters->meter_title;
        $account = Account::with('tariffTemplate')->where('id', $accountID)->first();

        // New simplified architecture: get tariff template directly from account
        $region_cost = $account->tariffTemplate;

        if (isset($region_cost) && !empty($region_cost)) {
            $water_in_total  = 0;
            $sub_total  = 0;
            $water_out_total = 0;
            $electricity_total  = 0;
            $ratable_value = 0;
            $month_day = 31;
            $rebate = 0;
            $waterin_additional_total = 0;
            $waterout_additional_total = 0;
            $electricity_additional_total = 0;
            $region_cost->bill_day = $account->bill_day;

            if ($region_cost->ratable_value == 0) {
                $ratable_value = 250000;
            } else {
                $ratable_value = $region_cost->ratable_value;
            }
            // actual calculation
            $common_additional = json_decode($region_cost->additional);

            if (isset($common_additional) && !empty($common_additional)) {
                foreach ($common_additional as $key => $value) {
                    $data[] = array('title' => $value->name, 'total' => $value->cost);
                }
                $region_cost->common_additional = $data;
            } else {
                $region_cost->common_additional = [];
            }
            if ($type_id == 1) {
                // water
                $water_remaning = $reading;
                $electricity_remaning = 0;
                $unit = "kl";

                if ($water_remaning > 0) {
                    // echo $water_remaning;exit();
                    // water in logic
                    if ($region_cost->water_in) {
                        $water_in = json_decode($region_cost->water_in);
                        if (isset($water_in) && !empty($water_in)) {
                            foreach ($water_in as $key => $value) {
                                $minmax = $value->max - $value->min;
                                if ($water_remaning > 0) {
                                    if (($water_remaning - $minmax) <= 0) {

                                        $water_in[$key]->total = number_format($water_remaning * $value->cost, 2, '.', '');
                                        $water_in[$key]->water_remeaning = 0;
                                        $water_remaning = $water_in[$key]->water_remeaning;
                                    } else {

                                        $water_in[$key]->total = number_format($minmax * $value->cost, 2, '.', '');
                                        $water_in[$key]->water_remeaning = $water_remaning - $minmax;
                                        $water_remaning = $water_in[$key]->water_remeaning;
                                    }
                                } else {
                                    $water_in[$key]->water_remeaning = 0;
                                    $water_in[$key]->total = 0;
                                }
                                if ($ratable_value <= 250000 && ($value->max <= 6)) {
                                    $water_in[$key]->total = 0;
                                }
                                $water_in_total += $water_in[$key]->total;
                            }
                            $region_cost->water_in_total = number_format($water_in_total, 2, '.', '');
                        }
                    }
                    // addtional water in logic
                    $waterin_additional = [];
                    if ($region_cost->waterin_additional) {
                        $waterin_additional = json_decode($region_cost->waterin_additional);
                        if (isset($waterin_additional) && !empty($waterin_additional)) {
                            foreach ($waterin_additional as $key => $value) {
                                if ($value->percentage == null) {
                                    $cal_total = $value->cost;
                                } else {
                                    $cal_total = $reading * $value->percentage / 100 * $value->cost;
                                }
                                $waterin_additional[$key]->total =  number_format($cal_total, 2, '.', '');
                                $waterin_additional_total += number_format($cal_total, 2, '.', '');
                            }
                            $region_cost->water_in_related_total = number_format($waterin_additional_total, 2, '.', '');
                            $region_cost->waterin_additional = $waterin_additional;
                            $waterin_additional = $waterin_additional;
                        }
                    }
                    // echo "<pre>";print_r($waterin_additional_total);exit();

                    //water out logic
                    if (!empty($region_cost->water_out)) {
                        $water_out = json_decode($region_cost->water_out);
                        $water_out_remaning = $reading; //$region_cost->water_used;
                        if (isset($water_out) && !empty($water_out)) {
                            foreach ($water_out as $key => $value) {
                                $minmax = $value->max - $value->min;
                                if ($water_out_remaning > 0) {
                                    if (($water_out_remaning - $minmax) <= 0) {
                                        $t = ($water_out_remaning / 100 * $value->percentage) * $value->cost;
                                        $water_out[$key]->total = number_format($t, 2, '.', '');
                                        $water_out[$key]->water_remeaning = 0;
                                        $water_out_remaning = $water_out[$key]->water_remeaning;
                                        // $water_out[$key]->sewage_charge = ($water_out_remaning / 100 * $value->percentage) * 1.48;
                                    } else {
                                        $t = ($minmax / 100 * $value->percentage) * $value->cost;
                                        $water_out[$key]->total = number_format($t, 2, '.', '');
                                        $water_out[$key]->water_remeaning = $water_out_remaning - $minmax;
                                        $water_out_remaning = $water_out[$key]->water_remeaning;
                                        //  $water_out[$key]->sewage_charge = ($minmax / 100 * $value->percentage) * 1.48;
                                    }
                                } else {
                                    $water_out[$key]->total = 0;
                                    // $water_out[$key]->sewage_charge = 0;
                                    $water_out[$key]->water_remeaning = 0;
                                }
                                if ($ratable_value <= 250000 && ($value->max <= 6)) {
                                    $water_out[$key]->total = 0;
                                }
                                $water_out_total += $water_out[$key]->total;
                                //$sewage_charge += $water_out[$key]->sewage_charge;
                            }
                            $region_cost->water_out_total = number_format($water_out_total, 2, '.', '');
                            // $region_cost->sewage_charge = number_format($sewage_charge, 2, '.', '');
                            //  $region_cost->water_out = json_encode($water_out);
                        }
                    }

                    // addtional water out logic
                    $waterout_additional = [];
                    if ($region_cost->waterout_additional) {
                        $waterout_additional = json_decode($region_cost->waterout_additional);
                        if (isset($waterout_additional) && !empty($waterout_additional)) {
                            foreach ($waterout_additional as $key => $value) {
                                if ($value->percentage == null) {
                                    $cal_total = $value->cost;
                                } else {
                                    $cal_total = $reading * $value->percentage / 100 * $value->cost;
                                }
                                $waterout_additional[$key]->total = number_format($cal_total, 2, '.', '');
                                $waterout_additional_total += number_format($cal_total, 2, '.', '');
                            }
                            $region_cost->water_out_related_total = number_format($waterout_additional_total, 2, '.', '');
                            $region_cost->waterout_additional = $waterout_additional;
                            $waterout_additional = $waterout_additional;
                        }
                    }
                }
                // additional cost
                $additional = json_decode($region_cost->additional);

                $sub_total = $water_in_total + $water_out_total + $electricity_total + $waterin_additional_total + $waterout_additional_total + $electricity_additional_total;

                if ($bill_type == "fullbill") {

                    if (isset($additional) && !empty($additional)) {
                        foreach ($additional as $key => $value) {
                            if ($value->cost >= 0) {
                                $sub_total += $value->cost;
                            } else {
                                $rebate += $value->cost;
                            }
                        }
                    }
                }
                // start usages logic
                $region_cost->usage = $reading;
                $region_cost->usage_days = $daydiff;
                
                $region_cost->daily_usage = number_format($reading / $daydiff, 2, '.', '') . ' ' . $unit;
                $daily_uages = $reading / $daydiff;
               // $region_cost->monthly_usage =  number_format($reading / $daydiff * $month_day, 2, '.', ''). ' ' . $unit;
                $monthly_uages =  $daily_uages * $month_day;
                $region_cost->monthly_usage =  number_format($monthly_uages, 2, '.', ''). ' ' . $unit;

                // end usages logic
                $subtotal_final = $sub_total - abs($rebate);

                $region_cost->sub_total = number_format($subtotal_final, 2, '.', '');

                $sub_total_vat = $subtotal_final * $region_cost->vat_percentage / 100;
                $region_cost->sub_total_vat = number_format($sub_total_vat, 2, '.', '');

                $final_total  = $subtotal_final + $sub_total_vat + $region_cost->vat_rate;
                $region_cost->final_total = number_format($final_total, 2, '.', '');


                $water_in_project[] = array(
                    'title' => 'water In',
                    'total' => number_format($water_in_total, 2, '.', '')
                );
                $water_out_project[] = array(
                    'title' => 'water Out',
                    'total' => number_format($water_out_total, 2, '.', '')
                );

                $vat[] = array(
                    'title' => 'VAT',
                    'total' => number_format($region_cost->sub_total_vat, 2, '.', '')
                );

                $region_cost->projection = array_merge($water_in_project, $water_out_project, $waterin_additional, $waterout_additional, $vat);
                $region_cost->daily_cost = number_format($region_cost->final_total / $month_day, 2, '.', '');
                $region_cost->meter_number = $meter_number;
                $region_cost->meter_title = $meter_title;
                $region_cost->type = $type_id;
                $region_cost->meter_id = $meter_id;
                $region_cost->water_email = $region_cost->water_email;
                unset(
                    $region_cost->water_in,
                    $region_cost->water_out,
                    $region_cost->template_name,
                    $region_cost->read_day,
                    $region_cost->start_date,
                    $region_cost->end_date,
                    $region_cost->water_used,
                    $region_cost->electricity_used,
                    // $region_cost->electricity,
                    //  $region_cost->electricity_additional,
                    // $region_cost->additional,
                    $region_cost->billing_date,
                    $region_cost->reading_date,
                    $region_cost->created_at,
                    $region_cost->updated_at,
                    //$region_cost->waterin_additional,
                    //$region_cost->waterout_additional,

                );
                return $region_cost;
            } elseif ($type_id == 2) {
                $electricity_remaning = $reading;
                $water_remaning = 0;
                $unit = "kWh";
                $region_cost->electricity_used = $electricity_remaning;
                // electricity

                if ($electricity_remaning > 0) {

                    $electricity = json_decode($region_cost->electricity);
                    if (isset($electricity) && !empty($electricity)) {
                        foreach ($electricity as $key => $value) {
                            $minmax = $value->max - $value->min;
                            if ($electricity_remaning > 0) {
                                if (($electricity_remaning - $minmax) <= 0) {
                                    //   echo $electricity_remaning;
                                    $electricity[$key]->total = number_format($electricity_remaning * $value->cost, 2, '.', '');
                                    $electricity[$key]->electricity_remeaning = 0;
                                    $electricity_remaning = $electricity[$key]->electricity_remeaning;
                                } else {

                                    $electricity[$key]->total = number_format($minmax * $value->cost, 2, '.', '');
                                    $electricity[$key]->electricity_remeaning = $electricity_remaning - $minmax;
                                    $electricity_remaning = $electricity[$key]->electricity_remeaning;
                                }
                            } else {
                                $electricity[$key]->electricity_remeaning = 0;
                                $electricity[$key]->total = 0;
                            }

                            $electricity_total += $electricity[$key]->total;
                        }

                        $region_cost->electricity_total = number_format($electricity_total, 2, '.', '');
                        // $region_cost->electricity = json_encode($electricity);
                    }
                    // additional cost for electricity
                    $electricity_additional = [];
                    if ($region_cost->electricity_additional) {
                        $electricity_additional = json_decode($region_cost->electricity_additional);
                        if (isset($electricity_additional) && !empty($electricity_additional)) {
                            foreach ($electricity_additional as $key => $value) {
                                if ($value->percentage == null) {
                                    $cal_total = $value->cost;
                                } else {
                                    $cal_total = $region_cost->electricity_used * $value->percentage / 100 * $value->cost;
                                }
                                $electricity_additional[$key]->total =  number_format($cal_total, 2, '.', '');
                                $electricity_additional_total += number_format($cal_total, 2, '.', '');
                            }
                            $region_cost->electricity_related_total = number_format($electricity_additional_total, 2, '.', '');
                            $region_cost->electricity_additional = $electricity_additional;
                            $electricity_additional = $region_cost->electricity_additional;
                        }
                    }
                }

                $additional = json_decode($region_cost->additional);
                $sub_total = $electricity_total  + $electricity_additional_total;
                if (isset($additional) && !empty($additional)) {
                    foreach ($additional as $key => $value) {
                        if ($value->cost >= 0) {
                            $sub_total += $value->cost;
                        } else {
                            $rebate += $value->cost;
                        }
                    }
                }
                // start usages logic
                $region_cost->usage = $reading;
                $region_cost->usage_days = $daydiff;

                $region_cost->daily_usage = number_format($reading / $daydiff, 2, '.', '') . ' ' . $unit;
                $region_cost->monthly_usage =  number_format($reading / $daydiff * $month_day, 2, '.', '');

                // end usages logic
                $subtotal_final = $sub_total - abs($rebate);

                $region_cost->sub_total = number_format($subtotal_final, 2, '.', '');

                $sub_total_vat = $subtotal_final * $region_cost->vat_percentage / 100;
                $region_cost->sub_total_vat = number_format($sub_total_vat, 2, '.', '');

                $final_total  = $subtotal_final + $sub_total_vat + $region_cost->vat_rate;
                $region_cost->final_total = number_format($final_total, 2, '.', '');


                $electricity_project[] = array(
                    'title' => 'electricity',
                    'total' => number_format($electricity_total, 2, '.', '')
                );

                $vat[] = array(
                    'title' => 'VAT',
                    'total' => $region_cost->sub_total_vat
                );

                $region_cost->projection = array_merge($electricity_project, $electricity_additional, $vat);
                $region_cost->daily_cost = number_format($region_cost->final_total / $month_day, 2, '.', '');
                $region_cost->meter_number = $meter_number;
                $region_cost->meter_title = $meter_title;
                $region_cost->type = $type_id;
                $region_cost->meter_id = $meter_id;
                $region_cost->electricity_email = $region_cost->electricity_email;
                unset(
                    $region_cost->water_in,
                    $region_cost->water_out,
                    $region_cost->template_name,
                    $region_cost->read_day,
                    $region_cost->start_date,
                    $region_cost->end_date,
                    $region_cost->water_used,
                    $region_cost->electricity_used,
                    $region_cost->electricity,
                    // $region_cost->electricity_additional,
                    $region_cost->additional,
                    $region_cost->billing_date,
                    $region_cost->reading_date,
                    $region_cost->created_at,
                    $region_cost->updated_at,
                    $region_cost->waterin_additional,
                    $region_cost->waterout_additional
                );
                return $region_cost;
            }
        } else {
            return 0;
        }
    }
    public function getAdditionalCost(Request $request)
    { 

        $postData = $request->post();

        // if (!isset($postData['account_id']) && empty($postData['account_id'])) {
        //     return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_id is required!']);
        // }
        // if (!isset($postData['region_id']) && empty($postData['region_id'])) {
        //     return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, region_id is required!']);
        // }
        // if (!isset($postData['account_type_id']) && empty($postData['account_type_id'])) {
        //     return response()->json(['status' => false, 'code' => 400, 'msg' => 'Oops, account_type_id is required!']);
        // }
        $fixedCosts = FixedCost::select('title as name', 'value as cost', 'is_active')->where('account_id', $postData['account_id'])->get()->toArray();
        if (isset($fixedCosts) && !empty($fixedCosts)) {
            // after first time save
           
            $array_merged = $fixedCosts;
            
            if (isset($array_merged) && !empty($array_merged)) {
                foreach ($array_merged as $key => $value) {
                    if (isset($value['is_active']) && $value['is_active'] == 1) {
                    $array_merged[$key]['isApplicable'] = true;
                    } else {
                        $array_merged[$key]['isApplicable'] = false;
                    }
                }
            }
        }
        return response()->json($array_merged);
        $region_cost = RegionsAccountTypeCost::select('additional')->where('region_id', $postData['region_id'])->where('account_type_id', $postData['account_type_id'])->first();
        if (isset($region_cost['additional']) && !empty($region_cost['additional'])) {
            $additional_arr = json_decode($region_cost['additional'], true);
            if (isset($fixedCosts) && !empty($fixedCosts)) {
                // after first time save
                $result = array_udiff(
                    $additional_arr,
                    $fixedCosts,
                    fn ($a, $b) => ($a['name'] ?? $a['name']) <=> ($b['name'] ?? $b['name'])
                );
                $array_merged = array_merge($fixedCosts, $result);
                if (isset($array_merged) && !empty($array_merged)) {
                    foreach ($array_merged as $key => $value) {
                        if (isset($value['is_active']) && $value['is_active'] == 1) {
                        $array_merged[$key]['isApplicable'] = true;
                        } else {
                            $array_merged[$key]['isApplicable'] = false;
                        }
                    }
                }
                return response()->json($array_merged);
            } else {
                if (isset($additional_arr) && !empty($additional_arr)) {
                    foreach ($additional_arr as $key => $value) {
                        $additional_arr[$key]['isApplicable'] = true;
                    }
                }
                return response()->json($additional_arr);
            }
        } else {
            return response()->json([]);
        }
        // echo "<pre>";print_r($region_cost);exit();

    }
    public function getBillday(Request $request)
    {

        $postData = $request->post();
        $acc = Account::where('id', $postData['account_id'])->first();
        if ($acc->bill_read_day_active == 0) {
            $acc->bill_read_day_active = false;
        } else {
            $acc->bill_read_day_active = true;
        }

        return response()->json($acc);
    }

    public function siteLogin(Request $request)
    {
        $postData = $request->post();

        if (empty($postData['site_username']) || empty($postData['site_password'])) {
            return response()->json(['status' => false, 'code' => 400, 'msg' => 'site_username and site_password are required!'], 400);
        }

        $site = Site::where('site_username', $postData['site_username'])->first();

        if (empty($site)) {
            return response()->json(['status' => false, 'code' => 401, 'msg' => 'Invalid credentials!'], 401);
        }

        if (!Hash::check($postData['site_password'], $site->site_password)) {
            return response()->json(['status' => false, 'code' => 401, 'msg' => 'Invalid credentials!'], 401);
        }

        return response()->json(['status' => true, 'code' => 200, 'msg' => 'Login successful!', 'data' => $site]);
    }

    /**
     * Get navigation pages for the app
     * Returns pages structured for app navigation:
     * - header_tabs: Single pages that appear in header
     * - menu_groups: Parent pages with children for hamburger menu
     */
    public function getNavigationPages()
    {
        // Get single pages (header tabs) - no parent
        $headerTabs = \App\Models\Page::root()
            ->single()
            ->active()
            ->inNavigation()
            ->orderBy('sort_order')
            ->select(['id', 'title', 'slug', 'icon', 'page_type'])
            ->get();

        // Get parent pages with their children (menu groups)
        $menuGroups = \App\Models\Page::root()
            ->parentType()
            ->active()
            ->inNavigation()
            ->with(['activeChildren' => function($q) {
                $q->inNavigation()
                  ->orderBy('sort_order')
                  ->select(['id', 'parent_id', 'title', 'slug', 'icon']);
            }])
            ->orderBy('sort_order')
            ->select(['id', 'title', 'slug', 'icon', 'page_type'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'header_tabs' => $headerTabs,
                'menu_groups' => $menuGroups,
            ]
        ]);
    }

    /**
     * Get page content by slug
     */
    public function getPageBySlug($slug)
    {
        $page = \App\Models\Page::where('slug', $slug)
            ->active()
            ->with(['children' => function($q) {
                $q->active()->inNavigation()->orderBy('sort_order');
            }, 'parent'])
            ->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'msg' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'icon' => $page->icon,
                'page_type' => $page->page_type,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'parent' => $page->parent ? [
                    'id' => $page->parent->id,
                    'title' => $page->parent->title,
                    'slug' => $page->parent->slug,
                ] : null,
                'children' => $page->children->map(function($child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->title,
                        'slug' => $child->slug,
                        'icon' => $child->icon,
                    ];
                }),
            ]
        ]);
    }
}
