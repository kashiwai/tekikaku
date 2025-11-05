-- サンプル会員データ投入
USE net8_dev;

-- テスト会員を追加（パスワードは "admin123" のbcryptハッシュ）
INSERT INTO mst_member (nickname, mail, pass, point, draw_point, loss_count, mail_magazine) VALUES
('やまちゃん', 'test1@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 1000, 0, 0, 0),
('はなちゃん', 'test2@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 2500, 0, 0, 0),
('いっちゃん', 'test3@example.com', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', 500, 0, 0, 0);

SELECT '✅ サンプル会員データを追加しました！' AS status;
SELECT 'Total Members:', COUNT(*) as count FROM mst_member;
