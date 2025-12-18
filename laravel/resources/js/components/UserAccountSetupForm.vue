<template>
    <div class="user-account-setup-wrapper">
        <!-- Progress Steps -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div v-for="(step, index) in steps" :key="index" 
                         class="step-indicator" 
                         :class="{ 'active': currentStep === index + 1, 'completed': currentStep > index + 1 }">
                        <div class="step-number">
                            <i v-if="currentStep > index + 1" class="fas fa-check"></i>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="step-label">{{ step }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Step {{ currentStep }}: {{ steps[currentStep - 1] }}</h6>
            </div>
            <div class="card-body">
                <!-- Step 1: User Details -->
                <div v-show="currentStep === 1">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="formData.name" placeholder="Enter full name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       :class="{ 'is-invalid': emailError, 'is-valid': emailValid }"
                                       v-model="formData.email" 
                                       @blur="validateEmail"
                                       placeholder="Enter email address" required>
                                <div v-if="emailError" class="invalid-feedback">{{ emailError }}</div>
                                <div v-if="emailValid" class="valid-feedback">Email is available</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Number <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       :class="{ 'is-invalid': phoneError, 'is-valid': phoneValid }"
                                       v-model="formData.contact_number" 
                                       @blur="validatePhone"
                                       placeholder="Enter phone number" required>
                                <div v-if="phoneError" class="invalid-feedback">{{ phoneError }}</div>
                                <div v-if="phoneValid" class="valid-feedback">Phone number is available</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           class="form-control" 
                                           v-model="formData.password" 
                                           placeholder="Enter password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" @click="togglePassword">
                                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Region Selection -->
                <div v-show="currentStep === 2">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Region <span class="text-danger">*</span></label>
                                <select class="form-control" v-model="formData.region_id" @change="onRegionChange" required>
                                    <option value="">Select a Region</option>
                                    <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Site Title</label>
                                <input type="text" class="form-control" v-model="formData.site_title" placeholder="Optional site title">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" class="form-control" v-model="formData.address" placeholder="Enter address">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Tariff Template Selection -->
                <div v-show="currentStep === 3">
                    <div v-if="loadingTemplates" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading tariff templates...</p>
                    </div>
                    <div v-else-if="tariffTemplates.length === 0" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No active tariff templates found for the selected region. Please go back and select a different region, or create a tariff template first.
                    </div>
                    <div v-else class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Tariff Template <span class="text-danger">*</span></label>
                                <select class="form-control" v-model="formData.tariff_template_id" required>
                                    <option value="">Select a Tariff Template</option>
                                    <option v-for="template in tariffTemplates" :key="template.id" :value="template.id">
                                        {{ template.template_name }}
                                        <span v-if="template.is_water">(Water)</span>
                                        <span v-if="template.is_electricity">(Electricity)</span>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12" v-if="selectedTemplate">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="font-weight-bold">Selected Template Details:</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ selectedTemplate.template_name }}</p>
                                    <p class="mb-1">
                                        <strong>Services:</strong> 
                                        <span v-if="selectedTemplate.is_water" class="badge badge-primary mr-1">Water</span>
                                        <span v-if="selectedTemplate.is_electricity" class="badge badge-warning">Electricity</span>
                                    </p>
                                    <p class="mb-0" v-if="selectedTemplate.start_date">
                                        <strong>Validity:</strong> {{ selectedTemplate.start_date }} to {{ selectedTemplate.end_date || 'Ongoing' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Account Details (Create Account) -->
                <div v-show="currentStep === 4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="formData.account_name" placeholder="Enter account name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="formData.account_number" placeholder="Enter account number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Billing Type</label>
                                <select class="form-control" v-model="formData.billing_type">
                                    <option value="monthly">Monthly</option>
                                    <option value="date-to-date">Date to Date</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bill Day</label>
                                <input type="number" 
                                       min="1" max="31" 
                                       class="form-control" 
                                       :class="{ 'disabled-field': isDateToDate }"
                                       v-model="formData.bill_day" 
                                       placeholder="1-31"
                                       :disabled="isDateToDate">
                                <small v-if="isDateToDate" class="text-muted">Not applicable for Date to Date billing</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Read Day</label>
                                <input type="number" 
                                       min="1" max="31" 
                                       class="form-control" 
                                       :class="{ 'disabled-field': isDateToDate }"
                                       v-model="formData.read_day" 
                                       placeholder="1-31"
                                       :disabled="isDateToDate">
                                <small v-if="isDateToDate" class="text-muted">Not applicable for Date to Date billing</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Add Meters -->
                <div v-show="currentStep === 5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold mb-0"><i class="fas fa-tachometer-alt mr-2"></i>Meters</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="addMeter">
                            <i class="fas fa-plus"></i> Add Another Meter
                        </button>
                    </div>
                    
                    <div v-for="(meter, index) in formData.meters" :key="index" class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <strong><i class="fas fa-tachometer-alt mr-2"></i>Meter {{ index + 1 }}</strong>
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeMeter(index)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Meter Type <span class="text-danger">*</span></label>
                                        <select class="form-control" v-model="meter.meter_type_id" @change="onMeterTypeChange(meter)">
                                            <option value="">Select Type</option>
                                            <option v-for="type in meterTypes" :key="type.id" :value="type.id">{{ type.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Meter Name</label>
                                        <input type="text" class="form-control" v-model="meter.meter_title" placeholder="Enter meter name">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Meter Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="meter.meter_number" placeholder="Enter meter number">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Start Reading</label>
                                        <!-- Dual Input Field Design for Meter Reading with type-based styling -->
                                        <div class="meter-reading-input-wrapper">
                                            <div class="meter-reading-dual-input">
                                                <input 
                                                    type="text" 
                                                    :class="getMeterMainInputClass(meter.meter_type_id)"
                                                    :maxlength="getMaxWholeDigits(meter.meter_type_id)"
                                                    v-model="meter.initial_reading_whole"
                                                    @input="formatMeterWholeInput(meter)"
                                                    @keydown="handleMeterKeyDown($event, meter, 'whole', index)"
                                                    :ref="el => setMeterRef(el, index, 'whole')"
                                                    :placeholder="getWholePlaceholder(meter.meter_type_id)"
                                                    inputmode="numeric">
                                                <span class="meter-decimal-separator">.</span>
                                                <input 
                                                    type="text" 
                                                    :class="getMeterDecimalInputClass(meter.meter_type_id)"
                                                    :maxlength="getMaxDecimalDigits(meter.meter_type_id)"
                                                    v-model="meter.initial_reading_decimal"
                                                    @input="formatMeterDecimalInput(meter)"
                                                    @keydown="handleMeterKeyDown($event, meter, 'decimal', index)"
                                                    :ref="el => setMeterRef(el, index, 'decimal')"
                                                    :placeholder="getDecimalPlaceholder(meter.meter_type_id)"
                                                    inputmode="numeric">
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <span v-if="isMeterWater(meter.meter_type_id)">Water: 6 digits + 1 decimal place (kL)</span>
                                                <span v-else-if="isMeterElectricity(meter.meter_type_id)">Electricity: 5 digits + 1 decimal place (kWh)</span>
                                                <span v-else>Select meter type for format</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="formData.meters.length === 0" class="text-center py-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No meters added yet. Click "Add Another Meter" to add meters to this account.
                        </div>
                    </div>

                    <!-- Validation Messages for Step 5 -->
                    <div v-if="formData.meters.length > 0 && !isStep5Valid" class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Please fill in all required fields for each meter (Meter Type, Meter Number).
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" @click="prevStep" :disabled="currentStep === 1">
                    <i class="fas fa-arrow-left mr-1"></i> Previous
                </button>
                <div>
                    <!-- Save & Exit button - only on Step 1 -->
                    <button v-if="currentStep === 1" type="button" class="btn btn-outline-success mr-2" @click="saveUserOnly" :disabled="saving || !canSaveUserOnly">
                        <span v-if="savingUserOnly"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        <span v-else><i class="fas fa-user-plus mr-1"></i> Save User Only</span>
                    </button>
                    <button v-if="currentStep < 5" type="button" class="btn btn-primary" @click="nextStep" :disabled="!canProceed">
                        Next <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                    <button v-else type="button" class="btn btn-success" @click="submitForm" :disabled="saving || !canProceed">
                        <span v-if="saving"><i class="fas fa-spinner fa-spin"></i> Creating...</span>
                        <span v-else><i class="fas fa-check mr-1"></i> Create Account</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Notification Toast -->
        <div class="position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            <div v-if="notification.show" :class="['alert', 'alert-' + notification.type, 'alert-dismissible', 'fade', 'show']" role="alert">
                {{ notification.message }}
                <button type="button" class="close" @click="notification.show = false">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';

const props = defineProps({
    csrfToken: {
        type: String,
        required: true
    },
    regions: {
        type: Array,
        required: true
    },
    meterTypes: {
        type: Array,
        required: true
    },
    apiUrls: {
        type: Object,
        required: true
    }
});

const steps = ['User Details', 'Assign Region', 'Select Tariff', 'Create Account', 'Add Meters'];
const currentStep = ref(1);
const saving = ref(false);
const savingUserOnly = ref(false);
const loadingTemplates = ref(false);
const tariffTemplates = ref([]);

// Password visibility toggle
const showPassword = ref(false);

// Email/Phone validation state
const emailError = ref('');
const emailValid = ref(false);
const phoneError = ref('');
const phoneValid = ref(false);
const validatingEmail = ref(false);
const validatingPhone = ref(false);

// Refs for meter inputs - stored by index
const meterInputRefs = ref({});

const notification = reactive({
    show: false,
    type: 'success',
    message: ''
});

const formData = reactive({
    // Step 1
    name: '',
    email: '',
    contact_number: '',
    password: '',
    // Step 2
    region_id: '',
    site_title: '',
    address: '',
    // Step 3
    tariff_template_id: '',
    // Step 4
    account_name: '',
    account_number: '',
    bill_day: '',
    read_day: '',
    billing_type: 'monthly',
    // Step 5
    meters: []
});

// Computed
const selectedTemplate = computed(() => {
    if (!formData.tariff_template_id) return null;
    return tariffTemplates.value.find(t => t.id === formData.tariff_template_id);
});

// Check if all meters in Step 5 have required fields (meters are optional, but if added must be complete)
const isStep5Valid = computed(() => {
    if (formData.meters.length === 0) return true; // No meters is valid - meters are optional
    return formData.meters.every(meter => 
        meter.meter_type_id && 
        meter.meter_number
    );
});

// Check if billing type is date-to-date
const isDateToDate = computed(() => {
    return formData.billing_type === 'date-to-date';
});

// Check if user details are valid for "Save User Only" option
const canSaveUserOnly = computed(() => {
    return formData.name && 
           formData.email && 
           formData.contact_number && 
           formData.password &&
           !emailError.value &&
           !phoneError.value;
});

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return formData.name && formData.email && formData.contact_number && formData.password && !emailError.value && !phoneError.value;
        case 2:
            return formData.region_id;
        case 3:
            return formData.tariff_template_id;
        case 4:
            return formData.account_name && formData.account_number;
        case 5:
            return isStep5Valid.value;
        default:
            return false;
    }
});

// Constants for meter digit counts (Water: 6+1, Electricity: 5+1)
const WATER_WHOLE_DIGITS = 6;
const WATER_DECIMAL_DIGITS = 1;
const ELECTRICITY_WHOLE_DIGITS = 5;
const ELECTRICITY_DECIMAL_DIGITS = 1;

// Methods
function showNotification(message, type = 'success') {
    notification.message = message;
    notification.type = type;
    notification.show = true;
    setTimeout(() => {
        notification.show = false;
    }, 5000);
}

function buildUrl(urlTemplate, replacements) {
    let url = urlTemplate;
    for (const [placeholder, value] of Object.entries(replacements)) {
        url = url.replace(placeholder, value);
    }
    return url;
}

// Password visibility toggle
function togglePassword() {
    showPassword.value = !showPassword.value;
}

// Email validation with AJAX
async function validateEmail() {
    if (!formData.email) {
        emailError.value = '';
        emailValid.value = false;
        return;
    }
    
    // Basic email format check
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        emailError.value = 'Please enter a valid email address';
        emailValid.value = false;
        return;
    }
    
    validatingEmail.value = true;
    try {
        const response = await fetch(props.apiUrls.validateEmail || '/admin/user-accounts/setup/validate-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({ email: formData.email })
        });
        
        if (!response.ok) {
            // Server error or endpoint issue - don't block the user
            emailError.value = '';
            emailValid.value = false;
            return;
        }
        
        const data = await response.json();
        if (data.exists) {
            emailError.value = 'This email address is already registered';
            emailValid.value = false;
        } else {
            emailError.value = '';
            emailValid.value = true;
        }
    } catch (error) {
        // Network error or JSON parsing error - don't block the user
        emailError.value = '';
        emailValid.value = false;
    } finally {
        validatingEmail.value = false;
    }
}

// Phone validation with AJAX
async function validatePhone() {
    if (!formData.contact_number) {
        phoneError.value = '';
        phoneValid.value = false;
        return;
    }
    
    validatingPhone.value = true;
    try {
        const response = await fetch(props.apiUrls.validatePhone || '/admin/user-accounts/setup/validate-phone', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({ contact_number: formData.contact_number })
        });
        
        if (!response.ok) {
            // Server error or endpoint issue - don't block the user
            phoneError.value = '';
            phoneValid.value = false;
            return;
        }
        
        const data = await response.json();
        if (data.exists) {
            phoneError.value = 'This phone number is already registered';
            phoneValid.value = false;
        } else {
            phoneError.value = '';
            phoneValid.value = true;
        }
    } catch (error) {
        // Network error or JSON parsing error - don't block the user
        phoneError.value = '';
        phoneValid.value = false;
    } finally {
        validatingPhone.value = false;
    }
}

// Save user only (without region, tariff, account, meters)
async function saveUserOnly() {
    if (!canSaveUserOnly.value) {
        showNotification('Please fill in all required fields correctly', 'danger');
        return;
    }
    
    savingUserOnly.value = true;
    
    try {
        const response = await fetch(props.apiUrls.storeUserOnly || '/admin/user-accounts/setup/user-only', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({
                name: formData.name,
                email: formData.email,
                contact_number: formData.contact_number,
                password: formData.password
            })
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message || 'User created successfully', 'success');
            setTimeout(() => {
                window.location.href = '/admin/user-accounts/manager';
            }, 1500);
        } else {
            showNotification(data.message || 'Error creating user', 'danger');
        }
    } catch (error) {
        showNotification('Error creating user: ' + error.message, 'danger');
    } finally {
        savingUserOnly.value = false;
    }
}

async function onRegionChange() {
    if (!formData.region_id) {
        tariffTemplates.value = [];
        formData.tariff_template_id = '';
        return;
    }
    
    loadingTemplates.value = true;
    try {
        const url = buildUrl(props.apiUrls.getTariffTemplates, { '__REGION_ID__': formData.region_id });
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        if (data.status === 200) {
            tariffTemplates.value = data.data;
            formData.tariff_template_id = '';
        }
    } catch (error) {
        showNotification('Error loading tariff templates: ' + error.message, 'danger');
    } finally {
        loadingTemplates.value = false;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function nextStep() {
    if (currentStep.value < 5 && canProceed.value) {
        currentStep.value++;
    }
}

function addMeter() {
    formData.meters.push({
        meter_type_id: '',
        meter_title: '',
        meter_number: '',
        initial_reading: '',
        initial_reading_whole: '',
        initial_reading_decimal: '',
        initial_reading_date: new Date().toISOString().split('T')[0]
    });
}

function removeMeter(index) {
    formData.meters.splice(index, 1);
    // Rebuild refs object with correct indices after removal
    // This ensures keyboard navigation works correctly after meter removal
    const newRefs = {};
    formData.meters.forEach((_, i) => {
        // Transfer refs from old indices to new indices
        const oldIndex = i >= index ? i + 1 : i;
        if (meterInputRefs.value[`${oldIndex}_whole`]) {
            newRefs[`${i}_whole`] = meterInputRefs.value[`${oldIndex}_whole`];
        }
        if (meterInputRefs.value[`${oldIndex}_decimal`]) {
            newRefs[`${i}_decimal`] = meterInputRefs.value[`${oldIndex}_decimal`];
        }
    });
    meterInputRefs.value = newRefs;
}

function isMeterWater(meterTypeId) {
    if (!meterTypeId) return false;
    const type = props.meterTypes.find(t => t.id === meterTypeId);
    return type && type.title && type.title.toLowerCase() === 'water';
}

function isMeterElectricity(meterTypeId) {
    if (!meterTypeId) return false;
    const type = props.meterTypes.find(t => t.id === meterTypeId);
    return type && type.title && type.title.toLowerCase() === 'electricity';
}

// Get max digits based on meter type
function getMaxWholeDigits(meterTypeId) {
    if (isMeterWater(meterTypeId)) return WATER_WHOLE_DIGITS;
    if (isMeterElectricity(meterTypeId)) return ELECTRICITY_WHOLE_DIGITS;
    return WATER_WHOLE_DIGITS; // Default to water
}

function getMaxDecimalDigits(meterTypeId) {
    if (isMeterWater(meterTypeId)) return WATER_DECIMAL_DIGITS;
    if (isMeterElectricity(meterTypeId)) return ELECTRICITY_DECIMAL_DIGITS;
    return WATER_DECIMAL_DIGITS; // Default to water
}

function getWholePlaceholder(meterTypeId) {
    if (isMeterWater(meterTypeId)) return '000000';
    if (isMeterElectricity(meterTypeId)) return '00000';
    return '000000';
}

function getDecimalPlaceholder(meterTypeId) {
    if (isMeterWater(meterTypeId)) return '0';
    if (isMeterElectricity(meterTypeId)) return '0';
    return '0';
}

// Get CSS class for main meter input based on meter type
function getMeterMainInputClass(meterTypeId) {
    if (isMeterElectricity(meterTypeId)) {
        return 'meter-main-input meter-electricity-main';
    }
    return 'meter-main-input meter-water-main';
}

// Get CSS class for decimal meter input based on meter type
function getMeterDecimalInputClass(meterTypeId) {
    if (isMeterElectricity(meterTypeId)) {
        return 'meter-decimal-input meter-electricity-decimal';
    }
    return 'meter-decimal-input meter-water-decimal';
}

// Store meter input refs
function setMeterRef(el, index, type) {
    if (el) {
        meterInputRefs.value[`${index}_${type}`] = el;
    }
}

// Handle meter type change - reset and adjust digits
function onMeterTypeChange(meter) {
    // Reset the reading values when meter type changes
    meter.initial_reading_whole = '';
    meter.initial_reading_decimal = '';
    meter.initial_reading = '';
}

// Format whole part of meter reading
function formatMeterWholeInput(meter) {
    const maxDigits = getMaxWholeDigits(meter.meter_type_id);
    // Only allow numeric input
    meter.initial_reading_whole = meter.initial_reading_whole.replace(/[^0-9]/g, '').slice(0, maxDigits);
    updateMeterReading(meter);
}

// Format decimal part of meter reading
function formatMeterDecimalInput(meter) {
    const maxDigits = getMaxDecimalDigits(meter.meter_type_id);
    // Only allow numeric input
    meter.initial_reading_decimal = meter.initial_reading_decimal.replace(/[^0-9]/g, '').slice(0, maxDigits);
    updateMeterReading(meter);
}

// Handle keyboard navigation between meter input fields
function handleMeterKeyDown(event, meter, fieldType, meterIndex) {
    const key = event.key;
    
    // Tab or Enter moves from whole to decimal
    if ((key === 'Tab' || key === 'Enter') && fieldType === 'whole' && !event.shiftKey) {
        event.preventDefault();
        const decimalRef = meterInputRefs.value[`${meterIndex}_decimal`];
        if (decimalRef) {
            decimalRef.focus();
            decimalRef.select();
        }
    }
    
    // Shift+Tab moves from decimal to whole
    if (key === 'Tab' && fieldType === 'decimal' && event.shiftKey) {
        event.preventDefault();
        const wholeRef = meterInputRefs.value[`${meterIndex}_whole`];
        if (wholeRef) {
            wholeRef.focus();
            wholeRef.select();
        }
    }
    
    // Arrow right at end of whole field moves to decimal
    if (key === 'ArrowRight' && fieldType === 'whole') {
        const input = event.target;
        if (input.selectionStart === input.value.length) {
            event.preventDefault();
            const decimalRef = meterInputRefs.value[`${meterIndex}_decimal`];
            if (decimalRef) {
                decimalRef.focus();
                decimalRef.setSelectionRange(0, 0);
            }
        }
    }
    
    // Arrow left at start of decimal field moves to whole
    if (key === 'ArrowLeft' && fieldType === 'decimal') {
        const input = event.target;
        if (input.selectionStart === 0) {
            event.preventDefault();
            const wholeRef = meterInputRefs.value[`${meterIndex}_whole`];
            if (wholeRef) {
                wholeRef.focus();
                const len = wholeRef.value.length;
                wholeRef.setSelectionRange(len, len);
            }
        }
    }
}

// Shared function to format meter reading value with proper padding
function formatMeterReading(meter) {
    if (!meter.initial_reading_whole && !meter.initial_reading_decimal) {
        return '';
    }
    const maxWholeDigits = getMaxWholeDigits(meter.meter_type_id);
    const maxDecimalDigits = getMaxDecimalDigits(meter.meter_type_id);
    
    // Pad whole part with leading zeros, decimal part with trailing zeros
    const whole = (meter.initial_reading_whole || '0').padStart(maxWholeDigits, '0');
    const decimal = (meter.initial_reading_decimal || '0').padEnd(maxDecimalDigits, '0');
    
    return `${whole}.${decimal}`;
}

function updateMeterReading(meter) {
    // Combine whole and decimal parts using shared formatting function
    meter.initial_reading = formatMeterReading(meter);
}

// Prepare meter data before submission - ensure proper formatting
function prepareMeterData() {
    return formData.meters.map(meter => ({
        meter_type_id: meter.meter_type_id,
        meter_title: meter.meter_title,
        meter_number: meter.meter_number,
        initial_reading: formatMeterReading(meter),
        initial_reading_date: meter.initial_reading_date
    }));
}

async function submitForm() {
    if (!canProceed.value) {
        showNotification('Please fill in all required fields', 'danger');
        return;
    }
    
    saving.value = true;
    
    try {
        // Prepare data with properly formatted meter readings
        const submitData = {
            ...formData,
            meters: prepareMeterData()
        };
        
        const response = await fetch(props.apiUrls.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify(submitData)
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            // Reset form after success
            setTimeout(() => {
                window.location.href = '/admin/user-accounts/manager';
            }, 1500);
        } else {
            showNotification(data.message || 'Error creating account', 'danger');
        }
    } catch (error) {
        showNotification('Error creating account: ' + error.message, 'danger');
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.user-account-setup-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step-indicator:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    left: calc(50% + 20px);
    width: calc(100% - 40px);
    height: 2px;
    background: #e9ecef;
}

.step-indicator.completed:not(:last-child)::after {
    background: #1cc88a;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 5px;
    position: relative;
    z-index: 1;
}

.step-indicator.active .step-number {
    background: #4e73df;
    color: white;
}

.step-indicator.completed .step-number {
    background: #1cc88a;
    color: white;
}

.step-label {
    font-size: 0.75rem;
    color: #858796;
    text-align: center;
}

.step-indicator.active .step-label {
    color: #4e73df;
    font-weight: bold;
}

/* Meter Reading Dual Input Design */
.meter-reading-input-wrapper {
    margin: 5px 0;
}

.meter-reading-dual-input {
    display: flex;
    align-items: center;
    gap: 4px;
}

.meter-main-input {
    flex: 1;
    max-width: 180px;
    height: 48px;
    padding: 8px 12px;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 20px;
    text-align: center;
    letter-spacing: 2px;
    background: #ffffff;
    color: #000000;
    border: 2px solid #333;
    border-radius: 6px;
}

.meter-main-input:focus {
    outline: none;
    border-color: #4e73df;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
}

.meter-decimal-separator {
    font-size: 28px;
    font-weight: bold;
    color: #333;
    margin: 0 2px;
}

.meter-decimal-input {
    width: 70px;
    height: 48px;
    padding: 8px 12px;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 20px;
    text-align: center;
    letter-spacing: 2px;
    background: #b30101;
    color: #ffffff;
    border: 2px solid #b30101;
    border-radius: 6px;
}

.meter-decimal-input:focus {
    outline: none;
    border-color: #8a0000;
    box-shadow: 0 0 0 3px rgba(179, 1, 1, 0.25);
}

.meter-decimal-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.meter-main-input::placeholder {
    color: rgba(0, 0, 0, 0.3);
}

/* Water Meter Styling - White main, Red decimal */
.meter-water-main {
    background: #ffffff;
    color: #000000;
    border-color: #333;
}

.meter-water-main::placeholder {
    color: rgba(0, 0, 0, 0.3);
}

.meter-water-decimal {
    background: #b30101;
    color: #ffffff;
    border-color: #b30101;
}

.meter-water-decimal::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.meter-water-decimal:focus {
    border-color: #8a0000;
    box-shadow: 0 0 0 3px rgba(179, 1, 1, 0.25);
}

/* Electricity Meter Styling - Black main, Grey decimal */
.meter-electricity-main {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
}

.meter-electricity-main::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.meter-electricity-main:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
}

.meter-electricity-decimal {
    background: #666666;
    color: #ffffff;
    border-color: #666666;
}

.meter-electricity-decimal::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.meter-electricity-decimal:focus {
    border-color: #444444;
    box-shadow: 0 0 0 3px rgba(102, 102, 102, 0.25);
}

/* Disabled field styling for Date to Date billing */
.disabled-field {
    background-color: #e9ecef !important;
    cursor: not-allowed;
    opacity: 0.7;
}

.alert {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive adjustments for meter inputs */
@media (max-width: 576px) {
    .meter-main-input {
        max-width: 140px;
        font-size: 16px;
        height: 42px;
    }
    
    .meter-decimal-input {
        width: 50px;
        font-size: 16px;
        height: 42px;
    }
    
    .meter-decimal-separator {
        font-size: 22px;
    }
}
</style>
