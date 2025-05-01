DELETE FROM endpoints
WHERE uuid = :uuid
    AND created_by = :userId
