<style type="text/css">
	<!--
	body {
		font-size: 16px;
	}

	table {
		width: 100%;
		border-collapse: collapse;
	}

	table tr td {
		padding: 0;
	}

	table tr td:last-child {
		text-align: right;
	}

	.bold {
		font-weight: bold;
	}

	.right {
		text-align: right;
	}

	.large {
		font-size: 1.75em;
	}

	.total {
		font-weight: bold;
		color: #fb7578;
	}

	.logo-container {
		margin: 20px 0 70px 0;
	}

	.invoice-info-container {
		font-size: 0.875em;
	}

	.invoice-info-container td {
		padding: 4px 0;
	}

	.client-name {
		font-size: 1.5em;
		vertical-align: top;
	}

	.line-items-container {
		margin: 70px 0;
		font-size: 0.875em;
	}

	.line-items-container th {
		text-align: left;
		color: #999;
		border-bottom: 2px solid #ddd;
		padding: 10px 0 15px 0;
		font-size: 0.75em;
		text-transform: uppercase;
	}

	.line-items-container th:last-child {
		text-align: right;
	}

	.line-items-container td {
		padding: 15px 0;
	}

	.line-items-container tbody tr:first-child td {
		padding-top: 25px;
	}

	.line-items-container.has-bottom-border tbody tr:last-child td {
		padding-bottom: 25px;
		border-bottom: 2px solid #ddd;
	}

	.line-items-container.has-bottom-border {
		margin-bottom: 0;
	}

	.line-items-container th.heading-quantity {
		width: 50px;
	}

	.line-items-container th.heading-price {
		text-align: right;
		width: 100px;
	}

	.line-items-container th.heading-subtotal {
		width: 100px;
	}

	.payment-info {
		width: 38%;
		font-size: 0.75em;
		line-height: 1.5;
	}

	.footer {
		margin-top: 100px;
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
		margin-top: 5px;
		font-size: 0.75em;
		color: #ccc;
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
	-->

</style>
<page style="font-size: 10pt">

	<div class="logo-container">
		<img style="height: 18px" src="https://app.useanvil.com/img/email-logo-black.png">
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
				Invoice Date: <strong>
					<?php echo $order->get_date_created()->date( 'F j, Y, g:i A T' ); ?>
				</strong>
			</td>
			<td>
				San Francisco CA, 94103
			</td>
		</tr>
		<tr>
			<td>
				Invoice No: <strong>
					<?php $order->get_id(); ?>
				</strong>
			</td>
			<td>
				hello@useanvil.com
			</td>
		</tr>
	</table>


	<table class="line-items-container">
		<colgroup>
			<col style="width: 64%; text-align: left">
			<col style="width: 13%; text-align: right">
			<col style="width: 10%; text-align: center">
			<col style="width: 13%; text-align: right">
		</colgroup>
		<thead>
			<tr>
				<th class="heading-description">Namse</th>
				<th class="heading-quantity">Qty</th>
				<th class="heading-price">Subtotal</th>
				<th class="heading-subtotal">Total</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
				<tr>
					<td><?php echo $item->get_name(); ?></td>    
					<td><?php echo $item->get_quantity(); ?></td>
					<td class="right"><?php echo $item->get_subtotal(); ?></td>
					<td class="bold"><?php echo $item->get_total(); ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>


	<table class="line-items-container has-bottom-border">
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
				<td class="large total">$105.00</td>
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
