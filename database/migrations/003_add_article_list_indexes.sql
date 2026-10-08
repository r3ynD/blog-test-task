CREATE INDEX idx_articles_published ON articles (published_at, id);
CREATE INDEX idx_articles_views ON articles (views, published_at, id);
