UPDATE interfaces AS i
SET i.label = :label, i.logo = :logo, i.content = :content, i.server_url = :server_url, i.updated_at = NOW()
WHERE i.uuid = :uuid
