UPDATE endpoints AS e
SET e.url = :url, e.updated_at = NOW()
WHERE e.uuid = :uuid
    AND e.created_by = :userId
