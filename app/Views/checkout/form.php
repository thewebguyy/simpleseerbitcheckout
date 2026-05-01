<div class="checkout-header">
    <h1><?= h(APP_NAME) ?></h1>
    <p>Secure Payment Initialization</p>
</div>

<form id="checkout-form" class="checkout-form" method="POST" action="/api/checkout/initialize">
    <?= \App\Utils\Csrf::field() ?>

    <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" placeholder="John Doe" required maxlength="100">
        <div class="error-msg" id="err_full_name"></div>
    </div>

    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="john@example.com" required maxlength="254">
        <div class="error-msg" id="err_email"></div>
    </div>

    <div class="form-row">
        <div class="form-group half">
            <label for="amount">Amount</label>
            <input type="number" id="amount" name="amount" placeholder="0.00" step="0.01" min="0.01" required>
            <div class="error-msg" id="err_amount"></div>
        </div>

        <div class="form-group half">
            <label for="currency">Currency</label>
            <select id="currency" name="currency" required>
                <?php foreach ($currencies as $curr): ?>
                    <option value="<?= h($curr) ?>" <?= $curr === 'NGN' ? 'selected' : '' ?>><?= h($curr) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="error-msg" id="err_currency"></div>
        </div>
    </div>

    <div class="form-group">
        <label for="payment_method">Payment Method</label>
        <select id="payment_method" name="payment_method" required>
            <option value="card">Credit/Debit Card</option>
            <option value="transfer">Bank Transfer</option>
        </select>
    </div>

    <button type="submit" class="button" id="pay-btn">
        <span class="btn-text">Pay Now</span>
        <span class="btn-loader" style="display: none;">Processing...</span>
    </button>
</form>

<!-- SeerBit SDK -->
<script src="https://checkout.seerbitapi.com/api/v2/seerbit.js"></script>
<!-- Our rebuilt checkout JS -->
<script src="/assets/js/checkout.js?v=<?= filemtime(ROOT_PATH . '/public/assets/js/checkout.js') ?>"></script>
