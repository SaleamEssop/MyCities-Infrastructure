<template>
    <div class="user-account-manager-wrapper">
        <!-- Search & Filter Section -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Search & Filter Users</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" v-model="filters.name" @input="debounceSearch" placeholder="Search by name...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" class="form-control" v-model="filters.address" @input="debounceSearch" placeholder="Search by address...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" v-model="filters.phone" @input="debounceSearch" placeholder="Search by phone...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>User Type</label>
                            <select class="form-control" v-model="filters.user_type" @change="searchUsers">
                                <option value="">All Users</option>
                                <option value="real">Real Users</option>
                                <option value="test">Test Users (@test.com)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User List -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Users List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Sites</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in usersList" :key="user.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ user.contact_number }}</td>
                                <td><span class="badge badge-primary">{{ user.sites_count || 0 }} site(s)</span></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm btn-circle mr-1" @click="viewUser(user.id)" title="View/Edit">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm btn-circle" @click="confirmDeleteUser(user)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="usersList.length === 0">
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Details Modal -->
        <div class="modal fade" :class="{ show: showUserModal, 'd-block': showUserModal }" tabindex="-1" v-show="showUserModal">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user mr-2"></i>
                            User Management - {{ selectedUser?.name }}
                        </h5>
                        <button type="button" class="close text-white" @click="closeUserModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;" v-if="selectedUser">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'user' }" href="#" @click.prevent="activeTab = 'user'">
                                    <i class="fas fa-user mr-1"></i> User Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'accounts' }" href="#" @click.prevent="activeTab = 'accounts'">
                                    <i class="fas fa-file-invoice mr-1"></i> Accounts & Meters
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'editAccount' }" href="#" @click.prevent="activeTab = 'editAccount'">
                                    <i class="fas fa-edit mr-1"></i> Edit Account
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'readings' }" href="#" @click.prevent="activeTab = 'readings'">
                                    <i class="fas fa-tachometer-alt mr-1"></i> Add Readings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'billing' }" href="#" @click.prevent="switchToBilling">
                                    <i class="fas fa-dollar-sign mr-1"></i> Billing & Payments
                                </a>
                            </li>
                        </ul>

                        <!-- User Details Tab -->
                        <div v-show="activeTab === 'user'">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="editUserData.name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" v-model="editUserData.email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="editUserData.contact_number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" class="form-control" v-model="editUserData.password" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary" @click="updateUser" :disabled="saving">
                                <i class="fas fa-save mr-1"></i> Save User Details
                            </button>
                        </div>

                        <!-- Accounts & Meters Tab -->
                        <div v-show="activeTab === 'accounts'">
                            <div v-for="site in selectedUser.sites" :key="site.id" class="card mb-3">
                                <div class="card-header">
                                    <strong><i class="fas fa-home mr-2"></i>{{ site.title || 'Site' }}</strong>
                                    <small class="text-muted ml-2">{{ site.address }}</small>
                                </div>
                                <div class="card-body">
                                    <div v-for="account in site.accounts" :key="account.id" class="border-left border-primary pl-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong>{{ account.account_name }}</strong>
                                                <span class="badge badge-secondary ml-2">{{ account.account_number }}</span>
                                            </div>
                                        </div>

                                        <!-- Meters List -->
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="font-weight-bold"><i class="fas fa-tachometer-alt mr-1"></i>Meters</small>
                                                <button type="button" class="btn btn-outline-success btn-sm" @click="openAddMeterModal(account.id)">
                                                    <i class="fas fa-plus"></i> Add Meter
                                                </button>
                                            </div>
                                            <div v-for="meter in account.meters" :key="meter.id" class="card bg-light mb-2">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="badge" :class="getMeterTypeBadgeClass(meter.meter_type_id)">
                                                                {{ getMeterTypeName(meter.meter_type_id) }}
                                                            </span>
                                                            <strong class="ml-2">{{ meter.meter_title }}</strong>
                                                            <small class="text-muted ml-2">#{{ meter.meter_number }}</small>
                                                        </div>
                                                        <div>
                                                            <button type="button" class="btn btn-info btn-sm mr-1" @click="viewReadings(meter)" title="View Readings">
                                                                <i class="fas fa-list"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" @click="deleteMeter(meter.id)" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-if="meter.readings && meter.readings.length > 0" class="mt-2">
                                                        <small class="text-muted">
                                                            Latest Reading: {{ meter.readings[0].reading_value }} on {{ meter.readings[0].reading_date }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!account.meters || account.meters.length === 0" class="text-muted small">
                                                No meters for this account
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!selectedUser.sites || selectedUser.sites.length === 0" class="text-muted text-center py-3">
                                No sites found for this user
                            </div>
                        </div>

                        <!-- Edit Account Tab -->
                        <div v-show="activeTab === 'editAccount'">
                            <div v-if="selectedUser.sites && selectedUser.sites.length > 0">
                                <div v-for="site in selectedUser.sites" :key="site.id">
                                    <div v-for="account in site.accounts" :key="account.id" class="card mb-4">
                                        <div class="card-header bg-light">
                                            <strong>{{ account.account_name }}</strong>
                                            <span class="badge badge-secondary ml-2">{{ account.account_number }}</span>
                                            <span v-if="isDateToDateTariff(account)" class="badge badge-info ml-2">Date-to-Date</span>
                                            <span v-else class="badge badge-primary ml-2">Monthly</span>
                                        </div>
                                        <div class="card-body">
                                            <!-- Region & Tariff (Read-only) -->
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="font-weight-bold text-muted">Region</label>
                                                    <input type="text" class="form-control bg-light" :value="site.region?.name || 'Not set'" readonly disabled>
                                                    <small class="text-muted">Region cannot be changed</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="font-weight-bold text-muted">Tariff Template</label>
                                                    <input type="text" class="form-control bg-light" :value="account.tariff?.template_name || 'Not set'" readonly disabled>
                                                    <small class="text-muted">Tariff cannot be changed</small>
                                                </div>
                                            </div>

                                            <hr>

                                            <!-- Editable Fields -->
                                            <h6 class="text-primary mb-3"><i class="fas fa-edit mr-1"></i> Editable Details</h6>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Name on Bill <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" v-model="account.name_on_bill" placeholder="Name as per utility bill">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Account Number</label>
                                                        <input type="text" class="form-control bg-light" :value="account.account_number" readonly disabled>
                                                        <small class="text-muted">Account number cannot be changed</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Water Department Email</label>
                                                        <input type="email" class="form-control" 
                                                               v-model="account.water_email" 
                                                               :disabled="isDateToDateTariff(account)"
                                                               :class="{ 'bg-light': isDateToDateTariff(account) }"
                                                               placeholder="water@municipality.gov.za">
                                                        <small v-if="isDateToDateTariff(account)" class="text-muted">Not applicable for Date-to-Date</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Electricity Department Email</label>
                                                        <input type="email" class="form-control" 
                                                               v-model="account.electricity_email" 
                                                               :disabled="isDateToDateTariff(account)"
                                                               :class="{ 'bg-light': isDateToDateTariff(account) }"
                                                               placeholder="electricity@municipality.gov.za">
                                                        <small v-if="isDateToDateTariff(account)" class="text-muted">Not applicable for Date-to-Date</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Billing Day</label>
                                                        <select class="form-control" 
                                                                v-model="account.bill_day"
                                                                :disabled="isDateToDateTariff(account)"
                                                                :class="{ 'bg-light': isDateToDateTariff(account) }">
                                                            <option value="">Select Day</option>
                                                            <option v-for="day in 28" :key="day" :value="day">{{ day }}{{ getDaySuffix(day) }} of each month</option>
                                                        </select>
                                                        <small v-if="isDateToDateTariff(account)" class="text-muted">Not applicable for Date-to-Date</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Read Day</label>
                                                        <select class="form-control" 
                                                                v-model="account.read_day"
                                                                :disabled="isDateToDateTariff(account)"
                                                                :class="{ 'bg-light': isDateToDateTariff(account) }">
                                                            <option value="">Select Day</option>
                                                            <option v-for="day in 28" :key="day" :value="day">{{ day }}{{ getDaySuffix(day) }} of each month</option>
                                                        </select>
                                                        <small v-if="isDateToDateTariff(account)" class="text-muted">Not applicable for Date-to-Date</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Customer Editable Costs -->
                                            <div v-if="account.customer_costs && account.customer_costs.length > 0" class="mt-3">
                                                <h6 class="text-success mb-2"><i class="fas fa-sliders-h mr-1"></i> Customer Editable Costs</h6>
                                                <div v-for="(cost, index) in account.customer_costs" :key="index" class="row mb-2">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control form-control-sm" v-model="cost.title" placeholder="Cost Name">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">R</span></div>
                                                            <input type="number" step="0.01" class="form-control" v-model="cost.value">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fixed Costs (Read-only) -->
                                            <div v-if="account.fixed_costs && account.fixed_costs.length > 0" class="mt-3">
                                                <h6 class="text-muted mb-2"><i class="fas fa-lock mr-1"></i> Fixed Costs (from template)</h6>
                                                <div v-for="(cost, index) in account.fixed_costs" :key="index" class="row mb-2">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control form-control-sm bg-light" :value="cost.title || cost.name" readonly>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">R</span></div>
                                                            <input type="text" class="form-control bg-light" :value="parseFloat(cost.value || cost.amount || 0).toFixed(2)" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>
                                            <button type="button" class="btn btn-primary" @click="saveAccountDetails(account)" :disabled="saving">
                                                <i class="fas fa-save mr-1"></i> Save Account Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-muted text-center py-3">
                                No accounts found for this user
                            </div>
                        </div>

                        <!-- Add Readings Tab -->
                        <div v-show="activeTab === 'readings'">
                            <h6 class="font-weight-bold mb-3">Add New Reading</h6>
                            
                            <!-- Meter Selection -->
                            <div class="form-group">
                                <label>Select Meter <span class="text-danger">*</span></label>
                                <select class="form-control" v-model="newReading.meter_id" @change="onMeterSelectForReading">
                                    <option value="">Select a Meter</option>
                                    <template v-for="site in selectedUser.sites" :key="site.id">
                                        <template v-for="account in site.accounts" :key="account.id">
                                            <option v-for="meter in account.meters" :key="meter.id" :value="meter.id">
                                                {{ site.title }} / {{ account.account_name }} / {{ meter.meter_title }} ({{ getMeterTypeName(meter.meter_type_id) }})
                                            </option>
                                        </template>
                                    </template>
                                </select>
                            </div>

                            <div v-if="newReading.meter_id">
                                <!-- Previous Reading Info -->
                                <div v-if="previousReading" class="alert alert-info mb-3">
                                    <strong>Previous Reading:</strong> {{ previousReading.reading_value }} on {{ previousReading.reading_date }}
                                </div>

                                <!-- Date Input -->
                                <div class="form-group">
                                    <label>Reading Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="newReading.reading_date" :min="previousReading?.reading_date">
                                    <small v-if="previousReading" class="text-muted">Cannot be earlier than {{ previousReading.reading_date }}</small>
                                </div>

                                <!-- Reading Value Input -->
                                <div class="form-group">
                                    <label>Reading Value <span class="text-danger">*</span></label>
                                    
                                    <!-- Water Meter Pigeonhole Input -->
                                    <div v-if="isMeterWater(selectedMeterType)" class="meter-input-container">
                                        <div class="water-meter-display">
                                            <!-- White section: 6 digits -->
                                            <div class="meter-section white-section">
                                                <div v-for="i in 6" :key="'white-' + i" class="meter-box white-box">
                                                    <input 
                                                        type="text" 
                                                        maxlength="1"
                                                        class="meter-input"
                                                        :value="getDigit(newReading.wholeDigits, i - 1)"
                                                        @input="updateDigit($event, i - 1, 'whole')"
                                                        @keydown="handleKeyDown($event, i - 1, 'whole')"
                                                        :ref="el => wholeInputRefs[i - 1] = el">
                                                </div>
                                            </div>
                                            <span class="meter-decimal-point">.</span>
                                            <!-- Red section: 1 decimal digit -->
                                            <div class="meter-section red-section">
                                                <div class="meter-box red-box">
                                                    <input 
                                                        type="text" 
                                                        maxlength="1"
                                                        class="meter-input"
                                                        :value="newReading.decimalDigit"
                                                        @input="updateDigit($event, 0, 'decimal')"
                                                        ref="decimalInput">
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1">Enter water meter reading (6 whole digits + 1 decimal)</small>
                                        <small v-if="previousReading" class="text-danger d-block">Value must be >= {{ previousReading.reading_value }}</small>
                                    </div>

                                    <!-- Electricity Meter: 5 digits + 1 decimal -->
                                    <div v-else-if="isMeterElectricity(selectedMeterType)" class="meter-input-container">
                                        <div class="electricity-meter-display">
                                            <!-- Black section: 5 digits -->
                                            <div class="meter-section black-section">
                                                <div v-for="i in 5" :key="'black-' + i" class="meter-box black-box">
                                                    <input 
                                                        type="text" 
                                                        maxlength="1"
                                                        class="meter-input"
                                                        :value="getDigit(newReading.wholeDigits, i - 1)"
                                                        @input="updateDigit($event, i - 1, 'whole')"
                                                        @keydown="handleKeyDown($event, i - 1, 'whole')"
                                                        :ref="el => wholeInputRefs[i - 1] = el">
                                                </div>
                                            </div>
                                            <span class="meter-decimal-point">.</span>
                                            <!-- Red section: 1 decimal digit -->
                                            <div class="meter-section red-section">
                                                <div class="meter-box red-box">
                                                    <input 
                                                        type="text" 
                                                        maxlength="1"
                                                        class="meter-input"
                                                        :value="newReading.decimalDigit"
                                                        @input="updateDigit($event, 0, 'decimal')"
                                                        ref="decimalInput">
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1">Enter electricity meter reading (5 digits + 1 decimal)</small>
                                        <small v-if="previousReading" class="text-danger d-block">Value must be >= {{ previousReading.reading_value }}</small>
                                    </div>

                                    <!-- Standard input for other meter types -->
                                    <div v-else>
                                        <input type="number" class="form-control" v-model="newReading.reading_value" step="0.1">
                                        <small v-if="previousReading" class="text-danger">Value must be >= {{ previousReading.reading_value }}</small>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-success" @click="addReading" :disabled="saving">
                                    <i class="fas fa-plus mr-1"></i> Add Reading
                                </button>
                            </div>
                        </div>

                        <!-- Billing Tab -->
                        <div v-show="activeTab === 'billing'">
                            <!-- Account Selection -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Select Account</label>
                                <select class="form-control" v-model="billingAccountId" @change="loadBillingHistory">
                                    <option value="">-- Select an Account --</option>
                                    <template v-for="site in selectedUser.sites" :key="site.id">
                                        <option v-for="account in site.accounts" :key="account.id" :value="account.id">
                                            {{ site.title }} - {{ account.account_name }} ({{ account.account_number }})
                                        </option>
                                    </template>
                                </select>
                            </div>

                            <!-- Mobile Billing Preview -->
                            <div class="row">
                                <div class="col-12 d-flex justify-content-center">
                                    <div class="mobile-billing-wrapper">
                                        <!-- Mobile Frame -->
                                        <div class="mobile-frame">
                                            <div class="mobile-notch"></div>
                                            <div class="mobile-billing-container">
                                                <!-- Loading State -->
                                                <div v-if="loadingBilling" class="mobile-loading">
                                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                                    <p>Loading...</p>
                                                </div>

                                                <!-- Empty State -->
                                                <div v-else-if="!billingHistory" class="mobile-empty">
                                                    <i class="fas fa-hand-point-up fa-3x mb-3"></i>
                                                    <p>Select an account to view billing</p>
                                                </div>

                                                <!-- Billing Content -->
                                                <template v-else>
                                                    <!-- Header -->
                                                    <div class="mobile-header">
                                                        <h2>User: {{ billingHistory.user_name }}</h2>
                                                        <div class="current-date">Current Date: {{ formatDateMobile(new Date()) }}</div>
                                                    </div>

                                                    <!-- Payment Entry -->
                                                    <div class="payment-entry-section">
                                                        <div class="label">Click to enter a payment amount</div>
                                                        <div class="payment-amount-display" @click="showAddPaymentModal = true" style="cursor: pointer;">
                                                            <span class="amount">R{{ formatCurrency(billingHistory.total_owing) }}</span>
                                                        </div>
                                                        <button class="update-btn" @click="showAddPaymentModal = true">Update</button>
                                                    </div>

                                                    <!-- Billing Periods -->
                                                    <div class="billing-periods">
                                                        <div v-if="!billingHistory.periods || billingHistory.periods.length === 0" class="no-periods">
                                                            No billing periods found
                                                        </div>
                                                        <div v-else v-for="(period, index) in billingHistory.periods" :key="index" class="period-card">
                                                            <div class="period-header" :class="{ current: index === 0 }">
                                                                <span class="period-dates">{{ formatDateMobile(period.start_date) }} > {{ formatDateMobile(period.end_date) }}</span>
                                                                <span class="period-total">R{{ formatCurrency(period.period_total) }}</span>
                                                            </div>
                                                            <div class="period-details">
                                                                <div class="period-row">
                                                                    <span class="label">Consumption - {{ period.days }} days</span>
                                                                    <span class="value">R{{ formatCurrency(period.consumption_charge) }}</span>
                                                                </div>
                                                                <div v-if="period.balance_bf > 0" class="period-row">
                                                                    <span class="label">Balance B/F</span>
                                                                    <span class="value">R{{ formatCurrency(period.balance_bf) }}</span>
                                                                </div>
                                                                <div v-for="payment in period.payments" :key="payment.id" class="period-row payment">
                                                                    <span class="label">Payment - {{ formatDateMobile(payment.date) }} {{ new Date(payment.date).getFullYear() }}</span>
                                                                    <span class="value">R{{ formatCurrency(payment.amount) }}</span>
                                                                </div>
                                                                <div class="period-row balance">
                                                                    <span class="label">Balance</span>
                                                                    <span class="value">R{{ formatCurrency(period.balance) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body" v-else>
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading user data...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeUserModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showUserModal"></div>

        <!-- Add Meter Modal -->
        <div class="modal fade" :class="{ show: showAddMeterModal, 'd-block': showAddMeterModal }" tabindex="-1" v-show="showAddMeterModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Add New Meter</h5>
                        <button type="button" class="close text-white" @click="showAddMeterModal = false">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Meter Type <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="newMeter.meter_type_id">
                                <option value="">Select Type</option>
                                <option v-for="type in meterTypes" :key="type.id" :value="type.id">{{ type.title }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Meter Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="newMeter.meter_title" placeholder="Enter meter title">
                        </div>
                        <div class="form-group">
                            <label>Meter Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="newMeter.meter_number" placeholder="Enter meter number">
                        </div>
                        <div class="form-group">
                            <label>Initial Reading</label>
                            <input type="number" class="form-control" v-model="newMeter.initial_reading" placeholder="Optional initial reading">
                        </div>
                        <div class="form-group" v-if="newMeter.initial_reading">
                            <label>Initial Reading Date</label>
                            <input type="date" class="form-control" v-model="newMeter.initial_reading_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showAddMeterModal = false">Cancel</button>
                        <button type="button" class="btn btn-success" @click="addMeter" :disabled="saving">
                            <i class="fas fa-plus mr-1"></i> Add Meter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showAddMeterModal" style="z-index: 1040;"></div>

        <!-- Readings History Modal -->
        <div class="modal fade" :class="{ show: showReadingsModal, 'd-block': showReadingsModal }" tabindex="-1" v-show="showReadingsModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="fas fa-list mr-2"></i>Readings History</h5>
                        <button type="button" class="close text-white" @click="showReadingsModal = false">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingReadings" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                        </div>
                        <div v-else-if="meterReadings.length === 0" class="text-center py-4 text-muted">
                            No readings found for this meter
                        </div>
                        <table v-else class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reading Value</th>
                                    <th>Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(reading, index) in meterReadings" :key="reading.id">
                                    <td>{{ reading.reading_date }}</td>
                                    <td>{{ reading.reading_value }}</td>
                                    <td>
                                        <span v-if="index < meterReadings.length - 1">
                                            {{ (parseFloat(reading.reading_value) - parseFloat(meterReadings[index + 1].reading_value)).toFixed(2) }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showReadingsModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showReadingsModal" style="z-index: 1040;"></div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" :class="{ show: showDeleteModal, 'd-block': showDeleteModal }" tabindex="-1" v-show="showDeleteModal" style="z-index: 1060;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Confirm Delete</h5>
                        <button type="button" class="close text-white" @click="closeDeleteModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ deleteMessage }}</p>
                        <p class="text-danger font-weight-bold">This action cannot be undone!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeleteModal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="executeDelete" :disabled="loading">
                            <span v-if="loading"><i class="fas fa-spinner fa-spin"></i></span>
                            <span v-else><i class="fas fa-trash mr-1"></i> Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showDeleteModal" @click="closeDeleteModal" style="z-index: 1055;"></div>

        <!-- Add Payment Modal -->
        <div class="modal fade" :class="{ show: showAddPaymentModal, 'd-block': showAddPaymentModal }" tabindex="-1" v-show="showAddPaymentModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-credit-card mr-2"></i>Record Payment</h5>
                        <button type="button" class="close text-white" @click="showAddPaymentModal = false">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Amount (R) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-lg" v-model="newPayment.amount" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" v-model="newPayment.payment_date">
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select class="form-control" v-model="newPayment.payment_method">
                                <option value="EFT">EFT (Bank Transfer)</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Debit Order">Debit Order</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" class="form-control" v-model="newPayment.reference" placeholder="e.g. EFT-12345">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" v-model="newPayment.notes" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showAddPaymentModal = false">Cancel</button>
                        <button type="button" class="btn btn-success" @click="addPayment" :disabled="saving || !newPayment.amount">
                            <span v-if="saving"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                            <i v-else class="fas fa-check mr-1"></i> Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showAddPaymentModal" style="z-index: 1055;"></div>

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
import { ref, reactive, computed, onMounted } from 'vue';

const props = defineProps({
    csrfToken: {
        type: String,
        required: true
    },
    users: {
        type: Array,
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

// State
const usersList = ref([...props.users]);
const selectedUser = ref(null);
const showUserModal = ref(false);
const showAddMeterModal = ref(false);
const showReadingsModal = ref(false);
const showDeleteModal = ref(false);
const showAddPaymentModal = ref(false);
const activeTab = ref('user');
const loading = ref(false);
const saving = ref(false);
const loadingReadings = ref(false);
const loadingBilling = ref(false);
const searchTimeout = ref(null);
const deleteMessage = ref('');
const deleteAction = ref(null);
const meterReadings = ref([]);
const previousReading = ref(null);
const selectedMeterType = ref(null);

// Billing state
const billingAccountId = ref('');
const billingData = ref(null);
const billingHistory = ref(null);

// Refs for pigeonhole inputs
const wholeInputRefs = ref([]);
const decimalInput = ref(null);

// Notification
const notification = reactive({
    show: false,
    type: 'success',
    message: ''
});

// Filters
const filters = reactive({
    name: '',
    address: '',
    phone: '',
    user_type: ''
});

// Edit User Data
const editUserData = reactive({
    name: '',
    email: '',
    contact_number: '',
    password: ''
});

// New Meter
const newMeter = reactive({
    account_id: '',
    meter_type_id: '',
    meter_title: '',
    meter_number: '',
    initial_reading: '',
    initial_reading_date: new Date().toISOString().split('T')[0]
});

// New Reading with pigeonhole data
const newReading = reactive({
    meter_id: '',
    reading_date: new Date().toISOString().split('T')[0],
    reading_value: '',
    wholeDigits: '000000', // For water: 6 digits, for electricity: 5 digits (padded)
    decimalDigit: '0'
});

// New Payment
const newPayment = reactive({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'EFT',
    reference: '',
    notes: ''
});

// Helper functions
function buildUrl(urlTemplate, replacements) {
    let url = urlTemplate;
    if (typeof replacements === 'object') {
        for (const [placeholder, value] of Object.entries(replacements)) {
            url = url.replace(placeholder, value);
        }
    } else {
        // Backward compatible: single ID replaces all placeholders
        url = url.replace('__ID__', replacements).replace('__METER_ID__', replacements).replace('__REGION_ID__', replacements);
    }
    return url;
}

function showNotification(message, type = 'success') {
    notification.message = message;
    notification.type = type;
    notification.show = true;
    setTimeout(() => {
        notification.show = false;
    }, 5000);
}

function getMeterTypeName(typeId) {
    const type = props.meterTypes.find(t => t.id === typeId);
    return type ? type.title : 'Unknown';
}

// Check if account uses a Date-to-Date tariff
function isDateToDateTariff(account) {
    if (!account || !account.tariff) return false;
    const billingType = account.tariff.billing_type || '';
    return billingType.toUpperCase() === 'DATE_TO_DATE' || 
           billingType.toLowerCase().includes('date-to-date') ||
           billingType.toLowerCase().includes('date to date');
}

// Get day suffix (1st, 2nd, 3rd, 4th, etc.)
function getDaySuffix(day) {
    if (day >= 11 && day <= 13) return 'th';
    switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}

// Save account details
async function saveAccountDetails(account) {
    saving.value = true;
    try {
        const response = await fetch(buildUrl(props.apiUrls.updateAccount, account.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({
                name_on_bill: account.name_on_bill,
                water_email: account.water_email,
                electricity_email: account.electricity_email,
                bill_day: account.bill_day,
                read_day: account.read_day,
                customer_costs: account.customer_costs
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showNotification('Account details saved successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to save account details', 'danger');
        }
    } catch (error) {
        console.error('Error saving account:', error);
        showNotification('An error occurred while saving', 'danger');
    } finally {
        saving.value = false;
    }
}

function getMeterTypeBadgeClass(typeId) {
    const type = props.meterTypes.find(t => t.id === typeId);
    if (!type) return 'badge-secondary';
    const title = type.title.toLowerCase();
    if (title === 'water') return 'badge-primary';
    if (title === 'electricity') return 'badge-warning';
    return 'badge-secondary';
}

function isMeterWater(typeId) {
    if (!typeId) return false;
    const type = props.meterTypes.find(t => t.id === typeId);
    return type && type.title && type.title.toLowerCase() === 'water';
}

function isMeterElectricity(typeId) {
    if (!typeId) return false;
    const type = props.meterTypes.find(t => t.id === typeId);
    return type && type.title && type.title.toLowerCase() === 'electricity';
}

// Constants for meter digit counts
const WATER_METER_DIGITS = 6;
const ELECTRICITY_METER_DIGITS = 5;
const DECIMAL_DIGITS = 1;

// Helper to get max digits for meter type
function getMaxDigitsForMeterType(typeId) {
    return isMeterWater(typeId) ? WATER_METER_DIGITS : ELECTRICITY_METER_DIGITS;
}

// Pigeonhole input helpers
function getDigit(digits, index) {
    return digits && digits[index] ? digits[index] : '0';
}

function updateDigit(event, index, section) {
    const value = event.target.value.replace(/[^0-9]/g, '');
    
    if (section === 'whole') {
        const maxDigits = getMaxDigitsForMeterType(selectedMeterType.value);
        let digits = newReading.wholeDigits.split('');
        digits[index] = value || '0';
        newReading.wholeDigits = digits.join('').padEnd(maxDigits, '0').slice(0, maxDigits);
        
        // Auto-focus next input
        if (value && index < maxDigits - 1) {
            const nextRef = wholeInputRefs.value[index + 1];
            if (nextRef) nextRef.focus();
        } else if (value && index === maxDigits - 1 && decimalInput.value) {
            decimalInput.value.focus();
        }
    } else {
        newReading.decimalDigit = value || '0';
    }
    
    // Update combined reading value
    updateCombinedReadingValue();
}

function handleKeyDown(event, index, section) {
    if (event.key === 'Backspace' && !event.target.value && index > 0) {
        const prevRef = wholeInputRefs.value[index - 1];
        if (prevRef) prevRef.focus();
    }
}

function updateCombinedReadingValue() {
    newReading.reading_value = `${newReading.wholeDigits}.${newReading.decimalDigit}`;
}

// Search
function debounceSearch() {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    searchTimeout.value = setTimeout(() => {
        searchUsers();
    }, 300);
}

async function searchUsers() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.name) params.append('name', filters.name);
        if (filters.address) params.append('address', filters.address);
        if (filters.phone) params.append('phone', filters.phone);
        if (filters.user_type) params.append('user_type', filters.user_type);
        
        const response = await fetch(`${props.apiUrls.search}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        if (data.status === 200) {
            usersList.value = data.data;
        }
    } catch (error) {
        showNotification('Error searching users: ' + error.message, 'danger');
    } finally {
        loading.value = false;
    }
}

// View User
async function viewUser(userId) {
    loading.value = true;
    selectedUser.value = null;
    activeTab.value = 'user';
    showUserModal.value = true;
    
    try {
        const response = await fetch(buildUrl(props.apiUrls.getUserData, userId), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        
        if (data.status === 200) {
            selectedUser.value = data.data;
            editUserData.name = data.data.name;
            editUserData.email = data.data.email;
            editUserData.contact_number = data.data.contact_number;
            editUserData.password = '';
        } else {
            showNotification('Error loading user data', 'danger');
            showUserModal.value = false;
        }
    } catch (error) {
        showNotification('Error loading user: ' + error.message, 'danger');
        showUserModal.value = false;
    } finally {
        loading.value = false;
    }
}

function closeUserModal() {
    showUserModal.value = false;
    selectedUser.value = null;
}

// Update User
async function updateUser() {
    if (!editUserData.name || !editUserData.email || !editUserData.contact_number) {
        showNotification('Please fill in all required fields', 'danger');
        return;
    }
    
    saving.value = true;
    
    try {
        const response = await fetch(buildUrl(props.apiUrls.updateUser, selectedUser.value.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify(editUserData)
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            // Update local data
            selectedUser.value.name = editUserData.name;
            selectedUser.value.email = editUserData.email;
            selectedUser.value.contact_number = editUserData.contact_number;
            searchUsers();
        } else {
            showNotification(data.message || 'Error updating user', 'danger');
        }
    } catch (error) {
        showNotification('Error updating user: ' + error.message, 'danger');
    } finally {
        saving.value = false;
    }
}

// Add Meter
function openAddMeterModal(accountId) {
    newMeter.account_id = accountId;
    newMeter.meter_type_id = '';
    newMeter.meter_title = '';
    newMeter.meter_number = '';
    newMeter.initial_reading = '';
    newMeter.initial_reading_date = new Date().toISOString().split('T')[0];
    showAddMeterModal.value = true;
}

async function addMeter() {
    if (!newMeter.meter_type_id || !newMeter.meter_title || !newMeter.meter_number) {
        showNotification('Please fill in all required fields', 'danger');
        return;
    }
    
    saving.value = true;
    
    try {
        const response = await fetch(props.apiUrls.addMeter, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify(newMeter)
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            showAddMeterModal.value = false;
            // Refresh user data
            viewUser(selectedUser.value.id);
        } else {
            showNotification(data.message || 'Error adding meter', 'danger');
        }
    } catch (error) {
        showNotification('Error adding meter: ' + error.message, 'danger');
    } finally {
        saving.value = false;
    }
}

// Delete Meter
async function deleteMeter(meterId) {
    if (!confirm('Are you sure you want to delete this meter?')) return;
    
    try {
        const response = await fetch(buildUrl(props.apiUrls.deleteMeter, meterId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            viewUser(selectedUser.value.id);
        } else {
            showNotification(data.message || 'Error deleting meter', 'danger');
        }
    } catch (error) {
        showNotification('Error deleting meter: ' + error.message, 'danger');
    }
}

// View Readings
async function viewReadings(meter) {
    loadingReadings.value = true;
    meterReadings.value = [];
    showReadingsModal.value = true;
    
    try {
        const response = await fetch(buildUrl(props.apiUrls.getReadings, meter.id), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        
        if (data.status === 200) {
            meterReadings.value = data.data;
        }
    } catch (error) {
        showNotification('Error loading readings: ' + error.message, 'danger');
    } finally {
        loadingReadings.value = false;
    }
}

// Meter selection for new reading
function onMeterSelectForReading() {
    if (!newReading.meter_id) {
        previousReading.value = null;
        selectedMeterType.value = null;
        return;
    }
    
    // Find the selected meter
    let selectedMeter = null;
    for (const site of selectedUser.value.sites) {
        for (const account of site.accounts) {
            for (const meter of account.meters) {
                if (meter.id === newReading.meter_id) {
                    selectedMeter = meter;
                    break;
                }
            }
        }
    }
    
    if (selectedMeter) {
        selectedMeterType.value = selectedMeter.meter_type_id;
        previousReading.value = selectedMeter.readings && selectedMeter.readings.length > 0 ? selectedMeter.readings[0] : null;
        
        // Initialize pigeonhole values
        const maxDigits = getMaxDigitsForMeterType(selectedMeterType.value);
        newReading.wholeDigits = '0'.repeat(maxDigits);
        newReading.decimalDigit = '0';
        newReading.reading_value = '';
    }
}

// Add Reading
async function addReading() {
    if (!newReading.meter_id || !newReading.reading_date) {
        showNotification('Please select a meter and enter a date', 'danger');
        return;
    }
    
    // Calculate combined value for pigeonhole meters
    if (isMeterWater(selectedMeterType.value) || isMeterElectricity(selectedMeterType.value)) {
        updateCombinedReadingValue();
    }
    
    if (!newReading.reading_value) {
        showNotification('Please enter a reading value', 'danger');
        return;
    }
    
    saving.value = true;
    
    try {
        const response = await fetch(props.apiUrls.addReading, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({
                meter_id: newReading.meter_id,
                reading_date: newReading.reading_date,
                reading_value: newReading.reading_value
            })
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            // Reset form
            newReading.meter_id = '';
            newReading.reading_value = '';
            newReading.wholeDigits = '000000';
            newReading.decimalDigit = '0';
            previousReading.value = null;
            selectedMeterType.value = null;
            // Refresh user data
            viewUser(selectedUser.value.id);
        } else {
            showNotification(data.message || 'Error adding reading', 'danger');
        }
    } catch (error) {
        showNotification('Error adding reading: ' + error.message, 'danger');
    } finally {
        saving.value = false;
    }
}

// Delete User
function closeDeleteModal() {
    showDeleteModal.value = false;
    deleteAction.value = null;
    deleteMessage.value = '';
}

function confirmDeleteUser(user) {
    deleteMessage.value = `Are you sure you want to delete user "${user.name}" and all associated data (sites, accounts, meters, readings, payments, history)?`;
    deleteAction.value = () => deleteUser(user.id);
    showDeleteModal.value = true;
}

async function deleteUser(userId) {
    loading.value = true;
    
    try {
        const response = await fetch(buildUrl(props.apiUrls.deleteUser, userId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification(data.message, 'success');
            showDeleteModal.value = false;
            searchUsers();
        } else {
            showNotification(data.message || 'Error deleting user', 'danger');
        }
    } catch (error) {
        showNotification('Error deleting user: ' + error.message, 'danger');
    } finally {
        loading.value = false;
    }
}

function executeDelete() {
    if (deleteAction.value) {
        deleteAction.value();
    }
}

// Billing Methods
function switchToBilling() {
    activeTab.value = 'billing';
    // Auto-select first account if available
    if (selectedUser.value?.sites?.length > 0) {
        const firstSite = selectedUser.value.sites[0];
        if (firstSite.accounts?.length > 0) {
            billingAccountId.value = firstSite.accounts[0].id;
            loadAccountBilling();
        }
    }
}

async function loadAccountBilling() {
    if (!billingAccountId.value) {
        billingData.value = null;
        return;
    }
    
    loadingBilling.value = true;
    billingData.value = null;
    
    try {
        const url = buildUrl(props.apiUrls.getAccountBilling, { '__ACCOUNT_ID__': billingAccountId.value });
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        
        if (data.status === 200) {
            billingData.value = data.data;
        } else {
            showNotification(data.message || 'Error loading billing data', 'danger');
        }
    } catch (error) {
        showNotification('Error loading billing data: ' + error.message, 'danger');
    } finally {
        loadingBilling.value = false;
    }
}

async function loadBillingHistory() {
    console.log('loadBillingHistory called, accountId:', billingAccountId.value);
    
    if (!billingAccountId.value) {
        billingHistory.value = null;
        return;
    }
    
    loadingBilling.value = true;
    billingHistory.value = null;
    
    try {
        const url = buildUrl(props.apiUrls.getBillingHistory, { '__ACCOUNT_ID__': billingAccountId.value });
        console.log('Fetching billing history from:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        const data = await response.json();
        console.log('Billing history response:', data);
        
        if (data.status === 200) {
            billingHistory.value = data.data;
            console.log('billingHistory set to:', billingHistory.value);
        } else {
            showNotification(data.message || 'Error loading billing history', 'danger');
        }
    } catch (error) {
        console.error('Error loading billing history:', error);
        showNotification('Error loading billing history: ' + error.message, 'danger');
    } finally {
        loadingBilling.value = false;
    }
}

function formatDateMobile(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const day = date.getDate();
    const months = ['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
    
    function getOrdinal(n) {
        if (n > 3 && n < 21) return 'th';
        switch (n % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    }
    
    return `${day}${getOrdinal(day)} ${months[date.getMonth()]}`;
}

async function addPayment() {
    if (!newPayment.amount || !newPayment.payment_date) {
        showNotification('Please enter an amount and date', 'danger');
        return;
    }
    
    saving.value = true;
    
    try {
        const response = await fetch(props.apiUrls.addPayment, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            body: JSON.stringify({
                account_id: billingAccountId.value,
                amount: parseFloat(newPayment.amount),
                payment_date: newPayment.payment_date,
                payment_method: newPayment.payment_method,
                reference: newPayment.reference || 'PAY-' + Date.now(),
                notes: newPayment.notes
            })
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification('Payment recorded successfully!', 'success');
            showAddPaymentModal.value = false;
            // Reset form
            newPayment.amount = '';
            newPayment.reference = '';
            newPayment.notes = '';
            // Reload billing data
            loadBillingHistory();
        } else {
            showNotification(data.message || 'Error adding payment', 'danger');
        }
    } catch (error) {
        showNotification('Error adding payment: ' + error.message, 'danger');
    } finally {
        saving.value = false;
    }
}

function confirmDeletePayment(payment) {
    deleteMessage.value = `Are you sure you want to delete payment of R${formatCurrency(payment.amount)} (${payment.reference})?`;
    deleteAction.value = () => deletePayment(payment.id);
    showDeleteModal.value = true;
}

async function deletePayment(paymentId) {
    loading.value = true;
    
    try {
        const url = buildUrl(props.apiUrls.deletePayment, paymentId);
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.status === 200) {
            showNotification('Payment deleted successfully', 'success');
            showDeleteModal.value = false;
            loadBillingHistory();
        } else {
            showNotification(data.message || 'Error deleting payment', 'danger');
        }
    } catch (error) {
        showNotification('Error deleting payment: ' + error.message, 'danger');
    } finally {
        loading.value = false;
    }
}

function formatCurrency(value) {
    if (value === null || value === undefined) return '0.00';
    return parseFloat(value).toFixed(2);
}

// Initial load
onMounted(() => {
    // Users are already loaded via props
});
</script>

<style scoped>
.user-account-manager-wrapper {
    padding: 0;
}

.modal.show {
    display: block;
}

.btn-circle {
    border-radius: 50%;
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.nav-tabs .nav-link {
    cursor: pointer;
}

.nav-tabs .nav-link.active {
    font-weight: bold;
}

/* Water Meter Pigeonhole Styles */
.meter-input-container {
    margin: 10px 0;
}

.water-meter-display,
.electricity-meter-display {
    display: flex;
    align-items: center;
    gap: 2px;
}

.meter-section {
    display: flex;
    gap: 2px;
}

.meter-box {
    width: 40px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 2px solid #333;
}

.white-box {
    background: #fff;
    border-color: #333;
}

.white-box .meter-input {
    color: #000;
    background: transparent;
}

.black-box {
    background: #333;
    border-color: #333;
}

.black-box .meter-input {
    color: #fff;
    background: transparent;
}

.red-box {
    background: #b30101;
    border-color: #b30101;
}

.red-box .meter-input {
    color: #fff;
    background: transparent;
}

.meter-input {
    width: 100%;
    height: 100%;
    text-align: center;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 24px;
    border: none;
    outline: none;
}

.meter-decimal-point {
    font-size: 30px;
    font-weight: bold;
    margin: 0 4px;
}

.badge-primary {
    background-color: #4e73df;
}

.badge-warning {
    background-color: #f6c23e;
    color: #1f2d3d;
}

.badge-secondary {
    background-color: #858796;
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

/* Mobile Billing Preview Styles */
.mobile-billing-wrapper {
    padding: 20px;
}

.mobile-frame {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 30px;
    display: inline-block;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.mobile-notch {
    width: 120px;
    height: 25px;
    background: #1a1a1a;
    border-radius: 0 0 15px 15px;
    margin: 0 auto -5px;
    position: relative;
    z-index: 10;
}

.mobile-billing-container {
    width: 375px;
    min-height: 600px;
    max-height: 70vh;
    overflow-y: auto;
    background: #f5f5f5;
    border-radius: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.mobile-loading,
.mobile-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    color: #888;
}

.mobile-header {
    background: #fff;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.mobile-header h2 {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.mobile-header .current-date {
    font-size: 13px;
    color: #666;
}

.payment-entry-section {
    background: #fff;
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.payment-entry-section .label {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.payment-amount-display {
    background: #f0f0f0;
    border-radius: 10px;
    padding: 20px;
    margin: 10px 0;
    transition: all 0.2s ease;
}

.payment-amount-display:hover {
    background: #e8e8e8;
}

.payment-amount-display .amount {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.update-btn {
    font-size: 20px;
    color: #333;
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px 20px;
}

.update-btn:hover {
    color: #007bff;
}

.billing-periods {
    padding: 15px;
}

.no-periods {
    text-align: center;
    padding: 40px;
    color: #888;
}

.period-card {
    background: #fff;
    border-radius: 12px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.period-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #e8e8e8;
}

.period-header.current {
    background: linear-gradient(135deg, #4ECDC4, #44A08D);
    color: #fff;
}

.period-header .period-dates {
    font-size: 15px;
    font-weight: 600;
}

.period-header .period-total {
    font-size: 18px;
    font-weight: 700;
}

.period-header.current .period-total {
    color: #fff;
}

.period-details {
    padding: 12px 20px;
}

.period-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    color: #555;
}

.period-row .label {
    color: #666;
}

.period-row .value {
    font-weight: 500;
    color: #333;
}

.period-row.balance {
    border-top: 1px solid #eee;
    margin-top: 5px;
    padding-top: 12px;
}

.period-row.balance .label {
    font-weight: 600;
    color: #333;
}

.period-row.balance .value {
    font-weight: 700;
}

.period-row.payment .value {
    color: #4CAF50;
}
</style>
