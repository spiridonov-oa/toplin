{*
*Template for Payment Return Hook
*}
{if $status == 'success'}
<p><img src="modules/ecm_privat24/img/ok.png" align="left"><br /><strong>{l s='Ваш заказ выполнен' sprintf=$shop_name mod='ecm_privat24'}</strong></p>
<br />
<p style="margin-left:80px; margin-bottom: 0px; font-size:15px;"><strong>{l s='Ваш заказ будет отправлен в ближайшее время.' mod='ecm_privat24'}</strong></p>
		<p style="margin-left:80px; margin-bottom: 0px; font-size:15px;">{l s='Вы можете просмотреть историю заказов по этой ссылке:' mod='ecm_privat24'} <a href="{$link->getPageLink('history', true)}"><span style="color: #0664BF;">
		<br /><strong>{l s='История Заказов' mod='ecm_privat24'}</strong></span></a></p>
		<p style="margin-left:80px; margin-bottom: 0px; font-size:15px;">{l s='По любым вопросам или для получения дополнительной информации, пожалуйста, свяжитесь с нашей' mod='ecm_privat24'} <a href="{$link->getPageLink('contact', true)}"><span style="color: #0664BF;"><strong>{l s='службой поддержки' mod='ecm_privat24'}</strong></span></a></p>
<p style="margin-left:80px; margin-bottom: 0px; font-size:15px;"><strong>{l s='Спасибо!' mod='ecm_privat24'}</strong>
	</p>

{elseif $status == 'wait_secure'}
<p>	<img src="modules/ecm_privat24/img/ok.png" align="left"> <strong>{l s='Система Приват24 проверяет ваш платеж' mod='ecm_privat24'}</strong>
<br /><br /><strong>{l s='Ваш заказ будет отправлен как только мы получим подтверждение Вашей оплаты' mod='ecm_privat24'}</strong>
<br /><br />{l s='По любым вопросам или для получения дополнительной информации, пожалуйста, свяжитесь с нашей' mod='ecm_privat24'} <a href="{$link->getPageLink('contact', true)}">{l s='службой поддержки' mod='ecm_privat24'}</a>.
<br /><br /><strong>{l s='Спасибо!' mod='ecm_privat24'}</strong>

{elseif $status == 'failure'}
	<p class="warning">
		<img src="modules/ecm_privat24/img/not_ok.png" align="left">{l s='Мы заметили проблемы с вашим заказом. Пожалуйста, свяжитесь с нами как можно скорее' mod='ecm_privat24'}.
		<br /><br />
		{l s='Ваш заказ не будет отправлен, пока проблема не будет решена' mod='ecm_privat24'}
		<br /><br />
	</p>
{else}
<p class="warning">
		<img src="modules/ecm_privat24/img/not_ok.png" align="left">{l s='Мы заметили проблемы с вашим заказом.' mod='ecm_privat24'}.
		<br /><br />
		{l s='Пожалуйста, выберите способ оплаты' mod='ecm_privat24'}<a href="{$link->getPageLink('order', true)}">{l s='Страница заказа' mod='ecm_privat24'}</a>
		<br /><br />
	</p>
{/if}
