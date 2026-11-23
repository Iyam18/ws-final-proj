USE daymark;

CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(50) NOT NULL,
  color VARCHAR(7) NOT NULL DEFAULT '#5f8f6b',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_user_name (user_id, name),
  CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET @add_project_id = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'project_id') = 0,
  'ALTER TABLE tasks ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER due_date',
  'SELECT ''tasks.project_id already exists'''
);
PREPARE add_project_id_statement FROM @add_project_id;
EXECUTE add_project_id_statement;
DEALLOCATE PREPARE add_project_id_statement;

SET @add_recurrence = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'recurrence') = 0,
  'ALTER TABLE tasks ADD COLUMN recurrence ENUM(''none'', ''daily'', ''weekly'', ''monthly'') NOT NULL DEFAULT ''none'' AFTER project_id',
  'SELECT ''tasks.recurrence already exists'''
);
PREPARE add_recurrence_statement FROM @add_recurrence;
EXECUTE add_recurrence_statement;
DEALLOCATE PREPARE add_recurrence_statement;

SET @add_sort_order = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'sort_order') = 0,
  'ALTER TABLE tasks ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER recurrence',
  'SELECT ''tasks.sort_order already exists'''
);
PREPARE add_sort_order_statement FROM @add_sort_order;
EXECUTE add_sort_order_statement;
DEALLOCATE PREPARE add_sort_order_statement;

SET @add_completed_at = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'completed_at') = 0,
  'ALTER TABLE tasks ADD COLUMN completed_at DATETIME NULL AFTER sort_order',
  'SELECT ''tasks.completed_at already exists'''
);
PREPARE add_completed_at_statement FROM @add_completed_at;
EXECUTE add_completed_at_statement;
DEALLOCATE PREPARE add_completed_at_statement;

SET @add_task_order_index = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_tasks_user_order') = 0,
  'ALTER TABLE tasks ADD INDEX idx_tasks_user_order (user_id, sort_order)',
  'SELECT ''idx_tasks_user_order already exists'''
);
PREPARE add_task_order_index_statement FROM @add_task_order_index;
EXECUTE add_task_order_index_statement;
DEALLOCATE PREPARE add_task_order_index_statement;

SET @add_project_index = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_tasks_project') = 0,
  'ALTER TABLE tasks ADD INDEX idx_tasks_project (project_id)',
  'SELECT ''idx_tasks_project already exists'''
);
PREPARE add_project_index_statement FROM @add_project_index;
EXECUTE add_project_index_statement;
DEALLOCATE PREPARE add_project_index_statement;

SET @add_project_fk = IF(
  (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND CONSTRAINT_NAME = 'fk_tasks_project') = 0,
  'ALTER TABLE tasks ADD CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL',
  'SELECT ''fk_tasks_project already exists'''
);
PREPARE add_project_fk_statement FROM @add_project_fk;
EXECUTE add_project_fk_statement;
DEALLOCATE PREPARE add_project_fk_statement;

UPDATE tasks SET completed_at = updated_at WHERE status = 'completed' AND completed_at IS NULL;
