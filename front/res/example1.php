<style type="text/css">
    body {
        font-size: 16px;
        font-family: Arial, sans-serif;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table tr td,
    table tr th {
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

    .line-items-container th {
        text-align: left;
        color: #999;
        background-color: #f9f9f9;
        border-bottom: 2px solid #ddd;
        padding: 10px;
        text-transform: uppercase;
    }

    .line-items-container {
        margin-top: 25px 0;
        font-size: 0.875em;
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

    .noBorder td {
        border: none !important;
    }
</style>

<page style="font-size: 10pt">

    <div class="logo-container" style="height: 40px;position: absolute;z-index: 99999;right: 20px;">
        <img style="height: 40px" src="https://app.useanvil.com/img/email-logo-black.png">
    </div>

    <table width="100%" class="invoice-info-container noBorder">
        <colgroup>
            <col style="width: 50%; text-align: left">
            <col style="width: 50%; text-align: right">
        </colgroup>
        <tr>
            <td class="client-name">
                <strong>Seller</strong>
            </td>
            <td rowspan="3">
            </td>
        </tr>
        <tr>
            <td>
                business_name:
                <?php echo get_post_meta($order->get_id(), 'business_name', true); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo get_option('woocommerce_store_address') . ', ' . get_option('woocommerce_store_city') . ', ' . get_option('woocommerce_store_postcode'); // Seller Address ?>
            </td>
        </tr>
        <tr>
            <td>
                Seller Country
            </td>
        </tr>
    </table>
    <table width="100%" class="invoice-info-container noBorder" style="margin-top: 15px ;">
        <colgroup>
            <col style="width: 40%; text-align: left">
            <col style="width: 25%; text-align: center">
            <col style="width: 35%; text-align: right">
        </colgroup>
        <tr>
            <td class="client-name">
                <strong>Buyer</strong>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); // Buyer Name ?>
            </td>
            <td>
                <strong>Invoice</strong>
            </td>
            <td>
                <?php echo $order->get_id(); // Invoice Number ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $order->get_billing_address_1() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_postcode(); // Buyer Billing Address ?>
            </td>
            <td>
                Invoice Date
            </td>
            <td>
                <?php echo $order->get_date_created()->date('F j, Y, g:i A T'); // Invoice Date ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $order->get_billing_country(); // Buyer Country ?>
            </td>
            <td>
                Order amount
            </td>
            <td>
                <?php echo ($order->get_total()) . ' ' . $currency; // Order Total ?>
            </td>
        </tr>
    </table>

    <table class="line-items-container">
        <colgroup>
            <col style="width: 25%; text-align: center">
            <col style="width: 45%; text-align: left">
            <col style="width: 10%; text-align: center">
            <col style="width: 10%; text-align: center">
            <col style="width: 10%; text-align: center">
        </colgroup>
        <thead>
            <tr>
                <th class="heading">Serial No</th>
                <th class="heading">Product</th>
                <th class="heading">Price</th>
                <th class="heading">Qty</th>
                <th class="heading">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->get_items() as $item_id => $item): ?>
            <?php
                    $product = $item->get_product();
                    $price = $item->get_subtotal() / $item->get_quantity();  // Get the price at the time of purchase
                    $sku = $product ? $product->get_sku() : '';
                ?>
            <tr>
                <td>
                    <?php echo $sku ?>
                </td>
                <td>
                    <?php echo $item->get_name(); ?>
                </td>
                <td class="right">
                    <?php echo $price ?>
                </td>
                <td class="right">
                    <?php echo $item->get_quantity(); ?>
                </td>
                <td class="right bold">
                    <?php echo ($item->get_total()); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="line-items-container has-bottom-border noBorder">
        <colgroup>
            <col style="width: 50%; text-align: left">
            <col style="width: 25%; text-align: center">
            <col style="width: 25%; text-align: center">
        </colgroup>
        <tbody>
            <tr>
                <td></td>
                <td class="right"><strong>Subtotal:</strong></td>
                <td class="right">
                    <?php echo format_number($order->get_subtotal()) . ' ' . $currency; ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="right"><strong>Tax:</strong></td>
                <td class="right">
                    <?php echo format_number($order->get_total_tax()) . ' ' . $currency; ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="right"><strong>Shipping:</strong></td>
                <td class="right">
                    <?php echo format_number($order->get_shipping_total()) . ' ' . $currency; ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="right"><strong>Total:</strong></td>
                <td class="right">
                    <?php echo format_number($order->get_total()) . ' ' . $currency; ?>
                </td>
            </tr>

        </tbody>
    </table>

    <table width="100%" class="invoice-info-container noBorder">
        <tr>
            <td><strong>Shipping address</strong></td>
        </tr>
        <tr>
            <td>
                <?php 
                    echo $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '<br>';
                    echo $order->get_shipping_address_1() . '<br>';
                    if ($order->get_shipping_address_2()) {
                        echo $order->get_shipping_address_2() . '<br>';
                    }
                    echo $order->get_shipping_city() . ', ' . $order->get_shipping_state() . ' ' . $order->get_shipping_postcode() . '<br>';
                    echo $order->get_shipping_country();
                ?>
            </td>
        </tr>
        <tr>
            <td><strong>Shipping method</strong>:
                <?php echo $order->get_shipping_method(); ?>
            </td>
        </tr>
        <tr>
            <td><strong>Payment method</strong>:
                <?php echo $order->get_payment_method(); ?>
            </td>
        </tr>
    </table>


</page>