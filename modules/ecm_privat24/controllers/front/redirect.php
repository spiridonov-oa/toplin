<?php
class ecm_privat24redirectModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = true;
    public $display_footer = true;
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
		global $cookie;
        if($id_cart=Tools::getValue('id_cart'))
        {
            $myCart=new Cart($id_cart);
            if(!Validate::isLoadedObject($myCart))
                $myCart=$this->context->cart;
        }else
            $myCart=$this->context->cart;
        $currency = new Currency($myCart->id_currency);
        //$amount_ = $myCart->getOrderTotal(true, 3);
        //$delivery_cost = $myCart->getTotalShippingCost();
        if (Configuration::get('p24_delivery'))
			//$amount= $amount_;
			$amount = $myCart->getOrderTotal(true, Cart::BOTH);
		else
			//$amount= $amount_-$delivery_cost;
			$amount = $myCart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
		$id_cart = $myCart->id;
        //$myCart=new Cart($id_cart);
       // d($cookie->customer_lastname);
        $fio = 'Плательщик: '.$cookie->customer_firstname.' '.$cookie->customer_lastname;
        $details = $this->module->l('Payment for cart № ').$id_cart;
        $currency_order = new Currency(intval($myCart->id_currency));
        $id_currency_uah = new Currency(intval(Currency::getIdByIsoCode('UAH')));
        if($id_currency_uah){
		$amount_uah = Tools::convertPriceFull($amount,$currency_order,$id_currency_uah);
        if ($postvalidate=Configuration::get('p24_postvalidate'))
            $order_number=$myCart->id;
        else
        {
            if(!($order_number=Order::getOrderByCartId($myCart->id)))
            {
                $this->module->validateOrder((int)$myCart->id, Configuration::get('EC_OS_WAITPAYMENT'), $amount_uah, $this->module->displayName, NULL, array(), NULL, false, $myCart->secure_key);
                $order_number=$this->module->currentOrder;
                $details = $this->module->l('Payment for order № ').$order_number;
            }
        }
        $ssl_enable = Configuration::get('PS_SSL_ENABLED');
		$base = (($ssl_enable) ? 'https://' : 'http://');
		$server_url =  $base.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/ecm_privat24/validation.php';
		$success_url =  $this->context->link->getModuleLink('ecm_privat24', 'success', array('order_id' => $order_number), true);
        $this->context->smarty->assign(array(
            'currency1'     => 'UAH',
            'order'        => $order_number.'-'.uniqid(),
            'amount'       => $amount_uah,
            'details'  	   => "$details",
            'ext_details'  => "$fio",
            'merchant'     => htmlentities(Tools::getValue('p24_id', $this->module->privat24_merchant_id), ENT_COMPAT, 'UTF-8'),
            'return_url'   => $success_url,
            'server_url'   => $server_url
        ));

        $this->setTemplate('redirect.tpl');
	}
    }
}
