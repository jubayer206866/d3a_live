<?php defined('BASEPATH') or exit('No direct script access allowed');
$html = <<<EOF
<div class="div_pdf">
$pur_invoice
</div>
EOF;

$pdf->writeHTML($html, true, false, true, false, '');
