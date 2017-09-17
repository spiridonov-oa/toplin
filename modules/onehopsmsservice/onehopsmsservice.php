<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class Onehopsmsservice extends Module
{
    protected $html = '';
    protected $postErrors = array();
    protected $postsmsErrors = array();
    protected $postsmsRulesetsErrors = array();
    protected $postsmsTemplateErrors = array();
    protected $postSendSMSTempError = array();
    protected $options = array();
    
    public $smsAPI;
    public $adminMobile;
    public $outStock;
    public $extra_mail_vars;
    public $getactiveTab;
    public $btnurlLink;
    
    private static $msg = array(
    'SHIPTXT_1' => 'Send shipment details to buyers after a purchase. ',
    'SHIPTXT_2' => 'SMS will be sent when you update the Order status to Shipped.',
    'OUT_TEXT' => 'Send alerts whenever a product is Out of Stock. ',
    'BACK_TEXT' => 'Send Alerts whenever a product is Back in Stock. ',
    'MAIL_TEXT' => 'Ensure you have Mail Alerts plugin installed for this feature to work.'
    );
    
    /**
     * Onehop Constructor.
     */
    public function __construct()
    {
        $this->preload();
        $this->name          = 'onehopsmsservice';
        $this->tab           = 'emailing';
        $this->module_key    = 'adf36fff96a6cad8dd5537e0d90bc005';
        $this->version       = '1.1.4';
        $this->author        = 'Screen-Magic Mobile Media Inc.';
        $this->need_instance = 0;
        
        $config = Configuration::getMultiple(array(
            'ONEHOP_SEND_SMS_API',
            'MA_LAST_QTIES',
            'ONEHOP_ADMIN_MOBILE'
        ));
        if (!empty($config['ONEHOP_SEND_SMS_API'])) {
            $this->smsAPI = $config['ONEHOP_SEND_SMS_API'];
        }
        if (!empty($config['MA_LAST_QTIES'])) {
            $this->outStock = $config['MA_LAST_QTIES'];
        }
        if (!empty($config['ONEHOP_ADMIN_MOBILE'])) {
            $this->adminMobile = $config['ONEHOP_ADMIN_MOBILE'];
        }
        
        $this->bootstrap = true;
        parent::__construct();
        
        $this->displayName = $this->l('Onehop SMS Services');
        $this->description = $this->l('Easily Send SMSes on Prestashop. Search, Compare and Buy the best SMS products. 
Switch providers with one click using Labels.');
        
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        if ($this->smsAPI == null || $this->smsAPI == '') {
            $this->warning = $this->l('Please select template for order confirmation.');
        }
    }
 
    /**
     * Automatically called after plugin installation. Register hooks and create tables in database.
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install()
        || !$this->registerHook('displayBackOfficeHeader')
        || !$this->installModuleTab('onehopsmsservice', array(
            1 => 'Onehop SMS Services'
        ), 0)
        || !$this->installDB()
        || !$this->registerHook('orderConfirmation')
        || !$this->registerHook('postUpdateOrderStatus')
        || !$this->registerHook('actionUpdateQuantity')
        || !$this->registerHook('actionObjectProductUpdateAfter')) {
            return false;
        }
        return true;
    }
    
    /**
     * Automatically called after plugin uninstallation. Remove tables and other entries from database.
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!Configuration::deleteByName('ONEHOP_SEND_SMS_API')
        || !Configuration::deleteByName('ONEHOP_ADMIN_MOBILE')
        || !$this->uninstallModuleTab('onehopsmsservice')
        || !$this->uninstallDB()
        || !parent::uninstall()) :
            return false;
        endif;
        return true;
    }
    
    /**
     * Create plugin tab. Called from install function.
     *
     * @param string $tabClass
     * @param string $tabName
     * @param array $idTabParent
     * @return bool
     */
    private function installModuleTab($tabClass, $tabName, $idTabParent)
    {
        @copy(_PS_MODULE_DIR_ . $this->name . '/logo.png', _PS_IMG_DIR_ . 't/' . $tabClass . '.png');
        if (_PS_VERSION_ >= 1.7 || (_PS_VERSION_ < 1.6 && _PS_VERSION_ >= 1.5)) {
            $partab             = new Tab();
            $partab->name       = $tabName;
            $partab->class_name = $tabClass;
            $partab->module     = $this->name;
            $partab->id_parent  = $idTabParent;
            if ($partab->save()) {
                $tab             = new Tab();
                $tab->name       = $tabName;
                $tab->class_name = $tabClass;
                $tab->id_parent  = $partab->id;
                $tab->module     = $this->name;
                $tab->add();
                return true;
            }
            return false;
        } elseif (_PS_VERSION_ >= 1.6 && _PS_VERSION_ < 1.7) {
            $partab             = new Tab();
            $partab->name       = $tabName;
            $partab->class_name = $tabClass;
            $partab->module     = $this->name;
            $partab->id_parent  = $idTabParent;
            if ($partab->save()) {
                return true;
            }
            return false;
        }
    }
    
    /**
     * Delete plugin tab. Called from uninstall function.
     *
     * @param string $tabClass
     * @return bool
     */
    private function uninstallModuleTab($tabClass)
    {
        $sql = 'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE class_name = "' . pSQL($tabClass) . '"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                $idTab = $row['id_tab'];
                if ($idTab != 0) {
                    $tab = new Tab((int)$idTab);
                    $tab->delete();
                    @unlink(_PS_IMG_DIR . "t/" . $tabClass . ".png");
                }
            }
        }
        return true;
    }
    
    /**
     * Create database tables. Called from install function.
     */
    public function installDB()
    {
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'onehop_sms_rulesets` (
              `ruleid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `rule_name` varchar(200) NOT NULL,
              `template` varchar(100) NOT NULL,
              `label` varchar(100) NOT NULL,
              `senderid` varchar(100) NOT NULL,
              `active` enum("1","0") NOT NULL DEFAULT "1"                  
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');
        
        return Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'onehop_sms_templates` (
              `temp_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `temp_name` varchar(200) NOT NULL,
              `temp_body` text NOT NULL,
              `submitdate` datetime NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');
    }
    
    /**
     * Drop database tables. Called from uninstall function.
     */
    public function uninstallDB()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'onehop_sms_rulesets`;');
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'onehop_sms_templates`;');
    }
    
    /**
     * Button click event on Configuration page
     */
    protected function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('SEND_SMS_API')) {
                $this->postErrors[] = $this->l('API Key is required.');
            }
            if (Tools::getValue('SEND_SMS_API')) {
                $isapiKey = $this->isValidAPIKey(Tools::getValue('SEND_SMS_API'));
                if ($isapiKey->status != 'success') {
                    $this->postErrors[] = $this->l('API Key is not valid.');
                }
            }
            if (!Tools::getValue('ADMIN_MOBILE')) {
                $this->postErrors[] = $this->l('Admin Mobile Number is required.');
            }
            if (Tools::getValue('ADMIN_MOBILE')) {
                $isvalid = preg_match('/^[0-9]*$/', Tools::getValue('ADMIN_MOBILE'));
                if ($isvalid == false) {
                    $this->postErrors[] = $this->l('Admin Mobile Number is not valid.');
                }
            }
        }
    }
    
    /**
     * Button click event on Automation page
     */
    protected function postSMSRulesetsValidation()
    {
        if (Tools::isSubmit('orderConfirmBtn')) {
            if (!Tools::getValue('ORDER_SMS_TEMPLATE')) {
                $this->postsmsRulesetsErrors[] = $this->l('Please select template for order confirmation.');
            } elseif (!Tools::getValue('ORDER_SMS_LABEL')) {
                $this->postsmsRulesetsErrors[] = $this->l('Label is required for order confirmation.');
            } elseif (!Tools::getValue('ORDER_SENDER_ID')) {
                $this->postsmsRulesetsErrors[] = $this->l('Sender id is required for order confirmation.');
            }
        }
        if (Tools::isSubmit('shipmentConfirmBtn')) {
            if (!Tools::getValue('SHIPMENT_SMS_TEMPLATE')) {
                $this->postsmsRulesetsErrors[] = $this->l('Please select template for shipment confirmation.');
            } elseif (!Tools::getValue('SHIPMENT_SMS_LABEL')) {
                $this->postsmsRulesetsErrors[] = $this->l('Label is required for shipment confirmation.');
            } elseif (!Tools::getValue('SHIPMENT_SENDER_ID')) {
                $this->postsmsRulesetsErrors[] = $this->l('Sender id is required for shipment confirmation.');
            }
        }
        if (Tools::isSubmit('onDeliveryBtn')) {
            if (!Tools::getValue('ON_DELIVERY_SMS_TEMPLATE')) {
                $this->postsmsRulesetsErrors[] = $this->l('Please select template for On Delivery Followups.');
            } elseif (!Tools::getValue('ON_DELIVERY_SMS_LABEL')) {
                $this->postsmsRulesetsErrors[] = $this->l('Label is required for On Delivery Followups.');
            } elseif (!Tools::getValue('ON_DELIVERY_SENDER_ID')) {
                $this->postsmsRulesetsErrors[] = $this->l('Sender id is required for On Delivery Followups.');
            }
        }
        if (Tools::isSubmit('OutStockBtn')) {
            if (!Tools::getValue('OUTSTOCK_SMS_TEMPLATE')) {
                $this->postsmsRulesetsErrors[] = $this->l('Please select template for out of stock alerts.');
            } elseif (!Tools::getValue('OUTSTOCK_SMS_LABEL')) {
                $this->postsmsRulesetsErrors[] = $this->l('Label is required for out of stock alerts.');
            } elseif (!Tools::getValue('OUTSTOCK_SENDER_ID')) {
                $this->postsmsRulesetsErrors[] = $this->l('Sender id is required for out of stock alerts.');
            }
        }
        if (Tools::isSubmit('BackStockBtn')) {
            if (!Tools::getValue('BACKSTOCK_SMS_TEMPLATE')) {
                $this->postsmsRulesetsErrors[] = $this->l('Please select template for back of stock alerts.');
            } elseif (!Tools::getValue('BACKSTOCK_SMS_LABEL')) {
                $this->postsmsRulesetsErrors[] = $this->l('Label is required for back of stock alerts.');
            } elseif (!Tools::getValue('BACKSTOCK_SENDER_ID')) {
                $this->postsmsRulesetsErrors[] = $this->l('Sender id is required for back of stock alerts.');
            }
        }
    }
    
    /**
     * Button click event on Send SMS page
     *
     * @return array
     */
    protected function postSendSMSTempValidation()
    {
        if (Tools::isSubmit('sendSingleSMS')) {
            $mobile = Tools::getValue('SEND_SINGLE_SMS_MOBILE');
            
            if (!Tools::getValue('SEND_SINGLE_SMS_MOBILE')) :
                $this->postSendSMSTempError[] = $this->l('Mobile number is required.');
            elseif (preg_match('/[^0-9]/', $mobile)) :
                $this->postSendSMSTempError[] = $this->l('Please add valid mobile number.');
            elseif (!Tools::getValue('SEND_SINGLE_SENDER_ID')) :
                $this->postSendSMSTempError[] = $this->l('Sender Id is required.');
            elseif (!Tools::getValue('SEND_SINGLE_SMS_LABEL')) :
                $this->postSendSMSTempError[] = $this->l('Select Label is required.');
            elseif (!Tools::getValue('SEND_SIGNLE_SMS_BODY')) :
                $this->postSendSMSTempError[] = $this->l('Message body is required.');
            elseif ($this->smsAPI == '' || $this->smsAPI == null) :
                $this->postSendSMSTempError[] = $this->l('Please configure user API details.');
            endif;
        }
        return $this->postSendSMSTempError;
    }
    
    /**
     * Button click event on Template page
     *
     * @return array
     */
    protected function postSMSTemplateValidation()
    {
        if (Tools::isSubmit('saveTemplate') || Tools::isSubmit('editTemplate')) {
            if (!Tools::getValue('TEMPLATE_NAME')) :
                $this->postsmsTemplateErrors[] = $this->l('Temaplate name is required.');
            elseif (!Tools::getValue('TEMPLATE_BODY')) :
                $this->postsmsTemplateErrors[] = $this->l('Temaplate body is required.');
            endif;
        }
        return $this->postsmsTemplateErrors;
    }
    
    /**
     * Configuration page process if validation is success
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('ONEHOP_SEND_SMS_API', Tools::getValue('SEND_SMS_API'));
            Configuration::updateValue('ONEHOP_ADMIN_MOBILE', Tools::getValue('ADMIN_MOBILE'));
        }
        $token        = Tools::getAdminTokenLite('AdminModules');
        $request_scheme = $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $hostlink     = $request_scheme . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $mainPara     = "controller=AdminModules&configure=onehopsmsservice";
        $reDirectLink = $hostlink . "?" . $mainPara . "&token=" . $token . "&smswelcome=yes";
        Tools::redirect($reDirectLink);
    }
    
    /**
     * Redirect to Template page
     */
    protected function redirectTemplate()
    {
        $token        = Tools::getAdminTokenLite('AdminModules');
        $request_scheme = $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $hostlink     = $request_scheme . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $mainPara     = "controller=AdminModules&configure=onehopsmsservice";
        $reDirectLink = $hostlink . "?" . $mainPara . "&token=" . $token . "&smstemplates=yes";
        Tools::redirect($reDirectLink);
    }
    
    /**
     * Register CSS files and show breadcrumbs on each page
     *
     * @return bool
     */
    protected function displayCssfile()
    {
        
        if (Tools::getValue('smswelcome') != '' && Tools::getValue('smswelcome') != null) {
            $getbreadcrumbs = 'Welcome';
        } elseif (Tools::getValue('configuration') != '' && Tools::getValue('configuration') != null) {
            $getbreadcrumbs = 'Configuration';
        } elseif (Tools::getValue('smsrulesets') != '' && Tools::getValue('smsrulesets') != null) {
            $getbreadcrumbs = 'SMS Automation';
        } elseif (Tools::getValue('smscampaign') != '' && Tools::getValue('smscampaign') != null) {
            $getbreadcrumbs = 'Send SMS';
        } elseif (Tools::getValue('smstemplates') != '' && Tools::getValue('smstemplates') != null) {
            $getbreadcrumbs = 'Manage Templates';
        } else {
            $getbreadcrumbs = 'Welcome';
        }
        
        $this->context->smarty->assign('breadcrumbs', $getbreadcrumbs);
        if (_PS_VERSION_ > 1.5) {
            return $this->display(__FILE__, 'mymodule_page.tpl');
        } else {
            return $this->display(dirname(__FILE__), '/views/templates/front/mymodule_page.tpl');
        }
    }
    
    /**
     * Open template page.
     *
     * @return bool
     */
    protected function displaySMSTemplates()
    {
        if (_PS_VERSION_ > 1.5) {
            return $this->display(__FILE__, 'template_list.tpl');
        } else {
            return $this->display(dirname(__FILE__), '/views/templates/front/template_list.tpl');
        }
    }
    
    /**
     * Open send sms page. Assign default value to controls (as blank) to prevent debug notices.
     *
     * @return bool
     */
    public function displaySendSingleSMS()
    {
        if (Tools::getValue('SEND_SINGLE_SMS_MOBILE') == null) {
            $this->context->smarty->assign('smsMobileNo', '');
        }
        if (Tools::getValue('SEND_SINGLE_SENDER_ID') == null) {
            $this->context->smarty->assign('smsSenderid', '');
        }
        if (Tools::getValue('SEND_SINGLE_SMS_LABEL') == null) {
            $this->context->smarty->assign('smsLabel', '');
        }
        if (Tools::getValue('SEND_SINGLE_SMS_TEMPLATE') == null) {
            $this->context->smarty->assign('smsTemplate', '');
        }
        if (Tools::getValue('SEND_SIGNLE_SMS_BODY') == null) {
            $this->context->smarty->assign('templateBody', '');
        }
        if (_PS_VERSION_ > 1.5) {
            return $this->display(__FILE__, 'send_single_sms.tpl');
        } else {
            return $this->display(dirname(__FILE__), '/views/templates/front/send_single_sms.tpl');
        }
    }

    /**
     * Active menu tab, Retrieve required values from database and Process button click events on all pages.
     *
     * @return string
     */
    public function getContent()
    {
        $this->html .= $this->displayCssfile();
        
        if (Tools::getValue('smswelcome') != null && Tools::getValue('smswelcome') != '') {
            $this->getactiveTab = 'smswelcome';
        } elseif (Tools::getValue('configuration') != null && Tools::getValue('configuration') != '') {
            $this->getactiveTab = 'configuration';
        } elseif (Tools::getValue('smsrulesets') != null && Tools::getValue('smsrulesets') != '') {
            $this->getactiveTab = 'smsrulesets';
        } elseif (Tools::getValue('smscampaign') != null && Tools::getValue('smscampaign') != '') {
            $this->getactiveTab = 'smscampaign';
        } elseif (Tools::getValue('smstemplates') != null && Tools::getValue('smstemplates') != '') {
            $this->getactiveTab = 'smstemplates';
        } else {
            $this->getactiveTab = 'smswelcome';
        }
        
        $token            = Tools::getAdminTokenLite('AdminModules');
        $WelVar = "controller=AdminModules&configure=onehopsmsservice";
        $this->btnurlLink = $_SERVER['PHP_SELF'] . "?" . $WelVar . "&token=" . $token;
        
        $this->context->smarty->assign('SMSMenuLink', $this->btnurlLink);
        $this->context->smarty->assign('SMSGetTab', $this->getactiveTab);
        $this->context->smarty->assign('SMSIsAPIKey', $this->smsAPI);
        
        if (_PS_VERSION_ > 1.5) {
            $this->html .= $this->display(__FILE__, 'menu_tabs.tpl');
        } else {
            $this->html .= $this->display(dirname(__FILE__), '/views/templates/front/menu_tabs.tpl');
        }
        
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) :
                    $this->html .= $this->displayError($err);
                endforeach;
            }
        }
        if ((Tools::getValue('smswelcome') != null && Tools::getValue('smswelcome') != '')
        || (Tools::getValue('smswelcome') == null
        && Tools::getValue('configuration') == null
        && Tools::getValue('smscampaign') == null
        && Tools::getValue('smsrulesets') == null
        && Tools::getValue('smstemplates') == null)) {
            $this->html .= $this->screenMagicDetails();
        }
        
        if (Tools::getValue('smscampaign') != null && Tools::getValue('smscampaign') != '') {
            $this->context->smarty->assign('SMSTemplateslist', array());
            $this->context->smarty->assign('SMSLabellist', array());
            
            $allSMSTemplates = $this->getAllSMSTemplates();
            $allSMSLabel     = $this->getSMSLabels();
            if ($allSMSTemplates) :
                $this->context->smarty->assign('SMSTemplateslist', $allSMSTemplates);
            endif;
            if ($allSMSLabel) :
                $this->context->smarty->assign('SMSLabellist', $allSMSLabel['labellist']);
            endif;
            if (Tools::getValue('MessageBody')) {
                $temp_ID = Tools::getValue('tempId');
                if ($temp_ID) {
                    $SelTemplateBody = 'SELECT temp_body FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
                    $SelTemplateBody .= ' WHERE md5(temp_id) = "' . pSQL($temp_ID) . '"';
                    if ($ViewTemplatesBody = Db::getInstance()->ExecuteS($SelTemplateBody)) {
                        echo Tools::jsonEncode($ViewTemplatesBody);
                        exit;
                    }
                }
            }
            if (Tools::isSubmit('sendSingleSMS')) {
                $ErrorMsg = $this->postSendSMSTempValidation();
                $this->context->smarty->assign('smsMobileNo', Tools::getValue('SEND_SINGLE_SMS_MOBILE'));
                $this->context->smarty->assign('smsSenderid', Tools::getValue('SEND_SINGLE_SENDER_ID'));
                $this->context->smarty->assign('smsLabel', Tools::getValue('SEND_SINGLE_SMS_LABEL'));
                $this->context->smarty->assign('smsTemplate', Tools::getValue('SEND_SINGLE_SMS_TEMPLATE'));
                $this->context->smarty->assign('templateBody', Tools::getValue('SEND_SIGNLE_SMS_BODY'));
                
                if (!count($this->postSendSMSTempError)) {
                    //use send SMS API to send SMS from here
                    $body = Tools::getValue('SEND_SIGNLE_SMS_BODY');
                    $body = preg_replace('/<br(\s+)?\/?>/i', "\n", $body);
                    $postdata        = array(
                        'label' => Tools::getValue('SEND_SINGLE_SMS_LABEL'),
                        'sms_text' => $body,
                        'source' => '21000',
                        'sender_id' => Tools::getValue('SEND_SINGLE_SENDER_ID'),
                        'mobile_number' => Tools::getValue('SEND_SINGLE_SMS_MOBILE')
                    );
                    $isSendSMS = $this->sendSMSByAPI($postdata);
                    if ($isSendSMS && isset($isSendSMS->status) && $isSendSMS->status == 'submitted') {
                        Onehopsmsservice::onehopSaveLog('SendSMS', $body, Tools::getValue('SEND_SINGLE_SMS_MOBILE'));
                        $this->context->smarty->assign('smsMobileNo', '');
                        $this->context->smarty->assign('smsSenderid', '');
                        $this->context->smarty->assign('smsTemplate', '');
                        $this->context->smarty->assign('smsLabel', '');
                        $this->context->smarty->assign('templateBody', '');
                        $this->context->smarty->assign('SuccessMsg', 'SMS sent successfully.');
                    } else {
                        $this->context->smarty->assign('ErrorMsg', 'Error while sending the SMS.');
                    }
                } else {
                    $this->context->smarty->assign('ErrorMsg', $ErrorMsg[0]);
                }
            }
            $this->html .= $this->displaySendSingleSMS();
        }
        
        if (Tools::getValue('smsrulesets') != '' && Tools::getValue('smsrulesets') != null) {
            if (Tools::isSubmit('orderConfirmBtn')
            || Tools::isSubmit('shipmentConfirmBtn')
            || Tools::isSubmit('onDeliveryBtn')
            || Tools::isSubmit('OutStockBtn')
            || Tools::isSubmit('BackStockBtn')) {
                $this->postSMSRulesetsValidation();
                if (!count($this->postsmsRulesetsErrors)) {
                    if (Tools::isSubmit('orderConfirmBtn')) {
                        $activateFeature = Tools::getValue('ORDER_FEATURE_1');
                        if ($activateFeature != '' && $activateFeature != null) :
                            $activeRuleset = '1';
                        else :
                            $activeRuleset = '0';
                        endif;
                        $sql = 'SELECT * FROM '._DB_PREFIX_.'onehop_sms_rulesets WHERE rule_name="order_confirmation"';
                        if (Db::getInstance()->ExecuteS($sql)) {
                            $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_rulesets SET';
                            $updateVars = ' active="' . (int)$activeRuleset . '",';
                            $updateVars .= ' template="' . pSQL(Tools::getValue('ORDER_SMS_TEMPLATE')) . '",';
                            $updateVars .= ' label="' . pSQL(Tools::getValue('ORDER_SMS_LABEL')) . '",';
                            $updateVars .= ' senderid = "' . pSQL(Tools::getValue('ORDER_SENDER_ID')) . '"';
                            $updateQuery .= $updateVars;
                            $updateQuery .= ' WHERE rule_name="order_confirmation"';
                            Db::getInstance()->execute($updateQuery);
                        } else {
                            $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                            $insertQuery .= ' (rule_name,template,label,senderid,active)';
                            $insertVars = '"order_confirmation",';
                            $insertVars .= ' "' . pSQL(Tools::getValue('ORDER_SMS_TEMPLATE')) . '",';
                            $insertVars .= ' "' . pSQL(Tools::getValue('ORDER_SMS_LABEL')) . '",';
                            $insertVars .= ' "' . pSQL(Tools::getValue('ORDER_SENDER_ID')) . '",';
                            $insertVars .= ' "' . (int)$activeRuleset . '"';
                            
                            $insertQuery .= ' VALUES (' . $insertVars . ')';
                            Db::getInstance()->execute($insertQuery);
                        }
                        $confMsg = $this->l('Rule set saved successfully for order confirmation.');
                        $this->html .= $this->displayConfirmation($confMsg);
                    }
                    if (Tools::isSubmit('shipmentConfirmBtn')) {
                        $activateFeature = Tools::getValue('SHIPMENT_FEATURE_1');
                        if ($activateFeature != '' && $activateFeature != null) :
                            $activeRuleset = '1';
                        else :
                            $activeRuleset = '0';
                        endif;
                        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                        $sql .= ' WHERE rule_name = "shipment_confirmation"';
                        if (Db::getInstance()->ExecuteS($sql)) {
                            $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_rulesets SET';
                            $updateVars = ' active="' . (int)$activeRuleset . '",';
                            $updateVars .= ' template="' . pSQL(Tools::getValue('SHIPMENT_SMS_TEMPLATE')) . '",';
                            $updateVars .= ' label="' . pSQL(Tools::getValue('SHIPMENT_SMS_LABEL')) . '",';
                            $updateVars .= ' senderid = "' . pSQL(Tools::getValue('SHIPMENT_SENDER_ID')) . '"';
                            $updateQuery .= $updateVars;
                            $updateQuery .= ' WHERE rule_name="shipment_confirmation"';
                            Db::getInstance()->execute($updateQuery);
                        } else {
                            $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                            $insertQuery .= ' (rule_name,template,label,senderid,active)';
                            $insertvars = '"shipment_confirmation",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('SHIPMENT_SMS_TEMPLATE')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('SHIPMENT_SMS_LABEL')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('SHIPMENT_SENDER_ID')) . '",';
                            $insertvars .= ' "' . (int)$activeRuleset . '"';
                            
                            $insertQuery .= ' VALUES (' . $insertvars . ')';
                            Db::getInstance()->execute($insertQuery);
                        }
                        $shipconfmsg = $this->l('Rule set saved successfully for shipment confirmation.');
                        $this->html .= $this->displayConfirmation($shipconfmsg);
                    }
                    if (Tools::isSubmit('onDeliveryBtn')) {
                        $activateFeature = Tools::getValue('ON_DELIVERY_FEATURE_1');
                        if ($activateFeature != '' && $activateFeature != null) :
                            $activeRuleset = '1';
                        else :
                            $activeRuleset = '0';
                        endif;
                        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                        $sql .= ' WHERE rule_name = "on_delivery_confirmation"';
                        if (Db::getInstance()->ExecuteS($sql)) {
                            $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_rulesets SET';
                            $updatevars = ' active="' . (int)$activeRuleset . '",';
                            $updatevars .= ' template="' . pSQL(Tools::getValue('ON_DELIVERY_SMS_TEMPLATE')) . '",';
                            $updatevars .= ' label="' . pSQL(Tools::getValue('ON_DELIVERY_SMS_LABEL')) . '",';
                            $updatevars .= ' senderid = "' . pSQL(Tools::getValue('ON_DELIVERY_SENDER_ID')) . '"';
                            $updateQuery .= $updatevars;
                            $updateQuery .= ' WHERE rule_name="on_delivery_confirmation"';
                            Db::getInstance()->execute($updateQuery);
                        } else {
                            $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                            $insertQuery .= ' (rule_name,template,label,senderid,active)';
                            $insertvars = '"on_delivery_confirmation",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('ON_DELIVERY_SMS_TEMPLATE')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('ON_DELIVERY_SMS_LABEL')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('ON_DELIVERY_SENDER_ID')) . '",';
                            $insertvars .= ' "' . (int)$activeRuleset . '"';
                            $insertQuery .= ' VALUES (' . $insertvars . ')';
                            
                            Db::getInstance()->execute($insertQuery);
                        }
                        $deliveryconfmsg = $this->l('Rule set saved successfully for Delivery Confirmation.');
                        $this->html .= $this->displayConfirmation($deliveryconfmsg);
                    }
                    if (Tools::isSubmit('OutStockBtn')) {
                        $activateFeature = Tools::getValue('OUTSTOCK_FEATURE_1');
                        if ($activateFeature != '' && $activateFeature != null) :
                            $activeRuleset = '1';
                        else :
                            $activeRuleset = '0';
                        endif;
                        $sql = 'SELECT * FROM '._DB_PREFIX_.'onehop_sms_rulesets WHERE rule_name="out_of_stock_alerts"';
                        if (Db::getInstance()->ExecuteS($sql)) {
                            $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_rulesets SET';
                            $updateQuery .= ' active="' . (int)$activeRuleset . '",';
                            $updateQuery .= ' template="' . pSQL(Tools::getValue('OUTSTOCK_SMS_TEMPLATE')) . '",';
                            $updateQuery .= ' label="' . pSQL(Tools::getValue('OUTSTOCK_SMS_LABEL')) . '",';
                            $updateQuery .= ' senderid = "' . pSQL(Tools::getValue('OUTSTOCK_SENDER_ID')) . '"';
                            $updateQuery .= ' WHERE rule_name="out_of_stock_alerts"';
                            Db::getInstance()->execute($updateQuery);
                        } else {
                            $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                            $insertQuery .= ' (rule_name,template,label,senderid,active)';
                            $insertvars = '"out_of_stock_alerts",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('OUTSTOCK_SMS_TEMPLATE')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('OUTSTOCK_SMS_LABEL')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('OUTSTOCK_SENDER_ID')) . '",';
                            $insertvars .= ' "' . (int)$activeRuleset . '"';
                            $insertQuery .= ' VALUES (' . $insertvars . ')';
                            Db::getInstance()->execute($insertQuery);
                        }
                        $outStockmsg = $this->l('Rule set saved successfully for Out of Stock Alerts.');
                        $this->html .= $this->displayConfirmation($outStockmsg);
                    }
                    if (Tools::isSubmit('BackStockBtn')) {
                        $activateFeature = Tools::getValue('BACKSTOCK_FEATURE_1');
                        if ($activateFeature != '' && $activateFeature != null) :
                            $activeRuleset = '1';
                        else :
                            $activeRuleset = '0';
                        endif;
                        $sql='SELECT * FROM '._DB_PREFIX_.'onehop_sms_rulesets WHERE rule_name="back_of_stock_alerts"';
                        if (Db::getInstance()->ExecuteS($sql)) {
                            $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_rulesets SET';
                            $updateQuery .= ' active="' . (int)$activeRuleset . '",';
                            $updateQuery .= ' template="' . pSQL(Tools::getValue('BACKSTOCK_SMS_TEMPLATE')) . '",';
                            $updateQuery .= ' label="' . pSQL(Tools::getValue('BACKSTOCK_SMS_LABEL')) . '",';
                            $updateQuery .= ' senderid = "' . pSQL(Tools::getValue('BACKSTOCK_SENDER_ID')) . '"';
                            $updateQuery .= ' WHERE rule_name="back_of_stock_alerts"';
                            Db::getInstance()->execute($updateQuery);
                        } else {
                            $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                            $insertQuery .= ' (rule_name,template,label,senderid,active)';
                            $insertvars = '"back_of_stock_alerts",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('BACKSTOCK_SMS_TEMPLATE')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('BACKSTOCK_SMS_LABEL')) . '",';
                            $insertvars .= ' "' . pSQL(Tools::getValue('BACKSTOCK_SENDER_ID')) . '",';
                            $insertvars .= ' "' . (int)$activeRuleset . '"';
                            $insertQuery .= ' VALUES (' . $insertvars . ')';
                            Db::getInstance()->execute($insertQuery);
                        }
                        $backStockmsg = $this->l('Rule set saved successfully for Back in Stock Alerts.');
                        $this->html .= $this->displayConfirmation($backStockmsg);
                    }
                } else {
                    foreach ($this->postsmsRulesetsErrors as $err) :
                        $this->html .= $this->displayError($err);
                    endforeach;
                }
            }
            $getAllRulesetTemplates = $this->getAllSMSTemplates();
            $Template               = array();
            if ($getAllRulesetTemplates) {
                $Template[] = array(
                    'id_option' => '',
                    'name' => 'Select template'
                );
                foreach ($getAllRulesetTemplates as $tempVal) {
                    $Template[] = array(
                        'id_option' => $tempVal['temp_id'],
                        'name' => $tempVal['temp_name']
                    );
                }
            } else {
                $Template[] = array(
                    'id_option' => '',
                    'name' => 'No template available'
                );
            }
            $this->html .= $this->orderConfirmationRuleset($Template);
            $this->html .= $this->shipmentConfirmationRuleset($Template);
            $this->html .= $this->onDeliveryConfirmationRuleset($Template);
            $this->html .= $this->outStockAlertsRuleset($Template);
            $this->html .= $this->backStockAlertsRuleset($Template);
        }
        if (Tools::getValue('smstemplates') != '' && Tools::getValue('smstemplates') != null) {
            if (Tools::getValue('TemplateType') || Tools::getValue('TemplateType')) {
                $templateType        = Tools::getValue('Temptype');
                $templatePlaceholder = array();
                switch ($templateType) {
                    case 'customer':
                        $templatePlaceholder = array(
                            'First name',
                            'Last name',
                            'Email',
                            'Mobile'
                        );
                        break;
                    case 'order':
                        $templatePlaceholder = array(
                            'Order ID',
                            'Transaction ID',
                            'Tracking Id',
                            'Invoice',
                            'Price',
                            'Discount',
                            'Shipping_Address'
                        );
                        break;
                    case 'product':
                        $templatePlaceholder = array(
                            'Product Id',
                            'Product name'
                        );
                        break;
                }
                if ($templatePlaceholder) {
                    foreach ($templatePlaceholder as $val) {
                        $this->options[] = array('name'=>$val,'value'=>Tools::strtoupper(str_replace(' ', '', $val)));
                    }
                }
                echo Tools::jsonEncode($this->options);
                exit;
            }
            if (Tools::isSubmit('addTemplateBtn') || Tools::isSubmit('saveTemplate')) {
                $this->context->smarty->assign('isAddTemp', 'addTemplate');
                if (Tools::getValue('TEMPLATE_NAME') == null) {
                    $this->context->smarty->assign('templateName', '');
                } else {
                    $this->context->smarty->assign('templateName', Tools::getValue('TEMPLATE_NAME'));
                }
                if (Tools::getValue('TEMPLATE_BODY') == null) {
                    $this->context->smarty->assign('templateBody', '');
                } else {
                    $this->context->smarty->assign('templateBody', Tools::getValue('TEMPLATE_BODY'));
                }
                $this->context->smarty->assign('templateID', 0);
            }
            if (Tools::isSubmit('editTemplateBtn') || Tools::isSubmit('editTemplate')) {
                $this->context->smarty->assign('isAddTemp', 'editTemplate');
            }
            
            if (Tools::isSubmit('saveTemplate')) {
                $ErrorMsg = $this->postSMSTemplateValidation();
                if (!count($this->postsmsTemplateErrors)) {
                    $insertQuery = 'INSERT INTO ' . _DB_PREFIX_ . 'onehop_sms_templates';
                    $insertQuery .= ' (temp_name,temp_body,submitdate)';
                    $insertvars = '"' . pSQL(Tools::getValue('TEMPLATE_NAME')) . '",';
                    $insertvars .= ' "' . pSQL(Tools::getValue('TEMPLATE_BODY')) . '",';
                    $insertvars .= ' Now()';
                    $insertQuery .= ' VALUES (' . $insertvars . ')';
                    $saveTemplate = Db::getInstance()->execute($insertQuery);
                    if ($saveTemplate) :
                        $this->context->smarty->assign('isAddTemp', '');
                        $this->redirectTemplate();
                    endif;
                } else {
                    $this->context->smarty->assign('ErrorMsg', $ErrorMsg[0]);
                }
            }
            
            if (Tools::isSubmit('editTemplateBtn')) {
                if (Tools::getValue('editTemplateBtn')) {
                    $SelTemplate = 'SELECT temp_id, temp_name, temp_body';
                    $SelTemplate .= ' FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
                    $SelTemplate .= ' WHERE md5(temp_id) = "' . pSQL(Tools::getValue('editTemplateBtn')) . '"';
                    if ($ViewTemplates = Db::getInstance()->ExecuteS($SelTemplate)) {
                        $this->context->smarty->assign('templateName', $ViewTemplates[0]['temp_name']);
                        $this->context->smarty->assign('templateBody', $ViewTemplates[0]['temp_body']);
                        $this->context->smarty->assign('templateID', (int)$ViewTemplates[0]['temp_id']);
                    } else {
                        $this->context->smarty->assign('isAddTemp', '');
                    }
                }
            }
            if (Tools::isSubmit('editTemplate')) {
                $ErrorMsg = $this->postSMSTemplateValidation();
                
                $this->context->smarty->assign('templateName', Tools::getValue('TEMPLATE_NAME'));
                $this->context->smarty->assign('templateBody', Tools::getValue('TEMPLATE_BODY'));
                $this->context->smarty->assign('templateID', Tools::getValue('TEMPLATE_ID'));
                
                if (!count($this->postsmsTemplateErrors)) {
                    $updateQuery = 'UPDATE ' . _DB_PREFIX_ . 'onehop_sms_templates SET';
                    $updateQuery .= ' temp_name = "' . pSQL(Tools::getValue('TEMPLATE_NAME')) . '",';
                    $updateQuery .= ' temp_body = "' . pSQL(Tools::getValue('TEMPLATE_BODY')) . '",';
                    $updateQuery .= ' submitdate = Now()';
                    $updateQuery .= ' WHERE md5(temp_id) = "' . pSQL(Tools::getValue('TEMPLATE_ID')) . '"';
                    $updateTemplate = Db::getInstance()->execute($updateQuery);
                    if ($updateTemplate) :
                        $this->context->smarty->assign('isAddTemp', '');
                        $this->redirectTemplate();
                    endif;
                } else {
                    $this->context->smarty->assign('ErrorMsg', $ErrorMsg[0]);
                }
            }
            
            if (Tools::getValue('deleteTemplate')) {
                $tempID = Tools::getValue('tempID');
                
                $SelTemplate = 'SELECT ruleid FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
                $SelTemplate .= ' WHERE md5(template) = "' . pSQL($tempID) . '"';
                if (Db::getInstance()->ExecuteS($SelTemplate)) {
                    $jsonRes = array(
                        'Error' => 'You can not delete this template because its already set in SMS automation.'
                    );
                } else {
                    $deleteQuery = 'DELETE FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
                    $deleteQuery .= ' WHERE md5(temp_id) = "' . pSQL($tempID) . '"';
                    $deleteTemplate = Db::getInstance()->execute($deleteQuery);
                    if ($deleteTemplate) :
                        $jsonRes = array(
                            'Success' => '1'
                        );
                    else :
                        $jsonRes = array(
                            'Error' => 'Unknown error occurred. Please try again.'
                        );
                    endif;
                }
                
                echo Tools::jsonEncode($jsonRes);
                exit;
            }
            
            $allTemplatesList = $this->getAllSMSTemplates();
            if ($allTemplatesList) :
                $this->context->smarty->assign('Templateslist', $allTemplatesList);
            endif;
            $this->html .= $this->displaySMSTemplates();
        }
        if (Tools::getValue('configuration') != '' && Tools::getValue('configuration') != null) {
            $this->html .= $this->renderForm();
        }
        
        return $this->html;
    }
    
    /**
     * Hook function to include module CSS in page head tag.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . '/css/onehopsmsservice.css', 'all');
        $ctrl = $this->context->controller;
        if ($ctrl instanceof AdminController && method_exists($ctrl, 'addCss')) {
            $ctrl->addCss($this->_path.'views/css/onehopsmsservice-back-office.css');
        }
    }
    
    /**
     * Hook called automatically when order placed by user.
     *
     * @param order object $params
     */
    public function hookorderConfirmation($params)
    {
        // For order confirmation
        $isActivate = $this->isRulesetActive('order_confirmation');
        if ($isActivate != null && $isActivate != '' && $this->smsAPI != null && $this->smsAPI != '') {
            $param            = $params['objOrder'];
            $order = new Order((int)$param->id);
            
            if ($order) {
                $address = new Address((int)$order->id_address_delivery);
                $legendstemp = $this->replaceOrderLegends(
                    $isActivate[0]['template'],
                    $order,
                    $address
                );
                // Use send SMS API here
                $legendstemp = preg_replace('/<br(\s+)?\/?>/i', "\n", $legendstemp);
                $postAPIdata = array(
                    'label' => $isActivate[0]['label'],
                    'sms_text' => $legendstemp,
                    'source' => '21000',
                    'sender_id' => $isActivate[0]['senderid'],
                    'mobile_number' => $address->phone_mobile
                );
                $this->sendSMSByAPI($postAPIdata);
                Onehopsmsservice::onehopSaveLog('Confirmed', $legendstemp, $address->phone_mobile);
                $productArray = $order->getProducts();
                if ($productArray) {
                    foreach ($productArray as $product) {
                        $this->productOutofOrderProcess($product['product_id']);
                    }
                }
            }
        }
    }
    /**
     * Hook called automatically when order's status updated from Backend.
     *
     * @param order object $params
     */
    public function hookpostUpdateOrderStatus($params)
    {
        $orderid  = $params['id_order'];
        $order = new Order((int)$orderid);
        $statusid = ($order)? $order->current_state : '';
        if ($statusid == Configuration::get('PS_OS_SHIPPING') || $statusid == Configuration::get('PS_OS_DELIVERED')) {
            // For Shipment confirmation
            if ($statusid == Configuration::get('PS_OS_SHIPPING')) {
                $isActivate = $this->isRulesetActive('shipment_confirmation');
            }
            // For On Delivery confirmation
            if ($statusid == Configuration::get('PS_OS_DELIVERED')) {
                $isActivate = $this->isRulesetActive('on_delivery_confirmation');
            }
            if ($isActivate != null && $isActivate != '' && $this->smsAPI != null && $this->smsAPI != '') {
                $address = new Address((int)$order->id_address_delivery);
                if ($order) {
                    $address = new Address((int)$order->id_address_delivery);
                    $legendstemp = $this->replaceOrderLegends(
                        $isActivate[0]['template'],
                        $order,
                        $address
                    );
                    // Use send SMS API here
                    $legendstemp = preg_replace('/<br(\s+)?\/?>/i', "\n", $legendstemp);
                    $postAPIdata = array(
                        'label' => $isActivate[0]['label'],
                        'sms_text' => $legendstemp,
                        'source' => '21000',
                        'sender_id' => $isActivate[0]['senderid'],
                        'mobile_number' => $address->phone_mobile
                    );
                    $this->sendSMSByAPI($postAPIdata);
                    Onehopsmsservice::onehopSaveLog($statusid, $legendstemp, $address->phone_mobile);
                }
            }
        }
    }
    /**
     * Process out of order after successful order.
     *
     * @param string $id_product
     */
    public function productOutofOrderProcess($id_product)
    {
        $product  = new Product((int)$id_product, true);
        if (empty($product)) {
             return;
        }
        
        $productInfo      = array(
            'product_name' => $product->name[1],
            'product_id' => $id_product
        );
        
        if (Module::isInstalled('mailalerts')) {
            // For out of stock notification to admin
            $isActivate = $this->isRulesetActive('out_of_stock_alerts');
            if ($isActivate != null
            && $isActivate != ''
            && $this->smsAPI != null
            && $this->smsAPI != ''
            && $product->quantity <= $this->outStock) {
                $legendstemp = $this->replaceProductLegends(
                    $isActivate[0]['template'],
                    $productInfo
                );
                // Use send SMS API here
                $legendstemp = preg_replace('/<br(\s+)?\/?>/i', "\n", $legendstemp);
                $postAPIdata = array(
                    'label' => $isActivate[0]['label'],
                    'sms_text' => $legendstemp,
                    'source' => '21000',
                    'sender_id' => $isActivate[0]['senderid'],
                    'mobile_number' => $this->adminMobile
                );
                $this->sendSMSByAPI($postAPIdata);
                Onehopsmsservice::onehopSaveLog('OutStock', $legendstemp, $this->adminMobile);
            }
        }
    }
     /**
     * Hook called automatically when product quantity updated.
     *
     * @param product object $params
     */
    public function hookactionUpdateQuantity($params)
    {
        $id_product       = (int)$params['id_product'];
        $id_product_attribute = (int)$params['id_product_attribute'];
        
        $context = Context::getContext();
        $id_shop = (int)$context->shop->id;
        $id_lang = (int)$context->language->id;
        $product = new Product($id_product, false, $id_lang, $id_shop, $context);
        
        if (empty($product)) {
             return;
        }
        
        $product_name = Product::getProductName($id_product, $id_product_attribute, $id_lang);
        $product_has_attributes = $product->hasAttributes();
        $productInfo      = array(
            'product_name' => $product_name,
            'product_id' => $id_product
        );
        $check_oos = ($product_has_attributes && $id_product_attribute)
                    || (!$product_has_attributes && !$id_product_attribute);
        $product_quantity = (int)$params['quantity'];
        if (Module::isInstalled('mailalerts')) {
            // For out of stock notification to admin
            $isActivate = $this->isRulesetActive('out_of_stock_alerts');
            if ($check_oos
            && $product->active == 1
            && $isActivate != null
            && $isActivate != ''
            && $this->smsAPI != null
            && $this->smsAPI != ''
            && $product_quantity <= $this->outStock) {
                $legendstemp = $this->replaceProductLegends(
                    $isActivate[0]['template'],
                    $productInfo
                );
                // Use send SMS API here
                $legendstemp = preg_replace('/<br(\s+)?\/?>/i', "\n", $legendstemp);
                $postAPIdata = array(
                    'label' => $isActivate[0]['label'],
                    'sms_text' => $legendstemp,
                    'source' => '21000',
                    'sender_id' => $isActivate[0]['senderid'],
                    'mobile_number' => $this->adminMobile
                );
                
                $this->sendSMSByAPI($postAPIdata);
                Onehopsmsservice::onehopSaveLog('OutStock', $legendstemp, $this->adminMobile);
            }
            
            // For back in stock
            $isActivate = $this->isRulesetActive('back_of_stock_alerts');
            if ($isActivate != null
            && $isActivate != ''
            && $this->smsAPI != null
            && $this->smsAPI != ''
            && $product_quantity > 0) {
                $bosSql  = 'SELECT DISTINCT adr.phone_mobile, oos.id_customer';
                $bosSql .= ' FROM '._DB_PREFIX_. 'mailalert_customer_oos as oos';
                $bosSql .= ' INNER JOIN '._DB_PREFIX_. 'address as adr ON oos.id_customer = adr.id_customer';
                $bosSql .= ' WHERE oos.id_product = "' . (int)$id_product . '" AND deleted = 0';
                $bosResults = Db::getInstance()->ExecuteS($bosSql);
                
                if ($bosResults) {
                    foreach ($bosResults as $customerVal) {
                        $customerInfo      = array(
                            'id_customer' => $customerVal['id_customer'],
                            'phone_mobile' => $customerVal['phone_mobile']
                        );
                        $legendstemp  = $this->replaceProductCustomerLegends(
                            $isActivate[0]['template'],
                            $customerInfo,
                            $productInfo
                        );
                        $legendstemp = preg_replace('/<br(\s+)?\/?>/i', "\n", $legendstemp);
                        // Use send SMS API here
                        $postAPIdata  = array(
                            'label' => $isActivate[0]['label'],
                            'sms_text' => $legendstemp,
                            'source' => '21000',
                            'sender_id' => $isActivate[0]['senderid'],
                            'mobile_number' => $customerVal['phone_mobile']
                        );
                        $this->sendSMSByAPI($postAPIdata);
                        $delQuery = 'DELETE FROM ' . _DB_PREFIX_ . 'mailalert_customer_oos';
                        $delQuery .= ' WHERE id_customer = "' . (int)$customerVal['id_customer'] . '"';
                        Db::getInstance()->execute($delQuery);
                        Onehopsmsservice::onehopSaveLog('BackStock', $legendstemp, $customerVal['phone_mobile']);
                    }
                }
            }
        }
    }
    
    /**
     * Open welcome page.
     *
     * @return bool
     */
    public function screenMagicDetails()
    {
        $this->context->smarty->assign('imagepath', $this->_path . 'views/img');
        if (_PS_VERSION_ > 1.5) {
            return $this->display(__FILE__, 'screen_magic_details.tpl');
        } else {
            return $this->display(dirname(__FILE__), '/views/templates/front/screen_magic_details.tpl');
        }
    }
    
    /**
     * Create automation page - Order Confirmation section using Prestashop generate form method.
     *
     * @param array $getAllTemplates
     */
    public function orderConfirmationRuleset($getAllTemplates)
    {
        $SMSLabels     = $this->getSMSLabels();
        $orderCheckbox = array(
            array(
                'id_option' => '1',
                'name' => ''
            )
        );
        $fields_form   = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Order Confirmation')
                ),
                'input' => array(
                    array(
                        'label' => $this->l('Send notifications to your buyers whenever an order is confirmed.')
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Activate Feature'),
                        'name' => 'ORDER_FEATURE',
                        'values' => array(
                            'query' => $orderCheckbox,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select SMS Template'),
                        'name' => 'ORDER_SMS_TEMPLATE',
                        'options' => array(
                            'query' => $getAllTemplates,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select Label'),
                        'name' => 'ORDER_SMS_LABEL',
                        'options' => array(
                            'query' => $SMSLabels['labellist'],
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sender Id'),
                        'name' => 'ORDER_SENDER_ID',
                        'class' => 'automation_text',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'orderConfirmBtn';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name . '&smsrulesets=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getOrderFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Create automation page - Shipment Confirmation section using Prestashop generate form method.
     *
     * @param array $getAllTemplates
     */
    public function shipmentConfirmationRuleset($getAllTemplates)
    {
        $SMSLabels     = $this->getSMSLabels();
        $orderCheckbox = array(
            array(
                'id_option' => '1',
                'name' => ''
            )
        );
        $fields_form   = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Shipment Confirmation')
                ),
                'input' => array(
                    array(
                        'label' => $this->l(Onehopsmsservice::$msg['SHIPTXT_1'] . Onehopsmsservice::$msg['SHIPTXT_2'])
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Activate Feature'),
                        'name' => 'SHIPMENT_FEATURE',
                        'values' => array(
                            'query' => $orderCheckbox,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select SMS Template'),
                        'name' => 'SHIPMENT_SMS_TEMPLATE',
                        'options' => array(
                            'query' => $getAllTemplates,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select Label'),
                        'name' => 'SHIPMENT_SMS_LABEL',
                        'options' => array(
                            'query' => $SMSLabels['labellist'],
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sender Id'),
                        'name' => 'SHIPMENT_SENDER_ID',
                        'class' => 'automation_text',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'shipmentConfirmBtn';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name . '&smsrulesets=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getShipmentFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Create automation page - Delivery Confirmation section using Prestashop generate form method.
     *
     * @param array $getAllTemplates
     */
    public function onDeliveryConfirmationRuleset($getAllTemplates)
    {
        $SMSLabels     = $this->getSMSLabels();
        $orderCheckbox = array(
            array(
                'id_option' => '1',
                'name' => ''
            )
        );
        $fields_form   = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Delivery Confirmation')
                ),
                'input' => array(
                    array(
                        'label' => $this->l('Send Delivery Confirmation to Buyer on delivery of his shipment.')
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Activate Feature'),
                        'name' => 'ON_DELIVERY_FEATURE',
                        'values' => array(
                            'query' => $orderCheckbox,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select SMS Template'),
                        'name' => 'ON_DELIVERY_SMS_TEMPLATE',
                        'options' => array(
                            'query' => $getAllTemplates,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select Label'),
                        'name' => 'ON_DELIVERY_SMS_LABEL',
                        'options' => array(
                            'query' => $SMSLabels['labellist'],
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sender Id'),
                        'name' => 'ON_DELIVERY_SENDER_ID',
                        'class' => 'automation_text',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'onDeliveryBtn';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name . '&smsrulesets=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getCartFollowupsFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Create automation page - Out of Stock section using Prestashop generate form method.
     *
     * @param array $getAllTemplates
     */
    public function outStockAlertsRuleset($getAllTemplates)
    {
        $SMSLabels     = $this->getSMSLabels();
        $orderCheckbox = array(
            array(
                'id_option' => '1',
                'name' => ''
            )
        );
        $fields_form   = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Out of stock Alerts')
                ),
                'input' => array(
                    array(
                        'label' => $this->l(Onehopsmsservice::$msg['OUT_TEXT'] . Onehopsmsservice::$msg['MAIL_TEXT'])
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Activate Feature'),
                        'name' => 'OUTSTOCK_FEATURE',
                        'values' => array(
                            'query' => $orderCheckbox,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select SMS Template'),
                        'name' => 'OUTSTOCK_SMS_TEMPLATE',
                        'options' => array(
                            'query' => $getAllTemplates,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select Label'),
                        'name' => 'OUTSTOCK_SMS_LABEL',
                        'options' => array(
                            'query' => $SMSLabels['labellist'],
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sender Id'),
                        'name' => 'OUTSTOCK_SENDER_ID',
                        'class' => 'automation_text',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'OutStockBtn';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name . '&smsrulesets=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getOutStockFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Create automation page - Back Stock section using Prestashop generate form method.
     *
     * @param array $getAllTemplates
     */
    public function backStockAlertsRuleset($getAllTemplates)
    {
        $SMSLabels     = $this->getSMSLabels();
        $orderCheckbox = array(
            array(
                'id_option' => '1',
                'name' => ''
            )
        );
        $fields_form   = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Back in stock Alerts')
                ),
                'input' => array(
                    array(
                        'label' => $this->l(Onehopsmsservice::$msg['BACK_TEXT'] . Onehopsmsservice::$msg['MAIL_TEXT'])
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Activate Feature'),
                        'name' => 'BACKSTOCK_FEATURE',
                        'values' => array(
                            'query' => $orderCheckbox,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select SMS Template'),
                        'name' => 'BACKSTOCK_SMS_TEMPLATE',
                        'options' => array(
                            'query' => $getAllTemplates,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Select Label'),
                        'name' => 'BACKSTOCK_SMS_LABEL',
                        'options' => array(
                            'query' => $SMSLabels['labellist'],
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sender Id'),
                        'name' => 'BACKSTOCK_SENDER_ID',
                        'class' => 'automation_text',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'BackStockBtn';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name . '&smsrulesets=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getBackStockFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Create configuration page using Prestashop generate form method.
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-gears'
                ),
                'input' => array(
                    array(),
                    array(
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'SEND_SMS_API',
                        'desc' => $this->l('The API Key is used to authenticate in order to send SMS.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Admin Mobile Number'),
                        'name' => 'ADMIN_MOBILE',
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
        $fields_form = $this->setDefaultInput($fields_form);
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
        ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();
        $helper->id                       = (int) Tools::getValue('id_carrier');
        $helper->identifier               = $this->identifier;
        $helper->submit_action            = 'btnSubmit';
        $helper->currentIndex             = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex             .= '&configure=' . $this->name . '&tab_module=' . $this->tab;
        $helper->currentIndex             .= '&module_name=' . $this->name;
        $helper->currentIndex             .= '&configuration=yes';
        $helper->token                    = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                 = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $fields_form
        ));
    }
    
    /**
     * Get values from Prestashop configuration table.
     *
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'SEND_SMS_API' => Tools::getValue('SEND_SMS_API', Configuration::get('ONEHOP_SEND_SMS_API')),
            'ADMIN_MOBILE' => Tools::getValue('ADMIN_MOBILE', Configuration::get('ONEHOP_ADMIN_MOBILE'))
        );
    }
    
    /**
     * Get order confirmation value from ruleset table.
     *
     * @return array
     */
    public function getOrderFieldsValues()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets WHERE rule_name = "order_confirmation"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if ($results[0]['active'] == '1') :
                $featureActive = '1';
            else :
                $featureActive = '0';
            endif;
            return array(
                'ORDER_FEATURE_1' => Tools::getValue('ORDER_FEATURE_1', (int)$featureActive),
                'ORDER_SMS_TEMPLATE' => Tools::getValue('ORDER_SMS_TEMPLATE', $results[0]['template']),
                'ORDER_SMS_LABEL' => Tools::getValue('ORDER_SMS_LABEL', $results[0]['label']),
                'ORDER_SENDER_ID' => Tools::getValue('ORDER_SENDER_ID', $results[0]['senderid'])
            );
        }
    }
    
    /**
     * Get shipment confirmation value from ruleset table.
     *
     * @return array
     */
    public function getShipmentFieldsValues()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets WHERE rule_name = "shipment_confirmation"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if ($results[0]['active'] == '1') :
                $featureActive = '1';
            else :
                $featureActive = '0';
            endif;
            return array(
                'SHIPMENT_FEATURE_1' => Tools::getValue('SHIPMENT_FEATURE_1', (int)$featureActive),
                'SHIPMENT_SMS_TEMPLATE' => Tools::getValue('SHIPMENT_SMS_TEMPLATE', $results[0]['template']),
                'SHIPMENT_SMS_LABEL' => Tools::getValue('SHIPMENT_SMS_LABEL', $results[0]['label']),
                'SHIPMENT_SENDER_ID' => Tools::getValue('SHIPMENT_SENDER_ID', $results[0]['senderid'])
            );
        }
    }
    
    /**
     * Get delivery confirmation value from ruleset table.
     *
     * @return array
     */
    public function getCartFollowupsFieldsValues()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets WHERE rule_name = "on_delivery_confirmation"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if ($results[0]['active'] == '1') :
                $featureActive = '1';
            else :
                $featureActive = '0';
            endif;
            return array(
                'ON_DELIVERY_FEATURE_1' => Tools::getValue('ON_DELIVERY_FEATURE_1', (int)$featureActive),
                'ON_DELIVERY_SMS_TEMPLATE' => Tools::getValue('ON_DELIVERY_SMS_TEMPLATE', $results[0]['template']),
                'ON_DELIVERY_SMS_LABEL' => Tools::getValue('ON_DELIVERY_SMS_LABEL', $results[0]['label']),
                'ON_DELIVERY_SENDER_ID' => Tools::getValue('ON_DELIVERY_SENDER_ID', $results[0]['senderid'])
            );
        }
    }
    
    /**
     * Get out of stock value from ruleset table.
     *
     * @return array
     */
    public function getOutStockFieldsValues()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets WHERE rule_name = "out_of_stock_alerts"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if ($results[0]['active'] == '1') :
                $featureActive = '1';
            else :
                $featureActive = '0';
            endif;
            return array(
                'OUTSTOCK_FEATURE_1' => Tools::getValue('OUTSTOCK_FEATURE_1', (int)$featureActive),
                'OUTSTOCK_SMS_TEMPLATE' => Tools::getValue('OUTSTOCK_SMS_TEMPLATE', $results[0]['template']),
                'OUTSTOCK_SMS_LABEL' => Tools::getValue('OUTSTOCK_SMS_LABEL', $results[0]['label']),
                'OUTSTOCK_SENDER_ID' => Tools::getValue('OUTSTOCK_SENDER_ID', $results[0]['senderid'])
            );
        }
    }
    
    /**
     * Get back of stock value from ruleset table.
     *
     * @return array
     */
    public function getBackStockFieldsValues()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets WHERE rule_name = "back_of_stock_alerts"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if ($results[0]['active'] == '1') :
                $featureActive = '1';
            else :
                $featureActive = '0';
            endif;
            return array(
                'BACKSTOCK_FEATURE_1' => Tools::getValue('BACKSTOCK_FEATURE_1', (int)$featureActive),
                'BACKSTOCK_SMS_TEMPLATE' => Tools::getValue('BACKSTOCK_SMS_TEMPLATE', $results[0]['template']),
                'BACKSTOCK_SMS_LABEL' => Tools::getValue('BACKSTOCK_SMS_LABEL', $results[0]['label']),
                'BACKSTOCK_SENDER_ID' => Tools::getValue('BACKSTOCK_SENDER_ID', $results[0]['senderid'])
            );
        }
    }
    
    /**
     * Get all templates from database.
     *
     * @return array
     */
    public function getAllSMSTemplates()
    {
        $selectTempsql = 'SELECT temp_id, temp_name FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
        $selectTempsql .= ' WHERE 1 Order By temp_name ASC';
        $templateRes   = Db::getInstance()->ExecuteS($selectTempsql);
        return $templateRes;
    }
    
    /**
     * Check ruleset is active or not.
     *
     * @param string $rulesetName
     * @return array
     */
    public function isRulesetActive($rulesetName)
    {
        $isactive     = 'SELECT template,label,senderid FROM ' . _DB_PREFIX_ . 'onehop_sms_rulesets';
        $isactive    .= ' WHERE rule_name = "' . pSQL($rulesetName) . '" AND active = "1"';
        $activeResult = Db::getInstance()->ExecuteS($isactive);
        return $activeResult;
    }
    
    /**
     * Get labels from API using CURL.
     *
     * @return array
     */
    public function getSMSLabels()
    {
        $urllink = "http://api.onehop.co/v1/labels/";
        $ch      = curl_init($urllink);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: ',
            'apiKey:' . $this->smsAPI
        ));
        $output = Tools::jsonDecode(curl_exec($ch));
        curl_close($ch);
        $labelArr  = array();
        $labelInfo = array();
        if ($output && isset($output->labelsList) && $output->labelsList) {
            $output->accountId = '';
            $labelInfo['acountid'] = $output->accountId;
            $labelArr[]            = array(
                'id_option' => '',
                'name' => 'Select Label'
            );
            foreach ($output->labelsList as $labelVal) {
                $labelArr[] = array(
                    'id_option' => $labelVal,
                    'name' => $labelVal
                );
            }
        } elseif ($output && isset($output->message)) {
            $labelArr[]            = array(
                'id_option' => '',
                'name' => 'No label available'
            );
        }
        $labelInfo['labellist'] = $labelArr;
        return $labelInfo;
    }
    
    /**
     * Replace legends, placeholders for order template body.
     *
     * @return string
     */
    private function replaceOrderLegends(
        $templateId,
        $order = '',
        $address = ''
    ) {
        $seltemplate    = 'SELECT temp_body FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
        $seltemplate    .= ' WHERE temp_id = "' . (int)$templateId . '"';
        $getTemplateRes = Db::getInstance()->ExecuteS($seltemplate);
        $shippingAddr   = '';
        $productid      = '';
        $productname    = '';
        $invoice        = '';
        $ship_number    = '';
        $tran_id        = '';
        
        if ($address != '') {
            $shippingAddr .= $address->address1 . ' ' . $address->address2 . '<br>';
            $shippingAddr .= $address->postcode . ' ' . $address->city.' '.$address->country;
        }
                
        $productArray = $order->getProducts();
        $total_product_disc = 0;
        if ($productArray) {
            $idarray = array();
            $namearray = array();
            foreach ($productArray as $product) {
                array_push($namearray, $product['product_name']);
                array_push($idarray, $product['product_id']);
                $product_amt = (float)$product['total_price_tax_incl'];
                
                $reduction_precent = (float)$product['reduction_percent'];
                if ($reduction_precent > 0) {
                    $actual_amt = ($product_amt*100)/(100-$reduction_precent);
                    $total_product_disc += ($actual_amt-$product_amt);
                }
                
                $reduction_amount = (float)$product['reduction_amount_tax_incl'];
                if ($reduction_amount > 0) {
                    $total_product_disc += $reduction_amount;
                }
            }
            $productid   = implode(', ', $idarray);
            $productname = implode(', ', $namearray);
        }
        
        $getOrderTrack = $this->getOrderTrackNTransID($order->id);
        $orderTrack = ($getOrderTrack !== null && sizeof($getOrderTrack) > 0) ? $getOrderTrack[0] : '';
        if ($orderTrack != '') {
            $invoiceNo = Tools::strlen($orderTrack['invoice_number']);
            if ((int) $invoiceNo > 0 && (int) $orderTrack['invoice_number'] > 0) {
                switch ($invoiceNo) {
                    case '1':
                        $invoicePre = '#IN00000';
                        break;
                    case '2':
                        $invoicePre = '#IN0000';
                        break;
                    case '3':
                        $invoicePre = '#IN000';
                        break;
                    case '4':
                        $invoicePre = '#IN00';
                        break;
                    case '5':
                        $invoicePre = '#IN0';
                        break;
                    default:
                        $invoicePre = '#IN';
                        break;
                }
                $invoice = $invoicePre . $orderTrack['invoice_number'];
            }
            $ship_number = $orderTrack['shipping_number'];
            $tran_id = $orderTrack['transaction_id'];
        }
                
        $legends = array(
            "{FIRSTNAME}",
            "{LASTNAME}",
            "{EMAIL}",
            "{MOBILE}",
            "{SHIPPING_ADDRESS}",
            "{ORDERID}",
            "{PRICE}",
            "{DISCOUNT}",
            "{PRODUCTID}",
            "{PRODUCTNAME}",
            "{TRACKINGID}",
            "{TRANSACTIONID}",
            "{INVOICE}"
        );
        
        $customer = new Customer((int)$order->id_customer);
        
        $coupon_disc = (float)$order->total_discounts_tax_incl;
        $coupon_disc += $total_product_disc;
        
        $total_amt = number_format($order->total_paid_tax_incl, 2);
        $discount = number_format($coupon_disc, 2);
        $currencyInfo = new Currency((int)$order->id_currency);
        if ($currencyInfo != '') {
            $total_amt = $currencyInfo->iso_code.' '.$total_amt;
            $discount = $currencyInfo->iso_code.' '.$discount;
        }
        
        $replacedLegends = array(
            $customer->firstname,
            $customer->lastname,
            $customer->email,
            $address->phone_mobile,
            $shippingAddr,
            $order->id,
            $total_amt,
            $discount,
            $productid,
            $productname,
            $ship_number,
            $tran_id,
            $invoice
        );
        
        $NewReplacedTemp = str_replace($legends, $replacedLegends, $getTemplateRes[0]['temp_body']);
        return $NewReplacedTemp;
    }
    
    /**
     * Replace legends, placeholders for product template body.
     *
     * @return string
     */
    private function replaceProductCustomerLegends(
        $templateId,
        $customerinfo,
        $productInfo = ''
    ) {
        $seltemplate    = 'SELECT temp_body FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
        $seltemplate    .= ' WHERE temp_id = "' . (int)$templateId . '"';
        $getTemplateRes = Db::getInstance()->ExecuteS($seltemplate);
        
        $customer = new Customer((int)$customerinfo['id_customer']);
          
        $legends = array(
            "{FIRSTNAME}",
            "{LASTNAME}",
            "{EMAIL}",
            "{MOBILE}",
            "{PRODUCTID}",
            "{PRODUCTNAME}"
        );
        if ($productInfo != '') {
            $replacedLegends = array(
                $customer->firstname,
                $customer->lastname,
                $customer->email,
                $customerinfo['phone_mobile'],
                $productInfo['product_id'],
                $productInfo['product_name']
            );
        } else {
            $replacedLegends = array(
                '',
                '',
                '',
                '',
                '',
                ''
            );
        }
        
        $NewReplacedTemp = str_replace($legends, $replacedLegends, $getTemplateRes[0]['temp_body']);
        return $NewReplacedTemp;
    }
    /**
     * Replace legends, placeholders for product template body.
     *
     * @return string
     */
    private function replaceProductLegends(
        $templateId,
        $productInfo = ''
    ) {
        $seltemplate    = 'SELECT temp_body FROM ' . _DB_PREFIX_ . 'onehop_sms_templates';
        $seltemplate    .= ' WHERE temp_id = "' . (int)$templateId . '"';
        $getTemplateRes = Db::getInstance()->ExecuteS($seltemplate);
                
        $legends = array(
            "{PRODUCTID}",
            "{PRODUCTNAME}"
        );
        if ($productInfo != '') {
            $replacedLegends = array(
                $productInfo['product_id'],
                $productInfo['product_name']
            );
        } else {
            $replacedLegends = array(
                '',
                ''
            );
        }
        
        $NewReplacedTemp = str_replace($legends, $replacedLegends, $getTemplateRes[0]['temp_body']);
        return $NewReplacedTemp;
    }
    
    /**
     * Send SMS via API.
     *
     * @param array $postdata
     * @return json
     */
    public function sendSMSByAPI($postdata)
    {
        $urllink = "http://api.onehop.co/v1/sms/send/";
        $ch = curl_init($urllink);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postdata != '' && !empty($postdata)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: ',
            'apiKey:' . $this->smsAPI
        ));
        $output = Tools::jsonDecode(curl_exec($ch));
        curl_close($ch);
        return $output;
    }
    
    /**
     * Get order tracking details from database.
     *
     * @param int $orderid
     * @return array
     */
    public function getOrderTrackNTransID($orderid)
    {
        $selOrder      = 'SELECT ordr.reference, ordr.shipping_number,ordr.invoice_number, paymt.transaction_id';
        $selOrder      .= ' FROM ' . _DB_PREFIX_ . 'orders as ordr';
        $selOrder      .= ' LEFT JOIN ' . _DB_PREFIX_ . 'order_payment as paymt';
        $selOrder      .= ' ON paymt.order_reference = ordr.reference';
        $selOrder      .= ' WHERE ordr.id_order = "' . (int)$orderid . '"';
        $selOrder      .= ' order by paymt.id_order_payment desc';
        $getOrderTrack = Db::getInstance()->ExecuteS($selOrder);
        return $getOrderTrack;
    }
    
    /**
     * Initialization function called from Constructor.
     */
    public function preload()
    {
        if (Tools::getValue('controller') != ''
            && (Tools::getValue('controller') == 'Onehopsmsservice'
            || Tools::getValue('controller') == 'onehopsmsservice')) {
                $token   = Tools::getAdminTokenLite('AdminModules');
                $request_scheme = $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
                $hostlink = $request_scheme . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
                $ctrlconfi = "?controller=AdminModules&configure=" . Tools::getValue('controller');
                $urlLink = $hostlink . $ctrlconfi ."&token=" . $token;
                Tools::redirect($urlLink);
        }
    }
    
    /**
     * Validate API key entered by user on configuration page.
     *
     * @param string $key
     * @return json
     */
    public function isValidAPIKey($apikey)
    {
        $urllink = "http://api.onehop.co/v1/api_key/validate/";
        $ch      = curl_init($urllink);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: ',
            'apiKey:' . $apikey
        ));
        $output = Tools::jsonDecode(curl_exec($ch));
        curl_close($ch);
        return $output;
    }
    
    /**
     * If default values are not provided while creating form, set it with default blank values.
     *
     * @param json $fields_form
     * @return json
     */
    private function setDefaultInput($fields_form)
    {
        if (isset($fields_form['form']['input'])) {
            foreach ($fields_form['form']['input'] as &$params) {
                if (!isset($params['type'])) {
                    $params['type'] = '';
                }
                if (!isset($params['name'])) {
                     $params['name'] = '';
                }
            }
        }
        return $fields_form;
    }
    
    /**
     * Save Log.
     *
     * @param string $key
     * @param string $data
     * @param string $mobile
     */
    public static function onehopSaveLog($key, $data, $mobile)
    {
        if (_PS_MODE_DEV_ == false) {
            return;
        }
        $temp = array();
        $temp['mobile'] = $mobile;
        $temp['smsbody'] = $data;
        date_default_timezone_set('GMT');
        $currentdate = date_create();
        $tempdate = $currentdate->format('d/m/Y H:i');
        $message = $key.' ['.$tempdate.']'."\r\n".Tools::jsonEncode($temp).
            "\r\n--------------------------------------------\r\n\r\n";
        $logger = new FileLogger(0);
        $logger->setFilename(_PS_ROOT_DIR_.'/log/debug.log');
        $logger->logDebug($message);
    }
}
