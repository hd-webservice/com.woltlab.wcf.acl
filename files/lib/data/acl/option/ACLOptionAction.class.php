<?php
namespace wcf\data\acl\option;
use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes acl option-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.acl
 * @subpackage	data.acl.option
 * @category 	Community Framework
 */
class ACLOptionAction extends AbstractDatabaseObjectAction {
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\acl\option\ACLOptionEditor';
}
