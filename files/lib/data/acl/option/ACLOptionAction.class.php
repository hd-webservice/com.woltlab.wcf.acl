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
		$data = array(
			'options' => array(),
			'values' => array(
				'group' => array(),
				'user' => array()
			)
		);
		
		// get acl options for this object type
		$optionList = new ACLOptionList();
		$optionList->sqlSelects = "package.package";
		$optionList->sqlJoins = "LEFT JOIN wcf".WCF_N."_package package ON (package.packageID = acl_option.packageID)";
		$optionList->getConditionBuilder()->add("acl_option.objectTypeID = ?", array($this->parameters['data']['objectTypeID']));
		$optionList->getConditionBuilder()->add("acl_option.packageID IN (?)", array(PackageDependencyHandler::getDependencies()));
		$optionList->sqlLimit = 0;
		$optionList->readObjects();
		$aclOptions = $optionList->getObjects();
		
		if (!empty($aclOptions)) {
			$aclOptionIDs = array();
			foreach ($aclOptions as $aclOption) {
				$aclOptionIDs[] = $aclOption->optionID;
				
				$data['options'][$aclOption->optionID] = array(
					'label' => WCF::getLanguage()->get('wcf.acl.option.' . $aclOption->package . '.' . $aclOption->optionName),
					'optionName' => $aclOption->optionName
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
			if (!isset($data['values'][$type][$row[$type.'ID']])) {
				$data['values'][$type][$row[$type.'ID']] = array();
			}
			
			$data['values'][$type][$row[$type.'ID']][$row['optionID']] = $row['optionValue'];
		}
	}
}
