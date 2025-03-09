SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_interface,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name
FROM interfaces AS i
    INNER JOIN users AS u ON u.id = i.created_by
    LEFT JOIN permissions AS pa ON pa.interface_uuid = i.uuid AND pa.type = 'admin'
    LEFT JOIN permissions AS pi ON pi.interface_uuid = i.uuid AND pi.type = 'interface'
WHERE i.hash = ?
