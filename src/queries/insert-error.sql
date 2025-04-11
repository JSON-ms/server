INSERT INTO errors (`key`, `message`, `source`, `line`, `column`, `stack`, `occurred_on`, `last_timestamp`, `version`, `route`, `count`, `user_agent`, `created_by`)
VALUES (:key, :message, :source, :line, :column, :stack, :occurred_on, :last_timestamp, :version, :route, :count, :user_agent, :created_by)
ON DUPLICATE KEY UPDATE `count` = `count` + :count, `updated_at` = NOW();