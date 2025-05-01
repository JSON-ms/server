UPDATE structures AS i
SET i.label = :label, i.logo = :logo, i.content = :content, i.endpoint = :endpoint, i.updated_at = NOW()
WHERE i.uuid = :uuid
