'use strict';
BX.namespace('BX.ShInSync');

BX.ShInSync.ImportStatController = function()
{
	this.params = {};
	this.isDebug = false;
	this.gridId = null;
	this.agentListId = null;
	this.loader = null;
};

BX.ShInSync.ImportStatController.prototype = {
	initialize: function(params)
	{
		this.params = BX.type.isPlainObject(params) ? params : {};
		this.gridId = this.params.gridId || '';
		this.agentListId = BX(this.params.agentListId || 'notSet');
		
		this.loader = BX.ShUiClear.Loader.create();
		
		this.bind();
		this._log('init', true);
	}
	// region Bind ////
	, bind: function()
	{
		BX.addCustomEvent('onPullEvent', this.onPullEvent.bind(this));
	}
	// endregion ////
	// region Actions ////
	, onPullEvent: function(moduleId, command, params)
	{
		if(
			moduleId === 'shef.insync'
			&& command === 'reload'
			&& params?.component === (this.params?.component || '?')
		)
		{
			this.reload();
		}
	}
	
	, reload: function(event)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
		
		this.reloadGrid();
		this.reloadAgentsList();
		
		return false;
	}
	
	, reloadGrid: function(event)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
		
		// @memo: need sync agents too ////
		BX.Main.gridManager.reload(this.gridId);
		
		return false;
	}
	
	, onStopAgent: function(event, agentId)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		let action = 'stopAgent',
			params = {
				id: agentId || 0,
			}
		;
		
		this.loader.fade();

		this.callMethod(action, params)
		.then(function (response)
		{
			this.reload();
		}.bind(this))
		.catch(function (error)
		{
			this.loader.unFade();
			this.reload();

			BX.UI.Notification.Center.notify({
				content: error,
				category: this.params.component + '-error',
				position: 'top-right',
				autoHideDelay: 1500
			});
		}.bind(this));
		return false;
	}

	, onStartAgent: function(event, agentId)
	{
		event = event || (event = window.event);
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		let action = 'startAgent',
			params = {
				id: agentId || 0,
			}
		;
		
		this.loader.fade();

		this.callMethod(action, params)
		.then(function (response)
		{
			this.reload();
		}.bind(this))
		.catch(function (error)
		{
			this.loader.unFade();
			this.reload();

			BX.UI.Notification.Center.notify({
				content: error,
				category: this.params.component + '-error',
				position: 'top-right',
				autoHideDelay: 1500
			});
		}.bind(this));
		return false;
	}
	
	, reloadAgentsList: function()
	{
		let action = 'getAgents',
			params = {}
		;
		
		this.loader.fade();

		this.callMethod(action, params)
		.then(function (response)
		{
			this.renderAgentsList(response?.data?.content || '');
			this.loader.unFade();
		}.bind(this))
		.catch(function (error)
		{
			this.loader.unFade();

			BX.UI.Notification.Center.notify({
				content: error,
				category: this.params.component + '-error',
				position: 'top-right',
				autoHideDelay: 1500
			});
		}.bind(this));
		return false;
	}
	
	, renderAgentsList(content)
	{
		this.agentListId.innerHTML = content;
	}
	
	, clearRow: function(rowData)
	{
		let action = 'clearRow',
			params = {
				rowData: rowData || {},
			}
		;
		
		this.loader.fade();
		
		this.callMethod(action, params)
			.then(function (response)
			{
				this.reloadGrid();
				this.loader.unFade();
			}.bind(this))
			.catch(function (error)
			{
				this.loader.unFade();
				this.reloadGrid();
				
				BX.UI.Notification.Center.notify({
					content: error,
					category: this.params.component + '-error',
					position: 'top-right',
					autoHideDelay: 1500
				});
			}.bind(this));
		
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
			console.log('>> BX.ShInSync >>> ImportStatController >>', value);
		}
	}
	// endregion ////
};

BX.ShInSync.ImportStatController.create = function(params)
{
	params = BX.type.isPlainObject(params) ? params : {};

	let self = new BX.ShInSync.ImportStatController();
	self.initialize(params);
	return self;
};