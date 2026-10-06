<?php
class SortDonation {

    protected array $_donations_list = [];
    protected array $_loto_cfg = [];
    protected array $_log_stack = [];
    protected string $_donations_filename;
    protected bool $_is_valid = true;

    protected static int $_try_counter = 0;

    public function __construct(string $donations_filename, int $try_counter = 1) {
        $this->_donations_filename = $donations_filename;
        static::$_try_counter += $try_counter;

        $this->_log("OUTIL DE TRI DES DONS");
        $this->_log("Date du : " . date('D d F, Y, H:i:s'));
        $this->_log("Nombre de boucle(s) : " . static::$_try_counter);

        if ( 300 <= static::$_try_counter) {
            $this->_log("Trop de boucles…");
            return $this->_printLog();
        }

        $this->_donations_list = [];
        $this->_donations_list = array_map(fn($line) => str_getcsv($line, escape: ''),
                                            file($donations_filename));
        $this->_donations_list = $this->_cleanDonations();

        shuffle($this->_donations_list);
        $this->_log(sprintf("Nombre de lots à trier : %d",
                            count($this->_donations_list)));
        $this->_loto_cfg = parse_ini_file('./loto_config.ini', true);

        $sorted_donations_list = SortedDonationsList::getInstance();
        $sorted_donations_list->addRow("GRILLES AUTOMATIQUES");
        $sorted_donations_list->addBlankRow();

        foreach($this->_loto_cfg as $player => $rules)
            $this->_sortDonationFor($player, $rules);

        if ( ! $this->_is_valid)
            return;

        $this->_exportCsv($sorted_donations_list);

        $this->_log(sprintf('Nombre de lots triés : %d',
                            $sorted_donations_list->size()));

        $this->_exportNotSortedCsv();

        $this->_log(sprintf('Nombre de lots non triés : %d',
                            count($this->_donations_list)));

        $this->_log(sprintf('Nombre de lots MIX non triés : %d',
                            count($this->_mixDonations())));

        $this->_log(sprintf('Nombre de lots ADULTES non triés : %d avec %d parties, quine à %d€, double quine à %d€, carton à %d€, gros lot à plus de %d€ et pas de bol à plus de %d€.',
                            count($this->_adultsDonations()),
                            $this->_loto_cfg['adult']['round'] ?? 0,
                            $this->_loto_cfg['adult']['quine'] ?? 0,
                            $this->_loto_cfg['adult']['double_quine'] ?? 0,
                            $this->_loto_cfg['adult']['carton'] ?? 0,
                            $this->_loto_cfg['adult']['gros_lot'] ?? 0,
                            $this->_loto_cfg['adult']['pas_de_bol'] ?? 0));

        $this->_log(sprintf('Nombre de lots ENFANTS non triés : %d avec %d parties, quine à %d€, double quine à %d€ et carton à %d€, gros lot à plus de %d€ et pas de bol à plus de %d€.',
                            count($this->_childDonations()),
                            $this->_loto_cfg['kid']['round'] ?? 0,
                            $this->_loto_cfg['kid']['quine'] ?? 0,
                            $this->_loto_cfg['kid']['double_quine'] ?? 0,
                            $this->_loto_cfg['kid']['carton'] ?? 0,
                            $this->_loto_cfg['kid']['gros_lot'] ?? 0,
                            $this->_loto_cfg['kid']['pas_de_bol'] ?? 0));

        $this->_exportResult($sorted_donations_list);
        $this->_printLog();
    }


    protected function _printLog(): void
    {
        array_map(fn($log) => print(reset($log) . "\n"), $this->_log_stack);
    }


    protected function _childDonations() : array {
        return $this->_filterByTarget('Enfant');
    }


    protected function _adultsDonations() : array {
        return $this->_filterByTarget('Adulte');
    }


    protected function _mixDonations() : array {
        return $this->_filterByTarget('Mix');
    }


    protected function _filterByTarget(string $target) : array {
        return array_filter($this->_donations_list,
                            fn($donation) => $target == ($donation[4] ?? '') );
    }


    protected function _log(string $message) : self {
        $this->_log_stack [] = [$message];
        return $this;
    }


    protected function _cleanDonations() : array {
        return array_filter($this->_donations_list,
                            fn($donation) => 0 < (int) ($donation[2] ?? 0));
    }


    protected function _sortDonationFor(string $player,
                                        array $rules) : self {
        if ( ! $this->_is_valid)
            return $this;

        $sorted_donations_by_player = ($player == 'kid')
            ? new SortDonationsForKid($rules, $this->_donations_list)
            : new SortDonationsForAdult($rules, $this->_donations_list);

        if ( ! $sorted_donations_by_player->isValid())
            return $this->_beInvalid();

        $this->_donations_list = $sorted_donations_by_player->getDonationsList();
        return $this;
    }


    protected function _beInvalid(): self
    {
        $this->_is_valid = false;
        SortedDonationsList::getInstance()->reset();
        $this->_donations_list = [];
        new self($this->_donations_filename);
        return $this;
    }


    protected function _exportCsv(SortedDonationsList $sorted_donations_list) : self {
        @unlink('sorted_donations.csv');
        $fp = fopen('sorted_donations.csv', 'w');
        array_map(fn ($row) => fputcsv($fp, $row, escape: ''), $sorted_donations_list->asArray());
        fclose($fp);
        return $this;
    }


    protected function _exportNotSortedCsv() : self {
        @unlink('not_sorted_donations.csv');
        $fp = fopen('not_sorted_donations.csv', 'w');
        fputcsv($fp, ['RESTE DES DONS NON TRIÉS'], escape: '');
        array_map(fn ($row) => fputcsv($fp, $row, escape: ''), $this->_donations_list);
        fclose($fp);
        return $this;
    }


    protected function _exportResult(SortedDonationsList $sorted_donations_list) : self {
        $result = array_merge($this->_log_stack,
                              [[]],
                              $this->_donations_list,
                              [[]],
                              $sorted_donations_list->asArray());
        @unlink('auto_sort_loto_donations.csv');
        $fp = fopen('auto_sort_loto_donations.csv', 'w');
        array_map(fn ($row) => fputcsv($fp, $row, escape: ''), $result);
        fclose($fp);
        return $this;
    }
}




class SortDonationsForAdult extends SortDonationsForKid {
    protected string $_player_key = 'Adulte';
}



class SortDonationsForKid {

    protected static float $_variance_up = 0.1; // lower is better
    protected static float $_variance_down = 0.01; // lower is better
    protected static float $_double_quine_max = 0.3; // lower is better to max carton
    protected static float $_carton_min = 0.17; // higher is better
    protected static float $_quine_max = 0.25; // lower is better

    protected static float $_min_price = 5.00; // ignore lower donation price

    protected static array $_allowed_as_multiples_donator = ['Intermarché Peron',
                                                             'Papo\'thé',
                                                             'Tabac Viry',
                                                             'Maped',
                                                             'APE de Valleiry'];

    protected array $_donations;
    protected string $_player_key = 'Enfant';
    protected array $_rules = [];
    protected bool $_is_valid = true;


    public function __construct(array $rules, array $donations) {
        $this->_rules = $rules;
        $this->_donations = $donations;

        SortedDonationsList::getInstance()->addRow("Parties " . $this->_player_key);

        $this
            ->_sortDonationsWithRules()
            ->_sortDonationsForGrosLotAndPasDeBol();
    }


    protected function _sortDonationsForGrosLotAndPasDeBol(): static
    {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $gros_lot = (int) $this->_rules['gros_lot'] ?? 0;
        $pas_de_bol = (int) $this->_rules['pas_de_bol'] ?? 0;

        if ($gros_lot)
        {
            $sorted_donations_list->addRow("Gros lot");
            $sorted_donations_list->addRow("Carton");
            if ( !$this->_sortDonationsForPrice($gros_lot, true))
                return $this->_invalidSort(0, "gros_lot");
        }

        if ($pas_de_bol)
        {
            $sorted_donations_list->addRow("Pas de bol");
            $sorted_donations_list->addRow("Carton");
            if ( !$this->_sortDonationsForPrice($pas_de_bol))
                return $this->_invalidSort(0, "gros_lot");
        }

        return $this;
    }


    protected function _filterDonations(string $player) : array {
        return array_filter($this->_donations,
                            fn($row) => ($row[4] == $this->_player_key || $row[4] == 'Mix' ) && $row[2] != '');
    }


    protected function _sortDonationsWithRules(): static {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $count = (int) $this->_rules['round'] ?? 0;
        $quine = (int) $this->_rules['quine'] ?? 0;
        $double_quine = (int) $this->_rules['double_quine'] ?? 0;
        $carton = (int) $this->_rules['carton'] ?? 0;

        $i = 1;
        while ($i <= $count) {
            $sorted_donations_list->addRow(sprintf("Partie %s n°%s",
                                                   $this->_player_key
                                                   ,$i));

            $sorted_donations_list->addRow("Quine");
            if ( !$this->_sortDonationsForPrice($quine))
                return $this->_invalidSort($i, "quine");

            $sorted_donations_list->addRow("Double-quine");
            if ( !$this->_sortDonationsForPrice($double_quine))
                return $this->_invalidSort($i, "double quine");

            $sorted_donations_list->addRow("Carton");
            if (!$this->_sortDonationsForPrice($carton))
                return $this->_invalidSort($i, "carton");

            $sorted_donations_list->addBlankRow();
            $i++;
        }

        return $this;
    }


    protected function _invalidSort(int $partie_id, $round_name): self
    {
        SortedDonationsList::getInstance()->addRow(sprintf('Partie %s n°%d, %s sans lot',
                                                           $this->_player_key,
                                                           $partie_id,
                                                           $round_name));
        $this->_is_valid = false;
        return $this;
    }


    public function isValid(): bool
    {
        return $this->_is_valid;
    }


    protected function _sortDonationsForPrice(int $price, bool $only_one_donation = false): array {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $sum = 0;
        $current = [];

        foreach ($this->_donations as $key => $donation) {
            if ( !$original_donatio_price = $donation[2] ?? 0)
                continue;

            $donation_price = $this->_parsePrice($original_donatio_price);

            if ( ! $this->_forMe($donation))
                continue;

            if ( $this->_donationPriceIsToLow($donation_price, $current, $price))
                continue;

            if ( $this->_inCurrentPrice($donation, $current))
                continue;

            if ( ! $this->_matchPrice($sum + $donation_price, $price, $current, $only_one_donation))
                continue;

            $sorted_donations_list->addRow($donation);
            $sorted_donations_list->addDonation($donation);
            $current [] = $donation;
            unset($this->_donations[$key]);
            $sum += $donation_price;

            if ( $only_one_donation || $this->_matchMinPrice($sum, $price, $current))
            {
                $this->_addTotal(count($current), $price);
                return $current;
            }
        }

        $this->_addTotal(count($current), $price);
        return $current;
    }


    // accepts "25,00 €", "37€", "12.5", "1 234,50 €"...
    protected function _parsePrice(string $raw_price): float
    {
        $price = preg_replace('/[^\d,.]/', '', $raw_price);

        if (preg_match('/^(.*)[,.](\d{1,2})$/', $price, $matches))
            return (float) (preg_replace('/\D/', '', $matches[1]) . '.' . $matches[2]);

        return (float) preg_replace('/\D/', '', $price);
    }


    protected function _matchMinPrice(float $sum, int $price, $current): bool
    {
        return $sum >= ($price - ($price * static::$_variance_down));
    }


    protected function _addTotal(int $count, int $price) : self {
        $sum = sprintf('=SUM(INDIRECT(ADDRESS(ROW()-1;COLUMN())):INDIRECT(ADDRESS(ROW()-%d;COLUMN())))',
                       $count);
        SortedDonationsList::getInstance()->addRow(['Mise de',
                                                    '',
                                                    $sum,
                                                    '/ ' . $price . '€ pour cette manche']);
        SortedDonationsList::getInstance()->addBlankRow();
        return $this;
    }


    protected function _donationPriceIsToLow(float $donation_price, array $current, int $price) : bool {
        if ( $donation_price < static::$_min_price)
            return true;

        if ( $this->_isQuine($price))
            return $this->_donationPriceIsToHighForQuine($donation_price, $current, $price);

        return $this->_isDoubleQuine($price)
            ? $this->_donationPriceIsToHighForDoubleQuine($donation_price, $current, $price)
            : $this->_donationPriceIsToLowForCarton($donation_price, $current, $price);
    }


    protected function _isQuine(int $price) : bool {
        return $this->_rules['quine'] == $price;
    }


    protected function _isDoubleQuine(int $price) : bool {
        return $this->_rules['double_quine'] == $price;
    }


    protected function _donationPriceIsToHighForQuine(float $donation_price, array $current, int $price) : bool {
        return $donation_price > ($price * static::$_quine_max);

    }


    protected function _donationPriceIsToHighForDoubleQuine(float $donation_price, array $current, int $price) : bool {
        return $donation_price > ($price * static::$_double_quine_max);

    }


    protected function _donationPriceIsToLowForCarton(float $donation_price, array $current, int $price) : bool {
        return $current
            ? false
            : $donation_price < ($price * static::$_carton_min);
    }


    protected function _forMe(array $donation) : bool {
        if ( ! $player = $donation[4] ?? '')
            return false;

        return $player == $this->_player_key
            || $player == 'Mix';
    }


    protected function _inCurrentPrice(array $donation, array $current) : bool {
        $donator = $donation[0] ?? '';
        if ( in_array($donator, static::$_allowed_as_multiples_donator))
            return false;

        foreach($current as $added_donation)
            if ($donator == $added_donation[0] ?? '')
                return true;

        $label = $donation[3] ?? '';
        $count_bons = 0;
        foreach($current as $added_donation)
        {
            $data_label = $added_donation[3] ?? '';
            if ($label ==  $data_label)
                return true;

            if ($count_bons == 2)
                return true;

            if (strpos(strtolower($data_label), 'bon '))
                $count_bons++;
        }

        return false;
    }


    protected function _matchPrice(float $sum, int $price, array $current, bool $only_one_donation = false) : bool {
        if ( $only_one_donation)
            return $sum >= ($price - ($price * static::$_variance_down));

        if ( $sum < ($price - ($price * static::$_variance_down)))
            return true;

        if ($sum < ($price + ($price * static::$_variance_up)))
            return true;

        return false;
    }


    public function getDonationsList() : array {
        return $this->_donations;
    }
}



class SortedDonationsList {

    protected static SortedDonationsList $_instance;

    protected array $_sorted_donations_list = [];
    protected array $_donations_list = [];


    public static function getInstance() : SortedDonationsList {
        return static::$_instance ??= new self;
    }


    public function addRow(string|array $value) : self {
        $this->_sorted_donations_list[] = is_string($value) ? [$value] : $value;
        return $this;
    }


    public function addDonation(array $donation) : self {
        $this->_donations_list[] = $donation;
        return $this;
    }


    public function addBlankRow() : self {
        $this->addRow('');
        return $this;
    }


    public function print() : self {
        print_r($this->_sorted_donations_list);
        return $this;
    }


    public function asArray() : array {
        return $this->_sorted_donations_list;
    }


    public function size() : int {
        return count($this->_donations_list);
    }


    public function reset(): self
    {
        $this->_sorted_donations_list = [];
        $this->_donations_list = [];
        return $this;
    }
}



// only run when called directly, not when included (tests)
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__)
    new SortDonation($argv[1]);
?>
