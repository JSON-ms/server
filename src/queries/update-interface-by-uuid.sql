UPDATE interfaces AS i
SET i.label = ?, i.logo = ?, i.content = ?, i.server_url = ?, i.updated_at = NOW()
WHERE i.uuid = ?
