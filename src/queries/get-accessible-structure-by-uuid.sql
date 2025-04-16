SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_structure,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name
FROM structures AS i
    INNER JOIN users AS u ON u.id = i.created_by
    LEFT JOIN permissions AS pa ON pa.structure_uuid = i.uuid AND pa.type = 'admin'
    LEFT JOIN permissions AS pi ON pi.structure_uuid = i.uuid AND pi.type = 'structure'
WHERE i.uuid = :uuid
  AND (i.created_by = :userId OR EXISTS (
    SELECT 1
    FROM permissions AS p
        JOIN users AS up ON up.email = p.email
    WHERE p.structure_uuid = i.uuid AND up.id = :userId
))
GROUP BY
    i.uuid, u.name;
