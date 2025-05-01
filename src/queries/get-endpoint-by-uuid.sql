SELECT
    e.*
FROM endpoints AS e
WHERE
    e.uuid = :uuid
    AND e.created_by = :userId
