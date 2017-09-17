{*
* 2007-2017 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div class="oneHop_wrapper">
<div class="welwrapper">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="acc_banner">
                    <img alt="Welcome to Onehop" src="{$imagepath|escape:'htmlall':'UTF-8'}/acc_banner.png" usemap="#onehopmap" />
                    <map name="onehopmap">
                        <area shape="rect" coords="601,4,771,63" alt="Onehop.co" title="Onehop.co" href="http://www.onehop.co" target="_blank"/>
                    </map>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="custom_container">
                    <div class="onehop_list">
                        <h2>With Onehop on PrestaShop, you can SMS all your:</h2>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <ul>
                                <li>Order Confirmations</li>
                                <li>Shipment Confirmations</li>
                                <li>Delivery Confirmations</li>
                            </ul>
                        </div>
                    
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <ul>
                                <li>Out of Stock Product Alerts</li>
                                <li>Back in Stock Product Alerts</li>
                                <li>Offers & Promotions</li>
                            </ul>
                        </div>
                    </div>
                    
                    <h2 class="acc_title">Get Started with Onehop on PrestaShop</h2>
                    
                    <div class="panel-group" id="accordion">
                        
                        <div class="panel panel-default">
                            <div>
                                <div class="panel-body">
                                    <ul class="acc_1_list">
                                        <li>You will receive an email including an <span class="stronger">API Key</span> within 24 hours of installing <span class="stronger">Onehop SMS Services</span> module.<br/><br/>
                                        </li>
                                        
                                        <li>You will also receive an email with the login credentials for your Onehop account. Login to your Onehop account at <a href="https://www.onehop.co">www.onehop.co</a>. Here you will be able to manage your account details, access your API key, purchase SMS credits and view SMS history.<br/><br/>
                                        </li>
                                        
                                        <li>
                                            In the <span class="stronger">Configuration</span> tab:
                                            <ul>
                                                <li>Copy and paste the <span class="stronger">API Key</span> from the email, in the <span class="stronger">API Key</span> Section</li>
                                                <li>Include the mobile number of the person who will receive the <span class="stronger">Out of Stock Alerts</span> in the <span class="stronger">Admin Mobile Section</span> </li>
                                            </ul> <br>  
                                        </li>
                                        
                                        <li>
                                            After clicking on the <span class="stronger">Save</span> button, the following additional tabs will appear in the Onehop SMS menu:<br/><br/>
                                            <ul>
                                                <li><span class="stronger">Send SMS :</span> You can send a single SMS using the <span class="stronger">Send SMS</span> tab on the menu screen <br/><br/></li>
                                                <li><span class="stronger">Manage Templates :</span> You can add, edit or delete templates with placeholder texts using <span class="stronger">Manage Templates</span> tab <br/><br/></li>
                                                <li><span class="stronger">SMS Automation :</span> Set automated rules for sending SMS with <span class="stronger">SMS Automation</span> tab</li>
                                            </ul>
                                        </li>                                    
                                    </ul>
                                </div>
                            </div>                           
                        </div>
                    </div>
                    <div class="onehop_docs">
                    <h2 class="acc_title productdoc_title">Product Documentation</h2>
                    <ul class="download_sec">
                        <li><img src="{$imagepath|escape:'htmlall':'UTF-8'}/pdf_icon.png" /> <a href="http://www.onehop.co/partners/prestashop/Onehop_Configuration_on_Prestashop_Quick_UserGuide.pdf" target="_blank">Onehop for PrestaShop user guide</a> </li> 
                        <li><img src="{$imagepath|escape:'htmlall':'UTF-8'}/pdf_icon.png" /> <a href="http://api.onehop.co/api-docs/" target="_blank">API documentation for custom development</a></li> 
                    </ul>
                    </div>
                    <h2 class="acc_title support_title">Contact Support</h2>
                    <div class="contact_info">
                        <img src="{$imagepath|escape:'htmlall':'UTF-8'}/email_icon.png" />For Sales and Support queries contact us <span class="stronger"><a target="_blank" href="https://addons.prestashop.com/en/contact-us">here</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
{literal}
<script type="text/javascript">
function toggleInstructions(e) {
    $(e.target)
        .prev('.panel-heading')
        .find("i")
        .toggleClass('icon-caret-down icon-caret-right');
}
$('#accordion').on('hidden.bs.collapse', toggleInstructions);
$('#accordion').on('shown.bs.collapse', toggleInstructions);
</script>
{/literal}
