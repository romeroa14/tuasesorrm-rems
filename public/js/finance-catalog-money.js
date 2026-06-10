(function(window, $) {
    'use strict';

    function formatMoney(value) {
        if (!isFinite(value)) {
            return '';
        }

        return value.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function FinanceCatalogMoney(options) {
        this.catalogUrl = options.catalogUrl;
        this.catalog = null;
        this.paymentTypeSelect = options.paymentTypeSelect || '#payment_type_id';
        this.amountInput = options.amountInput || '#amount';
        this.denominationInput = options.denominationInput || '#currency_denomination';
        this.rateInput = options.rateInput || '#exchange_rate';
        this.rateDisplayInput = options.rateDisplayInput || '#display_rate_to_base';
        this.detectedInput = options.detectedInput || '#detected_denomination';
        this.usdDisplayInput = options.usdDisplayInput || '#amount_usd_display';
        this.bsDisplayInput = options.bsDisplayInput || '#amount_bs_display';
        this.amountLabel = options.amountLabel || null;
        this.useHiddenRate = options.useHiddenRate !== false;
    }

    FinanceCatalogMoney.prototype.getContext = function() {
        return this.catalog && this.catalog.currency_context ? this.catalog.currency_context : {};
    };

    FinanceCatalogMoney.prototype.getPaymentType = function(id) {
        var match = null;
        (this.catalog && this.catalog.payment_types ? this.catalog.payment_types : []).forEach(function(paymentType) {
            if (String(paymentType.id) === String(id)) {
                match = paymentType;
            }
        });

        return match;
    };

    FinanceCatalogMoney.prototype.loadCatalog = function(callback) {
        var self = this;

        $.get(this.catalogUrl, function(response) {
            if (response.status === 'success') {
                self.catalog = response.data;
                self.populatePaymentTypes();
            }

            if (callback) {
                callback(response);
            }
        }).fail(function() {
            if (callback) {
                callback(null);
            }
        });
    };

    FinanceCatalogMoney.prototype.populatePaymentTypes = function(selectedId) {
        var select = $(this.paymentTypeSelect);
        if (!select.length) {
            return;
        }

        var options = '<option value="">Seleccionar...</option>';
        (this.catalog && this.catalog.payment_types ? this.catalog.payment_types : []).forEach(function(paymentType) {
            options += '<option value="' + paymentType.id + '">' + (paymentType.name || paymentType.code || '—') + '</option>';
        });

        select.html(options);

        if (selectedId) {
            select.val(String(selectedId));
        }
    };

    FinanceCatalogMoney.prototype.formatPrimaryAmount = function(amount, denomination) {
        var prefix = denomination === 'BS' ? 'Bs. ' : '$ ';

        return prefix + formatMoney(parseFloat(amount || 0));
    };

    FinanceCatalogMoney.prototype.refresh = function() {
        var context = this.getContext();
        var paymentType = this.getPaymentType($(this.paymentTypeSelect).val());
        var denomination = paymentType && paymentType.default_denomination ? paymentType.default_denomination : 'USD';
        var latestRate = parseFloat(context.latest_bs_rate || 0);
        var currentRate = parseFloat($(this.rateInput).val() || 0);
        var rate = denomination === 'BS' ? (currentRate > 0 ? currentRate : latestRate) : 1;
        var amount = parseFloat($(this.amountInput).val() || 0);

        $(this.denominationInput).val(denomination);
        $(this.detectedInput).val(denomination);

        if (this.amountLabel) {
            $(this.amountLabel).html(
                (denomination === 'BS' ? 'Monto en Bs.' : 'Monto en USD') + ' <span class="text-danger">*</span>'
            );
        }

        if (denomination === 'BS') {
            $(this.rateInput).val(rate > 0 ? rate.toFixed(6) : '');
        } else {
            $(this.rateInput).val('1.000000');
        }

        if (this.rateDisplayInput) {
            $(this.rateDisplayInput).val(denomination === 'BS' && rate > 0 ? rate.toFixed(4) : (denomination === 'USD' ? '1.0000' : 'Sin tasa'));
        }

        if (!isFinite(amount) || amount <= 0) {
            $(this.usdDisplayInput).val('');
            $(this.bsDisplayInput).val('');
            return;
        }

        var amountUsd = denomination === 'BS' ? (rate > 0 ? amount / rate : 0) : amount;
        var amountBs = denomination === 'BS' ? amount : amount * rate;

        $(this.usdDisplayInput).val(formatMoney(amountUsd));
        $(this.bsDisplayInput).val(formatMoney(amountBs));
    };

    FinanceCatalogMoney.prototype.bind = function() {
        var self = this;
        $(this.paymentTypeSelect + ', ' + this.amountInput + ', ' + this.rateInput).on('change keyup', function() {
            self.refresh();
        });
    };

    window.FinanceCatalogMoney = FinanceCatalogMoney;
    window.formatFinanceMoney = formatMoney;
})(window, jQuery);
