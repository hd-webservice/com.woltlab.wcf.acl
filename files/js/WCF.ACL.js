/**
 * Namespace for ACL
 */
WCF.ACL = {};

/**
 * ACL support for WCF
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.ACL.List = function(containerSelector, objectTypeID, objectIDs) { this.init(containerSelector, objectTypeID, objectIDs); };
WCF.ACL.List.prototype = {
	/**
	 * ACL container
	 * @var	jQuery
	 */
	_container: null,

	/**
	 * list of ACL container elements
	 * @var	object
	 */
	_containerElements: { },

	/**
	 * list of object ids
	 * @var	array
	 */
	_objectIDs: [ ],

	/**
	 * object type id
	 * @var	integer
	 */
	_objectTypeID: null,

	/**
	 * list of available ACL options
	 * @var	object
	 */
	_options: { },

	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,

	/**
	 * list of ACL settings
	 * @var	object
	 */
	_values: {
		group: { },
		user: { }
	},

	/**
	 * Initializes the ACL configuration.
	 * 
	 * @param	string		containerSelector
	 * @param	integer		objectTypeID
	 * @param	array		objectIDs
	 */
	init: function(containerSelector, objectTypeID, objectIDs) {
		this._objectIDs = objectIDs;
		this._objectTypeID = objectTypeID;
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});

		if (!this._objectIDs) {
			this._objectIDs = [ ];
		}
		
		// bind hidden container
		this._container = $(containerSelector).hide().addClass('aclContainer');
		
		// insert container elements
		var $elementContainer = this._container.children('dd');
		var $aclList = $('<ul class="aclList" />').appendTo($elementContainer);
		var $searchInput = $('<input type="search" class="aclSearchInput" />').appendTo($elementContainer);
		var $permissionList = $('<ul class="aclPermissionList" />').hide().appendTo($elementContainer);
		
		// set elements
		this._containerElements = {
			aclList: $aclList,
			permissionList: $permissionList,
			searchInput: $searchInput
		};

		// prepare search input
		new WCF.Search.User($searchInput, $.proxy(this.addObject, this), true);
		
		// bind event listener for submit
		var $form = this._container.parents('form:eq(0)');
		$form.submit($.proxy(this.submit, this));
		
		// reset ACL on click
		var $resetButton = $form.find('input[type=reset]:eq(0)');
		if ($resetButton.length) {
			$resetButton.click($.proxy(this._reset, this));
		}

		this._loadACL();
	},
	
	/**
	 * Restores the original ACL state.
	 */
	_reset: function() {
		// reset stored values
		this._values = {
			group: { },
			user: { }
		};
		
		// remove entries
		this._containerElements.aclList.empty();
		this._containerElements.searchInput.val('');
		this._containerElements.permissionList.empty().hide();
	},

	/**
	 * Loads current ACL configuration.
	 */
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

	/**
	 * Adds a new object to acl list.
	 * 
	 * @param	object		data
	 */
	addObject: function(data) {
		var $listItem = $('<li><img src="' + RELATIVE_WCF_DIR + 'icon/user' + ((data.type == 'group') ? 's' : '') +  '1.svg" alt="" /> <span>' + data.label + '</span></li>').appendTo(this._containerElements.aclList);
		$listItem.data('objectID', data.objectID).data('type', data.type).click($.proxy(this._click, this));
		
		var $removeItem = $('<img src="' + RELATIVE_WCF_DIR + 'icon/delete1.svg" alt="" />').click($.proxy(this._removeItem, this)).appendTo($listItem);

		this._containerElements.aclList.children('li').removeClass('active');
		$listItem.addClass('active');

		this._setupPermissions(data.type, data.objectID);
	},
	
	/**
	 * Removes an item from list.
	 * 
	 * @param	object		event
	 */
	_removeItem: function(event) {
		// TODO: determine list item and remove it (including stored data)
		console.debug('IMPLEMENT ME!');
	},

	/**
	 * Parses current ACL configuration.
	 * 
	 * @param	object		data
	 * @param	string		textStatus
	 * @param	jQuery		jqXHR
	 */
	_success: function(data, textStatus, jqXHR) {
		if (!$.getLength(data.returnValues.options)) {
			return;
		}

		// set options
		for (var $optionID in data.returnValues.options) {
			var $option = data.returnValues.options[$optionID];

			var $listItem = $('<li><span>' + $option.label +  '</span></li>').data('optionID', $optionID).data('optionName', $option.optionName).appendTo(this._containerElements.permissionList);
			var $grantPermission = $('<input type="checkbox" id="grant' + $optionID + '" />').wrap('<label for="grant' + $optionID + '" />').appendTo($listItem);
			var $denyPermission = $('<input type="checkbox" id="deny' + $optionID + '" />').wrap('<label for="deny' + $optionID + '" />').appendTo($listItem);

			$grantPermission.data('type', 'grant').data('optionID', $optionID).change($.proxy(this._change, this));
			$denyPermission.data('type', 'deny').data('optionID', $optionID).change($.proxy(this._change, this));
		}

		// set groups
		// ...

		// set user
		// ...
		
		this._container.show();
	},

	/**
	 * Prepares permission list for a specific object.
	 * 
	 * @param	object		event
	 */
	_click: function(event) {
		var $listItem  = $(event.currentTarget);
		if ($listItem.hasClass('active')) {
			return;
		}
		
		// save previous permissions
		this._savePermissions();
		
		// switch active item
		this._containerElements.aclList.children('li').removeClass('active');
		$listItem.addClass('active');
		
		// apply permissions for current item
		this._setupPermissions($listItem.data('type'), $listItem.data('objectID'));
	},

	/**
	 * Toggles between deny and grant.
	 * 
	 * @param	object		event
	 */
	_change: function(event) {
		var $checkbox = $(event.currentTarget);
		var $optionID = $checkbox.data('optionID');
		var $type = $checkbox.data('type');
		
		if ($checkbox.is(':checked')) {
			if ($type === 'deny') {
				$('#grant' + $optionID).removeAttr('checked');
			}
			else {
				$('#deny' + $optionID).removeAttr('checked');
			}
		}
	},

	/**
	 * Setups permission input for given object.
	 * 
	 * @param	string		type
	 * @param	integer		objectID
	 */
	_setupPermissions: function(type, objectID) {
		// use stored permissions if applicable
		if (this._values[type] && this._values[type][objectID]) {
			for (var $optionID in this._values[type][objectID]) {
				if (this._values[type][objectID][$optionID]) {
					$('#grant' + $optionID).attr('checked', 'checked');
				}
				else {
					$('#deny' + $optionID).attr('checked', 'checked');
				}
			}
		}
		
		// show permissions
		this._containerElements.permissionList.show();
	},

	/**
	 * Saves currently set permissions.
	 */
	_savePermissions: function() {
		if (this._containerElements.permissionList.is(':hidden')) {
			return;
		}

		// get active object
		var $activeObject = this._containerElements.aclList.find('li.active');
		var $objectID = $activeObject.data('objectID');
		var $type = $activeObject.data('type');

		var self = this;
		this._containerElements.permissionList.find("input[type='checkbox']").each(function(index, checkbox) {
			var $checkbox = $(checkbox);
			if ($checkbox.is(':checked')) {
				var $optionValue = ($checkbox.data('type') === 'deny') ? 0 : 1;
				var $optionID = $checkbox.data('optionID');
				
				if (!self._values[$type][$objectID]) {
					self._values[$type][$objectID] = { };
				}

				// store value
				self._values[$type][$objectID][$optionID] = $optionValue;
				
				// reset value afterwards
				$checkbox.removeAttr('checked');
			}
		});
	},

	/**
	 * Prepares ACL values on submit.
	 * 
	 * @param	object		event
	 */
	submit: function(event) {
		this._savePermissions();

		this._save('group');
		this._save('user');
	},

	/**
	 * Inserts hidden form elements for each value.
	 *
	 * @param	string		$type
	 */
	_save: function($type) {
		if ($.getLength(this._values[$type])) {
			var $form = this._container.parents('form:eq(0)');

			for (var $objectID in this._values[$type]) {
				var $object = this._values[$type][$objectID];

				for (var $optionID in $object) {
					$('<input type="hidden" name="aclValues[' + $type + '][' + $objectID + '][' + $optionID + ']" value="' + $object[$optionID] + '" />').appendTo($form);
				}
			}
		}
	}
};
