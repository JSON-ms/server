SELECT
    i.*,
    u.name AS owner_name
FROM interfaces AS i
         INNER JOIN users AS u ON u.id = i.created_by
WHERE i.hash = "demo"
GROUP BY i.uuid
