<?php

/**
 * Parameters configuration for the application.
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

/**
 * is default value for database fields in model and table migration
 */
$params['dbDefault'] = [
    'createdBy' => 'system',
    'masterID' => null,
    'syncMaster' => 1,
    'slaveID' => null,
    'syncSlave' => 1,
    'currency' => 'IDR',
    'optimisticLockingComment' => 'Optimistic Locking',
    'syncMdbComment' => '1: unsync, null: synced',
    'syncMasterComment' => '1: unsync, null: synced',
    'syncSlaveComment' => '1: unsync, null: synced',
    'statusComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance',
    'purchaseComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance, 6: Approved, 7: Rejected',
    'skipMigrateFresh' => 'Skipping migration/fresh for non-dev environment.\n',
];

/**
 * is default value for json fields in model
 */
$params['defaultValue'] = [
    
];

/**
 * is allowed fields for json data in model
 */
$params['allowedFields'] = [
    
];

/**
 * checking table field if already used in another table for update action.
 */
$params['dependenciesUpdate'] = [
    
];

/**
 * any prefix for table field unique data.
 */
$params['prefix'] = [
    
];

$params['settings'] = [
    
];

return $params;