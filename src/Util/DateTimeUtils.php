<?php

namespace SureLv\Emails\Util;

class DateTimeUtils
{

    const MAX_DATE_TIME = '9999-12-31 23:59:59';

    private static string $defaultTimezone = 'UTC';

    /**
     * Convert DB datetime/date value to the \DateTime object
     * 
     * @param string|\DateTime|null $v
     * @param \DateTime|null $defVal
     * return \DateTime|null
     */
    public static function toDateTime($v, ?\DateTime $defVal = null): ?\DateTime
    {
        if ($v instanceof \DateTime) {
            $d = clone $v;
        } elseif (is_string($v)) {
            $d = new \DateTime($v, new \DateTimeZone(self::$defaultTimezone));
        } else {
            $d = null;
        }
        if ($d instanceof \DateTime) {
            $y = (int)$d->format('Y');
            if ($y <= 1000 || $y > 9999) {
                $d = null;
            }
        }
        if ($d) {
            return $d;
        }
        return $defVal;
    }

    /**
     * Convert \DateTime object to DB datetime/date value
     * 
     * @param \DateTimeInterface|string|null $v
     * @param \DateTimeInterface|null $defVal
     * @param bool $isDate
     * @return string|null
     */
    public static function toDbDateTime($v = 'now', ?\DateTimeInterface $defVal = null, bool $isDate = false): ?string
    {
        if (is_null($v)) {
            if (is_null($defVal)) {
                return null;
            }
            $v = $defVal;
        }
        if (is_string($v)) {
            $v = new \DateTimeImmutable($v, new \DateTimeZone(self::$defaultTimezone));
            return $isDate ? $v->format('Y-m-d') : $v->format('Y-m-d H:i:s');
        }
        if ($v instanceof \DateTimeImmutable) {
            $newV = new \DateTime($v->format('Y-m-d H:i:s'), $v->getTimezone());
        } elseif ($v instanceof \DateTime) {
            $newV = clone $v;
        }
        $newV->setTimezone(new \DateTimeZone(self::$defaultTimezone));
        return $isDate ? $v->format('Y-m-d') : $v->format('Y-m-d H:i:s');
    }

    /**
     * Convert \DateTime object to DB date value
     * 
     * @param string|\DateTime|\DateTimeInterface|null $v
     * @param \DateTimeInterface|null $defVal
     * @return string|null
     */
    public static function toDbDate($v = 'now', ?\DateTimeInterface $defVal = null): ?string
    {
        return self::toDbDateTime($v, $defVal, true);
    }

    /**
     * Get max datetime
     * 
     * @return \DateTime
     */
    public static function getMaxDatetime(): \DateTime
    {
        return new \DateTime(self::MAX_DATE_TIME, new \DateTimeZone(self::$defaultTimezone));
    }

}
