<div><img src="{$img_ps_dir}admin/ajax-loader-yellow.gif"></div>
{l s='You will be redirected to the Privat24 website in a few seconds.' mod='ecm_privat24'}
	<form id="privat24_redirect" name="privat24_form"  method="POST" action="https://api.privatbank.ua:9083/p24api/ishop">
		<input type="hidden" name="PAYMENT_NO" value="{$order_number}"/>
		<input type="hidden" name="amt" value="{$amount}"/>
		<input type="hidden" name="ccy" value="{$currency1}" />
		<input type="hidden" name="merchant" value="{$merchant}" />
		<input type="hidden" name="order" value="{$order}" />
		<input type="hidden" name="details" value="{$details}" />
		<input type="hidden" name="ext_details" value="{$ext_details}" />
		<input type="hidden" name="pay_way" value="privat24" />
		<input type="hidden" name="return_url" value="{$return_url}" />
		<input type="hidden" name="server_url" value="{$server_url}" />
	</form>

	<script>document.getElementById("privat24_redirect").submit();</script>

