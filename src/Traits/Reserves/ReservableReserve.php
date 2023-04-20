<?php

namespace ShayanYS\LaraReserve\Traits\Reserves;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use ShayanYS\LaraReserve\Interfaces\Reserves\CustomerReserveInterface;
use ShayanYS\LaraReserve\Models\Reserve;
use ShayanYS\LaraReserve\Traits\LaraReserveDateTimeTrait;

trait ReservableReserve
{
    use LaraReserveDateTimeTrait;

    protected bool $checkAvailability = true;

    public function reserveForCustomer(CustomerReserveInterface $customer, DatetimeInterface|Carbon|DateTime $reserveDate, string $reserveTime = '00:00:00', ?array $metadata = null): Reserve | bool
    {
        return $customer->reserve($this, $reserveDate, $reserveTime, $metadata); // reserve without check availability
    }

    public function dontCheckAvailability():static{
        $this->checkAvailability = false;
        return $this;
    }

    public function shouldCheckAvailability():bool{
        return $this->checkAvailability;
    }

    public function isAvailable(DateTimeInterface|DateTime|Carbon $date, DateTimeInterface|DateTime|Carbon|string $time = '00:00:00'): bool
    {

        if (!$this->max_allowed_reserves) {
            return true;
        }

        return $this->getReserveCountInOneDateTime($date, $time) < $this->max_allowed_reserves;
    }

    private function getReserveCountInOneDateTime(DateTimeInterface|DateTime|Carbon $date, string $time = '00:00:00'): int
    {
        $date = $this->createCarbonDateTime($date);
        $reservedCountRow = $this->reserves()->select(DB::raw('count(*) as reserves_count'))->where([['reserved_date', $date->toDateString()], ['reserved_time', $time]])->first();

        if (!$reservedCountRow) {
            return 0;
        }

        return $reservedCountRow->reserves_count;

    }

    public function maxAllowedReserves(int $max): static
    {
        $this->max_allowed_reserves = $max;
        $this->save();
        return $this;
    }

    public function getMaxAllowedReserves(): int|null
    {
        return $this->max_allowed_reserves;
    }

    public function reserveWithoutCustomer(array $metadata, DatetimeInterface|Carbon|DateTime $reserveDate, string $reserveTime = '00:00:00'): Reserve
    {
        return $this->reserves()->create(['reserved_date' => $reserveDate, 'reserved_time' => $reserveTime, 'metadata' => $metadata]);
    }
}
