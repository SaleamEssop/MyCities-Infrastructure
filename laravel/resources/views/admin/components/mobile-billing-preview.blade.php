<style>
.mobile-billing-container {
    max-width: 400px;
    margin: 0 auto;
    background: #f5f5f5;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.mobile-header {
    background: #fff;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.mobile-header h2 {
    margin: 0 0 5px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.mobile-header .current-date {
    font-size: 14px;
    color: #666;
}

.payment-entry-section {
    background: #fff;
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.payment-entry-section .label {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.payment-amount-display {
    background: #f0f0f0;
    border-radius: 10px;
    padding: 20px;
    margin: 10px 0;
}

.payment-amount-display .amount {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.update-btn {
    font-size: 24px;
    color: #333;
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px;
}

.billing-periods {
    padding: 15px;
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
    font-size: 16px;
    font-weight: 600;
}

.period-header .period-total {
    font-size: 20px;
    font-weight: 700;
}

.period-header.current .period-total {
    color: #fff;
}

.period-details {
    padding: 15px 20px;
}

.period-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
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

/* Mobile frame styling */
.mobile-frame {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 30px;
    display: inline-block;
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
</style>

<div class="mobile-frame">
    <div class="mobile-notch"></div>
    <div class="mobile-billing-container" id="mobileBillingPreview">
        <!-- Header -->
        <div class="mobile-header">
            <h2>User: <span id="previewUserName">-</span></h2>
            <div class="current-date">Current Date: <span id="previewCurrentDate">-</span></div>
        </div>

        <!-- Payment Entry -->
        <div class="payment-entry-section">
            <div class="label">Click to enter a payment amount</div>
            <div class="payment-amount-display">
                <span class="amount" id="previewTotalOwing">R0.00</span>
            </div>
            <button class="update-btn" onclick="openAddPaymentFromPreview()">Update</button>
        </div>

        <!-- Billing Periods -->
        <div class="billing-periods" id="previewBillingPeriods">
            <!-- Periods will be dynamically inserted here -->
            <div class="text-center text-muted py-4">
                <i class="fas fa-spinner fa-spin"></i> Loading billing history...
            </div>
        </div>
    </div>
</div>

<script>
function formatDateShort(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const day = date.getDate();
    const months = ['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
    return `${day}${getOrdinalSuffix(day)} ${months[date.getMonth()]}`;
}

function getOrdinalSuffix(day) {
    if (day > 3 && day < 21) return 'th';
    switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}

function formatCurrency(amount) {
    return 'R' + parseFloat(amount || 0).toFixed(2);
}

function renderMobileBillingPreview(data) {
    if (!data) return;

    // Set header info
    document.getElementById('previewUserName').textContent = data.user_name || '-';
    document.getElementById('previewCurrentDate').textContent = formatDateShort(new Date().toISOString()) + ' ' + new Date().getFullYear();
    document.getElementById('previewTotalOwing').textContent = formatCurrency(data.total_owing);

    // Render periods
    const periodsContainer = document.getElementById('previewBillingPeriods');
    periodsContainer.innerHTML = '';

    if (!data.periods || data.periods.length === 0) {
        periodsContainer.innerHTML = '<div class="text-center text-muted py-4">No billing periods found</div>';
        return;
    }

    data.periods.forEach((period, index) => {
        const isCurrent = index === 0;
        const periodHtml = `
            <div class="period-card">
                <div class="period-header ${isCurrent ? 'current' : ''}">
                    <span class="period-dates">${formatDateShort(period.start_date)} > ${formatDateShort(period.end_date)}</span>
                    <span class="period-total">${formatCurrency(period.period_total)}</span>
                </div>
                <div class="period-details">
                    <div class="period-row">
                        <span class="label">Consumption - ${period.days} days</span>
                        <span class="value">${formatCurrency(period.consumption_charge)}</span>
                    </div>
                    ${period.balance_bf > 0 ? `
                    <div class="period-row">
                        <span class="label">Balance B/F</span>
                        <span class="value">${formatCurrency(period.balance_bf)}</span>
                    </div>
                    ` : ''}
                    ${period.payments && period.payments.length > 0 ? period.payments.map(p => `
                    <div class="period-row payment">
                        <span class="label">Payment - ${formatDateShort(p.date)} ${new Date(p.date).getFullYear()}</span>
                        <span class="value">${formatCurrency(p.amount)}</span>
                    </div>
                    `).join('') : ''}
                    <div class="period-row balance">
                        <span class="label">Balance</span>
                        <span class="value">${formatCurrency(period.balance)}</span>
                    </div>
                </div>
            </div>
        `;
        periodsContainer.innerHTML += periodHtml;
    });
}

function openAddPaymentFromPreview() {
    // Trigger the add payment modal if billing account is selected
    if (typeof showAddPaymentModal !== 'undefined') {
        showAddPaymentModal.value = true;
    }
}
</script>






