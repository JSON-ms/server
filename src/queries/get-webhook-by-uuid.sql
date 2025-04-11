SELECT
    w.*
FROM webhooks AS w
WHERE
    w.uuid = :uuid
    AND w.created_by = :userId
