// DEBUG ONLY -- REMOVE LATER
if (!WCF) var WCF = {};

WCF.ACL = {};

WCF.ACL.List = function(containerSelector, objectTypeID, objectIDs) { this.init(containerSelector, objectTypeID, objectIDs); };
WCF.ACL.List.prototype = {
	_containers: { },
	_containerElements: { },
	_objectIDs: [ ],
	_objectTypeID: null,
	_proxy: null,

	init: function(containerSelector, objectTypeID, objectIDs) {
		this._objectIDs = objectIDs;
		this._objectTypeID = objectTypeID;
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});

		if (!this._objectIDs) {
			this._objectIDs = [ ];
		}

		// fetch containers
		$(containerSelector).each($.proxy(function(index, container) {
			var $container = $(container);
			var $containerID = $container.wcfIdentify();

			// bind hidden container
			this._containers[$containerID] = $container.hide().addClass('aclContainer');

			// insert container elements
			var $elementContainer = $container.children('dd');
			var $aclList = $('<ul class="aclList" />').appendTo($elementContainer);
			var $searchInput = $('<input type="search" class="aclSearchInput" />').appendTo($elementContainer);
			var $permissionList = $('<ul class="aclPermissionList" />').hide().appendTo($elementContainer);

			// set elements
			this._containerElements[$containerID] = {
				aclList: $aclList,
				permissionList: $permissionList,
				searchInput: $searchInput
			};
		}, this));

		this._loadACL();
	},

	_loadACL: function() {
		this._proxy.setOption('data',  {
			actionName: 'loadAll',
			className: 'wcf\\data\\acl\\option\\ACLOptionAction',
			parameters: {
				data: {
					objectIDs: this._objectIDs,
					objectTypeID: this._objectTypeID
				}
			}
		});
		this._proxy.sendRequest();
	},

	_success: function(data, textStatus, jqXHR) {
		console.debug(data);
	}
};