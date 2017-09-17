<?php

if (!defined('_PS_VERSION_'))
	exit;

class ecm_privat24 extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	function __construct()
	{
		$this->name = 'ecm_privat24';
		$this->tab = 'payments_gateways';
		$this->version = '1.9';
		$this->author = 'Elcommerce';
		$this->need_instance = 1;
		$this->bootstrap = true;
		//Привязвать к валюте
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		 $config = Configuration::getMultiple(array('p24_id', 'p24_pass'));
        if (isset($config['p24_pass']))
            $this->privat24_merchant_pass = $config['p24_pass'];
        if (isset($config['p24_id']))
            $this->privat24_merchant_id = $config['p24_id'];
		parent::__construct();

		$this->displayName = $this->l('Privat24');
		$this->description = $this->l('Payments with Privat24');
		 if (!isset($this->privat24_merchant_pass) OR !isset($this->privat24_merchant_id))
            $this->warning = $this->l('Your Privat24 account must be set correctly (specify a password and a unique id merchant');
        if (!(Currency::getIdByIsoCode('UAH'))) $this->warning = $this->l('There is currency "UAH" not presently in your shop! Creat it!');
	}

	public function install()
	{
		return (parent::install()
			&& $this->registerHook('payment')
			&& $this->registerHook('paymentReturn')
			&&$this->_addOS()
		);
	}

	public function uninstall()
	{
		return (parent::uninstall()
			&& Configuration::deleteByName('p24_id')
			&& Configuration::deleteByName('p24_pass')
			&& Configuration::deleteByName('p24_postvalidate')
		);
	}

	public function getContent()
	{
		if (Tools::isSubmit('submitp24'))
		{
			$this->postValidation();
			if (!count(@$this->post_errors))
				$this->postProcess();
			else
				foreach ($this->post_errors as $err)
					$this->_html .= $this->displayError($err);
		}
		$this->_html .= $this->renderForm();
		$this->_displayabout();
		return $this->_html;
	}

		public function renderForm()
	{
		//$root_category = Category::getRootCategory();
		//$root_category = array('id_category' => $root_category->id, 'name' => $root_category->name);
			$this->fields_form[0]['form'] = array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-cog'

			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Merchant ID'),
					'desc' => $this->l('Merchant ID in Privat24'),
					'name' => 'p24_id',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Merchant password'),
					'desc' => $this->l('Merchant password in Privat24'),
					'name' => 'p24_pass',
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Order after payment'),
					'name' => 'p24_postvalidate',
					'desc' => $this->l('Create order after receive payment notification'),
					'values' => array(
						array(
							'id' => 'p24_postvalidate_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'p24_postvalidate_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Order total with delivery cost'),
					'name' => 'p24_delivery',
					'desc' => $this->l('Send order total with delivery cost'),
					'values' => array(
						array(
							'id' => 'p24_delivery_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'p24_delivery_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				),
			),

			'submit' => array(
				'name' => 'submitp24',
				'title' => $this->l('Save')
			)
		);


		$helper = new HelperForm();
		$helper->module = $this;
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitp24';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.
			'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);
		return $helper->generateForm($this->fields_form);
	}

	public function getConfigFieldsValues()
	{
		$fields_values = array();
		$languages = Language::getLanguages(false);
		$fields_values['p24_id'] = Configuration::get('p24_id');
		$fields_values['p24_pass'] = Configuration::get('p24_pass');
		$fields_values['p24_postvalidate'] = Configuration::get('p24_postvalidate');
		$fields_values['p24_delivery'] = Configuration::get('p24_delivery');
		return $fields_values;
	}

	private function postValidation()
	{
		if (Tools::getValue('p24_id') && (!Validate::isString(Tools::getValue('p24_id'))))
			$this->post_errors[] = $this->l('Invalid').' '.$this->l('Merchant ID');
		if (Tools::getValue('p24_pass') && (!Validate::isString(Tools::getValue('p24_pass'))))
			$this->post_errors[] = $this->l('Invalid').' '.$this->l('Password');
	}

	private function postProcess()
	{
		Configuration::updateValue('p24_id', Tools::getValue('p24_id'));
		Configuration::updateValue('p24_pass', Tools::getValue('p24_pass'));
		Configuration::updateValue('p24_postvalidate', Tools::getValue('p24_postvalidate'));
		Configuration::updateValue('p24_delivery', Tools::getValue('p24_delivery'));
		$this->_html .= $this->displayConfirmation($this->l('Settings updated.'));
	}
	public function hookpayment($params)
	{
		if (!$this->active)
			return ;

		$this->smarty->assign(array(
			'id_cart' => $params['cart']->id,
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'payment.tpl');

	}


	public function hookpaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if ($state == Configuration::get('PS_OS_PAYMENT'))
		{
			$this->smarty->assign(array(
				'status' => 'success',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}

		elseif ($state == Configuration::get('PS_OS_BANKWIRE'))
		{
			$this->smarty->assign(array(
				'status' => 'wait_secure',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}

		elseif ($state == Configuration::get('PS_OS_ERROR'))
		{
			$this->smarty->assign(array(
				'status' => 'failure',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}

		else
			$this->smarty->assign('status', 'other');
		return $this->display(__FILE__, 'payment_return.tpl');
	}


	static public function validateAnsver($message)
	{
		Logger::addLog('p24: ' . $message);
		die($message);
	}

	private function _addOS()
	{
		return ($this->_addStatus('EC_OS_WAITPAYMENT', $this->l('Waiting payment'))

		);
	}
	private function _addStatus($setting_name, $name, $template=false)
	{
		if (Configuration::get($setting_name))
			return true;

		$status= new OrderState();
		$status->send_email = ($template?1:0);
		$status->invoice = 0;
		$status->logable = 0;
		$status->delivery = 0;
		$status->hidden = 0;
		$status->color = '#00c305';

		$lngs = Language::getLanguages();
		foreach ($lngs as $lng) {
			$status->name[$lng['id_lang']] =$name ;
			if($template)
				$status->template[$lng['id_lang']] =$template ;
		}
		if($status->add()){
			Configuration::updateValue($setting_name, $status->id);
			return true;
		}
		return false;
	}

	private
	function _displayabout(){
		$this->_html .= '
		<div class="panel">
		<div class="panel-heading">
			<i class="icon-envelope"></i> ' . $this->l('Информация') . '
		</div>
		<div id="dev_div">
		<span><b>' . $this->l('Версия') . ':</b> ' . $this->version . '</span><br>
		<span><b>' . $this->l('Разработчик') . ':</b> <a class="link" href="mailto:support@elcommerce.com.ua" target="_blank">УElcommerce</a>

		<span><b>' . $this->l('Описание') . ':</b> <a class="link" href="http://elcommerce.com.ua" target="_blank">http://elcommerce.com.ua</a><br><br>
		<p style="text-align:center"><a href="http://elcommerce.com.ua/"><img src="http://elcommerce.com.ua/img/m/logo.png" alt="Электронный учет коммерческой деятельности" /></a>

		</div>
		</div>
		';
	}

}
