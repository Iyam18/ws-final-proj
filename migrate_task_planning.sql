USE daymark;

SET @add_priority = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'priority') = 0,
  'ALTER TABLE tasks ADD COLUMN priority ENUM(''low'', ''medium'', ''high'') NOT NULL DEFAULT ''medium'' AFTER status',
  'SELECT ''tasks.priority already exists'''
);
PREPARE add_priority_statement FROM @add_priority;
EXECUTE add_priority_statement;
DEALLOCATE PREPARE add_priority_statement;

SET @add_due_date = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'due_date') = 0,
  'ALTER TABLE tasks ADD COLUMN due_date DATE NULL AFTER priority',
  'SELECT ''tasks.due_date already exists'''
);
PREPARE add_due_date_statement FROM @add_due_date;
EXECUTE add_due_date_statement;
DEALLOCATE PREPARE add_due_date_statement;

SET @add_due_date_index = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_tasks_user_status_due') = 0,
  'ALTER TABLE tasks ADD INDEX idx_tasks_user_status_due (user_id, status, due_date)',
  'SELECT ''idx_tasks_user_status_due already exists'''
);
PREPARE add_due_date_index_statement FROM @add_due_date_index;
EXECUTE add_due_date_index_statement;
DEALLOCATE PREPARE add_due_date_index_statement;

SET @drop_old_index = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_tasks_user_status_created') > 0,
  'ALTER TABLE tasks DROP INDEX idx_tasks_user_status_created',
  'SELECT ''idx_tasks_user_status_created already removed'''
);
PREPARE drop_old_index_statement FROM @drop_old_index;
EXECUTE drop_old_index_statement;
DEALLOCATE PREPARE drop_old_index_statement;
