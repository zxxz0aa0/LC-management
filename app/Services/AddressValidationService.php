<?php

namespace App\Services;

class AddressValidationService
{
    protected $addressResolver;

    public function __construct(TaiwanAddressResolver $addressResolver)
    {
        $this->addressResolver = $addressResolver;
    }

    /**
     * 驗證訂單地址是否符合訂單類型規則
     *
     * @param  string  $orderType  訂單類型（台北長照、新北長照等）
     * @param  string  $pickupAddress  上車地址
     * @param  string  $dropoffAddress  下車地址
     * @return array ['valid' => bool, 'errors' => array, 'auto_no_send' => bool]
     */
    public function validateOrderAddresses($orderType, $pickupAddress, $dropoffAddress)
    {
        $result = [
            'valid' => true,
            'errors' => [
                'pickup' => [],
                'dropoff' => [],
            ],
            'auto_no_send' => false,
        ];

        // 解析地址縣市
        $pickupCounty = $this->addressResolver->extractCounty($pickupAddress);
        $dropoffCounty = $this->addressResolver->extractCounty($dropoffAddress);

        // 根據訂單類型進行驗證
        switch ($orderType) {
            case '台北長照':
                $this->validateTaipeiLongTermCare($pickupCounty, $dropoffCounty, $result);
                break;

            case '新北長照':
                $this->validateNewTaipeiLongTermCare(
                    $pickupCounty,
                    $dropoffCounty,
                    $pickupAddress,
                    $result
                );
                break;

            default:
                // 其他訂單類型不需要地址限制
                break;
        }

        return $result;
    }

    /**
     * 台北長照地址驗證
     * 規則：上下車地址都必須在「新北市」或「台北市」
     */
    protected function validateTaipeiLongTermCare($pickupCounty, $dropoffCounty, &$result)
    {
        $allowedCounties = $this->getAllowedCounties('台北長照');

        if (! in_array($pickupCounty, $allowedCounties)) {
            $result['valid'] = false;
            $result['errors']['pickup'][] = '上車地址必須在新北市或台北市';
        }

        if (! in_array($dropoffCounty, $allowedCounties)) {
            $result['valid'] = false;
            $result['errors']['dropoff'][] = '下車地址必須在新北市或台北市';
        }
    }

    /**
     * 新北長照地址驗證
     * 規則1：上下車地址都必須在「新北市、台北市、桃園市、基隆市」
     * 規則2：如果上車地址在特定區域（金山區等7個區），自動設為「不派遣」
     */
    protected function validateNewTaipeiLongTermCare($pickupCounty, $dropoffCounty, $pickupAddress, &$result)
    {
        $allowedCounties = $this->getAllowedCounties('新北長照');

        // 縣市驗證
        if (! in_array($pickupCounty, $allowedCounties)) {
            $result['valid'] = false;
            $result['errors']['pickup'][] = '上車地址必須在新北市、台北市、桃園市或基隆市';
        }

        if (! in_array($dropoffCounty, $allowedCounties)) {
            $result['valid'] = false;
            $result['errors']['dropoff'][] = '下車地址必須在新北市、台北市、桃園市或基隆市';
        }

        // 特定區域自動不派遣檢查
        if ($this->shouldAutoSetNoSend($pickupAddress)) {
            $result['auto_no_send'] = true;
        }
    }

    /**
     * 取得允許的縣市列表
     *
     * @param  string  $orderType
     * @return array
     */
    public function getAllowedCounties($orderType)
    {
        $allowedCounties = [
            '台北長照' => ['新北市', '台北市'],
            '新北長照' => ['新北市', '台北市', '桃園市', '基隆市'],
        ];

        return $allowedCounties[$orderType] ?? [];
    }

    /**
     * 取得新北長照不派遣的區域列表
     *
     * @return array
     */
    public function getNoSendDistricts()
    {
        return [
            '金山區',
            '鶯歌區',
            '三峽區',
            '淡水區',
            '五股區',
            '瑞芳區',
            '萬里區',
        ];
    }

    /**
     * 檢查是否應該自動設定為「不派遣」狀態
     * 規則：新北長照訂單，上車地址在特定區域（金山區等7個區）
     *
     * @param  string  $pickupAddress  上車地址
     * @return bool
     */
    public function shouldAutoSetNoSend($pickupAddress)
    {
        $pickupDistrict = $this->addressResolver->extractDistrict($pickupAddress);
        $noSendDistricts = $this->getNoSendDistricts();

        return in_array($pickupDistrict, $noSendDistricts);
    }

    /**
     * 取得不派遣區域的名稱（用於前端顯示）
     *
     * @param  string  $pickupAddress
     * @return string|null 如果在不派遣區域，返回區域名稱，否則返回 null
     */
    public function getNoSendDistrictName($pickupAddress)
    {
        $pickupDistrict = $this->addressResolver->extractDistrict($pickupAddress);
        $noSendDistricts = $this->getNoSendDistricts();

        if (in_array($pickupDistrict, $noSendDistricts)) {
            return $pickupDistrict;
        }

        return null;
    }
}
