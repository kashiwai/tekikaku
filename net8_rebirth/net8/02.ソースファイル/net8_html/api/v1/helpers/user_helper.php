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
    // 既存ユーザーを検索
    $stmt = $pdo->prepare("
        SELECT id, partner_user_id, api_key_id, email, username, is_active
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

    // 新規ユーザーを作成
    $stmt = $pdo->prepare("
        INSERT INTO sdk_users (partner_user_id, api_key_id, email, username, metadata, is_active)
        VALUES (:partner_user_id, :api_key_id, :email, :username, :metadata, 1)
    ");

    $stmt->execute([
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
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

    // ユーザー情報を返す
    return [
        'id' => $userId,
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
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
    $pdo->beginTransaction();

    try {
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

        $pdo->commit();

        return [
            'transaction_id' => $transactionId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'amount' => $amount
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
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
    $pdo->beginTransaction();

    try {
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

        $pdo->commit();

        return [
            'transaction_id' => $transactionId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'amount' => $amount
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
