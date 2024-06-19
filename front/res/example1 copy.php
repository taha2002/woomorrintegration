<style type="text/css">
    body {
        font-size: 16px;
        font-family: Arial, sans-serif;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table tr td, table tr th {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .bold {
        font-weight: bold;
    }

    .right {
        text-align: right;
    }

    .large {
        font-size: 1.5em;
    }

    .total {
        font-weight: bold;
        color: #fb7578;
    }

    .logo-container {
        margin: 20px 0 50px 0;
    }

    .invoice-info-container {
        font-size: 0.875em;
    }

    .client-name {
        font-size: 1.5em;
        vertical-align: top;
    }

    .line-items-container {
        margin: 50px 0;
        font-size: 0.875em;
    }

    .line-items-container th {
        text-align: left;
        color: #999;
        background-color: #f9f9f9;
        border-bottom: 2px solid #ddd;
        padding: 10px;
        text-transform: uppercase;
    }

    .line-items-container td {
        padding: 15px;
    }

    .line-items-container tbody tr:first-child td {
        padding-top: 25px;
    }

    .line-items-container.has-bottom-border tbody tr:last-child td {
        padding-bottom: 25px;
        border-bottom: 2px solid #ddd;
    }

    .payment-info {
        width: 40%;
        font-size: 0.875em;
        line-height: 1.5;
    }

    .footer {
        margin-top: 50px;
    }

    .footer-thanks {
        font-size: 1.125em;
    }

    .footer-thanks img {
        display: inline-block;
        position: relative;
        top: 1px;
        width: 16px;
        margin-right: 4px;
    }

    .footer-info {
        float: right;
        font-size: 0.75em;
        color: #666;
    }

    .footer-info span {
        padding: 0 5px;
        color: black;
    }

    .footer-info span:last-child {
        padding-right: 0;
    }

    .page-container {
        display: none;
    }
</style>

<page style="font-size: 10pt">

    <div class="logo-container">
        <img style="height: 40px" src="https://app.useanvil.com/img/email-logo-black.png">
    </div>

    <table align="center" width="100%" class="invoice-info-container">
        <tr>
            <td rowspan="2" class="client-name">
                Client Name
            </td>
            <td>
                Anvil Co
            </td>
        </tr>
        <tr>
            <td>
                123 Main Street
            </td>
        </tr>
        <tr>
            <td>
                Invoice Date: <strong><?php echo $order->get_date_created()->date('F j, Y, g:i A T'); ?></strong>
            </td>
            <td>
                San Francisco CA, 94103
            </td>
        </tr>
        <tr>
            <td>
                Invoice No: <strong><?php echo $order->get_id(); ?></strong>
            </td>
            <td>
                hello@useanvil.com
            </td>
        </tr>
    </table>

    <table class="line-items-container">
        <thead>
            <tr>
                <th class="heading-description">Name</th>
                <th class="heading-quantity">Qty</th>
                <th class="heading-price">Subtotal</th>
                <th class="heading-subtotal">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->get_items() as $item_id => $item): ?>
                <tr>
                    <td><?php echo $item->get_name(); ?></td>    
                    <td class="right"><?php echo $item->get_quantity(); ?></td>
                    <td class="right"><?php echo ($item->get_subtotal()); ?></td>
                    <td class="right bold"><?php echo ($item->get_total()); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="line-items-container has-bottom-border">
        <tbody>
            <tr>
                <td class="right"><strong>Subtotal:</strong></td>
                <td class="right"><?php echo ($order->get_subtotal()); ?></td>
            </tr>
            <tr>
                <td class="right"><strong>Tax:</strong></td>
                <td class="right"><?php echo ($order->get_total_tax()); ?></td>
            </tr>
            <tr>
                <td class="right"><strong>Shipping:</strong></td>
                <td class="right"><?php echo ($order->get_shipping_total()); ?></td>
            </tr>
            <tr>
                <td class="right large total"><strong>Total Due:</strong></td>
                <td class="right large total"><?php echo ($order->get_total()); ?></td>
            </tr>
        </tbody>
    </table>

    <table class="line-items-container">
        <thead>
            <tr>
                <th>Payment Info</th>
                <th>Due By</th>
                <th>Total Due</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="payment-info">
                    <div>
                        Account No: <strong>123567744</strong>
                    </div>
                    <div>
                        Routing No: <strong>120000547</strong>
                    </div>
                </td>
                <td class="large">May 30th, 2024</td>
                <td class="large total"><?php echo ($order->get_total()); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-info">
            <span>hello@useanvil.com</span> |
            <span>555 444 6666</span> |
            <span>useanvil.com</span>
        </div>
        <div class="footer-thanks">
            <img src="https://github.com/anvilco/html-pdf-invoice-template/raw/main/img/heart.png" alt="heart">
            <span>Thank you!</span>
        </div>
    </div>
</page>
