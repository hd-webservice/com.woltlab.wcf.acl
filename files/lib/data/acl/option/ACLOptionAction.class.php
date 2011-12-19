<?php
namespace wcf\data\acl\option;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\package\PackageDependencyHandler;
use wcf\system\WCF;

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
	
	public function validateLoadAll() { }
	
	public function loadAll() {
		$data = array();
		
		// get acl options for this object type
		$optionList = new ACLOptionList();
		$optionList->getConditionBuilder()->add("acl_option.objectTypeID = ?", array($this->parameters['data']['objectTypeID']));
		$optionList->getConditionBuilder()->add("acl_option.packageID IN (?)", array(PackageDependencyHandler::getDependencies()));
		$optionList->sqlLimit = 0;
		$optionList->readObjects();
		$aclOptions = $optionList->getObjects();
		
		if (!empty($aclOptions)) {
			$aclOptionIDs = array();
			foreach ($aclOptions as $aclOption) {
				$aclOptionIDs[] = $aclOption->optionID;
				
				$data[$aclOption->optionID] = array(
					'optionName' => $aclOption->optionName,
					'values' => array()
				);
			}
			
			if (!empty($this->parameters['data']['objectIDs'])) {
				// get group values
				$this->getOptionValues('group', $aclOptionIDs, $data);
				
				// get user values
				$this->getOptionValues('user', $aclOptionIDs, $data);
			}
		}
		
		return $data;
	}
	
	protected function getOptionValues($type, array $aclOptionIDs, array &$data) {
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("optionID IN (?)", array($aclOptionIDs));
		$conditions->add("objectID IN (?)", array($this->parameters['data']['objectIDs']));
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_acl_option_to_".$type."
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		while ($row = $statement->fetchArray()) {
			if (!isset($data[$row['optionID']]['values'][$row['objectID']])) {
				$data[$row['optionID']]['values'][$row['objectID']] = array(
					'group' => array(),
					'user' => array()
				);
			}
			
			$data[$row['optionID']]['values'][$row['objectID']][$type][] = array(
				$type.'ID' => $row[$type.'ID'],
				'optionValue' => $row['optionValue']
			);
		}
	}
}
