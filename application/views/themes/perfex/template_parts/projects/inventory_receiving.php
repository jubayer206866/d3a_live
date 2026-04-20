<table class="table table-bordered table-inventory-receiving">
    <thead>
        <tr>
            <th><?= _l('stock_received_docket_code'); ?></th>
            <th><?= _l('vendor'); ?></th>
            <th><?= _l('reference_purchase_order'); ?></th>
            <th><?= _l('day_vouchers'); ?></th>
            <th><?= _l('total_goods_money'); ?></th>
            <th><?= _l('Status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($inventory_receiving)) { ?>
            <?php foreach ($inventory_receiving as $gr) { ?>
                <?php
                    $po_number = '-';
                    if (!empty($gr['pr_order_id'])) {
                        $po = $this->db->select('pur_order_number')
                                       ->where('id', $gr['pr_order_id'])
                                       ->get(db_prefix().'pur_orders')->row();
                        if ($po) {
                            $po_number = $po->pur_order_number;
                        }
                    }
                    $status = '';
                    if ($gr['approval'] == 1) {
                        $status = '<span class="label label-success">' . _l('approved') . '</span>';
                    } elseif ($gr['approval'] == 0) {
                        $status = '<span class="label label-warning">' . _l('not_yet_approve') . '</span>';
                    } elseif ($gr['approval'] == -1) {
                        $status = '<span class="label label-danger">' . _l('reject') . '</span>';
                    }

                    $docket_html  = e($gr['goods_receipt_code']);
                    $docket_html .= '<div class="row-options">';
                    $docket_html .= '<a href="' . site_url('Clients/view_irv/' . $gr['id']) . '">' . _l('view') . '</a>';
                    $docket_html .= '</div>';

                ?>
                <tr>
                    <td><?= $docket_html; ?></td>
                    <td><?= $gr['supplier_name']; ?></td>
                    <td><?= e($po_number); ?></td>
                    <td><?= _d($gr['date_add']); ?></td>
                    <td><?= app_format_money($gr['total_goods_money'], $currency); ?></td>
                    <td><?= $status; ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6" class="text-center"><?= _l('no_inventory_receiving_found'); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<style>
    /* Style for the last row in inventory receiving table */
    .table-inventory-receiving tbody tr.last-row-highlight {
        background-color: #f4b084 !important;
    }
    
    .table-inventory-receiving tbody tr.last-row-highlight td {
        background-color: #f4b084 !important;
    }
</style>
<script>
    $(document).ready(function() {
        // Style the last row for inventory receiving table
        function styleLastRow() {
            $('.table-inventory-receiving tbody tr').removeClass('last-row-highlight');
            var lastRow = $('.table-inventory-receiving tbody tr:last');
            if(lastRow.length > 0 && !lastRow.find('td').hasClass('text-center')) {
                lastRow.addClass('last-row-highlight');
            }
        }
        styleLastRow();
    });
</script>
