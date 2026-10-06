<?php

declare(strict_types=1);

namespace LotoSorter\Config;

use LotoSorter\Domain\Audience;
use LotoSorter\Domain\LotType;

/**
 * Builds a LotoConfig from INI-like sections, as read from loto_config.ini or posted by the web form:
 * [adult] and [kid] for the games, [sorting] for the optional SortingPolicy.
 */
final class LotoConfigFactory
{
    private const GAME_SECTIONS = ['adult' => Audience::Adult, 'kid' => Audience::Kid];
    private const SORTING_SECTION = 'sorting';
    private const MAX_ROUNDS = 100;
    private const MAX_AMOUNT = 100000;

    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<mixed> $sections
     *
     * @throws InvalidConfigException
     */
    public function fromArray(array $sections): LotoConfig
    {
        $this->errors = [];
        $games = [];

        foreach ($sections as $name => $values) {
            $values = is_array($values) ? $values : [];
            if (isset(self::GAME_SECTIONS[$name])) {
                $game = $this->gameRules(self::GAME_SECTIONS[$name], (string) $name, $values);
                if ([] !== $game->lotTypes()) {
                    $games[] = $game;
                }
            } elseif (self::SORTING_SECTION !== $name) {
                $this->errors[(string) $name] = sprintf('Section inconnue « %s ».', $name);
            }
        }

        if ([] === $games) {
            $this->errors['games'] = 'Il faut au moins une partie adulte ou enfant.';
        }

        $policy = $this->policy(is_array($sections[self::SORTING_SECTION] ?? null)
            ? $sections[self::SORTING_SECTION]
            : []);

        if ([] !== $this->errors) {
            throw new InvalidConfigException($this->errors);
        }

        return new LotoConfig($games, $policy);
    }

    /**
     * The sections a config is built from: fromArray(toArray($config)) gives the same config.
     *
     * @return array<string, array<string, int|float|list<string>>>
     */
    public function toArray(LotoConfig $config): array
    {
        $sections = [];
        foreach ($config->games as $game) {
            $section = ['round' => $game->rounds];
            foreach (LotType::cases() as $type) {
                $section[$type->value] = $game->target($type);
            }
            $sections[(string) array_search($game->audience, self::GAME_SECTIONS, true)] = $section;
        }

        $policy = $config->policy;
        $sections[self::SORTING_SECTION] = [
            'min_amount' => $policy->minAmount,
            'quine_max_share' => $policy->quineMaxShare,
            'double_quine_max_share' => $policy->doubleQuineMaxShare,
            'carton_min_share' => $policy->cartonMinShare,
            'tolerance_below' => $policy->toleranceBelow,
            'tolerance_above' => $policy->toleranceAbove,
            'max_vouchers_per_lot' => $policy->maxVouchersPerLot,
            'max_attempts' => $policy->maxAttempts,
            'allowed_donors' => $policy->allowedDonors,
        ];

        return $sections;
    }

    /**
     * @param array<mixed> $values
     */
    private function gameRules(Audience $audience, string $section, array $values): GameRules
    {
        $rounds = $this->integer($values, $section, 'round', 0, self::MAX_ROUNDS);
        $targets = [];
        foreach (LotType::cases() as $type) {
            $targets[$type->value] = $this->integer($values, $section, $type->value, 0, self::MAX_AMOUNT);
        }

        if ($rounds > 0) {
            foreach (LotType::roundTypes() as $type) {
                if (0 === $targets[$type->value]) {
                    $this->errors[$section . '.' . $type->value] = sprintf(
                        '%s : le montant « %s » doit être supérieur à 0 quand il y a des parties.',
                        $audience->label(),
                        $type->label(),
                    );
                }
            }
        }

        return new GameRules($audience, $rounds, $targets);
    }

    /**
     * @param array<mixed> $values
     */
    private function policy(array $values): SortingPolicy
    {
        $defaults = new SortingPolicy();
        $section = self::SORTING_SECTION;

        return new SortingPolicy(
            minAmount: $this->share($values, $section, 'min_amount', $defaults->minAmount, self::MAX_AMOUNT),
            quineMaxShare: $this->share($values, $section, 'quine_max_share', $defaults->quineMaxShare),
            doubleQuineMaxShare: $this->share(
                $values,
                $section,
                'double_quine_max_share',
                $defaults->doubleQuineMaxShare,
            ),
            cartonMinShare: $this->share($values, $section, 'carton_min_share', $defaults->cartonMinShare),
            toleranceBelow: $this->share($values, $section, 'tolerance_below', $defaults->toleranceBelow),
            toleranceAbove: $this->share($values, $section, 'tolerance_above', $defaults->toleranceAbove),
            maxVouchersPerLot: $this->integer(
                $values,
                $section,
                'max_vouchers_per_lot',
                0,
                self::MAX_ROUNDS,
                $defaults->maxVouchersPerLot,
            ),
            maxAttempts: $this->integer($values, $section, 'max_attempts', 1, 10000, $defaults->maxAttempts),
            allowedDonors: $this->list($values['allowed_donors'] ?? []),
        );
    }

    /**
     * @param array<mixed> $values
     */
    private function integer(
        array $values,
        string $section,
        string $key,
        int $min,
        int $max,
        int $default = 0,
    ): int {
        $value = $values[$key] ?? '';
        if ('' === $value) {
            return $default;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if (false === $integer) {
            $this->errors[$section . '.' . $key] = sprintf(
                '[%s] %s : un nombre entier entre %d et %d est attendu.',
                $section,
                $key,
                $min,
                $max,
            );

            return $default;
        }

        return $integer;
    }

    /**
     * @param array<mixed> $values
     */
    private function share(array $values, string $section, string $key, float $default, float $max = 1.0): float
    {
        $value = $values[$key] ?? '';
        if ('' === $value) {
            return $default;
        }

        $number = is_scalar($value) ? filter_var(str_replace(',', '.', (string) $value), FILTER_VALIDATE_FLOAT) : false;
        if (false === $number || $number < 0 || $number > $max) {
            $this->errors[$section . '.' . $key] = sprintf(
                '[%s] %s : un nombre entre 0 et %s est attendu.',
                $section,
                $key,
                $max,
            );

            return $default;
        }

        return $number;
    }

    /**
     * Accepts an INI array (allowed_donors[] = ...) or a text with one name per line.
     *
     * @return list<string>
     */
    private function list(mixed $value): array
    {
        $items = match (true) {
            is_array($value) => array_filter($value, is_scalar(...)),
            is_scalar($value) => preg_split('/\R/', (string) $value) ?: [],
            default => [],
        };

        return array_values(array_unique(array_filter(
            array_map(static fn(bool|float|int|string $item): string => trim((string) $item), $items),
            static fn(string $item): bool => '' !== $item,
        )));
    }
}
