<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

/**
 * The donations not yet placed in a lot.
 */
final class DonationPool
{
    /** @var array<int, Donation> */
    private array $donations;

    /**
     * @param list<Donation> $donations
     */
    public function __construct(array $donations)
    {
        $this->donations = $donations;
    }

    /**
     * @return array<int, Donation>
     */
    public function all(): array
    {
        return $this->donations;
    }

    public function take(int $key): Donation
    {
        $donation = $this->donations[$key];
        unset($this->donations[$key]);

        return $donation;
    }

    /**
     * @return list<Donation>
     */
    public function remaining(): array
    {
        return array_values($this->donations);
    }
}
