<?php
namespace wcf\system\acl;
use wcf\data\acl\option\ACLOption;
use wcf\data\acl\option\ACLOptionList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\SystemException;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

class ACLHandler extends SingletonFactory {
	/**
	 * list of available object types
	 * @var array
	 */
	protected $availableObjectTypes = array();
	
	/**
	 * @see wcf\system\SingletonFactory::init()
	 */
	protected function init() {
		// get available object types
		$this->availableObjectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.woltlab.wcf.acl');
	}
	
	/**
	 * Gets the object type id.
	 * 
	 * @param	string 		$objectType
	 * @return	integer
	 */
	public function getObjectTypeID($objectType) {
		if (!isset($this->availableObjectTypes[$objectType])) {
			throw new SystemException("unknown object type '".$objectType."'");
		}
		
		return $this->availableObjectTypes[$objectType]->objectTypeID;
	}
	
	/**
	 * Saves acl for a given object.
	 * 
	 * @param	integer		$objectID
	 * @param	integer		$objectTypeID
	 */
	public function save($objectID, $objectTypeID) {
		// get options
		$optionList = ACLOption::getOptions($objectTypeID);
		
		$this->replaceValues($optionList, 'group', $objectID);
		$this->replaceValues($optionList, 'user', $objectID);
	}
	
	/**
	 * Replaces values for given type and object.
	 * 
	 * @param	wcf\data\acl\option\ACLOptionList	$optionList
	 * @param	string					$type
	 * @param	integer					$objectID
	 */
	protected function replaceValues(ACLOptionList $optionList, $type, $objectID) {
		$options = $optionList->getObjects();
		
		// remove previous values
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("optionID IN (?)", array(array_keys($options)));
		$conditions->add("objectID = ?", array($objectID));
		
		$sql = "DELETE FROM	wcf".WCF_N."_acl_option_to_".$type."
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		// add new values if given
		if (!isset($_POST['aclValues']) || !isset($_POST['aclValues'][$type])) {
			return;
		}
		
		$sql = "INSERT INTO	wcf".WCF_N."_acl_option_to_".$type."
					(optionID, objectID, ".$type."ID, optionValue)
			VALUES		(?, ?, ?, ?)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$values =& $_POST['aclValues'][$type];
		
		WCF::getDB()->beginTransaction();
		foreach ($values as $typeID => $optionData) {
			foreach ($optionData as $optionID => $optionValue) {
				// ignore invalid option ids
				if (!isset($options[$optionID])) {
					continue;
				}
				
				$statement->execute(array(
					$optionID,
					$objectID,
					$typeID,
					$optionValue
				));
			}
		}
		WCF::getDB()->commitTransaction();
	}
}
