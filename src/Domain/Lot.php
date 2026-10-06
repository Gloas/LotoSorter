<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

final class Lot
{
    /** @var list<Donation> */
    private array $donations = [];

    public function __construct(
        public readonly LotType $type,
        public readonly int $target,
        public readonly Audience $audience,
    ) {
    }

    public function add(Donation $donation): void
    {
        $this->donations[] = $donation;
    }

    /**
     * @return list<Donation>
     */
    public function donations(): array
    {
        return $this->donations;
    }

    public function isEmpty(): bool
    {
        return [] === $this->donations;
    }

    public function count(): int
    {
        return count($this->donations);
    }

    public function total(): float
    {
        return round(array_sum(array_map(
            static fn(Donation $donation): float => $donation->amount,
            $this->donations,
        )), 2);
    }

    public function reachesTarget(float $toleranceBelow): bool
    {
        return $this->total() >= $this->target * (1 - $toleranceBelow);
    }

    public function hasDonor(string $donor): bool
    {
        return $this->any(static fn(Donation $donation): bool => $donation->donor === $donor);
    }

    public function hasLabel(string $label): bool
    {
        return $this->any(static fn(Donation $donation): bool => $donation->label === $label);
    }

    public function voucherCount(): int
    {
        return count(array_filter(
            $this->donations,
            static fn(Donation $donation): bool => $donation->isVoucher(),
        ));
    }

    /**
     * @param callable(Donation): bool $matches
     */
    private function any(callable $matches): bool
    {
        foreach ($this->donations as $donation) {
            if ($matches($donation)) {
                return true;
            }
        }

        return false;
    }
}
