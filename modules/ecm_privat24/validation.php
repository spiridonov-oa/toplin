<?php

/*
amt=<сумма>
&ccy=<валюта UAH|USD|EUR>
&details=<информация о товаре/услуге>
&ext_details=<дополнительная информация о товаре/услуге>&pay_way=privat24
&order=<id платежа в системе мерчанта>
&merchant=<id мерчанта, принимающего платёж>
&state=<состояние платежа: ok|fail>
&date=<дата отправки платежа в проводку>
&ref=<id платежа в системе банка>
&sender_phone=<номер телефона плательщика>

amt=12.384
&ccy=UAH
&details=Oplata zakaza selko
&ext_details=Oplata zakaza selko
&pay_way=privat24
&order=54c3b7dd450d65
&merchant=10878
&state=test
&date=110710212135
&ref=test ok
&sender_phone=+380675494993
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/ecm_privat24.php');
$privat24      = new ecm_privat24();

$merchant_pass = Configuration::get('p24_pass');
$signature     = sha1(md5($_POST['payment'].$merchant_pass));

if($_POST['signature'] == $signature){
	$response     = $_POST['payment'];
	parse_str($response, $output);
	$errors       = '';
	$postvalidate = Configuration::get('p24_postvalidate');

	if($output['state'] == 'test' || $output['state'] == 'ok'){
		$id_currency_uah = new Currency(intval(Currency::getIdByIsoCode('UAH')));
		$rest_amount     = floatval($output['amt']);
		if($postvalidate == 1)
		{
			$id_cart = $output['order'];
			$cart    = new Cart((int)$id_cart);
			$currency_order = new Currency($cart->id_currency);
			$rest_amount = Tools::convertPriceFull($rest_amount,$id_currency_uah,$currency_order);
			if (Configuration::get('p24_delivery'))
				$amount= $rest_amount;
			else
				$amount= $rest_amount + $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
			$transaction_id = 'Privat24 Transaction ID: '.$output['ref'].' '.@$output['sender_phone'];
			$privat24->validateOrder($id_cart, _PS_OS_PAYMENT_, $amount, $privat24->displayName, $transaction_id);
			$ordernumber=Order::getOrderByCartId($cart->id);
			$order = new Order((int)$ordernumber);
		}
		else
		{
			$ordernumber = (int)$output['order'];
			$order = new Order((int)$ordernumber);
			//Проверка существования заказа
			if(!Validate::isLoadedObject($order))
			{
				ecm_privat24::validateAnsver($privat24->l('Order does not exist'));
			}
			$currency_order = new Currency($order->id_currency);
			$total_to_pay   = $order->total_products_wt - $order->total_discounts;
			$total_to_pay   = number_format($total_to_pay, 2, '.', '');
			$rest_amount    = Tools::convertPriceFull($rest_amount,$id_currency_uah,$currency_order);
			if (Configuration::get('p24_delivery'))
				$amount= $rest_amount ;
			else
				$amount= $rest_amount + $order->total_shipping;
			//Проверка суммы заказа
			if($amount != $total_to_pay)
			{
				ecm_privat24::validateAnsver($privat24->l('Incorrect payment summ'));
			}
			//Меняем статус заказа
			$history = new OrderHistory();
			$history->id_order = $ordernumber;
			$history->changeIdOrderState(_PS_OS_PAYMENT_, $ordernumber);
			$history->addWithemail(true);

		}
		$customer = new Customer((int)$order->id_customer);
		if ($order->hasBeenPaid())
			Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.$order->id_cart.
				'&id_module='.$privat24->id.'&id_order='.$order->id);
	}
	elseif($output['state'] == 'fail'){
		$privat24->validateOrder($id_cart, _PS_OS_ERROR_, 0, $privat24->displayName, $errors.'<br />');
	}
}
else
{
	Tools::redirectLink(__PS_BASE_URI__.'order.php');
}
?>
