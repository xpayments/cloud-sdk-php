<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud\Model;

class Subscription
{
    /**
     * Constants
     */

    /**
     * Plan types
     */
    const TYPE_EACH  = 'E';
    const TYPE_EVERY = 'D';

    /**
     * Plan periods
     */
    const PERIOD_DAY   = 'D';
    const PERIOD_WEEK  = 'W';
    const PERIOD_MONTH = 'M';
    const PERIOD_YEAR  = 'Y';

    /**
     * Subscription statuses
     */
    const STATUS_NOT_STARTED = 'N';
    const STATUS_ACTIVE      = 'A';
    const STATUS_STOPPED     = 'S';
    const STATUS_FAILED      = 'D';
    const STATUS_FINISHED    = 'F';
    const STATUS_RESTARTED   = 'R';

    /**
     * Default parameters for retrieving subscriptions list
     */
    const DEFAULT_OFFSET = 0;
    const DEFAULT_COUNT = 10;

    /**
     * Fields
     */
    private $publicId;
    private $customerId;
    private $type;
    private $number;
    private $period;
    private $reverse;
    private $periods;
    private $recurringAmount;
    private $cardId;
    private $failedAttempts;
    private $successfulAttempts;
    private $startDate;
    private $plannedDate;
    private $actualDate;
    private $status;
    private $uniqueOrderItemId;

    /**
     * Getters and setters
     */

    /**
     * @return mixed
     */
    public function getPublicId()
    {
        return $this->publicId;
    }

    /**
     * @param mixed $publicId
     *
     * @return Subscription
     */
    public function setPublicId($publicId)
    {
        $this->publicId = $publicId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return Subscription
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     *
     * @return Subscription
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param mixed $period
     *
     * @return Subscription
     */
    public function setPeriod($period)
    {
        $this->period = $period;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReverse()
    {
        return $this->reverse;
    }

    /**
     * @param mixed $reverse
     *
     * @return Subscription
     */
    public function setReverse($reverse)
    {
        $this->reverse = $reverse;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPeriods()
    {
        return $this->periods;
    }

    /**
     * @param mixed $periods
     *
     * @return Subscription
     */
    public function setPeriods($periods)
    {
        $this->periods = $periods;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecurringAmount()
    {
        return $this->recurringAmount;
    }

    /**
     * @param mixed $recurringAmount
     *
     * @return Subscription
     */
    public function setRecurringAmount($recurringAmount)
    {
        $this->recurringAmount = $recurringAmount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * @param mixed $cardId
     *
     * @return Subscription
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFailedAttempts()
    {
        return $this->failedAttempts;
    }

    /**
     * @param mixed $failedAttempts
     *
     * @return Subscription
     */
    public function setFailedAttempts($failedAttempts)
    {
        $this->failedAttempts = $failedAttempts;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulAttempts()
    {
        return $this->successfulAttempts;
    }

    /**
     * @param mixed $successfulAttempts
     *
     * @return Subscription
     */
    public function setSuccessfulAttempts($successfulAttempts)
    {
        $this->successfulAttempts = $successfulAttempts;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     *
     * @return Subscription
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlannedDate()
    {
        return $this->plannedDate;
    }

    /**
     * @param mixed $plannedDate
     *
     * @return Subscription
     */
    public function setPlannedDate($plannedDate)
    {
        $this->plannedDate = $plannedDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getActualDate()
    {
        return $this->actualDate;
    }

    /**
     * @param mixed $actualDate
     *
     * @return Subscription
     */
    public function setActualDate($actualDate)
    {
        $this->actualDate = $actualDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     *
     * @return Subscription
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUniqueOrderItemId()
    {
        return $this->uniqueOrderItemId;
    }

    /**
     * @param mixed $uniqueOrderItemId
     */
    public function setUniqueOrderItemId($uniqueOrderItemId): void
    {
        $this->uniqueOrderItemId = $uniqueOrderItemId;
    }

     /**
     * Subscription constructor.
     *
     * @param array $subscriptionData
     */
    public function __construct(array $subscriptionData = [])
    {
        if ($subscriptionData) {
            $this->createFromResponse($subscriptionData);
        }
    }

    /**
     * @param $subscriptionData
     *
     * @return $this
     */
    private function createFromResponse($subscriptionData)
    {
        // TODO: validate fields

        foreach ($subscriptionData as $key => $field) {

            if (property_exists($this, $key)) {
                $this->{$key} = $subscriptionData[$key];
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return self::STATUS_ACTIVE === $this->getStatus();
    }

}
