<?php
/**
 * TokenAuth.php
 *
 * マシン認証トークン検証クラス
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/13
 */

class TokenAuth {

    /**
     * トークン検証
     *
     * @param NetDB $DB データベース接続
     * @param int $machine_no マシン番号
     * @param string $token 認証トークン
     * @return bool 検証結果
     */
    public static function verify($DB, $machine_no, $token) {
        // トークンが空の場合は認証失敗
        if (empty($token) || empty($machine_no)) {
            return false;
        }

        // dat_machineからトークンを取得
        $sql = (new SqlString($DB))
            ->select()
                ->field("token")
                ->from("dat_machine")
                ->where()
                    ->and("machine_no = ", $machine_no, FD_NUM)
                    ->and("del_flg = ", 0, FD_NUM)
            ->createSQL();

        $row = $DB->getRow($sql);

        // マシンが見つからない場合は認証失敗
        if (empty($row) || empty($row['token'])) {
            return false;
        }

        // トークン一致確認（タイミング攻撃対策でhash_equalsを使用）
        return hash_equals($row['token'], $token);
    }

    /**
     * MACアドレスからマシン番号とトークンを取得
     *
     * @param NetDB $DB データベース接続
     * @param string $mac_address MACアドレス
     * @return array ['machine_no' => int, 'token' => string] or null
     */
    public static function getMachineByMac($DB, $mac_address) {
        // MAC addressを小文字に統一
        $mac_address = strtolower($mac_address);

        // mst_cameraからcamera_no取得
        $camera_sql = (new SqlString($DB))
            ->select()
                ->field("camera_no")
                ->from("mst_camera")
                ->where()
                    ->and("camera_mac = ", $mac_address, FD_STR)
                    ->and("del_flg = ", 0, FD_NUM)
            ->createSQL();

        $camera_row = $DB->getRow($camera_sql);

        if (empty($camera_row) || empty($camera_row['camera_no'])) {
            return null;
        }

        // dat_machineからmachine_noとtokenを取得
        $machine_sql = (new SqlString($DB))
            ->select()
                ->field("machine_no, token, camera_no")
                ->from("dat_machine")
                ->where()
                    ->and("camera_no = ", $camera_row['camera_no'], FD_NUM)
                    ->and("del_flg = ", 0, FD_NUM)
            ->createSQL();

        $machine_row = $DB->getRow($machine_sql);

        if (empty($machine_row)) {
            return null;
        }

        return [
            'machine_no' => $machine_row['machine_no'],
            'token' => $machine_row['token'],
            'camera_no' => $machine_row['camera_no']
        ];
    }
}
?>
