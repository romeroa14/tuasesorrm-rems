<?php
/**
 * Campos compartidos de monto + método de pago + equivalencias.
 *
 * @var array<string, mixed> $moneyFields
 */
$moneyFields = array_merge([
    'payment_type_id'       => 'payment_type_id',
    'amount_id'             => 'amount',
    'amount_label'          => 'Monto',
    'amount_col'            => 'col-md-4',
    'payment_col'           => 'col-md-4',
    'show_rate_display'     => true,
    'rate_input_name'       => 'exchange_rate',
    'rate_input_id'         => 'exchange_rate',
    'denomination_input_id' => 'currency_denomination',
    'usd_display_id'        => 'amount_usd_display',
    'bs_display_id'         => 'amount_bs_display',
], $moneyFields ?? []);
?>
<div class="row">
    <?php if (! empty($moneyFields['before_amount'])): ?>
        <?= $moneyFields['before_amount'] ?>
    <?php endif; ?>
    <div class="<?= esc($moneyFields['amount_col']) ?>">
        <div class="form-group">
            <label id="<?= esc($moneyFields['amount_id']) ?>_label">
                <?= esc($moneyFields['amount_label']) ?> <span class="text-danger">*</span>
            </label>
            <input type="number" step="0.01" class="form-control" name="<?= esc($moneyFields['amount_id']) ?>" id="<?= esc($moneyFields['amount_id']) ?>" required min="0" placeholder="0.00">
        </div>
    </div>
    <div class="<?= esc($moneyFields['payment_col']) ?>">
        <div class="form-group">
            <label>Método de pago <span class="text-danger">*</span></label>
            <select class="form-control" name="<?= esc($moneyFields['payment_type_id']) ?>" id="<?= esc($moneyFields['payment_type_id']) ?>" required></select>
        </div>
    </div>
    <?php if (! empty($moneyFields['after_payment'])): ?>
        <?= $moneyFields['after_payment'] ?>
    <?php endif; ?>
</div>
<input type="hidden" name="<?= esc($moneyFields['denomination_input_id']) ?>" id="<?= esc($moneyFields['denomination_input_id']) ?>">
<input type="hidden" name="<?= esc($moneyFields['rate_input_name']) ?>" id="<?= esc($moneyFields['rate_input_id']) ?>">
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Denominación detectada</label>
            <input type="text" class="form-control" id="detected_denomination" readonly>
        </div>
    </div>
    <?php if ($moneyFields['show_rate_display']): ?>
    <div class="col-md-3">
        <div class="form-group">
            <label>Tasa actual Bs/USD</label>
            <input type="text" class="form-control" id="display_rate_to_base" readonly>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-3">
        <div class="form-group">
            <label>Equivalente USD</label>
            <input type="text" class="form-control" id="<?= esc($moneyFields['usd_display_id']) ?>" readonly>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Equivalente BS</label>
            <input type="text" class="form-control" id="<?= esc($moneyFields['bs_display_id']) ?>" readonly>
        </div>
    </div>
</div>
