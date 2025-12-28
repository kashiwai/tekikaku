-- ====================================================================
-- NET8 実際に使用中のスロット機種 翻訳データ投入
-- 作成日: 2025-12-28
-- 翻訳方法: Claude Code自動翻訳
-- 対象機種: 11機種
--   吉宗4号機、北斗の拳4号機、押忍番長、カイジ4号機、南国物語
--   ジャグラー、ファイヤードリフト、ビンゴ、銭形、島唄、鬼武者
-- ====================================================================

-- ====================================================================
-- 【重要】実行前の準備
-- 1. database_migration_multilingual.sql を先に実行してください
-- 2. バックアップを取得してください:
--    mysqldump -u user -p database mst_model > mst_model_backup_$(date +%Y%m%d).sql
-- ====================================================================

-- ====================================================================
-- 実際に使用中のスロット機種（5機種）
-- ====================================================================

-- 1. 吉宗4号機（時代劇・徳川将軍）
UPDATE mst_model
SET
    model_name_ja = '吉宗4号機',
    model_name_ko = '요시무네 4호기',
    model_name_en = 'Yoshimune 4th Generation',
    description_ja = '徳川八代将軍・吉宗をテーマにした人気時代劇スロット機種の第4世代',
    description_ko = '도쿠가와 8대 쇼군 요시무네를 테마로 한 인기 시대극 슬롯 기종의 4세대',
    description_en = 'Fourth generation of the popular period drama slot machine themed around Tokugawa Yoshimune, the 8th Tokugawa Shogun'
WHERE model_name LIKE '%吉宗%' AND model_name LIKE '%4号機%';

-- 吉宗シリーズ全般（4号機以外も含む）
UPDATE mst_model
SET
    model_name_ja = COALESCE(model_name_ja, model_name),
    model_name_ko = COALESCE(model_name_ko, REPLACE(model_name, '吉宗', '요시무네')),
    model_name_en = COALESCE(model_name_en, REPLACE(model_name, '吉宗', 'Yoshimune')),
    description_ja = COALESCE(description_ja, '徳川八代将軍・吉宗をテーマにした時代劇スロット機種'),
    description_ko = COALESCE(description_ko, '도쿠가와 8대 쇼군 요시무네를 테마로 한 시대극 슬롯 기종'),
    description_en = COALESCE(description_en, 'Period drama slot machine themed around Tokugawa Yoshimune, the 8th Tokugawa Shogun')
WHERE model_name LIKE '%吉宗%' AND (model_name_ko IS NULL OR model_name_en IS NULL);


-- 2. 北斗の拳4号機（漫画・格闘）
UPDATE mst_model
SET
    model_name_ja = '北斗の拳4号機',
    model_name_ko = '북두의 권 4호기',
    model_name_en = 'Fist of the North Star 4th Generation',
    description_ja = '世紀末を舞台にした伝説的格闘漫画「北斗の拳」のスロット機種第4世代。圧倒的人気を誇る名機',
    description_ko = '세기말을 배경으로 한 전설적 격투 만화 "북두의 권"의 슬롯 기종 4세대. 압도적 인기를 자랑하는 명기',
    description_en = 'Fourth generation slot machine based on the legendary post-apocalyptic martial arts manga "Fist of the North Star". A legendary machine with overwhelming popularity'
WHERE model_name LIKE '%北斗の拳%' AND model_name LIKE '%4号機%';

-- 北斗の拳シリーズ全般
UPDATE mst_model
SET
    model_name_ja = COALESCE(model_name_ja, model_name),
    model_name_ko = COALESCE(model_name_ko, REPLACE(model_name, '北斗の拳', '북두의 권')),
    model_name_en = COALESCE(model_name_en, REPLACE(model_name, '北斗の拳', 'Fist of the North Star')),
    description_ja = COALESCE(description_ja, '伝説的格闘漫画「北斗の拳」のスロット機種'),
    description_ko = COALESCE(description_ko, '전설적 격투 만화 "북두의 권"의 슬롯 기종'),
    description_en = COALESCE(description_en, 'Slot machine based on the legendary martial arts manga "Fist of the North Star"')
WHERE model_name LIKE '%北斗の拳%' AND (model_name_ko IS NULL OR model_name_en IS NULL);


-- 3. 押忍番長（オリジナル・不良学生）
UPDATE mst_model
SET
    model_name_ja = '押忍！番長',
    model_name_ko = '오스! 반장',
    model_name_en = 'Osu! Banchou',
    description_ja = '不良学生の「番長」をテーマにしたコミカルな人気スロット機種。熱い男の友情と対決を描く',
    description_ko = '불량 학생의 "두목"을 테마로 한 코믹한 인기 슬롯 기종. 뜨거운 남자의 우정과 대결을 그림',
    description_en = 'Popular comical slot machine themed around delinquent student gang leaders. Depicts passionate male friendship and confrontations'
WHERE model_name LIKE '%押忍%' OR model_name LIKE '%番長%';


-- 4. カイジ4号機（漫画・ギャンブル）
UPDATE mst_model
SET
    model_name_ja = 'カイジ4号機',
    model_name_ko = '카이지 4호기',
    model_name_en = 'Kaiji 4th Generation',
    description_ja = '福本伸行のギャンブル漫画「賭博黙示録カイジ」のスロット機種第4世代。緊張感あふれる演出が特徴',
    description_ko = '후쿠모토 노부유키의 도박 만화 "도박묵시록 카이지"의 슬롯 기종 4세대. 긴장감 넘치는 연출이 특징',
    description_en = 'Fourth generation slot machine based on Nobuyuki Fukumoto\'s gambling manga "Kaiji". Features intense and suspenseful production'
WHERE model_name LIKE '%カイジ%' AND model_name LIKE '%4号機%';

-- カイジシリーズ全般
UPDATE mst_model
SET
    model_name_ja = COALESCE(model_name_ja, model_name),
    model_name_ko = COALESCE(model_name_ko, REPLACE(model_name, 'カイジ', '카이지')),
    model_name_en = COALESCE(model_name_en, REPLACE(model_name, 'カイジ', 'Kaiji')),
    description_ja = COALESCE(description_ja, 'ギャンブル漫画「カイジ」のスロット機種'),
    description_ko = COALESCE(description_ko, '도박 만화 "카이지"의 슬롯 기종'),
    description_en = COALESCE(description_en, 'Slot machine based on the gambling manga "Kaiji"')
WHERE model_name LIKE '%カイジ%' AND (model_name_ko IS NULL OR model_name_en IS NULL);


-- 5. 南国物語（オリジナル・リゾート）
UPDATE mst_model
SET
    model_name_ja = '南国物語',
    model_name_ko = '남국 이야기',
    model_name_en = 'Nangoku Monogatari (South Paradise Story)',
    description_ja = '南国リゾートをテーマにした癒し系スロット機種。トロピカルな雰囲気とハイビスカスが特徴',
    description_ko = '남국 리조트를 테마로 한 힐링계 슬롯 기종. 트로피컬한 분위기와 히비스커스가 특징',
    description_en = 'Healing-themed slot machine set in a tropical southern resort. Features tropical atmosphere and hibiscus flowers'
WHERE model_name LIKE '%南国物語%';


-- 6. ジャグラー（オリジナル・定番機）
UPDATE mst_model
SET
    model_name_ja = 'ジャグラー',
    model_name_ko = '저글러',
    model_name_en = 'Juggler',
    description_ja = '不動の人気を誇るスロット界の定番機種。シンプルなゲーム性とGOGOランプが特徴',
    description_ko = '확고한 인기를 자랑하는 슬롯계의 정통 기종. 심플한 게임성과 GOGO 램프가 특징',
    description_en = 'Classic slot machine with unwavering popularity in the pachislot world. Features simple gameplay and the iconic GOGO lamp'
WHERE model_name LIKE '%ジャグラー%';


-- 7. ファイヤードリフト（オリジナル・カーレース）
UPDATE mst_model
SET
    model_name_ja = 'ファイヤードリフト',
    model_name_ko = '파이어 드리프트',
    model_name_en = 'Fire Drift',
    description_ja = 'カーレースとドリフトをテーマにした爽快感あふれるスロット機種',
    description_ko = '카레이스와 드리프트를 테마로 한 상쾌함 넘치는 슬롯 기종',
    description_en = 'Exhilarating slot machine themed around car racing and drifting'
WHERE model_name LIKE '%ファイヤードリフト%' OR model_name LIKE '%ファイヤードラフト%';


-- 8. ビンゴ（オリジナル・ビンゴゲーム）
UPDATE mst_model
SET
    model_name_ja = 'ビンゴ',
    model_name_ko = '빙고',
    model_name_en = 'Bingo',
    description_ja = 'ビンゴゲームをモチーフにしたユニークなスロット機種',
    description_ko = '빙고 게임을 모티프로 한 독특한 슬롯 기종',
    description_en = 'Unique slot machine themed around the bingo game'
WHERE model_name LIKE '%ビンゴ%';


-- 9. 銭形（アニメ・ルパン三世）
UPDATE mst_model
SET
    model_name_ja = '銭形',
    model_name_ko = '제니가타',
    model_name_en = 'Zenigata',
    description_ja = 'ルパン三世シリーズの名キャラクター・銭形警部をテーマにしたスロット機種',
    description_ko = '루팡 3세 시리즈의 명캐릭터 제니가타 경부를 테마로 한 슬롯 기종',
    description_en = 'Slot machine themed around Inspector Zenigata, the iconic character from Lupin the Third series'
WHERE model_name LIKE '%銭形%';


-- 10. 島唄（オリジナル・沖縄）
UPDATE mst_model
SET
    model_name_ja = '島唄',
    model_name_ko = '시마우타',
    model_name_en = 'Shimauta (Island Song)',
    description_ja = '沖縄の伝統音楽「島唄」をテーマにした南国スロット機種',
    description_ko = '오키나와의 전통 음악 "시마우타"를 테마로 한 남국 슬롯 기종',
    description_en = 'Southern island-themed slot machine based on Okinawan traditional music "Shimauta"'
WHERE model_name LIKE '%島唄%';


-- 11. 鬼武者（ゲーム・戦国アクション）
UPDATE mst_model
SET
    model_name_ja = '鬼武者',
    model_name_ko = '오니무샤',
    model_name_en = 'Onimusha',
    description_ja = 'カプコンの人気アクションゲーム「鬼武者」のスロット機種。戦国時代と鬼のバトルを描く',
    description_ko = '캡콤의 인기 액션 게임 "오니무샤"의 슬롯 기종. 전국시대와 귀신의 전투를 그림',
    description_en = 'Slot machine based on Capcom\'s popular action game "Onimusha". Depicts battles between samurai and demons in the Sengoku period'
WHERE model_name LIKE '%鬼武者%';


-- ====================================================================
-- 一般的なスロット用語の翻訳（補助処理）
-- ====================================================================

-- 「号機」表記の統一処理
UPDATE mst_model
SET
    model_name_ko = REPLACE(model_name_ko, '号機', '호기'),
    model_name_en = REPLACE(model_name_en, '号機', 'th Generation')
WHERE model_name LIKE '%号機%'
  AND (model_name_ko LIKE '%号機%' OR model_name_en LIKE '%号機%');

-- 「ぱちスロ」プレフィックスの処理
UPDATE mst_model
SET
    model_name_ko = REPLACE(model_name_ko, 'ぱちスロ', '파치슬롯'),
    model_name_en = REPLACE(model_name_en, 'ぱちスロ', 'Pachislot')
WHERE model_name LIKE '%ぱちスロ%'
  AND (model_name_ko LIKE '%ぱちスロ%' OR model_name_en LIKE '%ぱちスロ%');


-- ====================================================================
-- 実行後の確認クエリ
-- ====================================================================

-- 1. 翻訳済みデータの確認
SELECT
    model_no,
    model_cd,
    model_name AS '元の名前',
    model_name_ja AS '日本語',
    model_name_ko AS '韓国語',
    model_name_en AS '英語'
FROM mst_model
WHERE model_name LIKE '%吉宗%'
   OR model_name LIKE '%北斗の拳%'
   OR model_name LIKE '%押忍%'
   OR model_name LIKE '%番長%'
   OR model_name LIKE '%カイジ%'
   OR model_name LIKE '%南国物語%'
   OR model_name LIKE '%ジャグラー%'
   OR model_name LIKE '%ファイヤードリフト%'
   OR model_name LIKE '%ファイヤードラフト%'
   OR model_name LIKE '%ビンゴ%'
   OR model_name LIKE '%銭形%'
   OR model_name LIKE '%島唄%'
   OR model_name LIKE '%鬼武者%'
ORDER BY model_no;

-- 2. 翻訳状態の確認
SELECT
    COUNT(*) as '総対象機種',
    SUM(CASE WHEN model_name_ko IS NOT NULL AND model_name_ko != '' THEN 1 ELSE 0 END) as '韓国語翻訳完了',
    SUM(CASE WHEN model_name_en IS NOT NULL AND model_name_en != '' THEN 1 ELSE 0 END) as '英語翻訳完了',
    SUM(CASE WHEN description_ko IS NOT NULL AND description_ko != '' THEN 1 ELSE 0 END) as '韓国語説明完了',
    SUM(CASE WHEN description_en IS NOT NULL AND description_en != '' THEN 1 ELSE 0 END) as '英語説明完了'
FROM mst_model
WHERE model_name LIKE '%吉宗%'
   OR model_name LIKE '%北斗の拳%'
   OR model_name LIKE '%押忍%'
   OR model_name LIKE '%番長%'
   OR model_name LIKE '%カイジ%'
   OR model_name LIKE '%南国物語%'
   OR model_name LIKE '%ジャグラー%'
   OR model_name LIKE '%ファイヤードリフト%'
   OR model_name LIKE '%ファイヤードラフト%'
   OR model_name LIKE '%ビンゴ%'
   OR model_name LIKE '%銭形%'
   OR model_name LIKE '%島唄%'
   OR model_name LIKE '%鬼武者%';

-- 3. 未翻訳機種の確認（念のため）
SELECT
    model_no,
    model_cd,
    model_name AS '未翻訳の機種名',
    CASE
        WHEN model_name_ko IS NULL OR model_name_ko = '' THEN '韓国語未翻訳'
        WHEN model_name_en IS NULL OR model_name_en = '' THEN '英語未翻訳'
        ELSE '翻訳済み'
    END as '状態'
FROM mst_model
WHERE (
    model_name LIKE '%吉宗%'
    OR model_name LIKE '%北斗の拳%'
    OR model_name LIKE '%押忍%'
    OR model_name LIKE '%番長%'
    OR model_name LIKE '%カイジ%'
    OR model_name LIKE '%南国物語%'
    OR model_name LIKE '%ジャグラー%'
    OR model_name LIKE '%ファイヤードリフト%'
    OR model_name LIKE '%ファイヤードラフト%'
    OR model_name LIKE '%ビンゴ%'
    OR model_name LIKE '%銭形%'
    OR model_name LIKE '%島唄%'
    OR model_name LIKE '%鬼武者%'
)
AND (
    model_name_ko IS NULL
    OR model_name_ko = ''
    OR model_name_en IS NULL
    OR model_name_en = ''
)
ORDER BY model_no;

-- ====================================================================
-- 実行手順
-- ====================================================================
-- 1. database_migration_multilingual.sql を実行（カラム追加）
-- 2. このファイルを実行（翻訳データ投入）
-- 3. 上記の確認クエリを実行して結果確認
-- 4. game_start.php と play_history.php を多言語対応版に更新
-- 5. デプロイ
-- ====================================================================

-- 🎉 翻訳完了：11機種
-- ====================================================================
-- 🎌 Japanese（日本語）:
--    1. 吉宗4号機
--    2. 北斗の拳4号機
--    3. 押忍！番長
--    4. カイジ4号機
--    5. 南国物語
--    6. ジャグラー
--    7. ファイヤードリフト
--    8. ビンゴ
--    9. 銭形
--    10. 島唄
--    11. 鬼武者
--
-- 🇰🇷 Korean（韓国語）:
--    1. 요시무네 4호기
--    2. 북두의 권 4호기
--    3. 오스! 반장
--    4. 카이지 4호기
--    5. 남국 이야기
--    6. 저글러
--    7. 파이어 드리프트
--    8. 빙고
--    9. 제니가타
--    10. 시마우타
--    11. 오니무샤
--
-- 🇬🇧 English（英語）:
--    1. Yoshimune 4th Generation
--    2. Fist of the North Star 4th Generation
--    3. Osu! Banchou
--    4. Kaiji 4th Generation
--    5. Nangoku Monogatari (South Paradise Story)
--    6. Juggler
--    7. Fire Drift
--    8. Bingo
--    9. Zenigata
--    10. Shimauta (Island Song)
--    11. Onimusha
-- ====================================================================
