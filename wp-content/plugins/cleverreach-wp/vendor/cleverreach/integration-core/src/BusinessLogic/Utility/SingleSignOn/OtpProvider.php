<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\SingleSignOn;

/**
 * Class OtpProvider
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\SingleSignOn
 */
class OtpProvider
{
    const EPOCH = 0;
    const PERIOD = 30;
    const DIGITS = 12;
    const ALGORITHM = 'sha256';

    /**
     * Generates OTP code.
     *
     * @param string $secret Encryption key.
     *
     * @return string
     *   The OTP at the specified input.
     */
    public static function generateOtp($secret)
    {
        $hmac = self::getHmac($secret);
        $offset = $hmac[count($hmac) - 1] & 0xF;

        $code = ($hmac[$offset + 0] & 0x7F) << 24
            | ($hmac[$offset + 1] & 0xFF) << 16
            | ($hmac[$offset + 2] & 0xFF) << 8
            | ($hmac[$offset + 3] & 0xFF);

        $otp = $code % pow(10, self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Generates hmac code.
     *
     * @param string $secret Encryption key.
     *
     * @return array
     *   Hash-based message authentication code (HMAC)
     */
    private static function getHmac($secret)
    {
        $timeCode = (int)floor((time() - self::EPOCH) / self::PERIOD);
        $hash = hash_hmac(self::ALGORITHM, self::intToByteString($timeCode), $secret);
        $hmac = array();
        foreach (str_split($hash, 2) as $hex) {
            $hmac[] = hexdec($hex);
        }

        return $hmac;
    }

    /**
     * Converts int value to byte string.
     *
     * @param int $int Integer value for converting to byte stream.
     *
     * @return string
     *   Byte stream of provided integer.
     */
    private static function intToByteString($int)
    {
        $result = array();
        while (0 !== $int) {
            $result[] = chr($int & 0xFF);
            $int >>= 8;
        }

        return str_pad(implode(array_reverse($result)), 8, "\000", STR_PAD_LEFT);
    }
}
