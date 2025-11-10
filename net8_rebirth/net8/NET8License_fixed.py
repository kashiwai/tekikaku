# NET8License.py の修正版（addCameraList関数のみ）
#
# 修正内容:
# - ローカルでLicense IDを生成
# - サーバーに送信
# - サーバー側では生成しない

def addCameraList(self):
    # ローカルでLicense IDを生成（サーバーに送る前に）
    license_id = self.phpEncrypt(self.getInfo('MACaddress'), 'NETPACHINCO-20200416-01234-01234')

    url = 'https://{domain}/api/cameraListAPI.php'.format(**self.getInfo())
    data = dict()
    data['M'] = 'add'
    data['MAC_ADDRESS'] = self.getInfo('MACaddress')
    data['IDENTIFING_NUMBER'] = self.getInfo('IdentifyingNumber')
    data['SYSTEM_NAME'] = self.getInfo('SystemName')
    data['PRODUCT_NAME'] = self.getInfo('ProductName')
    data['CPU_NAME'] = self.getInfo('CPUName')
    data['CORE'] = self.getInfo('NumberOfCores')
    data['UUID'] = self.getInfo('UUID')
    data['LICENSE_ID'] = license_id  # ← ローカルで生成したLicense IDを送信

    res = self.sendPost(url, data)
    self._info['camera_no'] = res['camera_no']

    # サーバーから返されたLicense IDではなく、ローカルで生成したものを使用
    return license_id
