DELETE FROM interfaces
WHERE uuid = :uuid
    AND created_by = :userId
