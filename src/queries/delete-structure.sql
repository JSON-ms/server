DELETE FROM structures
WHERE uuid = :uuid
    AND created_by = :userId
