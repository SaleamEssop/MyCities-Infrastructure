<?php

namespace App\Http\Controllers;

use App\Models\RegionsAccountTypeCost;
use App\Models\Regions;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RegionsCostController extends Controller
{
    public function index()
    {
        // Get costs with their related Region and Account Type
        $costs = RegionsAccountTypeCost::with(['region', 'accountType'])
                    ->orderBy('region_id')
                    ->orderBy('account_type_id')
                    ->get();

        return view('admin.region_cost.index', ['costs' => $costs]);
    }

    public function create()
    {
        return view('admin.region_cost.create', [
            'regions' => Regions::all(),
            'account_types' => AccountType::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'account_type_id' => 'required|exists:account_type,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Find existing record by id or create new
        if ($request->has('id') && $request->id) {
            $cost = RegionsAccountTypeCost::findOrFail($request->id);
        } else {
            $cost = new RegionsAccountTypeCost();
        }

        // Assign non-array fields
        $cost->fill($request->only([
            'template_name',
            'region_id',
            'account_type_id',
            'start_date',
            'end_date',
            'is_water',
            'is_electricity',
            'water_used',
            'electricity_used',
            'vat_rate',
            'vat_percentage',
            'ratable_value',
            'rates_rebate',
            'is_active',
            'billing_day',
            'read_day',
            'water_email',
            'electricity_email'
        ]));

        // Handle checkbox fields (convert to 1/0)
        $cost->is_water = $request->has('is_water') ? 1 : 0;
        $cost->is_electricity = $request->has('is_electricity') ? 1 : 0;

        // Explicitly assign array fields (Laravel casting handles JSON conversion)
        $cost->water_in = $request->input('waterin', []);
        $cost->water_out = $request->input('waterout', []);
        $cost->electricity = $request->input('electricity', []);
        $cost->additional = $request->input('additional', []);
        $cost->waterin_additional = $request->input('waterin_additional', []);
        $cost->waterout_additional = $request->input('waterout_additional', []);
        $cost->electricity_additional = $request->input('electricity_additional', []);

        $cost->save();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Cost Template saved successfully!');

        return redirect()->route('region-cost');
    }

    public function edit($id)
    {
        $region_cost = RegionsAccountTypeCost::findOrFail($id);

        return view('admin.region_cost.edit', [
            'region_cost' => $region_cost,
            'regions' => Regions::all(),
            'account_type' => AccountType::all()
        ]);
    }

    public function update(Request $request)
    {
        return $this->store($request);
    }

    public function delete($id)
    {
        RegionsAccountTypeCost::destroy($id);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Cost Template deleted successfully!');

        return redirect()->back();
    }

    public function copyRecord(Request $request)
    {
        $original = RegionsAccountTypeCost::findOrFail($request->id);

        $copy = $original->replicate();
        
        // Check if this should be a date child
        $isDateChild = $request->input('is_date_child', '0') === '1';
        
        if ($isDateChild) {
            // Set parent_id to create hierarchy
            $copy->parent_id = $original->id;
            $copy->template_name = $original->template_name . ' (Date Child)';
            
            Session::flash('alert-class', 'alert-success');
            Session::flash('alert-message', 'Date Child tariff created successfully! Update the date range for the new tariff.');
        } else {
            // Independent copy - no parent relationship
            $copy->parent_id = null;
            $copy->template_name = $original->template_name . ' (Copy)';
            
            Session::flash('alert-class', 'alert-success');
            Session::flash('alert-message', 'Independent copy created successfully!');
        }
        
        $copy->save();

        return redirect()->route('region-cost-edit', ['id' => $copy->id]);
    }
    
    /**
     * Get tariff hierarchy for a given tariff
     */
    public function getHierarchy($id)
    {
        $tariff = RegionsAccountTypeCost::findOrFail($id);
        $hierarchy = [];
        
        // Get ancestors
        $current = $tariff;
        while ($current->parent_id) {
            $parent = RegionsAccountTypeCost::find($current->parent_id);
            if ($parent) {
                array_unshift($hierarchy, [
                    'id' => $parent->id,
                    'name' => $parent->template_name,
                    'start_date' => $parent->start_date,
                    'end_date' => $parent->end_date,
                ]);
                $current = $parent;
            } else {
                break;
            }
        }
        
        // Add current tariff
        $hierarchy[] = [
            'id' => $tariff->id,
            'name' => $tariff->template_name,
            'start_date' => $tariff->start_date,
            'end_date' => $tariff->end_date,
            'is_current' => true,
        ];
        
        // Get children
        $children = RegionsAccountTypeCost::where('parent_id', $tariff->id)->get();
        foreach ($children as $child) {
            $hierarchy[] = [
                'id' => $child->id,
                'name' => $child->template_name,
                'start_date' => $child->start_date,
                'end_date' => $child->end_date,
                'is_child' => true,
            ];
        }
        
        return response()->json([
            'success' => true,
            'hierarchy' => $hierarchy,
        ]);
    }
}
