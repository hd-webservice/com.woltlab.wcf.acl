<?php
namespace wcf\system\package\plugin;
use wcf\system\exception\SystemException;
use wcf\system\WCF;

/**
 * This PIP installs, updates or deletes acl options.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.acl
 * @subpackage	system.package.plugin
 * @category 	Community Framework
 */
class ACLOptionPackageInstallationPlugin extends AbstractXMLPackageInstallationPlugin {
	/**
	 * @see	wcf\system\package\plugin\AbstractXMLPackageInstallationPlugin::$className
	 */
	public $className = 'wcf\data\acl\option\ACLOptionEditor';
	
	/**
	 * @see	wcf\system\package\plugin\AbstractPackageInstallationPlugin::$tableName
	 */
	public $tableName = 'acl_option';
	
	/**
	 * @see	wcf\system\package\plugin\AbstractXMLPackageInstallationPlugin::$tagName
	 */	
	public $tagName = 'option';
	
	/**
	 * @see	wcf\system\package\plugin\AbstractXMLPackageInstallationPlugin::handleDelete()
	 */
	protected function handleDelete(array $items) {
		$sql = "SELECT		object_type.objectTypeID
			FROM		wcf".WCF_N."_package_dependency package_dependency,
					wcf".WCF_N."_object_type object_type
			WHERE		object_type.packageID = package_dependency.dependency
					AND package_dependency.packageID = ?
					AND object_type.objectType = ?
					AND object_type.definitionID IN (
						SELECT	definitionID
						FROM	wcf".WCF_N."_object_type_definition
						WHERE	definitionName = 'com.woltlab.wcf.acl'
					)
			ORDER BY	package_dependency.priority DESC";
		$statement1 = WCF::getDB()->prepareStatement($sql, 1);
		
		$sql = "DELETE FROM	wcf".WCF_N."_".$this->tableName."
			WHERE		packageID = ?
					AND objectTypeID = ?
					AND optionName = ?";
		$statement2 = WCF::getDB()->prepareStatement($sql);
		
		foreach ($items as $item) {
			$statement1->execute(array($this->installation->getPackageID(), $item['elements']['objecttype']));
			$row = $statement1->fetchArray();
			if (empty($row['objectTypeID'])) throw new SystemException("unknown object type '".$item['elements']['objecttype']."' given");
			$objectTypeID = $row['objectTypeID'];
			
			$statement2->execute(array(
				$this->installation->getPackageID(),
				$objectTypeID,
				$item['attributes']['name']
			));
		}
	}
	
	/**
	 * @see	wcf\system\package\plugin\AbstractXMLPackageInstallationPlugin::prepareImport()
	 */
	protected function prepareImport(array $data) {
		// get object type id
		$sql = "SELECT		object_type.objectTypeID
			FROM		wcf".WCF_N."_package_dependency package_dependency,
					wcf".WCF_N."_object_type object_type
			WHERE		object_type.packageID = package_dependency.dependency
					AND package_dependency.packageID = ?
					AND object_type.objectType = ?
					AND object_type.definitionID IN (
						SELECT	definitionID
						FROM	wcf".WCF_N."_object_type_definition
						WHERE	definitionName = 'com.woltlab.wcf.acl'
					)
			ORDER BY	package_dependency.priority DESC";
		$statement = WCF::getDB()->prepareStatement($sql, 1);
		$statement->execute(array($this->installation->getPackageID(), $data['elements']['objecttype']));
		$row = $statement->fetchArray();
		if (empty($row['objectTypeID'])) throw new SystemException("unknown object type '".$data['elements']['objecttype']."' given");
		$objectTypeID = $row['objectTypeID'];
		
		return array(
			'optionName' => $data['attributes']['name'],
			'objectTypeID' => $objectTypeID
		);
	}
	
	/**
	 * @see	wcf\system\package\plugin\AbstractXMLPackageInstallationPlugin::findExistingItem()
	 */
	protected function findExistingItem(array $data) {
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_".$this->tableName."
			WHERE	packageID = ?
				AND objectTypeID = ?
				AND optionName = ?";
		$parameters = array(
			$this->installation->getPackageID(),
			$data['objectTypeID'],
			$data['optionName']
		);
		
		return array(
			'sql' => $sql,
			'parameters' => $parameters
		);
	}
}
