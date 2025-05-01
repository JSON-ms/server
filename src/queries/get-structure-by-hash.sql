SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_structure,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name,
    e.url AS server_url,
    e.secret AS server_secret
FROM structures AS i
    INNER JOIN users AS u ON u.id = i.created_by
    LEFT JOIN permissions AS pa ON pa.structure_uuid = i.uuid AND pa.type = 'admin'
    LEFT JOIN permissions AS pi ON pi.structure_uuid = i.uuid AND pi.type = 'structure'
    LEFT JOIN endpoints AS e ON e.uuid = i.endpoint
WHERE i.hash = :hash
GROUP BY
    i.uuid, u.name;
