<?php
// Do not Change or remove any values, this used by core

return [
    // General Rules
    'badRequest' => 'Bad Request.',
    'emptyParams' => 'At least one input must be provided except "id" to update data.',
    'dataNotFound' => 'Data not found.',
    'exceptionOccured' => 'An exception has occurred.',
    'unauthorizedAccess' => 'Unauthorized access.',
    'serverError' => 'Server error.',
    'lockVersionOutdated' => 'The data being updated is outdated. Please refresh the page and try again.',
    'unknownError' => 'An unknown error occurred.',

    // General Records Rules
    'createRecordSuccess' => 'Data has been saved successfully.',
    'createRecordFailed' => 'Failed to save data.',
    'updateRecordSuccess' => 'Data has been updated successfully.',
    'updateRecordFailed' => 'Failed to update data.',
    'deleteRecordSuccess' => 'Data has been deleted successfully.',
    'deleteRecordFailed' => 'Failed to delete data.',
    'noRecordDeleted' => 'Failed, Record already deleted.',
    'noRecordUpdated' => 'Failed, no record updated.',

    'create_shipment_doRecordSuccess' => 'Data has been saved successfully.',
    'create_shipment_doRecordFailed' => 'Failed to save data.',
    'scheduledRecordSuccess' => 'Data has been scheduled successfully.',
    'scheduledRecordFailed' => 'Failed to schedule data.',
    'rescheduledRecordSuccess' => 'Data has been rescheduled successfully.',
    'rescheduledRecordFailed' => 'Failed to reschedule data.',
    'departedRecordSuccess' => 'Data has been departed successfully.',
    'departedRecordFailed' => 'Failed to depart data.',
    'deliveredRecordSuccess' => 'Data has been delivered successfully.',
    'deliveredRecordFailed' => 'Failed to deliver data.',
    'delayRecordSuccess' => 'Data has been delayed successfully.',
    'delayRecordFailed' => 'Failed to delay data.',
    'canceledRecordSuccess' => 'Data has been canceled successfully.',
    'canceledRecordFailed' => 'Failed to cancel data.',

    // Field Validation Rules
    'required' => '{label} cannot be blank.',
    'integer' => '{label} must be an integer.',
    'array' => '{label} must be an array.',
    'number' => '{label} must be a number.',
    'validationFailed' => 'Field validation failed.',
    'invalidField' => 'Field {label} not a valid request parameter.',
    'fieldDataNotFound' => '{label} data not found.',
    'extraField' => 'Extra field found in {label}: {field}. Allowed field: {value}.',
    'extraFieldFound' => 'Extra field found in {label}.',
    'missingField' => 'Missing required field: {field}.',
    'missingFieldFound' => 'Missing required field in {label}.',
    'nullField' => '{label} field: {field} is cannot be null or empty.',
    'allowedField' => '{field} can only contain the field {value}.',
    'integerNoZero' => '{label} must be an integer and greater than 0.',


    // General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Page must be greater than 0.',
    
    // Status Update Rules
    'disallowedStatusUpdate' => 'Cannot change status because data already {value}.',
    'cannotChangeStatus' => 'Cannot change status from {value} to {newValue}.',
    'deletedStatusChanged' => 'You do not have permission to change the status from {value} to another status. Admin rights are required.',
    
    // Superadmin Rights Rules
    'superadminOnly' => 'You do not have permission to perform this action.',
    'updatePermission' => 'You do not have permission to update the {label} of this {tableName} because it is referenced in other data.',
];