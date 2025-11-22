<?php
/**
 * ユーザー管理ヘルパー関数
 * Version: 1.0.0
 * Created: 2025-11-18
 */

/**
 * パートナーユーザーを取得または作成
 *
 * @param PDO $pdo
 * @param int $apiKeyId
 * @param string $partnerUserId
 * @param array $userData (オプション)
 * @return array ユーザー情報
 */
function getOrCreateUser($pdo, $apiKeyId, $partnerUserId, $userData = []) {
    // 既存ユーザーを検索（member_noも取得）
    $stmt = $pdo->prepare("
        SELECT id, partner_user_id, api_key_id, email, username, is_active, member_no
        FROM sdk_users
        WHERE api_key_id = :api_key_id
        AND partner_user_id = :partner_user_id
    ");

    $stmt->execute([
        'api_key_id' => $apiKeyId,
        'partner_user_id' => $partnerUserId
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 既存ユーザーを返す
        return $user;
    }

    // mst_member（NET8側ユーザー）を取得または作成
    $mstMember = getOrCreateMstMember($pdo, $apiKeyId, $partnerUserId);
    $memberNo = $mstMember['member_no'];

    // 新規SDK sdk_usersレコードを作成（member_noと紐づけ）
    $stmt = $pdo->prepare("
        INSERT INTO sdk_users (partner_user_id, api_key_id, member_no, email, username, metadata, is_active)
        VALUES (:partner_user_id, :api_key_id, :member_no, :email, :username, :metadata, 1)
    ");

    $stmt->execute([
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
        'member_no' => $memberNo,
        'email' => $userData['email'] ?? null,
        'username' => $userData['username'] ?? null,
        'metadata' => isset($userData['metadata']) ? json_encode($userData['metadata']) : null
    ]);

    $userId = $pdo->lastInsertId();

    // 初期残高を作成
    $stmt = $pdo->prepare("
        INSERT INTO user_balances (user_id, balance, total_deposited)
        VALUES (:user_id, :initial_balance, :initial_balance)
    ");

    $initialBalance = $userData['initialBalance'] ?? 10000; // デフォルト10000ポイント

    $stmt->execute([
        'user_id' => $userId,
        'initial_balance' => $initialBalance
    ]);

    error_log("✅ Created SDK user: user_id={$userId}, member_no={$memberNo}, partner_user_id={$partnerUserId}");

    // ユーザー情報を返す（member_noを含む）
    return [
        'id' => $userId,
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
        'member_no' => $memberNo,
        'email' => $userData['email'] ?? null,
        'username' => $userData['username'] ?? null,
        'is_active' => 1
    ];
}

/**
 * ユーザーの残高を取得
 *
 * @param PDO $pdo
 * @param int $userId
 * @return array|null 残高情報
 */
function getUserBalance($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT user_id, balance, total_deposited, total_consumed, total_won, last_transaction_at
        FROM user_balances
        WHERE user_id = :user_id
    ");

    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ポイント消費
 *
 * @param PDO $pdo
 * @param int $userId
 * @param int $amount
 * @param string $gameSessionId
 * @return array 取引情報
 */
function consumePoints($pdo, $userId, $amount, $gameSessionId = null) {
    // NOTE: トランザクション管理は呼び出し側で行う前提
    // （game_start.php が既にトランザクションを開始している）

    // 現在の残高を取得（FOR UPDATE でロック）
    $stmt = $pdo->prepare("
        SELECT balance
        FROM user_balances
        WHERE user_id = :user_id
        FOR UPDATE
    ");

    $stmt->execute(['user_id' => $userId]);
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$balance) {
        throw new Exception('User balance not found');
    }

    $balanceBefore = $balance['balance'];

    if ($balanceBefore < $amount) {
        throw new Exception('Insufficient balance');
    }

    $balanceAfter = $balanceBefore - $amount;

    // 残高を更新
    $stmt = $pdo->prepare("
        UPDATE user_balances
        SET balance = :balance,
            total_consumed = total_consumed + :amount,
            last_transaction_at = NOW()
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        'balance' => $balanceAfter,
        'amount' => $amount,
        'user_id' => $userId
    ]);

    // 取引履歴を記録
    $transactionId = 'txn_' . uniqid() . '_' . time();

    $stmt = $pdo->prepare("
        INSERT INTO point_transactions
        (user_id, transaction_id, type, amount, balance_before, balance_after, game_session_id, description)
        VALUES
        (:user_id, :transaction_id, 'consume', :amount, :balance_before, :balance_after, :game_session_id, 'Game start point consumption')
    ");

    $stmt->execute([
        'user_id' => $userId,
        'transaction_id' => $transactionId,
        'amount' => -$amount, // 負の値で記録
        'balance_before' => $balanceBefore,
        'balance_after' => $balanceAfter,
        'game_session_id' => $gameSessionId
    ]);

    return [
        'transaction_id' => $transactionId,
        'balance_before' => $balanceBefore,
        'balance_after' => $balanceAfter,
        'amount' => $amount
    ];
}

/**
 * パートナーユーザーに対応するNET8ユーザー（mst_member）を取得または作成
 *
 * @param PDO $pdo
 * @param int $apiKeyId
 * @param string $partnerUserId
 * @return array mst_member情報 ['member_no' => int, 'nickname' => string, ...]
 */
function getOrCreateMstMember($pdo, $apiKeyId, $partnerUserId) {
    // APIキー情報を取得（パートナー名を取得するため）
    $stmt = $pdo->prepare("SELECT name FROM api_keys WHERE id = :id");
    $stmt->execute(['id' => $apiKeyId]);
    $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$apiKey) {
        throw new Exception('API key not found');
    }

    $partnerName = preg_replace('/[^a-zA-Z0-9]/', '', $apiKey['name'] ?? 'SDK'); // 英数字のみ

    // 一意のメールアドレスを生成（sdk_{partner}_{userId}@net8.local）
    $email = 'sdk_' . strtolower($partnerName) . '_' . $partnerUserId . '@net8.local';

    // 既存のmst_memberを検索
    $stmt = $pdo->prepare("
        SELECT member_no, nickname, mail, point
        FROM mst_member
        WHERE mail = :mail
        AND quit_dt IS NULL
    ");

    $stmt->execute(['mail' => $email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        // 既存ユーザーを返す
        return $member;
    }

    // 新規mst_memberを作成
    $nickname = 'SDK_' . $partnerName . '_' . substr($partnerUserId, 0, 10);
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // ランダムパスワード
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO mst_member (
            nickname,
            mail,
            pass,
            point,
            draw_point,
            mail_magazine,
            tester_flg,
            agent_flg,
            black_flg,
            state,
            regist_dt,
            join_dt,
            add_dt
        ) VALUES (
            :nickname,
            :mail,
            :pass,
            0,
            0,
            0,
            0,
            0,
            0,
            1,
            :regist_dt,
            :join_dt,
            :add_dt
        )
    ");

    $stmt->execute([
        'nickname' => $nickname,
        'mail' => $email,
        'pass' => $password,
        'regist_dt' => $now,
        'join_dt' => $now,
        'add_dt' => $now
    ]);

    $memberNo = $pdo->lastInsertId();

    error_log("✅ Created new mst_member: member_no={$memberNo}, email={$email}, partner={$partnerName}, userId={$partnerUserId}");

    // 作成したユーザー情報を返す
    return [
        'member_no' => $memberNo,
        'nickname' => $nickname,
        'mail' => $email,
        'point' => 0
    ];
}

/**
 * ポイント払い出し
 *
 * @param PDO $pdo
 * @param int $userId
 * @param int $amount
 * @param string $gameSessionId
 * @return array 取引情報
 */
function payoutPoints($pdo, $userId, $amount, $gameSessionId = null) {
    // NOTE: トランザクション管理は呼び出し側で行う前提
    // （game_end.php が既にトランザクションを開始している）

    // 現在の残高を取得（FOR UPDATE でロック）
    $stmt = $pdo->prepare("
        SELECT balance
        FROM user_balances
        WHERE user_id = :user_id
        FOR UPDATE
    ");

    $stmt->execute(['user_id' => $userId]);
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$balance) {
        throw new Exception('User balance not found');
    }

    $balanceBefore = $balance['balance'];
    $balanceAfter = $balanceBefore + $amount;

    // 残高を更新
    $stmt = $pdo->prepare("
        UPDATE user_balances
        SET balance = :balance,
            total_won = total_won + :amount,
            last_transaction_at = NOW()
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        'balance' => $balanceAfter,
        'amount' => $amount,
        'user_id' => $userId
    ]);

    // 取引履歴を記録
    $transactionId = 'txn_' . uniqid() . '_' . time();

    $stmt = $pdo->prepare("
        INSERT INTO point_transactions
        (user_id, transaction_id, type, amount, balance_before, balance_after, game_session_id, description)
        VALUES
        (:user_id, :transaction_id, 'payout', :amount, :balance_before, :balance_after, :game_session_id, 'Game win payout')
    ");

    $stmt->execute([
        'user_id' => $userId,
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'balance_before' => $balanceBefore,
        'balance_after' => $balanceAfter,
        'game_session_id' => $gameSessionId
    ]);

    return [
        'transaction_id' => $transactionId,
        'balance_before' => $balanceBefore,
        'balance_after' => $balanceAfter,
        'amount' => $amount
    ];
}
