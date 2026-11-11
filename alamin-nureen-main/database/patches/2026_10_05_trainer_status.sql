-- TrainHub Al Amin - trainer active/deactivate support
-- Safe to run against the combined MySQL database even when `status` already exists.

SET @db_name = DATABASE();

SET @sql_add_status = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `trainer` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT ''active'' AFTER `averageRating`',
        'SELECT ''trainer.status already exists'' AS info'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'trainer'
      AND COLUMN_NAME = 'status'
);
PREPARE stmt_add_status FROM @sql_add_status;
EXECUTE stmt_add_status;
DEALLOCATE PREPARE stmt_add_status;

SET @sql_add_remark = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `trainer` ADD COLUMN `deactivationRemark` text NULL AFTER `status`',
        'SELECT ''trainer.deactivationRemark already exists'' AS info'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'trainer'
      AND COLUMN_NAME = 'deactivationRemark'
);
PREPARE stmt_add_remark FROM @sql_add_remark;
EXECUTE stmt_add_remark;
DEALLOCATE PREPARE stmt_add_remark;
