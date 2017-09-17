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
<link href="../modules/onehopsmsservice/views/css/onehopsmsservice.css" rel="stylesheet" />
{if $breadcrumbs neq ''}
	<p style="display: none;" id="customBreadcrumbs">{$breadcrumbs|escape:'htmlall':'UTF-8'}</p>
{/if}

{literal}
	<script type="text/javascript">
	  	$(".menu li").removeClass("active");
        $("#maintab-onehopsmsservice").addClass("active");
        var custoBreadcrumbs = $("#customBreadcrumbs").text();
		$(".page-title").text('');
		$(".page-title").text(custoBreadcrumbs);
		$(".breadcrumb li:last-child").text('');
		$(".breadcrumb li:last-child").html(custoBreadcrumbs);		
	</script>
{/literal}

