<div class="confirmation-container">
    <div class="status-icon <?= h(strtolower($order['status'])) ?>">
        <?php if ($order['status'] === 'PAID'): ?>
            <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <?php elseif ($order['status'] === 'FAILED'): ?>
            <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        <?php else: ?>
            <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        <?php endif; ?>
    </div>

    <h1>Order <?= h($order['status']) ?></h1>
    
    <div class="order-details">
        <div class="detail-row">
            <span class="label">Reference:</span>
            <span class="value"><?= h($order['order_reference']) ?></span>
        </div>
        <div class="detail-row">
            <span class="label">Amount:</span>
            <span class="value"><?= \App\Services\CurrencyService::format((int)$order['total_amount'], $order['currency']) ?></span>
        </div>
        <div class="detail-row">
            <span class="label">Date:</span>
            <span class="value"><?= h(date('M j, Y H:i', strtotime($order['created_at']))) ?></span>
        </div>
        <div class="detail-row">
            <span class="label">Billed To:</span>
            <span class="value"><?= h($order['billing_name']) ?> (<?= h($order['billing_email']) ?>)</span>
        </div>
    </div>

    <div class="actions">
        <a href="/" class="button outline">Start New Payment</a>
    </div>
</div>
