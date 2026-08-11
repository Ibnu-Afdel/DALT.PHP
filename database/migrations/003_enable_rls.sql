ALTER TABLE posts ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON posts
USING (tenant_id = current_setting('app.tenant_id')::INT);
