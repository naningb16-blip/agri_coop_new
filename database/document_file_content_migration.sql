ALTER TABLE routed_documents
    ADD COLUMN IF NOT EXISTS file_content LONGBLOB NULL;
