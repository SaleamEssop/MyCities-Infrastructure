<template>
    <div class="cust-form-wrapper">
        <div class="row">
            <div class="col-md-12">
                <form method="POST" :action="submitUrl" @submit="handleSubmit">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input v-if="formData.id" type="hidden" name="id" :value="formData.id" />
                    
                    <!-- Basic Info -->
                    <div class="form-group">
                        <label><strong>Template Name :</strong></label>
                        <input class="form-control" type="text" placeholder="Template name" name="template_name" v-model="formData.template_name" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Select Region :</strong></label>
                        <select class="form-control" name="region_id" v-model="formData.region_id" @change="onRegionChange" required>
                            <option value="">Please select Region</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Select Account Type :</strong></label>
                        <select class="form-control" name="account_type_id" v-model="formData.account_type_id" required>
                            <option value="">Please select Account Type</option>
                            <option v-for="type in accountTypes" :key="type.id" :value="type.id">{{ type.type }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Applicable Start Date :</strong></label>
                        <input class="form-control" type="date" placeholder="Start Date" name="start_date" v-model="formData.start_date" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Applicable End Date :</strong></label>
                        <input class="form-control" type="date" placeholder="End Date" name="end_date" v-model="formData.end_date" required />
                    </div>
                    <div class="form-group" style="display:none;">
                        <label><strong>Water Email :</strong></label>
                        <input class="form-control" type="email" placeholder="Water Email" name="water_email" v-model="formData.water_email" />
                    </div>
                    <div class="form-group" style="display:none;">
                        <label><strong>Electricity Email :</strong></label>
                        <input class="form-control" type="email" placeholder="Electricity Email" name="electricity_email" v-model="formData.electricity_email" />
                    </div>
                    <div class="form-group">
                        <label><strong>Vat In Percentage :</strong></label>
                        <input class="form-control" type="text" placeholder="VAT Percentage" name="vat_percentage" v-model="formData.vat_percentage" @input="filterDecimal($event)" required />
                    </div>
                    <hr>
                    <label style="font-size: 24px;font-weight: 800;"><strong>User Input : </strong></label>
                    <div class="form-group">
                        <label><strong>Billing Day :</strong></label>
                        <input class="form-control" type="text" placeholder="Billing Day" name="billing_day" v-model="formData.billing_day" @input="filterDecimal($event)" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Read Day :</strong></label>
                        <input class="form-control" type="text" placeholder="Read Day" name="read_day" v-model="formData.read_day" @input="filterDecimal($event)" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Ratable Value :</strong></label>
                        <input class="form-control" type="text" placeholder="Ratable Value" name="ratable_value" v-model="formData.ratable_value" @input="filterDecimal($event)" required />
                    </div>
                    <hr>
                    <div class="form-group">
                        <label><strong>Select Meter Type :</strong></label>
                        <input type="checkbox" name="is_water" id="waterchk" v-model="formData.is_water" /> Water
                        <input type="checkbox" name="is_electricity" id="electricitychk" v-model="formData.is_electricity" /> Electricity
                    </div>
                    <div class="form-group" v-show="formData.is_water">
                        <label><strong>Water Used in KL :</strong></label>
                        <input class="form-control" type="text" placeholder="Water Usage" name="water_used" v-model="formData.water_used" @input="filterDecimal($event)" />
                    </div>
                    <div class="form-group" v-show="formData.is_electricity">
                        <label><strong>Electricity Used in KWH :</strong></label>
                        <input class="form-control" type="text" placeholder="Electricity Usage" name="electricity_used" v-model="formData.electricity_used" @input="filterDecimal($event)" />
                    </div>

                    <!-- Water In Section -->
                    <div v-show="formData.is_water" class="water_in_section">
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Add Water In Cost : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addWaterInRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in waterIn" :key="'waterin-' + index" class="row mb-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Min</label>
                                    <input class="form-control" type="text" placeholder="Min litres" :name="'waterin[' + index + '][min]'" v-model="row.min" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Max</label>
                                    <input class="form-control" type="text" placeholder="Max litres" :name="'waterin[' + index + '][max]'" v-model="row.max" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'waterin[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateWaterInRowTotal(index)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeWaterInRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>Water In Total :</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="waterInTotal.toFixed(2)" disabled />
                            </div>
                        </div>

                        <div class="row mb-3 mt-4">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Water in related Cost : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addWaterInAdditionalRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in waterInAdditional" :key="'waterin-add-' + index" class="row mb-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input class="form-control" type="text" placeholder="Title" :name="'waterin_additional[' + index + '][title]'" v-model="row.title" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Percentage</label>
                                    <input class="form-control" type="text" placeholder="%" :name="'waterin_additional[' + index + '][percentage]'" v-model="row.percentage" @input="filterDecimal($event)" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'waterin_additional[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateAdditionalRowTotal(row, formData.water_used)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeWaterInAdditionalRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>WaterIn related Total :</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="waterInRelatedTotal.toFixed(2)" disabled />
                            </div>
                        </div>
                    </div>

                    <!-- Water Out Section -->
                    <div v-show="formData.is_water" class="water_out_section">
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Add Water Out Cost : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addWaterOutRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in waterOut" :key="'waterout-' + index" class="row mb-2">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Min</label>
                                    <input class="form-control" type="text" placeholder="Min" :name="'waterout[' + index + '][min]'" v-model="row.min" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Max</label>
                                    <input class="form-control" type="text" placeholder="Max" :name="'waterout[' + index + '][max]'" v-model="row.max" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>%</label>
                                    <input class="form-control" type="text" placeholder="%" :name="'waterout[' + index + '][percentage]'" v-model="row.percentage" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'waterout[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateWaterOutRowTotal(index)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeWaterOutRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>Water Out Total :</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="waterOutTotal.toFixed(2)" disabled />
                            </div>
                        </div>

                        <div class="row mb-3 mt-4">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Water out related Cost : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addWaterOutAdditionalRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in waterOutAdditional" :key="'waterout-add-' + index" class="row mb-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input class="form-control" type="text" placeholder="Title" :name="'waterout_additional[' + index + '][title]'" v-model="row.title" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>%</label>
                                    <input class="form-control" type="text" placeholder="%" :name="'waterout_additional[' + index + '][percentage]'" v-model="row.percentage" @input="filterDecimal($event)" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'waterout_additional[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateAdditionalRowTotal(row, formData.water_used)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeWaterOutAdditionalRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>Waterout related Total:</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="waterOutRelatedTotal.toFixed(2)" disabled />
                            </div>
                        </div>
                    </div>

                    <!-- Electricity Section -->
                    <div v-show="formData.is_electricity" class="ele_section">
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Electricity : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addElectricityRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in electricity" :key="'electricity-' + index" class="row mb-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Min</label>
                                    <input class="form-control" type="text" placeholder="Min" :name="'electricity[' + index + '][min]'" v-model="row.min" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Max</label>
                                    <input class="form-control" type="text" placeholder="Max" :name="'electricity[' + index + '][max]'" v-model="row.max" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'electricity[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateElectricityRowTotal(index)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeElectricityRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>Electricity Total :</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="electricityTotal.toFixed(2)" disabled />
                            </div>
                        </div>

                        <div class="row mb-3 mt-4">
                            <div class="col-md-12">
                                <label class="mr-2"><strong>Electricity related Cost : </strong></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addElectricityAdditionalRow">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div v-for="(row, index) in electricityAdditional" :key="'electricity-add-' + index" class="row mb-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input class="form-control" type="text" placeholder="Title" :name="'electricity_additional[' + index + '][title]'" v-model="row.title" required />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>%</label>
                                    <input class="form-control" type="text" placeholder="%" :name="'electricity_additional[' + index + '][percentage]'" v-model="row.percentage" @input="filterDecimal($event)" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="text" placeholder="Cost" :name="'electricity_additional[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input class="form-control" type="text" placeholder="Total" :value="calculateAdditionalRowTotal(row, formData.electricity_used)" disabled />
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeElectricityAdditionalRow(index)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-2 offset-md-10">
                                <label><strong>Electricity related Total :</strong></label>
                                <input class="form-control" type="text" placeholder="Total" :value="electricityRelatedTotal.toFixed(2)" disabled />
                            </div>
                        </div>
                    </div>

                    <!-- Additional Cost Section -->
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="mr-2"><strong>Additional Cost : </strong></label>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-circle" @click="addAdditionalRow">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div v-for="(row, index) in additional" :key="'additional-' + index" class="row mb-2">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Name Of Cost</label>
                                <input class="form-control" type="text" placeholder="Name" :name="'additional[' + index + '][name]'" v-model="row.name" required />
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Cost</label>
                                <input class="form-control" type="text" placeholder="Cost" :name="'additional[' + index + '][cost]'" v-model="row.cost" @input="filterDecimal($event)" required />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="button" style="margin-top: 32px;" class="btn btn-outline-secondary btn-sm btn-circle" @click="removeAdditionalRow(index)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 ml-auto">
                            <label><strong>Sub Total :</strong></label>
                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="Sub Total" :value="subTotal.toFixed(2)" disabled />
                            </div>
                            <label><strong>VAT</strong></label>
                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="VAT Amount" :value="vatAmount.toFixed(2)" disabled />
                            </div>
                            <label><strong>Rates :</strong></label>
                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="VAT Rate" name="vat_rate" v-model="formData.vat_rate" @input="filterDecimal($event)" />
                            </div>
                            <label><strong>Rates Rebate :</strong></label>
                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="Rates Rebate" name="rates_rebate" v-model="formData.rates_rebate" @input="filterDecimal($event)" />
                            </div>
                            <label><strong>Final Total :</strong></label>
                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="Final Total" :value="finalTotal.toFixed(2)" disabled />
                            </div>
                            <button type="submit" class="btn btn-warning">{{ formData.id ? 'Save / Update' : 'Save Template' }}</button>
                            <a :href="cancelUrl" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';

const props = defineProps({
    regions: {
        type: Array,
        required: true
    },
    accountTypes: {
        type: Array,
        required: true
    },
    csrfToken: {
        type: String,
        required: true
    },
    submitUrl: {
        type: String,
        required: true
    },
    cancelUrl: {
        type: String,
        required: true
    },
    getEmailUrl: {
        type: String,
        default: ''
    },
    existingData: {
        type: Object,
        default: null
    }
});

// Form data
const formData = reactive({
    id: '',
    template_name: '',
    region_id: '',
    account_type_id: '',
    start_date: '',
    end_date: '',
    water_email: '',
    electricity_email: '',
    vat_percentage: '0',
    billing_day: '',
    read_day: '',
    ratable_value: '0',
    is_water: false,
    is_electricity: false,
    water_used: '1',
    electricity_used: '1',
    vat_rate: '0',
    rates_rebate: '0'
});

// Dynamic arrays for tiered pricing
const waterIn = ref([{ min: '', max: '', cost: '' }]);
const waterOut = ref([{ min: '', max: '', percentage: '', cost: '' }]);
const electricity = ref([{ min: '', max: '', cost: '' }]);
const additional = ref([{ name: '', cost: '' }]);
const waterInAdditional = ref([{ title: '', percentage: '', cost: '' }]);
const waterOutAdditional = ref([{ title: '', percentage: '', cost: '' }]);
const electricityAdditional = ref([{ title: '', percentage: '', cost: '' }]);

// Initialize from existing data if editing
onMounted(() => {
    if (props.existingData) {
        const data = props.existingData;
        formData.id = data.id || '';
        formData.template_name = data.template_name || '';
        formData.region_id = data.region_id || '';
        formData.account_type_id = data.account_type_id || '';
        formData.start_date = data.start_date || '';
        formData.end_date = data.end_date || '';
        formData.water_email = data.water_email || '';
        formData.electricity_email = data.electricity_email || '';
        formData.vat_percentage = data.vat_percentage ?? '0';
        formData.billing_day = data.billing_day || '';
        formData.read_day = data.read_day || '';
        formData.ratable_value = data.ratable_value ?? '0';
        formData.is_water = data.is_water == 1;
        formData.is_electricity = data.is_electricity == 1;
        formData.water_used = data.water_used ?? '1';
        formData.electricity_used = data.electricity_used ?? '1';
        formData.vat_rate = data.vat_rate ?? '0';
        formData.rates_rebate = data.rates_rebate ?? '0';

        // Load arrays
        if (Array.isArray(data.water_in) && data.water_in.length > 0) {
            waterIn.value = data.water_in.map(item => ({
                min: item.min ?? '',
                max: item.max ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.water_out) && data.water_out.length > 0) {
            waterOut.value = data.water_out.map(item => ({
                min: item.min ?? '',
                max: item.max ?? '',
                percentage: item.percentage ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.electricity) && data.electricity.length > 0) {
            electricity.value = data.electricity.map(item => ({
                min: item.min ?? '',
                max: item.max ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.additional) && data.additional.length > 0) {
            additional.value = data.additional.map(item => ({
                name: item.name ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.waterin_additional) && data.waterin_additional.length > 0) {
            waterInAdditional.value = data.waterin_additional.map(item => ({
                title: item.title ?? '',
                percentage: item.percentage ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.waterout_additional) && data.waterout_additional.length > 0) {
            waterOutAdditional.value = data.waterout_additional.map(item => ({
                title: item.title ?? '',
                percentage: item.percentage ?? '',
                cost: item.cost ?? ''
            }));
        }
        if (Array.isArray(data.electricity_additional) && data.electricity_additional.length > 0) {
            electricityAdditional.value = data.electricity_additional.map(item => ({
                title: item.title ?? '',
                percentage: item.percentage ?? '',
                cost: item.cost ?? ''
            }));
        }
    }
});

// Filter input to allow only decimals and negative sign
function filterDecimal(event) {
    const value = event.target.value.replace(/[^0-9.-]/g, '');
    event.target.value = value;
}

// Region change handler
function onRegionChange() {
    if (formData.region_id && props.getEmailUrl) {
        const url = props.getEmailUrl.replace('__ID__', formData.region_id);
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    formData.water_email = data.water_email || '';
                    formData.electricity_email = data.electricity_email || '';
                }
            })
            .catch(() => {});
    }
}

// Add/Remove row functions
function addWaterInRow() {
    waterIn.value.push({ min: '', max: '', cost: '' });
}
function removeWaterInRow(index) {
    if (waterIn.value.length > 1) {
        waterIn.value.splice(index, 1);
    }
}

function addWaterOutRow() {
    waterOut.value.push({ min: '', max: '', percentage: '', cost: '' });
}
function removeWaterOutRow(index) {
    if (waterOut.value.length > 1) {
        waterOut.value.splice(index, 1);
    }
}

function addElectricityRow() {
    electricity.value.push({ min: '', max: '', cost: '' });
}
function removeElectricityRow(index) {
    if (electricity.value.length > 1) {
        electricity.value.splice(index, 1);
    }
}

function addAdditionalRow() {
    additional.value.push({ name: '', cost: '' });
}
function removeAdditionalRow(index) {
    if (additional.value.length > 1) {
        additional.value.splice(index, 1);
    }
}

function addWaterInAdditionalRow() {
    waterInAdditional.value.push({ title: '', percentage: '', cost: '' });
}
function removeWaterInAdditionalRow(index) {
    if (waterInAdditional.value.length > 1) {
        waterInAdditional.value.splice(index, 1);
    }
}

function addWaterOutAdditionalRow() {
    waterOutAdditional.value.push({ title: '', percentage: '', cost: '' });
}
function removeWaterOutAdditionalRow(index) {
    if (waterOutAdditional.value.length > 1) {
        waterOutAdditional.value.splice(index, 1);
    }
}

function addElectricityAdditionalRow() {
    electricityAdditional.value.push({ title: '', percentage: '', cost: '' });
}
function removeElectricityAdditionalRow(index) {
    if (electricityAdditional.value.length > 1) {
        electricityAdditional.value.splice(index, 1);
    }
}

// Calculate row totals for tiered pricing (Water In - KL to Litres conversion)
function calculateWaterInRowTotal(index) {
    // Convert usage from KL to litres
    const usageLitres = parseFloat(formData.water_used) * 1000;
    
    // Calculate how many litres have been consumed by previous tiers
    let consumedLitres = 0;
    for (let i = 0; i < index; i++) {
        const tierMin = parseFloat(waterIn.value[i].min) || 0;
        const tierMax = parseFloat(waterIn.value[i].max) || 0;
        consumedLitres += (tierMax - tierMin);
    }
    
    const row = waterIn.value[index];
    const tierMin = parseFloat(row.min) || 0;
    const tierMax = parseFloat(row.max) || 0;
    const costPerKL = parseFloat(row.cost) || 0;
    
    const tierCapacity = tierMax - tierMin;  // e.g., 6000 - 0 = 6000 litres
    const remainingUsage = usageLitres - consumedLitres;
    const litresInThisTier = Math.max(0, Math.min(tierCapacity, remainingUsage));
    
    // Convert litres to KL for cost calculation
    const klInThisTier = litresInThisTier / 1000;
    const total = klInThisTier * costPerKL;
    
    return total.toFixed(2);
}

// Calculate row totals for Water Out (with percentage) - KL to Litres conversion
function calculateWaterOutRowTotal(index) {
    // Convert usage from KL to litres
    const usageLitres = parseFloat(formData.water_used) * 1000;
    
    // Calculate how many litres have been consumed by previous tiers
    let consumedLitres = 0;
    for (let i = 0; i < index; i++) {
        const tierMin = parseFloat(waterOut.value[i].min) || 0;
        const tierMax = parseFloat(waterOut.value[i].max) || 0;
        consumedLitres += (tierMax - tierMin);
    }
    
    const row = waterOut.value[index];
    const tierMin = parseFloat(row.min) || 0;
    const tierMax = parseFloat(row.max) || 0;
    const percentage = parseFloat(row.percentage) || 100;
    const costPerKL = parseFloat(row.cost) || 0;
    
    const tierCapacity = tierMax - tierMin;
    const remainingUsage = usageLitres - consumedLitres;
    let litresInThisTier = Math.max(0, Math.min(tierCapacity, remainingUsage));
    
    // Apply percentage
    litresInThisTier = (percentage / 100) * litresInThisTier;
    
    // Convert litres to KL for cost calculation
    const klInThisTier = litresInThisTier / 1000;
    const total = klInThisTier * costPerKL;
    
    return total.toFixed(2);
}

// Calculate row totals for Electricity (no percentage)
function calculateElectricityRowTotal(index) {
    const electricityUsage = parseFloat(formData.electricity_used) || 0;
    let remaining = electricityUsage;
    
    for (let i = 0; i < index; i++) {
        const min = parseFloat(electricity.value[i].min) || 0;
        const max = parseFloat(electricity.value[i].max) || 0;
        const unitsInBracket = max - min;
        remaining -= unitsInBracket;
        if (remaining < 0) remaining = 0;
    }
    
    const row = electricity.value[index];
    const min = parseFloat(row.min) || 0;
    const max = parseFloat(row.max) || 0;
    const cost = parseFloat(row.cost) || 0;
    
    const unitsInBracket = max - min;
    let actualUnits = unitsInBracket;
    if (remaining - unitsInBracket < 0) {
        actualUnits = Math.max(0, remaining);
    }
    
    const total = Math.round(actualUnits * cost * 100) / 100;
    return total.toFixed(2);
}

// Calculate additional/related row totals
function calculateAdditionalRowTotal(row, usage) {
    const usageValue = parseFloat(usage) || 0;
    const percentage = parseFloat(row.percentage);
    const cost = parseFloat(row.cost) || 0;
    
    let effectiveUsage = usageValue;
    if (isNaN(percentage) || row.percentage === '') {
        effectiveUsage = 1;
    } else if (percentage !== 100) {
        effectiveUsage = usageValue - (usageValue * (percentage / 100));
    }
    
    const total = Math.round(effectiveUsage * cost * 100) / 100;
    return total.toFixed(2);
}

// Computed totals
const waterInTotal = computed(() => {
    let total = 0;
    for (let i = 0; i < waterIn.value.length; i++) {
        total += parseFloat(calculateWaterInRowTotal(i)) || 0;
    }
    return total;
});

const waterOutTotal = computed(() => {
    let total = 0;
    for (let i = 0; i < waterOut.value.length; i++) {
        total += parseFloat(calculateWaterOutRowTotal(i)) || 0;
    }
    return total;
});

const electricityTotal = computed(() => {
    let total = 0;
    for (let i = 0; i < electricity.value.length; i++) {
        total += parseFloat(calculateElectricityRowTotal(i)) || 0;
    }
    return total;
});

const waterInRelatedTotal = computed(() => {
    let total = 0;
    for (const row of waterInAdditional.value) {
        total += parseFloat(calculateAdditionalRowTotal(row, formData.water_used)) || 0;
    }
    return total;
});

const waterOutRelatedTotal = computed(() => {
    let total = 0;
    for (const row of waterOutAdditional.value) {
        total += parseFloat(calculateAdditionalRowTotal(row, formData.water_used)) || 0;
    }
    return total;
});

const electricityRelatedTotal = computed(() => {
    let total = 0;
    for (const row of electricityAdditional.value) {
        total += parseFloat(calculateAdditionalRowTotal(row, formData.electricity_used)) || 0;
    }
    return total;
});

const additionalTotal = computed(() => {
    let total = 0;
    for (const row of additional.value) {
        total += parseFloat(row.cost) || 0;
    }
    return total;
});

const subTotal = computed(() => {
    return waterInTotal.value + waterInRelatedTotal.value + 
           waterOutTotal.value + waterOutRelatedTotal.value + 
           electricityTotal.value + electricityRelatedTotal.value + 
           additionalTotal.value;
});

const vatAmount = computed(() => {
    const vatPercentage = parseFloat(formData.vat_percentage) || 0;
    return Math.round((vatPercentage / 100) * subTotal.value * 100) / 100;
});

const finalTotal = computed(() => {
    const rates = parseFloat(formData.vat_rate) || 0;
    const ratesRebate = parseFloat(formData.rates_rebate) || 0;
    return Math.round((subTotal.value + vatAmount.value + rates - ratesRebate) * 100) / 100;
});

// Form submit handler
function handleSubmit(event) {
    // Form submits normally via POST
    return true;
}
</script>
