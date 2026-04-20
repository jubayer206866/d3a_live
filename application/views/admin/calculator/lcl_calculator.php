<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.lcl-calc-wrap { max-width: 1000px; margin: 0 auto; }
.lcl-calc-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e4e8eb; }
.lcl-calc-header-left { flex: 1; }
.lcl-calc-header-logo { flex: 0 0 auto; display: flex; justify-content: center; }
.lcl-calc-header-right { flex: 1; }
.lcl-calc-nav { margin: 0; }
.lcl-calc-nav .lcl-calc-title { font-size: 1.5rem; font-weight: 700; margin: 0; color: #2c3e50; }
.lcl-calc-billable-note { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; display: block; }
.lcl-calc-header-logo img { max-height: 40px; max-width: 140px; object-fit: contain; }
.lcl-calc-grid { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start; }
@media (max-width: 768px) { .lcl-calc-grid { grid-template-columns: 1fr; } }
.lcl-col-inputs .form-group { margin-bottom: 1rem; }
.lcl-col-inputs label { font-weight: 600; color: #374151; margin-bottom: 0.35rem; display: block; }
.lcl-col-inputs .form-control { border-radius: 6px; border: 1px solid #d1d5db; }
.lcl-calc-buttons { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
.lcl-btn-calc { background: #2563eb; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
.lcl-btn-calc:hover { background: #1d4ed8; }
.lcl-btn-reset { background: #6b7280; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 500; cursor: pointer; }
.lcl-btn-reset:hover { background: #4b5563; }
.lcl-col-details { background: #f8fafc; border-radius: 8px; padding: 1.25rem; }
.lcl-details-title { font-weight: 700; font-size: 0.9rem; color: #374151; margin-bottom: 1rem; }
.lcl-detail-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
.lcl-detail-row:last-child { border-bottom: none; }
.lcl-detail-label { color: #6b7280; }
.lcl-detail-value { font-weight: 600; color: #111; }
.lcl-final-wrap { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 2px solid #e5e7eb; text-align: center; }
.lcl-final-label { font-size: 1.5rem; font-weight: 700; color: #1e40af; margin-bottom: 0.25rem; }
.lcl-final-price { font-size: 1.75rem; font-weight: 800; color: #1e3a8a; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <div class="lcl-calc-wrap">
                    <?php $lcl_logo = get_admin_header_logo_url(); ?>
                    <div class="lcl-calc-header">
                        <div class="lcl-calc-header-left">
                            <div class="lcl-calc-nav">
                                <h4 class="lcl-calc-title active"><?= _l('lcl_nav_lcl'); ?></h4>
                                <span class="lcl-calc-billable-note"><?= _l('ddp_billable_note'); ?> (1 CBM = <?= (int)(isset($settings['kg_per_cbm']) ? $settings['kg_per_cbm'] : 500); ?> KG)</span>
                            </div>
                        </div>
                        <div class="lcl-calc-header-logo">
                            <?php if (!empty($lcl_logo)): ?>
                                <a href="<?= admin_url(); ?>"><img src="<?= htmlspecialchars($lcl_logo); ?>" alt="<?= e(get_option('companyname')); ?>"></a>
                            <?php else: ?>
                                <span class="tw-text-lg tw-font-semibold"><?= e(get_option('companyname')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="lcl-calc-header-right"></div>
                    </div>

                    <div class="lcl-calc-grid">
                        <!-- Left: Inputs -->
                        <div class="lcl-col-inputs">
                            <div class="form-group">
                                <label for="lcl-cbm"><?= _l('ddp_actual_cbm'); ?></label>
                                <input type="number" id="lcl-cbm" class="form-control" step="0.01" min="0" placeholder="">
                            </div>
                            <div class="form-group">
                                <label for="lcl-weight"><?= _l('ddp_weight_kg'); ?></label>
                                <input type="number" id="lcl-weight" class="form-control" step="0.01" min="0" placeholder="">
                            </div>
                            <div class="form-group">
                                <label for="lcl-crates"><?= _l('lcl_wooden_crates'); ?></label>
                                <input type="number" id="lcl-crates" class="form-control" step="1" min="0" placeholder="">
                            </div>
                            <div class="form-group">
                                <label for="lcl-sea-freight"><?= _l('lcl_sea_freight_eur'); ?></label>
                                <input type="number" id="lcl-sea-freight" class="form-control" step="0.01" min="0" placeholder="">
                            </div>
                            <div class="lcl-calc-buttons">
                                <button type="button" id="lcl-btn-calculate" class="lcl-btn-calc"><?= _l('lcl_calculate'); ?></button>
                                <button type="button" id="lcl-btn-reset" class="lcl-btn-reset"><?= _l('lcl_reset'); ?></button>
                            </div>
                        </div>

                        <!-- Right: Calculation details + final price -->
                        <div class="lcl-col-details">
                            <div class="lcl-details-title"><?= _l('lcl_calculation_breakdown'); ?></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_chargeable_cbm'); ?></span><span class="lcl-detail-value" id="out-chargeable-cbm">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_variable_cost'); ?></span><span class="lcl-detail-value" id="out-variable-cost">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_crate_fee'); ?></span><span class="lcl-detail-value" id="out-crate-fee">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_fixed_service_fee'); ?></span><span class="lcl-detail-value" id="out-fixed-fee">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_pre_discount_total'); ?></span><span class="lcl-detail-value" id="out-pre-discount">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_discount_applied'); ?></span><span class="lcl-detail-value" id="out-discount-applied">—</span></div>
                            <div class="lcl-detail-row"><span class="lcl-detail-label"><?= _l('lcl_discount_amount'); ?></span><span class="lcl-detail-value" id="out-discount-amount">—</span></div>
                            <div class="lcl-final-wrap">
                                <div class="lcl-final-label"><?= _l('lcl_final_price'); ?></div>
                                <div class="lcl-final-price" id="out-final-price">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function() {
    var settings = <?= json_encode(isset($settings) ? $settings : []); ?>;

    function num(x) { return typeof x === 'number' && !isNaN(x) ? x : parseFloat(x) || 0; }
    function fmtEur(x) { return num(x).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }
    function ceilTo10(x) { return Math.ceil(num(x) / 10) * 10; }

    // Discount rate by chargeable CBM: ≤5 → 0%, 5–10 → 5%, 10–15 → 10%, ≥15 → 13%
    function getDiscountRate(cbmChg) {
        if (cbmChg <= 5) return 0;
        if (cbmChg <= 10) return 0.05;
        if (cbmChg < 15) return 0.10;
        return 0.13;
    }

    function runLcl() {
        var V = num(document.getElementById('lcl-cbm').value);
        var W = num(document.getElementById('lcl-weight').value);
        var C = num(document.getElementById('lcl-crates').value);
        var SF = num(document.getElementById('lcl-sea-freight').value);

        var weightConversion = num(settings.kg_per_cbm) || 500;
        var fixedOperational = num(settings.fixed_operational_cost) || 6000;
        var containerCapacity = num(settings.container_capacity) || 68;
        var fixedServiceFee = num(settings.fixed_service_fee) || 300;
        var crateFeePerUnit = num(settings.crate_fee_per_unit) || 50;

        // 2. Chargeable CBM
        var cbmChg = Math.max(V, W / weightConversion);

        // 3. Cost pool rate per CBM
        var rate = containerCapacity > 0 ? (fixedOperational + SF) / containerCapacity : 0;

        // 4 & 5. Variable cost (raw then rounded UP to nearest 10)
        var varRaw = cbmChg * rate;
        var varCost = ceilTo10(varRaw);

        // 6. Crate fee
        var crateFee = crateFeePerUnit * C;

        // 7. Fixed service fee
        var fixedFee = fixedServiceFee;

        // 8. Pre-discount total
        var totalPre = varCost + crateFee + fixedFee;

        // 9 & 10. Discount rate and amount
        var discountRate = getDiscountRate(cbmChg);
        var discountAmount = totalPre * discountRate;
        var discountLabel = (discountRate * 100) + '%';
        if (discountRate > 0) {
            if (cbmChg <= 10) discountLabel += ' (5-10 CBM)';
            else if (cbmChg < 15) discountLabel += ' (10-15 CBM)';
            else discountLabel += ' (≥15 CBM)';
        }

        // 11. Total after discount (raw)
        var totalRaw = totalPre - discountAmount;

        // 12. Final payable (rounded UP to nearest 10)
        var totalFinal = ceilTo10(totalRaw);

        document.getElementById('out-chargeable-cbm').textContent = cbmChg > 0 ? cbmChg.toFixed(2) : '—';
        document.getElementById('out-variable-cost').textContent = varCost > 0 ? fmtEur(varCost) : '—';
        document.getElementById('out-crate-fee').textContent = fmtEur(crateFee);
        document.getElementById('out-fixed-fee').textContent = fmtEur(fixedFee);
        document.getElementById('out-pre-discount').textContent = fmtEur(totalPre);
        document.getElementById('out-discount-applied').textContent = discountRate > 0 ? discountLabel : '—';
        document.getElementById('out-discount-amount').textContent = discountAmount > 0 ? '-' + fmtEur(discountAmount) : '—';
        document.getElementById('out-final-price').textContent = totalFinal >= 0 ? fmtEur(totalFinal) : '—';
    }

    document.getElementById('lcl-btn-calculate').addEventListener('click', runLcl);
    document.getElementById('lcl-btn-reset').addEventListener('click', function() {
        document.getElementById('lcl-cbm').value = '';
        document.getElementById('lcl-weight').value = '';
        document.getElementById('lcl-crates').value = '';
        document.getElementById('lcl-sea-freight').value = '';
        runLcl();
    });

    runLcl();
})();
</script>
</body>
</html>
