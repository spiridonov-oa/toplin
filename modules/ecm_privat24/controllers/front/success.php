<?php

class ecm_privat24successModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	public function initContent()
	{
		parent::initContent();

		$ordernumber = Tools::getValue('order_id');
		$this->context->smarty->assign('ordernumber', $ordernumber);
		$postvalidate = Configuration::get('p24_postvalidate');
		if ($postvalidate == 1)
		{
			if (!$ordernumber)
				ecm_privat24::validateAnsver($this->module->l('Cart number is not set').$ordernumber);

			$cart = new Cart((int)$ordernumber);
			if (!Validate::isLoadedObject($cart))
				ecm_privat24::validateAnsver($this->module->l('Cart does not exist'));

			if (!($ordernumber = Order::getOrderByCartId($cart->id)))
			{
				$this->setTemplate('waitingPayment.tpl');
				return;
			}
		}

		if (!$ordernumber)
			ecm_privat24::validateAnsver($this->module->l('Order number is not set').$ordernumber);

		$order = new Order((int)$ordernumber);
		if (!Validate::isLoadedObject($order))
			ecm_privat24::validateAnsver($this->module->l('Order does not exist').$ordernumber);

		$customer = new Customer((int)$order->id_customer);

		if ($customer->id != $this->context->cookie->id_customer)
			ecm_liqpay::validateAnsver($this->module->l('You are not logged in'));

		if ($order->hasBeenPaid())
			Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.$order->id_cart.
				'&id_module='.$this->module->id.'&id_order='.$order->id);
		else
			$this->setTemplate('waitingPayment.tpl');
	}
}
