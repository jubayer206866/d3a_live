<?php
defined('BASEPATH') or exit('No direct script access allowed');

$currency = get_base_currency_pur()->symbol;
$project_id = isset($project_id) ? $project_id : 0;
$total_cbm_sum = 0;
$total_goods_money_sum = 0;

$this->db->select('gr.id, gr.goods_receipt_code, gr.supplier_name, gr.deliver_name, gr.buyer_id, gr.pr_order_id, gr.date_add, gr.total_goods_money, gr.approval, po.delivery_status as delivery_status, p.name as project_name, po.pur_order_number, cbm_sum.total_cbm');
$this->db->from(db_prefix() . 'goods_receipt gr');
$this->db->join(db_prefix() . 'projects p', 'p.id = gr.project', 'left');
$this->db->join(db_prefix() . 'pur_orders po', 'po.id = gr.pr_order_id', 'left');
$this->db->join('(
    SELECT goods_receipt_id, SUM(CAST(total_cbm AS DECIMAL(12,6))) AS total_cbm 
    FROM '.db_prefix().'goods_receipt_detail 
    GROUP BY goods_receipt_id
) cbm_sum', 'cbm_sum.goods_receipt_id = gr.id', 'left');
if ($project_id > 0) {
    $this->db->where('gr.project', $project_id);
}
$irv_items = $this->db->get()->result_array();
?>

<table id="table_manage_goods_receipt" class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Delivery Docket Code</th>
            <th>Project Name</th>
            <th>Supplier Name</th>
            <th>Purchase Order Number</th>
            <th>Total Value of Goods</th>
            <th>Volume</th>
            <th>Status</th>
            <th>Person in Charge</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($irv_items as $item) {

            $delivery_status_html = '';
            if (isset($item['delivery_status'])) {
                if ($item['delivery_status'] == 0) {
                    $delivery_status_html = '<span class="label label-warning">' . _l('waiting_to_receive') . '</span>';
                } elseif ($item['delivery_status'] == 1) {
                    $delivery_status_html = '<span class="label label-success">' . _l('received') . '</span>';
                }

                if (has_permission('purchase_orders', '', 'edit') || is_admin()) {
                    $delivery_status_html .= '<div class="dropdown inline-block mleft5">';
                    $delivery_status_html .= '<a href="#" class="dropdown-toggle text-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $delivery_status_html .= '<i class="fa fa-caret-down"></i></a>';
                    $delivery_status_html .= '<ul class="dropdown-menu dropdown-menu-right">';

                    if ($item['delivery_status'] == 0) {
                        $delivery_status_html .= '<li><a href="#" onclick="update_po_delivery_status(1, ' . $item['pr_order_id'] . '); return false;">' . _l('received') . '</a></li>';
                    }
                    if ($item['delivery_status'] == 1) {
                        $delivery_status_html .= '<li><a href="#" onclick="update_po_delivery_status(0, ' . $item['pr_order_id'] . '); return false;">' . _l('waiting_to_receive') . '</a></li>';
                    }

                    $delivery_status_html .= '</ul></div>';
                }
            }

            // Buyer
            $buyer_html = '';
            if (!empty($item['buyer_id'])) {
                $buyer_html .= '<a href="' . admin_url('staff/profile/' . $item['buyer_id']) . '">' . staff_profile_image($item['buyer_id'], ['staff-profile-image-small']) . '</a>';
                $buyer_html .= ' <a href="' . admin_url('staff/profile/' . $item['buyer_id']) . '">' . get_staff_full_name($item['buyer_id']) . '</a>';
            }

            // Docket Code
            $docket_code_html = $item['goods_receipt_code'] . '<div class="row-options">';
            $docket_code_html .= '<a href="' . admin_url('warehouse/edit_purchase/' . $item['id']) . '">' . _l('view') . '</a>';
            if ((has_permission('wh_stock_import', '', 'edit') || is_admin())) {
                $docket_code_html .= ' | <a href="' . admin_url('warehouse/manage_goods_receipt/' . $item['id']) . '">' . _l('edit') . '</a>';
            }
            if ((has_permission('wh_stock_import', '', 'delete') || is_admin()) && ($item['approval'] == 0)) {
                $docket_code_html .= ' | <a href="' . admin_url('warehouse/delete_goods_receipt/' . $item['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            if (get_warehouse_option('revert_goods_receipt_goods_delivery') == 1 && ($item['approval'] == 1) && (has_permission('wh_stock_import', '', 'delete') || is_admin())) {
                $docket_code_html .= ' | <a href="' . admin_url('warehouse/revert_goods_receipt/' . $item['id']) . '" class="text-danger _delete">' . _l('delete_after_approval') . '</a>';
            }
            if (empty($item['pur_order_number'])) {
                $docket_code_html .= ' | <a href="' . admin_url('warehouse/convert_pur_inv_from_irv/' . $item['id']) . '" class="_delete">' . _l('convert_to_pur_invoice') . '</a>';
            }
            $docket_code_html .= '</div>';

            $total_cbm_sum += $item['total_cbm'];
            $total_goods_money_sum += $item['total_goods_money'];
        ?>
            <tr>
                <td><?php echo $docket_code_html; ?></td>
                <td><?php echo $item['project_name']; ?></td>
                <td><?php echo $item['supplier_name']; ?></td>
                <td><?php echo $item['pur_order_number']; ?></td>
                <td><?php echo app_format_money($item['total_goods_money'], $currency); ?></td>
                <td><?php echo number_format($item['total_cbm'], 2) . ' m³'; ?></td>
                <td><?php echo $delivery_status_html; ?></td>
                <td><?php echo $buyer_html; ?></td>
            </tr>
        <?php } ?>

        <tr class="total-row">
            <td colspan="4" class="text-right">Total</td>
            <td><?php echo app_format_money($total_goods_money_sum, $currency); ?></td>
            <td><?php echo number_format($total_cbm_sum, 2) . ' m³'; ?></td>
            <td colspan="2"></td>
        </tr>
    </tbody>
</table>
<style>
.total-row td {
    background-color: #F4B084 !important; 
    color: #030303ff !important;
    font-weight: bold !important;
}
</style>

<script>
initDataTable('#table_manage_goods_receipt', window.location.href, [], []);
</script>
<script>
function update_po_delivery_status(new_status, po_id) {
    $.post(admin_url + 'purchase/change_po_delivery_status', {
        status: new_status,
        id: po_id
    }).done(function(response) {
        response = JSON.parse(response);
        if (response.success) {
            alert_float('success', 'Delivery status updated');

            location.reload();
        } else {
            alert_float('danger', 'Failed to update');
        }
    });
}
</script>