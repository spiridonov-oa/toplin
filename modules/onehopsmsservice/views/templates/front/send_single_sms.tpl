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
{if isset($ErrorMsg)}
	<div class="module_error alert alert-danger">
		<button class="close" data-dismiss="alert" type="button">×</button>
		{$ErrorMsg|escape:'htmlall':'UTF-8'}
	</div>
{elseif isset($SuccessMsg)}
	<div class="module_confirmation conf confirm alert alert-success">
		<button data-dismiss="alert" class="close" type="button">×</button>
		{$SuccessMsg|escape:'htmlall':'UTF-8'}
	</div>
{/if}

<div class="screen_magic_wrapper">
	<div class="panel-heading"><i></i>Send Single SMS</div>
	<div class="form-wrapper">
		<form action="" method="post" class="sms-form">
	    	<div class="div_fields">
	      		<label>Mobile Number : <span class="smsRequired">*</span></label>
	        	<input type="text" name="SEND_SINGLE_SMS_MOBILE" value="{$smsMobileNo|escape:'htmlall':'UTF-8'}">
	       </div>
	       <div class="div_fields">
	      		<label>Sender Id : <span class="smsRequired">*</span></label>
	        	<input type="text" name="SEND_SINGLE_SENDER_ID" value="{$smsSenderid|escape:'htmlall':'UTF-8'}">
	       </div>
	       <div class="div_fields">
	      		<label>Select Label : <span class="smsRequired">*</span></label>
	      		<select name="SEND_SINGLE_SMS_LABEL">
	        	{if $SMSLabellist <> ''}
	        		{foreach from=$SMSLabellist item=item key=name}
	        			{if $item.id_option eq $smsLabel}
	        				<option value="{$item.id_option|escape:'htmlall':'UTF-8'}" selected="">{$item.name|escape:'htmlall':'UTF-8'}</option>
	        			{else}
		  	   				<option value="{$item.id_option|escape:'htmlall':'UTF-8'}">{$item.name|escape:'htmlall':'UTF-8'}</option>
		  	   			{/if}
		  	   		{/foreach}
		        {else}
		        	<option value="">No label available</option>
		        {/if}
	        	</select>
	       </div>
	       <div class="div_fields">
	      		<label>Select Template : </label>
	      		<select name="SEND_SINGLE_SMS_TEMPLATE" id="smstemplates">
	        	{if $SMSTemplateslist <> ''}
	        		<option value="">Select Template</option>
		  	   		{foreach from=$SMSTemplateslist item=item key=name}
		  	   			{if $item.temp_id|md5 eq $smsTemplate}
		  	   				<option value="{$item.temp_id|md5|escape:'htmlall':'UTF-8'}" selected="">{$item.temp_name|escape:'htmlall':'UTF-8'}</option>
		  	   			{else}
		  	   				<option value="{$item.temp_id|md5|escape:'htmlall':'UTF-8'}">{$item.temp_name|escape:'htmlall':'UTF-8'}</option>
		  	   			{/if}
		        	{/foreach}
		        {else}
		        	<option value="">No templates available</option>
		        {/if}
	        	</select>
	       </div>
	       <div class="div_fields">
	      		<label>Message Body : <span class="smsRequired">*</span></label>
	        	<textarea name="SEND_SIGNLE_SMS_BODY" cols="40" rows="10" id="messagebody" maxlength="700">{$templateBody|escape:'htmlall':'UTF-8'}</textarea>
	        	<span class="formdescription">You can write upto 700 characters.</span>
	       </div>
	       <div class="div_fields">
	       		<label>&nbsp;</label>
	       		<input type="submit" class="btn btn-default" value="Send SMS" name="sendSingleSMS">
	       </div>	
		</form>
	</div>
</div>
{literal}
<script type="text/javascript">
  $(document).ready(function(){
		$("#smstemplates").change(function(){
			$("#messagebody").val('');
			var tempId = $("#smstemplates").val();
			if(tempId)
			{
				var currentLocation = window.location.href;
				$.ajax({
				  url: currentLocation,
				  method:"POST",
				  data:{tempId:tempId, MessageBody:'yes'},
				  dataType:"JSON"
				}).done(function(res) {
					$("#messagebody").val(res[0]['temp_body']);
				});
			}
			else
			{
				$("#messagebody").val('');
			}
		});
		
		$("#messagebody").keypress(function(){
			var msglength = $("#messagebody").val().length;
			if(msglength == 700)
			{
				alert('You can write upto 700 characters.')
			}
		});
  });
</script>
{/literal}
