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

		this.loader = BX.ShUiClear.Loader.create();

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
		
		this.loader.fade();

		return this.callMethod(action, params)
		.then(function(response)
		{
			this.renderResult((response.data || {}).content || '?');
			this.renderResultError((response.data || {}).errors || '');
			this.form.reset();
			this.loader.unFade();
		}.bind(this))
		.catch(function(error)
		{
			this.form.reset();
			this.loader.unFade();
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
	, getDemoFile: function(event, module, className)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
		
		let action = 'getDemoFile';
		
		let url = '/bitrix/services/main/ajax.php?mode=class&c='+(this.params.component || '?')+'&action='+action;
		url = url+'&sessid='+BX.bitrix_sessid();
		url = url+'&module='+(module || null);
		url = url+'&className='+(className || null);
		
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
			promise.reject('Error: ' + ((responseError.errors || [])[0] || {}).message || '?');
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