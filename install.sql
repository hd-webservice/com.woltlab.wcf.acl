DROP TABLE IF EXISTS wcf1_acl_option;
CREATE TABLE wcf1_acl_option (
	optionID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	packageID INT(10) NOT NULL,
	objectTypeID INT(10) NOT NULL,
	optionName VARCHAR(255) NOT NULL,
	UNIQUE KEY (packageID, objectTypeID, optionName)
);

DROP TABLE IF EXISTS wcf1_acl_option_to_user;
CREATE TABLE wcf1_acl_option_to_user (
	optionID INT(10) NOT NULL,
	objectID INT(10) NOT NULL,
	userID INT(10) NOT NULL,
	optionValue TINYINT(1) NOT NULL DEFAULT 0,
	UNIQUE KEY userID (userID, objectID, optionID)
);

DROP TABLE IF EXISTS wcf1_acl_option_to_group;
CREATE TABLE wcf1_acl_option_to_group (
	optionID INT(10) NOT NULL,
	objectID INT(10) NOT NULL,
	groupID INT(10) NOT NULL,
	optionValue TINYINT(1) NOT NULL DEFAULT 0,
	UNIQUE KEY groupID (groupID, objectID, optionID)
);

ALTER TABLE wcf1_acl_option ADD FOREIGN KEY (packageID) REFERENCES wcf1_package (packageID) ON DELETE CASCADE;
ALTER TABLE wcf1_acl_option ADD FOREIGN KEY (objectTypeID) REFERENCES wcf1_object_type (objectTypeID) ON DELETE CASCADE;

ALTER TABLE wcf1_acl_option_to_user ADD FOREIGN KEY (optionID) REFERENCES wcf1_acl_option (optionID) ON DELETE CASCADE;
ALTER TABLE wcf1_acl_option_to_user ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;

ALTER TABLE wcf1_acl_option_to_group ADD FOREIGN KEY (optionID) REFERENCES wcf1_acl_option (optionID) ON DELETE CASCADE;
ALTER TABLE wcf1_acl_option_to_group ADD FOREIGN KEY (groupID) REFERENCES wcf1_user_group (groupID) ON DELETE CASCADE;