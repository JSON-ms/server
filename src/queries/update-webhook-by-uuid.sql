UPDATE webhooks AS w
SET w.url = :url, w.updated_at = NOW()
WHERE w.uuid = :uuid
    AND w.created_by = :userId
