DELETE FROM webhooks
WHERE uuid = :uuid
    AND created_by = :userId
