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
<div class="screen_magic_wrapper">
	{if isset($isAddTemp)}
	<div class="panel-heading"><i></i>
	{if $isAddTemp eq 'addTemplate'}
		Add New Template
	{else}
		Update Template
	{/if}
	</div>
	<div class="form-wrapper">
		<div class="form-wrapper">
			{if isset($ErrorMsg)}
				<p style="color: red;">{$ErrorMsg|escape:'htmlall':'UTF-8'}</p>
			{elseif isset($SuccessMsg)}
				<p style="color: green;">{$SuccessMsg|escape:'htmlall':'UTF-8'}</p>
			{/if}
		    <form action="" method="post" class="sms-form" id="manage_sms">
		    	<div class="div_fields">
		      		<label>Template Name : <span class="smsRequired">*</span></label>
		        	<input type="text" name="TEMPLATE_NAME" value="{$templateName|escape:'htmlall':'UTF-8'}">
		       </div>
		       <div class="div_fields">
		      		<label>Template Placeholders : </label>
		        	<select name="templatetype" id="templatetype" >
		        		<option value="">-Select type-</option>
		        		<option value="customer">Customer</option>
		        		<option value="order">Order</option>
		        		<option value="product">Product</option>
		        	</select>&nbsp;&nbsp;
		        	<select name="templateplaceholder" id="templateplaceholder">
		        		<option value="">Select Placeholder</option>
		        	</select>&nbsp;&nbsp;
		        	<a href="javascript:;" id="insertPlacehoder" class="anchorBtn btn-default"> Insert </a>
		        	<div class="clearboth"></div>
		       </div>
		       
		       <div class="div_fields">
		      		<label>Template Body : <span class="smsRequired">*</span></label>
		        	<textarea name="TEMPLATE_BODY" cols="40" rows="10" maxlength="700" id="temp_message_body">{$templateBody|escape:'htmlall':'UTF-8'}</textarea>
		        	<span class="formdescription">You can write upto 700 characters.</span>
		        	<input type="hidden" name="TEMPLATE_ID" value="{$templateID|md5|escape:'htmlall':'UTF-8'}">
		       </div>
		       <div class="div_fields">
		       		<label>&nbsp;</label>
		       		{if $isAddTemp eq 'addTemplate'}
		       			<input type="submit" value="Submit" class="btn btn-default" name="saveTemplate">
		       		{else}
		       			<input type="submit" value="Update" class="btn btn-default" name="editTemplate">
		       		{/if}
		       </div>	
			</form>
		</div>
		<div class="clearboth"></div>
	</div>
	{else}
	<form action="" method="post">
	<div class="panel-heading"><i></i>List of Templates</div>
		<div class="form-wrapper">
		   <button name="addTemplateBtn" class="btn btn-default"><span style="font-size: 16px;padding-right: 5px; bold;">+</span>Add Template</button>
		   <table width="100%" border="0" class="templatesList">
		   	<tr>
		   		<th>No.</th>
		   		<th>Template Name</th>
		   		<th>Edit</th>
		   		<th>Delete</th>
		   	</tr>
		  	{if isset($Templateslist)}
		  	   	{foreach from=$Templateslist item=item key=name}
			   	<tr>
				   	<td>{$name+1|escape:'htmlall':'UTF-8'}</td>
			   		<td>{$item.temp_name|escape:'htmlall':'UTF-8'}</td>
			   		<td><button name="editTemplateBtn" value="{$item.temp_id|md5|escape:'htmlall':'UTF-8'}" class="smstemplatebtn"><i class="icon-edit" style="font-size: 20px;"></i></button></td>
			   		<td><a href="javascript:;" onclick="deleteTemplate('{$item.temp_id|md5|escape:'htmlall':'UTF-8'}')"><i class="icon-trash smsediticon"></i></a></td>
			   	</tr>		 
				{/foreach}
			{else}
				<tr>
				   	<td colspan="3" align="center"> <b>No template available.</b></td>
			   	</tr>		 
			{/if}
			</table>
		</div>
	</form>
	{/if}
</div>

{literal}
<script type="text/javascript">
  function deleteTemplate(tempID) {
    if (tempID != '')
    {
    	var currentLocation = window.location.href;
    	
    	var isConf = confirm("Are you sure to delete this template?");
    	if(isConf == true)
    	{
    		$.ajax({
			  url: currentLocation,
			  method:"POST",
			  data:{tempID:tempID, deleteTemplate:'yes'},
			  dataType:"JSON"
			}).done(function(res) {
				if(res['Success'])
				{
					alert("Deleted Successfully");
			  		window.location.reload();
				}
				else
				{
					alert(res['Error']);
				}
			});
    	}
    }
    else
    {
      	alert("Some thing went wrong.");
    }
  }
  function typeInTextarea(el, newText) {
	  var start = el.prop("selectionStart")
	  var end = el.prop("selectionEnd")
	  var text = el.val()
	  var before = text.substring(0, start)
	  var after  = text.substring(end, text.length)
	  el.val(before + newText + after)
	  el[0].selectionStart = el[0].selectionEnd = start + newText.length
	  el.focus()
	}
$(document).ready(function(){
	$("#temp_message_body").keypress(function(){
		var msglength = $("#temp_message_body").val().length;
		if(msglength == 700)
		{
			alert('You can write upto 700 characters.')
		}
	});
	
	$("#templatetype").change(function(){
		var Temptype = $("#templatetype").val();
		var currentLocation = window.location.href;
		
		if(Temptype != '')
		{
			$.ajax({
			  url: currentLocation,
			  method:"POST",
			  data:{Temptype:Temptype, TemplateType:'yes'},
			  dataType:"JSON"
			}).done(function(dataRes) {
				//console.log(dataRes);
				var select = document.getElementById("templateplaceholder"); 
				$("#templateplaceholder").html('');
				for(var i = 0; i <= dataRes.length; i++)
				{
					if(i == 0)
					{
						var opt = 'Select Placeholder';
						var optVal = '';					
						var el = document.createElement("option");
					    el.textContent = opt;
					    el.value = "{"+optVal+"}";
					    select.appendChild(el);
					}
					var opt = dataRes[i]['name'];
					var optVal = dataRes[i]['value'];					
					var el = document.createElement("option");
				    el.textContent = opt;
				    el.value = "{"+optVal+"}";
				    select.appendChild(el);
				}
			});
		}
		else
		{
			$("#templateplaceholder").html("<option value=''>Select Placeholder</option>");
		}
	});
		
	$("#insertPlacehoder").click(function(){
		var placeholder = $("#templateplaceholder").val();
		var msglength = $("#temp_message_body").val().length;
		if(msglength+placeholder.length > 700)
		{
			alert('You can write upto 700 characters.')
		}
		else
		{
			typeInTextarea($("#temp_message_body"), placeholder)	
		}
		return false
	});
});
</script>
{/literal}
