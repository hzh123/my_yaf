<?php

class UidEncryptUtil
{
    const UID_AES_KEY = 'ou^lk!54*98#';

    public static function encryptUid($uid)
    {
        if (empty($uid))
        {
            return null;
        }

        $enUid = bin2hex(aesEncrypt($uid, self::UID_AES_KEY));

        return $enUid;
    }

    public static function decryptUid($enUid)
    {
        if (empty($enUid))
        {
            return null;
        }

        $uid = aesDecrypt(hex2bin($enUid), self::UID_AES_KEY);

        $uid = trim($uid);
        return $uid;
    }
}