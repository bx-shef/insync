'use strict';
BX.namespace('BX.ShInSync');

BX.ShInSync.ImportFromFileController = function()
{
	this.params = {};
	this.isDebug = false;

	this.formId = null;
	this.responseId = null;
	this.responseErrorId = null;

	this.loader = null;
};

BX.ShInSync.ImportFromFileController.prototype = {
	initialize: function(params)
	{
		this.params = BX.type.isPlainObject(params) ? params : {};

		this.form = BX(this.params.formId || 'notSet');
		this.response = BX(this.params.responseId || 'notSet');
		this.responseErrorId = BX(this.params.responseErrorId || 'notSet');

		// Штатный загрузчик ядра (расширение main.loader) поверх формы.
		this.loader = new BX.Loader({ target: BX('sh-template') || document.body });

		this.bind();
		this._log('init', true);
	}
	// region Bind ////
	, bind: function()
	{
		this.form.onsubmit = this.onSubmit.bind(this);
	}
	// endregion ////
	// region Actions ////
	, onSubmit: function(event)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		// let action = 'demoComponent';
		let action = 'importFile';
		let params = new FormData(this.form);
		
		this.renderResult('');
		this.renderResultError('');
		
		this.loader.show();

		return this.callMethod(action, params)
		.then(function(response)
		{
			this.renderResult((response.data || {}).content || '?');
			this.renderResultError((response.data || {}).errors || '');
			this.form.reset();
			this.loader.hide();
		}.bind(this))
		.catch(function(error)
		{
			this.form.reset();
			this.loader.hide();
			BX.UI.Notification.Center.notify({
				content: error,
				category: this.params.component + '-error',
				position: "top-right",
				autoHideDelay: 1500
			});
		}.bind(this));
	}
	, renderResult(content)
	{
		this.response.innerHTML = content;
	}
	, renderResultError(content)
	{
		this.responseErrorId.innerHTML = content;
	}
	, getDemoFile: function(event)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
		
		// Модуль и класс — из конфигурации страницы, а не из разметки: там
		// они были строкой внутри onclick и не экранировались.
		let url = '/bitrix/services/main/ajax.php?' + [
			'mode=class',
			'c=' + encodeURIComponent(this.params.component || '?'),
			'action=getDemoFile',
			'sessid=' + encodeURIComponent(BX.bitrix_sessid()),
			'module=' + encodeURIComponent(this.params.module || ''),
			'className=' + encodeURIComponent(this.params.className || '')
		].join('&');
		
		window.open(url, '_blank');
		return false;
	}
	// endregion ////
	// region Ajax ////
	, callMethod: function (action, params)
	{
		let promise = new BX.Promise();

		BX.ajax.runComponentAction(
			this.params.component || '?',
			action,
			{
				mode: this.params.mode || 'class',
				data: params || null,
				signedParameters: this.params.signedParameters || null
			}
		)
		.then(function(result)
		{
			promise.fulfill(result);
		})
		.catch(function(responseError)
		{
			promise.reject('Error: ' + ((((responseError.errors || [])[0] || {}).message) || '?'));
		});

		return promise;
	}
	// endregion ////
	// region Tools ////
	, _log: function(value, allTime = false)
	{
		if(this.isDebug || allTime)
		{
			console.log('>> BX.ShInSync >>> ImportFromFileController >>', value);
		}
	}
	// endregion ////
};

BX.ShInSync.ImportFromFileController.create = function(params)
{
	params = BX.type.isPlainObject(params) ? params : {};

	let self = new BX.ShInSync.ImportFromFileController();
	self.initialize(params);
	return self;
};